<?php

/**
 * JsonIdentityToken is a class for creating small tokens with identity information.
 *
 * A token is a string with two parts: an HMAC and a message payload. The HMAC
 * is a sha256 hash concatenated with a JSON-encoded payload, which may contain
 * the following key/value pairs: 
 *
 *     (required) "id"     => the user identity
 *     (required) "issued" => the time at which the token was issued
 *     (optional) "name"   => the display name of the user
 *     (optional) "email"  => the email of the user
 *     (optional) "role"   => the role of the user
 *
 * Notes:
 *
 * - Token size must not exceed 4093 bytes when transmitted via cookies, because the browser
 *   limits all cookies on a single domain to 4093 bytes.
 * - Tokens should be expired after a short interval so that they cannot be reused by a
 *   third parties indefinitely if compromised. When using cookies, do not rely on 
 *   cookie expiration alone, which is not sufficient.
 *
 * @package HarvardKey
 */

class JsonIdentityToken
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
    const HMAC_ALGO = "sha256"; // algorithm to use for the hash-based message auth code
    const HMAC_SIZE = 64;       // size of sha256 hash (256 bits expressed in hex)

    /**
     * JsonIdentityToken constructor.
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
     * Factory method to create an instance from data fields. 
     *
     * @param array $data Contains the data that should be encoded in the token
     * @param string $secret Secret key for the HMAC
     * @param int $expires Expiration of token in seconds
     *
     * @return JsonIdentityToken
     */
    public static function create(array $data, string $secret, int $expires) {
        $msg = json_encode(array_merge($data, array("issued" => time())));
        $signed_msg = hash_hmac(self::HMAC_ALGO, $msg, $secret) . $msg;
        $token = new self($signed_msg, $secret, $expires);
        if(!$token->isValid()) {
            throw new Exception('Error creating token!');
        }
        return $token;
    }

    /**
     * Attempts to parse the token.
     *
     * @return $this
     */
    protected function _parse()
    {
        if(!$this->_token || strlen($this->_token) < self::HMAC_SIZE) {
            $this->_log("parse: missing hmac!", Zend_Log::WARN);
            return $this;
        }

        $this->_hmac = substr($this->_token, 0, self::HMAC_SIZE);
        $this->_msg = substr($this->_token, self::HMAC_SIZE);
        $this->_log("parse: hmac = {$this->_hmac} msg = {$this->_msg}");

        $token_decoded = json_decode($this->_msg, true);
        if (is_null($token_decoded)) {
            $this->_log("parse: decoding returned null");
            $token_decoded = array();
        }

        $this->_data = array();
        $fields = array_merge($this->_fieldsOptional, $this->_fieldsRequired);
        foreach($fields as $field) {
            $this->_data[$field] = null;
            if(array_key_exists($field, $token_decoded)) {
                if(is_scalar($token_decoded[$field])) {
                    $this->_data[$field] = $token_decoded[$field];
                } else {
                    $this->_log("parse: field=$field is not a scalar value");
                }
            }
        }
        $this->_log("parse: data=".var_export($this->_data,1));

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
        $computed_hmac = hash_hmac(self::HMAC_ALGO, $this->_msg, $this->_secret);
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
    public function hasValidFields()
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
        return $this->isAuthentic() && $this->hasValidFields() && !$this->isExpired();
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
        if(!$this->hasValidFields()) {
            $errors[] = "Token missing required fields";
        }
        if($this->isExpired()) {
            $errors[] = "Token is expired";
        }
        return $errors;
    }

    /**
     * Returns the token string.
     *
     * @return string
     */
    public function getToken()
    {
            return $this->_token;
    }

    /**
     * Returns the user ID.
     *
     * @return string
     */
    public function getId()
    {
        return $this->_data['id'];
    }

    /**
     * Returns the time the credentials were issued (unix timestamp).
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
        $msg = get_class($this).': '.$msg;
        if(function_exists("debug")) {
            debug($msg);
        } else {
            error_log($msg);
        }
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
        $msg = get_class($this).': '.$msg;
        if(function_exists("_log")) {
            _log($msg);
        } else {
            error_log($msg);
        }
        return $this;
    }

}
