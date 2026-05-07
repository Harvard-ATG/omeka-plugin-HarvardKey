<?php

/**
 * AlbOidcToken decodes and validates AWS ALB OIDC JWT tokens.
 * 
 * ALB validates the JWT signature, so this class only needs to decode
 * the token and extract user claims.
 *
 * @package HarvardKey
 */

class AlbOidcToken
{
    protected $_jwt = null;        // Raw JWT string from ALB header
    protected $_claims = null;     // Decoded JWT claims
    protected $_expires = null;    // Max seconds before token expires

    protected $_fieldsRequired = array("preferred_username", "email");

    /**
     * AlbOidcToken constructor.
     *
     * @param string $jwt JWT from x-amzn-oidc-data header
     * @param int $expires Expiration in seconds (for compatibility)
     * @throws Exception
     */
    public function __construct(string $jwt, int $expires = 600)
    {
        $this->_jwt = $jwt;
        $this->_expires = $expires;

        if (empty($this->_jwt)) {
            throw new Exception("JWT token cannot be empty");
        }

        $this->_log("construct: parsing JWT token");
        $this->_parse();
    }

    /**
     * Parse the JWT token.
     *
     * @return $this
     */
    protected function _parse()
    {
        $parts = explode('.', $this->_jwt);
        if (count($parts) !== 3) {
            $this->_log("parse: invalid JWT format - expected 3 parts, got " . count($parts));
            return $this;
        }

        list($header, $payload, $signature) = $parts;

        // Decode base64url to base64
        $payload = str_replace(['-', '_'], ['+', '/'], $payload);
        $decoded = json_decode(base64_decode($payload), true);

        if (is_null($decoded)) {
            $this->_log("parse: failed to decode JWT payload");
            $this->_claims = array();
            return $this;
        }

        $this->_claims = $decoded;
        $this->_log("parse: successfully decoded JWT claims");

        return $this;
    }

    /**
     * Check if token is valid.
     * ALB already validated signature, so we just check for required fields.
     *
     * @return bool
     */
    public function isValid()
    {
        return $this->hasValidFields();
    }

    /**
     * Check if token contains required fields.
     *
     * @return bool
     */
    public function hasValidFields()
    {
        if (!isset($this->_claims) || !is_array($this->_claims)) {
            return false;
        }

        foreach ($this->_fieldsRequired as $field) {
            if (!array_key_exists($field, $this->_claims) || empty($this->_claims[$field])) {
                $this->_log("hasValidFields: missing required field: $field");
                return false;
            }
        }

        return true;
    }

    /**
     * Check if token is authentic.
     * ALB validates JWT signature, so always return true if parsed.
     *
     * @return bool
     */
    public function isAuthentic()
    {
        return isset($this->_claims) && is_array($this->_claims);
    }

    /**
     * Check if token is expired.
     * ALB manages token expiration, so we return false.
     *
     * @return bool
     */
    public function isExpired()
    {
        // ALB manages session expiration
        return false;
    }

    /**
     * Get validation error messages.
     *
     * @return array
     */
    public function validationErrors()
    {
        $errors = array();

        if (!$this->isAuthentic()) {
            $errors[] = "Token could not be parsed";
        }
        if (!$this->hasValidFields()) {
            $errors[] = "Token missing required fields: " . implode(', ', $this->_fieldsRequired);
        }

        return $errors;
    }

    /**
     * Get user ID (EPPN from preferred_username).
     *
     * @return string|null
     */
    public function getId()
    {
        return $this->_claims['preferred_username'] ?? $this->_claims['email'] ?? null;
    }

    /**
     * Get user display name.
     *
     * @return string|null
     */
    public function getName()
    {
        if (isset($this->_claims['name'])) {
            return $this->_claims['name'];
        }

        // Construct from given_name and family_name if name not present
        $given = $this->_claims['given_name'] ?? '';
        $family = $this->_claims['family_name'] ?? '';

        if ($given || $family) {
            return trim("$given $family");
        }

        return null;
    }

    /**
     * Get user email.
     *
     * @return string|null
     */
    public function getEmail()
    {
        return $this->_claims['email'] ?? null;
    }

    /**
     * Check if user has email.
     *
     * @return bool
     */
    public function hasEmail()
    {
        $email = $this->getEmail();
        return $email !== null && strlen(trim($email)) > 0;
    }

    /**
     * Get token issued time (for compatibility).
     * ALB doesn't provide this in headers, so return current time.
     *
     * @return int
     */
    public function getIssued()
    {
        return $this->_claims['iat'] ?? time();
    }

    /**
     * Get raw token string.
     *
     * @return string
     */
    public function getToken()
    {
        return $this->_jwt;
    }

    /**
     * Get all claims.
     *
     * @return array
     */
    public function getClaims()
    {
        return $this->_claims ?? array();
    }

    /**
     * Log info message.
     *
     * @param string $msg
     * @return $this
     */
    protected function _log(string $msg)
    {
        $msg = get_class($this) . ': ' . $msg;
        if (function_exists("_log")) {
            _log($msg);
        } else {
            error_log($msg);
        }
        return $this;
    }
}