<?php

class HarvardKeyUser extends Omeka_Record_AbstractRecord implements Zend_Acl_Resource_Interface
{
    public $harvard_key_id;
    public $omeka_user_id;
    public $updated;
    public $inserted;

    protected function _initializeMixins()
    {
        $this->_mixins[] = new Mixin_Timestamp($this, 'inserted', 'updated');
    }

    public function saveIdentity($harvard_key_id)
    {
        $this->harvard_key_id = $harvard_key_id;
        $this->save(true);
        return $this;
    }

    public function linkToUser($omeka_user_id)
    {
        $this->omeka_user_id = $omeka_user_id;
        $this->save(true);
        return $this;
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
}