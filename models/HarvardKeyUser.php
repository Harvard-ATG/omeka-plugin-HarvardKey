<?php

class HarvardKeyUser extends Omeka_Record_AbstractRecord implements Zend_Acl_Resource_Interface
{
    /**
     * @var string Should contain the harvard key identifier (eduPersonPrincipalName or EPPN)
     */
    public $harvard_key_id;

    /**
     * @var int|null Should contain the Omeka User object primary key ID
     */
    public $omeka_user_id;

    /**
     * @var boolean When true, it means a new Omeka User was created for the harvard key login.
     */
    public $omeka_user_created;

    /**
     * @var string|null Timestamp when record was last updated.
     */
    public $updated;

    /**
     * @var string|null Timestamp when record was created.
     */
    public $inserted;

    /**
     * Initializes mixin for timestamp fields.
     */
    protected function _initializeMixins()
    {
        $this->_mixins[] = new Mixin_Timestamp($this, 'inserted', 'updated');
    }

    /**
     * Saves the harvard key identifier.
     *
     * @param string $harvard_key_id the harvard key identifier
     * @return boolean
     * @throws Omeka_Validate_Exception if the save failed
     */
    public function saveIdentity($harvard_key_id)
    {
        $this->harvard_key_id = $harvard_key_id;
        return $this->save(true);
    }

    /**
     * Links the harvard key entry to an omeka account.
     * Records whether or not a new account was created at the same time.
     *
     * @param int $omeka_user_id the user id
     * @param boolean $omeka_user_created true if the user was created, false otherwise
     * @return boolean
     * @throws Omeka_Validate_Exception if the save failed
     */
    public function linkTo($omeka_user_id, $omeka_user_created)
    {
        $this->omeka_user_id = $omeka_user_id;
        $this->omeka_user_created = ($omeka_user_created ? 1 : 0);
        return $this->save(true);
    }

    /**
     * Links to new user, created specifically for this harvard key entry.
     *
     * @param int $omeka_user_id the user id
     * @return boolean
     */
    public function linkToNew($omeka_user_id)
    {
        return $this->linkTo($omeka_user_id, true);
    }

    /**
     * Links to existing user that has been matched with this harvard key entry.
     *
     * @param int $omeka_user_id the user id
     * @return boolean
     */
    public function linkToExisting($omeka_user_id)
    {
        return $this->linkTo($omeka_user_id, false);
    }

    /**
     * Returns true if the harvard key entry is linked to an omeka account, false otherwise.
     *
     * @return boolean
     */
    public function isLinked()
    {
        return (bool) $this->omeka_user_id;
    }

    /**
     * Returns the generated username for the harvard key entry based on
     * the table's primary key.
     *
     * @return string
     */
    public function getUsername()
    {
        return "HarvardKeyUser{$this->id}";
    }

    /**
     * Returns the resource ID for this model, which is used for ACLs.
     *
     * @return string
     */
    public function getResourceId()
    {
        return 'HarvardKey_User';
    }

    /**
     * Finds an entry using the omeka user ID.
     *
     * @param int $omeka_user_id the user id
     * @return HarvardKeyUser|null
     */
    public static function findByOmekaUserId($omeka_user_id) {
        $table = get_db()->getTable('HarvardKeyUser');
        return $table->findBySql('omeka_user_id = ?', array($omeka_user_id), true);
    }

    /**
     * Finds an entry using the harvard key ID.
     *
     * @param int $harvard_key_id the harvard key identifier
     * @return HarvardKeyUser|null
     */
    public static function findByHarvardKeyId($harvard_key_id) {
        $table = get_db()->getTable('HarvardKeyUser');
        return $table->findBySql('harvard_key_id = ?', array($harvard_key_id), true);
    }
}
