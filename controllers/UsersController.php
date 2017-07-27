<?php
if (!defined('HARVARDKEY_PLUGIN_DIR')) {
    define('HARVARDKEY_PLUGIN_DIR', dirname(dirname(__FILE__)));
}
require_once CONTROLLER_DIR.'/UsersController.php';

class HarvardKey_UsersController extends UsersController
{
    public function init() {
        parent::init();
    }

    public function loginAction() {
        parent::loginAction();
    }

    public function logoutAction() {
        parent::logoutAction();
    }
}
