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
     * HarvardKey_Auth_Adapter constructor.
     *
     * @param Omeka_Db $db
     * @param JsonIdentityToken $token
     */
    public function __construct(Omeka_Db $db, JsonIdentityToken $token)
    {
        $this->_db = $db;
        $this->_token = $token;
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

        // (1) Check that the provided token data is valid
        if(!$this->_token->isValid()) {
            $this->_log("auth failure: harvard key token is invalid");
            return new Zend_Auth_Result(Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID, null, array('Login failed. Invalid token.'));
        }

        // (2) If site is restricted to site users only, then the user must either be in the whitelist OR they must have an omeka account
        $email = $this->_token->getEmail();
        if($this->_isRestrictedToSiteUsers()) {
            $email_whitelisted = in_array($email, $this->_getEmails());
            if(!$email_whitelisted) {
                $omeka_user = $this->_findOmekaUserByEmail($email);
                if(!$omeka_user) {
                    $errmsg = "Access Denied. Your email address [$email] is not authorized to access this site. Restricted to site users only.";
                    return new Zend_Auth_Result(Zend_Auth_result::FAILURE, null, array($errmsg));
                }
            }
        }

        // (3) Creates/updates a harvard key record linking to an omeka user.
        //  - When the user exists in omeka, tries to link via email
        //  - When the user does not exist, creates a new user and assigns a role such that if they are in the
        //    prescreened whitelist, they get the designated role, otherwise they get the guest role.
        $harvard_key_user = $this->_createOrUpdateUser();
        if(!$harvard_key_user) {
            $this->_log("auth failure: error creating or updating harvard key user", Zend_Log::ERROR);
            return new Zend_Auth_Result(Zend_Auth_result::FAILURE, null, array("Login failed. Harvard Key could not be associated with an Omeka account."));
        }

        // (4) Lookup the omeka user record
        $omeka_user = $this->_findOmekaUserById($harvard_key_user->omeka_user_id);
        if(!$omeka_user) {
            $this->_log("auth failure: error fetching omeka user id: {$harvard_key_user->omeka_user_id} for harvard key row id: {$harvard_key_user->id}");
            return new Zend_Auth_Result(Zend_Auth_result::FAILURE, null, array("Login failed. Omeka account not found."));
        } else if(!$omeka_user->active) {
            $this->_log("auth failure: omeka user {$omeka_user->id} is inactive");
            return new Zend_Auth_Result(Zend_Auth_result::FAILURE, $harvard_key_user->omeka_user_id, array("Login failed because Omeka account is inactive. "));
        }

        return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $harvard_key_user->omeka_user_id);
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
        if($harvard_key_user->isLinked()) {
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
        if(!$omeka_user_id) {
            if($this->_token->hasEmail()) {
                $email = $this->_token->getEmail();
                $this->_log("harvard key user $harvard_key_id has email = $email");
                $omeka_user = $this->_findOmekaUserByEmail($email);
                if ($omeka_user) {
                    $this->_log("omeka user with email = $email EXISTS... linking {$omeka_user->id}");
                    $harvard_key_user->linkToExisting($omeka_user->id);
                    $omeka_user->save();
                    $omeka_user_id = $omeka_user->id;
                } else {
                    $this->_log("omeka user with email = $email NOT FOUND", Zend_Log::WARN);
                }
            } else {
                $this->_log("harvard key user $harvard_key_id does not have an email", Zend_Log::WARN);
            }
        }

        // Create a new omeka user
        if(!$omeka_user_id) {
            $this->_log("creating new omeka user record...");
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
        $omeka_role = $this->_getOmekaRoleForEmail($harvard_key_email);
        $omeka_username = $harvard_key_user->getUsername();
        $omeka_name = $harvard_key_name ? $harvard_key_name : $omeka_username;
        $omeka_email = $harvard_key_email ? $harvard_key_email : "";
        $omeka_user_values = array(
            "username" => $omeka_username,
            "name"     => $omeka_name,
            "role"     => $omeka_role,
            "email"    => $omeka_email,
            "active"   => 1,
            "password" => HARVARDKEY_UNUSEABLE_PASSWORD
        );

        $this->_log("omeka user values: ".var_export($omeka_user_values,1));

        // Insert into the database and then link the omeka user to the harvard key identity
        $omeka_user_id = $this->_db->insert('User', $omeka_user_values);
        if(!$omeka_user_id) {
            $this->_log("error creating omeka user", Zend_Log::WARN);
            return false;
        }

        $harvard_key_user->linkToNew($omeka_user_id);
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
     * Returns the designated emails.
     *
     * @return array emails
     */
    protected function _getEmails()
    {
        $harvardkey_emails = get_option('harvardkey_emails');
        if($harvardkey_emails) {
            $emails = array_filter(preg_split("/(\r\n|\n|\r)/", $harvardkey_emails));
        } else {
            $emails = array();
        }
        return $emails;
    }

    /**
     * Returns true if authentication is restricted to the prescreened emails and existing omeka users.
     *
     * @return bool
     */
    protected function _isRestrictedToSiteUsers()
    {
        return get_option('harvardkey_restrict_access') == "site_users_only";
    }

    /**
     * Returns the role that should be assigned to the omeka user given a particular email address.
     *
     * This is used to automatically elevate the role for a group of users when they login for the first time.
     *
     * @param string $email
     * @return bool
     */
    protected function _getOmekaRoleForEmail($email)
    {
        $default_omeka_role = HARVARDKEY_GUEST_ROLE;
        $found = in_array($email, $this->_getEmails());
        if($found) {
            $omeka_role = $this->_getOmekaRole();
            $this->_log("email '$email' found in the whitelist, omeka role is: $omeka_role");
        } else {
            $omeka_role = $default_omeka_role;
            $this->_log("email '$email' not found in the whitelist, omeka role is: $default_omeka_role");
        }
        return $omeka_role;
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
