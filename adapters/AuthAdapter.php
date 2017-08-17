<?php

/**
 * Authenticate against a securely signed token.
 *
 * @package HarvardKey
 */

require_once(dirname(dirname(__FILE__))."/common.php");
require_once(HARVARDKEY_PLUGIN_DIR.'/libraries/HarvardKey/JsonIdentityToken.php');

class HarvardKey_Auth_Adapter implements Zend_Auth_Adapter_Interface
{
    /**
     * @var JsonIdentityToken|null The token object containing the user's identity and related attributes.
     */
    protected $_token = null;

    /**
     * @var string The whitelist of email addresses (newline separated) that are allowed to login.
     */
    protected $_allowedEmails = '';

    /**
     * HarvardKey_Auth_Adapter constructor.
     *
     * @param Omeka_Db $db
     * @param JsonIdentityToken $token
     */
    public function __construct(Omeka_Db $db, JsonIdentityToken $token, string $emails)
    {
        $this->_db = $db;
        $this->_token = $token;
        $this->_allowedEmails = $emails;
    }

    /**
     * Performs an authentication attempt.
     *
     * Attempts to retrieve the user's Harvard Key identity and link it to an Omeka User identity. A successful
     * authentication attempt will return the Omeka User identity.
     *
     * @return Zend_Auth_Result
     */
    public function authenticate()
    {
        $this->_log("authenticate()");

        // check that the provided token data is valid
        if(!$this->_token->isValid()) {
            $this->_log("auth failure: harvard key token is invalid");
            return new Zend_Auth_Result(Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID, null, array('Login failed because login data was invalid.'));
        }

        // check whitelist to see if email is permitted to login
        if(!$this->_isEmailAllowed($this->_token->getEmail())) {
            $this->_log("auth failure: harvard key email is not permitted: ". $this->_token->getEmail());
            return new Zend_Auth_Result(Zend_Auth_Result::FAILURE, null, array('Login failed because email address ['. $this->_token->getEmail() .'] is not in the list of allowed emails.'));
        }

        // attempt to load or create a harvard key record linked to an omeka user
        $harvard_key_user = $this->_createOrUpdateUser();
        if(!$harvard_key_user) {
            $this->_log("auth failure: error creating or updating harvard key user");
            return new Zend_Auth_Result(Zend_Auth_result::FAILURE, null, array("Login failed because could not associate Harvard Key credentials with an Omeka user."));
        }

        // attempt lookup the associated omeka user record
        $omeka_user = $this->_findOmekaUserById($harvard_key_user->omeka_user_id);
        if(!$omeka_user) {
            $this->_log("auth failure: error fetching omeka user id: {$harvard_key_user->omeka_user_id} for harvard key row id: {$harvard_key_user->id}");
            return new Zend_Auth_Result(Zend_Auth_result::FAILURE, null, array("Login failed because no Omeka user could be found for the Harvard Key user."));
        } else if(!$omeka_user->active) {
            $this->_log("auth failure: omeka user {$omeka_user->id} is inactive");
            return new Zend_Auth_Result(Zend_Auth_result::FAILURE, $harvard_key_user->omeka_user_id, array("Login failed because account is inactive. "));
        }

        return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $harvard_key_user->omeka_user_id);
    }

    /**
     * Ensures that the given email is allowed to login.
     *
     * @param string $email
     * @return bool
     */
    protected function _isEmailAllowed($email)
    {
        if($this->_allowedEmails == "") {
            return true;
        }
        $emails = preg_split("/(\r\n|\n|\r)/", $this->_allowedEmails);
        return in_array($email, $emails);
    }

    /**
     * Creates or updates a harvard key user record.
     *
     * @return HarvardKeyUser
     */
    protected function _createOrUpdateUser()
    {
        $this->_log("_createOrUpdateUser()");

        // Create a new harvard key entry if none exists
        $harvard_key_id = $this->_token->getId();
        $harvard_key_user = $this->_findHarvardKeyUser($harvard_key_id);
        if($harvard_key_user) {
            $this->_log("found harvard key entry with row id = {$harvard_key_user->id}");
        } else {
            $this->_log("creating entry for harvard key id = $harvard_key_id");
            $harvard_key_user = new HarvardKeyUser;
            $insert_row_id = $harvard_key_user->saveIdentity($harvard_key_id);
            if($insert_row_id) {
                $this->_log("successfully created harvard key entry with inserted id = $insert_row_id");
            } else {
                $this->_log("error creating harvard key user", Zend_Log::WARN);
                return false;
            }
        }

        // Check if a valid omeka user is linked to the harvard key record
        $omeka_user_id = null;
        if($harvard_key_user->isLinkedToUser()) {
            $this->_log("checking link {$harvard_key_user->harvard_key_id} => {$harvard_key_user->omeka_user_id}");
            $omeka_user = $this->_findOmekaUserById($harvard_key_user->omeka_user_id);
            if($omeka_user) {
                $this->_log("omeka user {$harvard_key_user->omeka_user_id} EXISTS");
                $omeka_user_id = $omeka_user->id;
            } else {
                $this->_log("omeka user {$harvard_key_user->omeka_user_id} NOT FOUND", Zend_Log::WARN);
            }
        }

        // Attempt to link to a user having the same email address
        if(!$omeka_user_id && $this->_token->hasEmail()) {
            $email = $this->_token->getEmail();
            $this->_log("harvard key user $harvard_key_id has email = $email");
            $omeka_user = $this->_findOmekaUserByEmail($email);
            if($omeka_user) {
                $this->_log("omeka user with email = $email EXISTS... linking {$omeka_user->id}");
                $harvard_key_user->linkExistingUser($omeka_user->id);
                $omeka_user->active = 1;
                $omeka_user->save();
                $omeka_user_id = $omeka_user->id;
            } else {
                $this->_log("omeka user with email = $email NOT FOUND", Zend_Log::WARN);
            }
        }

        // Attempt to create and link a new omeka user if none has already been linked
        if(!$omeka_user_id) {
            $this->_log("creating new omeka user record");
            $insert_row_id = $this->_createOmekaUser($harvard_key_user);
            if(!$insert_row_id) {
                $this->_log("error creating omeka user", Zend_Log::WARN);
                return false;
            }
        }

        return $harvard_key_user;
    }

    /**
     * Creates an Omeka User record.
     *
     * @param HarvardKeyUser $harvard_key_user
     * @return int ID of the User record
     */
    protected function _createOmekaUser($harvard_key_user)
    {
        $this->_log("_createOmekaUser()");

        // Get attributes provided by the harvard key token
        $harvard_key_name = $this->_token->getName();
        $harvard_key_email = $this->_token->getEmail();

        // Set the omeka user object attributes
        $omeka_role = $this->_getOmekaRole();
        $omeka_username = $harvard_key_user->generateUsername();
        $omeka_name = $harvard_key_name ? $harvard_key_name : $omeka_username;
        $omeka_email = $harvard_key_email ? $harvard_key_email : "";
        $omeka_user_values = array(
            "username" => $omeka_username,
            "name"     => $omeka_name,
            "role"     => $omeka_role,
            "email"    => $omeka_email,
            "active"   => 1,
            "password" => "unuseablepassword"
        );

        $this->_log("omeka user values: ".var_export($omeka_user_values,1));

        // Insert into the database and then link the omeka user to the harvard key identity
        $omeka_user_id = $this->_db->insert('User', $omeka_user_values);
        if(!$omeka_user_id) {
            $this->_log("error creating omeka user", Zend_Log::WARN);
            return false;
        }

        $harvard_key_user->linkNewUser($omeka_user_id);
        $this->_log("successfully linked harvard_key_id={$harvard_key_user->harvard_key_id} with omeka user id=$omeka_user_id");

        return $omeka_user_id;
    }

    /**
     * Returns the designated omeka role.
     *
     * @return string role name
     */
    protected function _getOmekaRole()
    {
        $valid_roles = get_user_roles();
        $role = get_option('harvardkey_role');
        return isset($valid_roles[$role]) ? $role : HARVARDKEY_GUEST_ROLE;
    }

    /**
     * Finds the harvard key user record by the harvard key ID.
     *
     * @param int $harvard_key_id
     * @return Omeka_Record_AbstractRecord
     */
    protected function _findHarvardKeyUser($harvard_key_id)
    {
        $table = $this->_db->getTable('HarvardKeyUser');
        return $table->findBySql('harvard_key_id = ?', array($harvard_key_id), true);
    }

    /**
     * Finds the Omeka user record by the omeka user ID.
     *
     * @param int $omeka_user_id
     * @return Omeka_Record_AbstractRecord
     */
    protected function _findOmekaUserById($omeka_user_id)
    {
        $table = $this->_db->getTable('User');
        return $table->findBySql('id = ?', array($omeka_user_id), true);
    }

    /**
     * Finds the Omeka user record by the omeka username.
     *
     * @param string $omeka_username
     * @return Omeka_Record_AbstractRecord
     */
    protected function _findOmekaUserByUsername($omeka_username)
    {
        $table = $this->_db->getTable('User');
        return $table->findBySql('username = ?', array($omeka_username), true);
    }

    /**
     * Finds the Omeka user record by the omeka email.
     *
     * @param string $omeka_email
     * @return Omeka_Record_AbstractRecord
     */
    protected function _findOmekaUserByEmail($omeka_email)
    {
        $table = $this->_db->getTable('User');
        return $table->findBySql('email = ?', array($omeka_email), true);
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
