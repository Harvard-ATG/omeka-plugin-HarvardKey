<?php

/**
* Authenticate against a JWT token.
*
* @package Omeka\Auth
*/
if (!defined('HARVARDKEY_PLUGIN_DIR')) {
    define('HARVARDKEY_PLUGIN_DIR', dirname(dirname(__FILE__)));
}

require_once(HARVARDKEY_PLUGIN_DIR.'/libraries/HarvardKey/HarvardKeySecureToken.php');

class HarvardKey_Auth_Adapter implements Zend_Auth_Adapter_Interface
{
    protected $_defaultOmekaRole = 'harvard_key_viewer';
    protected $_token = null;

    public function __construct(Omeka_Db $db, HarvardKeySecureToken $token)
    {
        $this->_db = $db;
        $this->_token = $token;
    }

    /**
    * Authenticate against secure token.
    *
    * @return Zend_Auth_Result|null
    */
    public function authenticate()
    {
        $this->_debug("authenticate()");
        if(!$this->_token->isValid()) {
            return new Zend_Auth_Result(Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID, null);
        }

        $harvard_key_user = $this->_createOrUpdateUser();
        if($harvard_key_user) {
            return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $harvard_key_user->omeka_user_id);
        }

        return new Zend_Auth_Result(Zend_Auth_result::FAILURE, null);
    }

    protected function _createOrUpdateUser()
    {
        $this->_debug("_createOrUpdateUser()");

        $harvard_key_id = $this->_token->getId();
        $harvard_key_role = $this->_token->getRole();
        $this->_log("creating or updating user with harvard key id = $harvard_key_id role = $harvard_key_role");

        $harvard_key_user = $this->_findHarvardKeyUser($harvard_key_id);
        $this->_log("harvard key user found with row id = {$harvard_key_user->id}");
        if(!$harvard_key_user) {
            $harvard_key_user = new HarvardKeyUser;
            $harvard_key_user->harvard_key_id = $harvard_key_id;
            $harvard_key_user->save(true);
        }

        if($harvard_key_user->omeka_user_id) {
            $this->_log("verifying that harvard key $harvard_key_id is linked to existing user {$harvard_key_user->omeka_user_id}");
            $omeka_user = $this->_findOmekaUserById($harvard_key_user->omeka_user_id);
            if($omeka_user) {
                $this->_log("verified that omeka user {$harvard_key_user->omeka_user_id} exists and has username={$omeka_user->username}");
            } else {
                $this->_log("failed to find omeka user={$harvard_key_user->omeka_user_id} for harvard_key_id={$harvard_key_user->harvard_key_id}", Zend_Log::WARN);
                $this->_log("now attempting to create and link a new omeka user to harvard_key_id={$harvard_key_user->harvard_key_id}");
                $omeka_user_id = $this->_createOmekaUser($harvard_key_user, $harvard_key_role);
                $harvard_key_user->omeka_user_id = $omeka_user_id;
                $harvard_key_user->save(true);
                $this->_log("successfully linked harvard_key_id={$harvard_key_user->harvard_key_id} with omeka user id={$harvard_key_user->omeka_user_id}");
            }
        } else {
            $omeka_user_id = $this->_createOmekaUser($harvard_key_user, $harvard_key_role);
            $harvard_key_user->omeka_user_id = $omeka_user_id;
            $harvard_key_user->save(true);
            $this->_log("successfully linked harvard_key_id={$harvard_key_user->harvard_key_id} with omeka user id={$harvard_key_user->omeka_user_id}");
        }

        return $harvard_key_user;
    }

    protected function _createOmekaUser($harvard_key_user, $role=null)
    {
        $this->_debug("_createOmekaUser()");
        $role = $this->_filterOmekaRole($role);
        $user_id = $this->_db->insert('User', array(
            "username" => "HarvardKeyUser{$harvard_key_user->id}",
            "name"     => "HarvardKeyUser{$harvard_key_user->id}",
            "role"     => $role,
            "active"   => 1,
            "email"    => "",
            "password" => "unuseablepassword"
        ));
        return $user_id;
    }

    protected function _filterOmekaRole($role)
    {
        $valid_role = $role && in_array($role, array('researcher', 'contributor', 'admin', 'super'));
        if(!$valid_role) {
            return $this->_defaultOmekaRole;
        }
        return $role;
    }

    protected function _findHarvardKeyUser($harvard_key_id)
    {
        $table = $this->_db->getTable('HarvardKeyUser');
        return $table->findBySql('harvard_key_id = ?', array($harvard_key_id), true);
    }

    protected function _findOmekaUserById($omeka_user_id)
    {
        $table = $this->_db->getTable('User');
        return $table->findBySql('id = ?', array($omeka_user_id), true);
    }

    protected function _findOmekaUserByUsername($omeka_username)
    {
        $table = $this->_db->getTable('User');
        return $table->findBySql('username = ?', array($omeka_username), true);
    }

    protected function _debug(string $msg)
    {
        debug(get_class($this) . ": $msg");
        return $this;
    }

    protected function _log(string $msg)
    {
        _log(get_class($this) . ": $msg");
        return $this;
    }
}