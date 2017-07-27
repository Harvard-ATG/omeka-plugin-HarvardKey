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

    public function getResourceId()
    {
        return 'HarvardKey_User';
    }
}