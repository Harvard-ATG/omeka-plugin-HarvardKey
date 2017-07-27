<?php

/**
 * HarvardKeySecureToken is a class for verifying and parsing a token that has been issued
 * on behalf of a user that has logged into the Harvard Key system.
 *
 * Tokens are composed of two parts: an HMAC and a message string. The HMAC
 * is a sha256 hash concatenated with a message string. The message string is
 * JSON-encoded containing the following key/value pairs:
 *
 *     (required) "id"   => the harvard key identifier (such as the EPPN)
 *     (required) "t"    => the time at which the token was issued
 *     (optional) "role" => the global user role, generally used for super user access
 *     (optional) "n"    => a nonce (not currently implemented)
 *
 * Assumptions:
 *
 * The assumption is that the token is issued from a trusted site via SSL using a shared
 * secret key. The token may come from a secure cookie on the same domain, although this class
 * is more or less agnostic about where the token comes from.
 *
 * Notes:
 *
 * - Token size must not exceed 4093 bytes if they are coming from cookies, because the browser
 *   limits all cookies on a single domain to 4093 bytes.
 * - Tokens must be expired after a short interval so that they cannot be reused by a
 *   third parties indefinitely if compromised.
 * - Ideally, a nonce should be incorporated into the token that covers the expiration interval
 *   (or a little more to cover clock drift) so that if a user re-authenticates during that time
 *   period, perhaps because they changed their password or some other reason, the old token
 *   is invalidated.
 */

class HarvardKeySecureToken
{
    protected $_secret = null;    // secret key used to verify token authenticity via hmac

    // Token components
    protected $_token = null;     // raw token string: HMAC concatenated with the JSON message
    protected $_tokenHmac = null; // stores the hash authentication code
    protected $_tokenMsg = null;  // stores the string (JSON-formatted) message to be decoded
    protected $_tokenData = null; // stores the parsed data from the token

    // Required and optional fields that may be present in a token
    protected $_tokenFieldsRequired = array("id", "t");
    protected $_tokenFieldsOptional = array("role");

    // Token settings
    protected $_maxTokenAge = 60;    // max seconds before a token expires
    protected $_maxTokenSize = 4093; // max bytes of a single browser cookie

    // HMAC settings
    protected $_hmacAlgorithm = "sha256";
    protected $_hmacSize = 64;            // size of sha256 hash (256 bits expressed in hex)

    public function __construct(string $token, string $secret)
    {
        $this->_token = $token;
        $this->_secret = $secret;

        if(strlen($this->_token) > $this->_maxTokenSize) {
            throw new Exception("Token is too large: ".strlen($this->_token)." exceeds max ".$this->tokenMaxSize);
        }
        if(!is_string($this->_secret) || $this->_secret == "") {
            throw new Exception("Secret key must be a non-empty string!");
        }

        $this->_parse();
    }

    protected function _debug(string $msg)
    {
        debug(get_class($this) . ": $msg");
        return $this;
    }

    protected function _parse()
    {
        $this->_debug("_parse()");
        $this->_debug("token = {$this->_token}");

        $this->_tokenHmac = substr($this->_token, 0, $this->_hmacSize);
        $this->_tokenMsg = substr($this->_token, $this->_hmacSize);

        $token_decoded = json_decode($this->_tokenMsg, true);
        if(is_null($token_decoded)) {
            $token_decoded = array();
        }
        $this->_debug("hmac = {$this->_tokenHmac} msg = {$this->_tokenMsg}");
        $this->_debug("decoded msg = ".var_export($token_decoded,1));

        $this->_tokenData = array();
        $fields = array_merge($this->_tokenFieldsOptional, $this->_tokenFieldsRequired);
        foreach($fields as $field) {
            if(array_key_exists($field, $token_decoded)) {
                $this->_tokenData[$field] = $token_decoded[$field];
            } else {
                $this->_tokenData[$field] = null;
            }
        }
        $this->_debug("parsed data = ".var_export($this->_tokenData,1));

        return $this;
    }

    public function isAuthentic()
    {
        $this->_debug("is_authentic()");

        $computed_hmac = hash_hmac($this->_hmacAlgorithm, $this->_tokenMsg, $this->_secret);
        $is_authentic = ($computed_hmac == $this->_tokenHmac);

        $this->_debug("received hmac = {$this->_tokenHmac}");
        $this->_debug("computed hmac = $computed_hmac");
        $this->_debug("authentic?    = " . ($is_authentic?"YES":"NO"));

        return $is_authentic;
    }

    public function isExpired()
    {
        $this->_debug("is_expired()");

        $issued_time = intval($this->_tokenData['t'], 10);
        $current_time = time();
        $elapsed = $current_time - $issued_time;
        $is_expired = ($elapsed > $this->_maxTokenAge);

        $this->_debug("issued time  = $issued_time");
        $this->_debug("elapsed time = $elapsed");
        $this->_debug("expires      = {$this->_maxTokenAge}");
        $this->_debug("expired?     = ".($is_expired?"YES":"NO"));

        return $is_expired;
    }

    public function hasRequiredFields()
    {
        $this->_debug("has_required_fields()");

        $has_required = is_array($this->_tokenData);
        foreach($this->_tokenFieldsRequired as $f) {
            $has_required = $has_required && array_key_exists($f, $this->_tokenData) && !is_null($this->_tokenData[$f]);
        }

        $this->_debug("has_required? = ".($has_required?"YES":"NO"));

        return $has_required;
    }

    public function isValid()
    {
        $is_valid = $this->isAuthentic() && !$this->isExpired() && $this->hasRequiredFields();
        $this->_debug("is_valid(): ".($is_valid?"YES":"NO"));
        return $is_valid;
    }

    public function getId()
    {
        return $this->_tokenData['id'];
    }

    public function getRole()
    {
        return $this->_tokenData['role'];
    }

    public function expires(int $seconds)
    {
        $this->_maxTokenAge = $seconds;
        return $this;
    }

}