<?php

class HarvardKeyUser extends Omeka_Record_AbstractRecord implements Zend_Acl_Resource_Interface
{
    public $harvard_key_id;
    public $omeka_user_id;
    public $omeka_user_created;
    public $updated;
    public $inserted;

    protected function _initializeMixins()
    {
        $this->_mixins[] = new Mixin_Timestamp($this, 'inserted', 'updated');
    }

    public function saveIdentity($harvard_key_id)
    {
        $this->harvard_key_id = $harvard_key_id;
        return $this->save(true);
    }

    public function linkUser($omeka_user_id, $omeka_user_created)
    {
        $this->omeka_user_id = $omeka_user_id;
        $this->omeka_user_created = ($omeka_user_created ? 1 : 0);
        $this->save(true);
        return $this;
    }

    public function linkNewUser($omeka_user_id)
    {
        return $this->linkUser($omeka_user_id, true);
    }

    public function linkExistingUser($omeka_user_id)
    {
        return $this->linkUser($omeka_user_id, false);
    }

    public function isLinkedToUser()
    {
        return (bool) $this->omeka_user_id;
    }

    public function getLinkedUserId()
    {
        return $this->omeka_user_id;
    }

    public function generateUsername()
    {
        return "HarvardKeyUser{$this->id}";
    }

    public function getResourceId()
    {
        return 'HarvardKey_User';
    }

    public static function findByOmekaUserId($omeka_user_id) {
        $table = get_db()->getTable('HarvardKeyUser');
        return $table->findBySql('omeka_user_id = ?', array($omeka_user_id), true);
    }
}
