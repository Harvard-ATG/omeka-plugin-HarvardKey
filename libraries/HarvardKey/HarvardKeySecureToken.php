<?php

/**
 * HarvardKeySecureToken is a class for verifying and parsing a token that has been issued
 * on behalf of a user that has logged into the Harvard Key system.
 *
 * Tokens are composed of two parts: an HMAC and a message string. The HMAC
 * is a sha256 hash concatenated with a message string. The message string is
 * JSON-encoded containing the following key/value pairs:
 *
 *     (required) "id"     => the harvard key identifier (such as the EPPN)
 *     (required) "issued" => the time at which the token was issued
 *     (optional) "name"   => the display name of the user
 *     (optional) "email"  => the email of the user
 *     (optional) "role"   => the role of the user
 *
 * Assumptions:
 *
 * The assumption is that the token is issued from a trusted site via SSL using a shared
 * secret key. The token may come from a secure cookie on the same domain, although this class
 * is more or less agnostic about where the token comes from.
 *
 * It's assumed that all attributes in the token are true for all sites that may consume the token,
 * so for example, if we wanted to grant "super" user access to certain users, we could set the
 * role to "super" and they would have "super" access to any/all sites that the token was submitted to.
 *
 * Notes:
 *
 * - Token size must not exceed 4093 bytes when transmitted via cookies, because the browser
 *   limits all cookies on a single domain to 4093 bytes.
 * - Tokens must be expired after a short interval so that they cannot be reused by a
 *   third parties indefinitely if compromised.
 *
 * @package HarvardKey
 */

class HarvardKeySecureToken
{
    // Constructor parameters
    protected $_token = null;     // raw token string: HMAC concatenated with the JSON message
    protected $_secret = null;    // secret key used to verify token authenticity via hmac
    protected $_expires = null;   // max seconds before a token expires

    // Token parsing
    protected $_hmac = null;     // token hash authentication code (hmac)
    protected $_msg = null;      // token message string formatted as JSON
    protected $_data = null;     // token data decoded from the message

    // Required and optional fields that may be present in data
    protected $_fieldsRequired = array("id", "issued");
    protected $_fieldsOptional = array("name", "email", "role");

    // HMAC settings
    protected $_hmacAlgo = "sha256";
    protected $_hmacSize = 64;    // size of sha256 hash (256 bits expressed in hex)

    /**
     * HarvardKeySecureToken constructor.
     *
     * @param string $token Contains the HMAC and JSON-encoded credentials
     * @param string $secret Secret key for the HMAC
     * @param int $expires Expiration of token in seconds
     * @throws Exception
     */
    public function __construct(string $token, string $secret, int $expires)
    {
        $this->_token = $token;
        $this->_secret = $secret;
        $this->_expires = $expires;

        if(strlen($this->_secret) < 32) {
            throw new Exception("Secret key must be a non-empty string at least 32 bytes (256 bits) in length");
        }
        if($this->_expires < 0) {
            throw new Exception("Expiration must be a non-negative integer");
        }

        $this->_log("construct: token={$this->_token} expires={$this->_expires}");
        $this->_parse();
    }

    /**
     * Attempts to parse the token.
     *
     * @return $this
     */
    protected function _parse()
    {
        if(!$this->_token || strlen($this->_token) < $this->_hmacSize) {
            $this->_log("cannot parse token: missing hmac", Zend_Log::WARN);
            return $this;
        }

        $this->_hmac = substr($this->_token, 0, $this->_hmacSize);
        $this->_msg = substr($this->_token, $this->_hmacSize);
        $this->_log("hmac = {$this->_hmac} msg = {$this->_msg}");

        $token_decoded = json_decode($this->_msg, true);
        if (is_null($token_decoded)) {
            $token_decoded = array();
        }

        $this->_data = array();
        $fields = array_merge($this->_fieldsOptional, $this->_fieldsRequired);
        foreach($fields as $field) {
            if(array_key_exists($field, $token_decoded)) {
                $this->_data[$field] = $token_decoded[$field];
            } else {
                $this->_data[$field] = null;
            }
        }
        $this->_log("data = ".var_export($this->_data,1));

        return $this;
    }

    /**
     * Checks if the token is authentic and hasn't been tampered with.
     *
     * @return bool
     */
    public function isAuthentic()
    {
        if(!isset($this->_hmac)) {
            return false;
        }
        $computed_hmac = hash_hmac($this->_hmacAlgo, $this->_msg, $this->_secret);
        return ($computed_hmac === $this->_hmac);
    }

    /**
     * Checks if the token should be considered expired based on the time it was issued.
     *
     * @return bool
     */
    public function isExpired()
    {
        if(!isset($this->_data['issued'])) {
            return false;
        }
        $issued_time = intval($this->_data['issued'], 10);
        $current_time = time();
        $elapsed = $current_time - $issued_time;
        return ($elapsed > $this->_expires);
    }

    /**
     * Checks if the token contains the minimum data attributes to be considered valid.
     *
     * @return bool
     */
    public function hasRequiredFields()
    {
        if(!isset($this->_data)) {
            return false;
        }
        $has_required = true;
        foreach($this->_fieldsRequired as $f) {
            $has_required = $has_required && array_key_exists($f, $this->_data) && !is_null($this->_data[$f]);
        }
        return $has_required;
    }

    /**
     * Checks the validity of the token.
     *
     * @return bool true if the token is authentic, contains the required data, and is not expired, otherwise false.
     */
    public function isValid()
    {
        return $this->isAuthentic() && $this->hasRequiredFields() && !$this->isExpired();
    }

    /**
     * Returns validation error messages.
     *
     * @return array
     */
    public function validationErrors()
    {
        $errors = array();
        if(!$this->isAuthentic()) {
            $errors[] = "Token could not be authenticated";
        }
        if(!$this->hasRequiredFields()) {
            $errors[] = "Token missing required fields";
        }
        if($this->isExpired()) {
            $errors[] = "Token is expired";
        }
        return $errors;
    }

    /**
     * Returns the harvard key ID.
     *
     * @return string
     */
    public function getId()
    {
        return $this->_data['id'];
    }

    /**
     * Returns the time the harvard key credentials were issued (unix timestamp).
     *
     * @return integer
     */
    public function getIssued()
    {
        return $this->_data['issued'];
    }

    /**
     * Returns the role.
     *
     * @return string|null
     */
    public function getRole()
    {
        return $this->_data['role'];
    }

    /**
     * Returns the user display name.
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->_data['name'];
    }

    /**
     * Returns the user email.
     *
     * @return string|null
     */
    public function getEmail()
    {
        return $this->_data['email'];
    }

    /**
     * Logs a debug message.
     *
     * @param string $msg
     * @return $this
     */
    protected function _debug(string $msg)
    {
        debug(get_class($this) . ": $msg");
        return $this;
    }

    /**
     * Logs an info message.
     *
     * @param string $msg
     * @return $this
     */
    protected function _log(string $msg)
    {
        _log(get_class($this) . ": $msg");
        return $this;
    }

}