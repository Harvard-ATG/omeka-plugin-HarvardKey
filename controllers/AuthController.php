<?php
if (!defined('HARVARDKEY_PLUGIN_DIR')) {
    define('HARVARDKEY_PLUGIN_DIR', dirname(dirname(__FILE__)));
}

require_once HARVARDKEY_PLUGIN_DIR.'/adapters/AuthAdapter.php';
require_once(HARVARDKEY_PLUGIN_DIR.'/libraries/HarvardKey/HarvardKeySecureToken.php');

class HarvardKey_AuthController extends Omeka_Controller_AbstractActionController
{
    protected $_settings = null;

    public function init() {
        $this->_auth = $this->getInvokeArg('bootstrap')->getResource('Auth');
        $this->_settings = new Zend_Config_Ini(HARVARDKEY_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'plugin.ini', 'auth');
    }

    protected function _handlePublicAction()
    {
        #$this->_debug(var_export($this->getCurrentUser(), 1));
        if (is_admin_theme()) {
            $header = 'login-header';
            $footer = 'login-footer';
        } else {
            $header = 'header';
            $footer = 'footer';
        }

        $this->view->header = $header;
        $this->view->footer = $footer;
    }

    public function chooseAction() {
        $this->_handlePublicAction();
        $this->view->assign('omekaLoginUrl', $this->view->url('/harvard-key/users/login'));
        $this->view->assign('harvardKeyLoginUrl', $this->_getHarvardKeyServiceUrl());
    }

    public function loginAction() {
        $this->_handlePublicAction();
        $this->view->assign('chooseUrl', $this->view->url('/harvard-key/auth/choose'));

        $cookie = $_COOKIE[$this->_settings->get("cookie_name")];
        $secret_key = $this->_settings->get("secret_key");
        $expires = intval($this->_settings->get("expires", 600));

        if(!$cookie) {
            $this->view->assign("authResult", "Authentication Failed");
            $this->view->assign('authMessages', array("Please ensure that you have cookies enabled in your browser and then try logging in again."));
            return;
        }

        $token = new HarvardKeySecureToken($cookie, $secret_key, $expires);
        $authAdapter = new HarvardKey_Auth_Adapter($this->_helper->db->getDb(), $token);
        $authResult = $this->_auth->authenticate($authAdapter);
        if(!$authResult->isValid()) {
            $this->view->assign("authResult", "Authentication Failed");
            $this->view->assign('authMessages', array("There was a problem with the Harvard Key login. Please try logging in again."));
            return;
        }

        $this->_log("Authentication Successful. Logged in as {$authResult->getIdentity()}");
        $this->_helper->redirector->gotoUrl('/');
    }


    protected function _redirectToHarvardKeyService()
    {
        $redirectcookie = "harvardkeyredirects";
        $redirectvalue = intval($_COOKIE[$redirectcookie], 10);
        $this->_log("cookie $redirectcookie = $redirectvalue");
        if(isset($redirectvalue) && $redirectvalue > 0) {
            setcookie($redirectcookie, 0);
            return false;
        } else {
            setcookie($redirectcookie, 1 + $redirectvalue);
        }
        $service_url = $this->_getHarvardKeyServiceUrl();
        $this->_log("Redirecting to: $service_url");
        header("Location: $service_url");
        return true;
    }

    protected function _getHarvardKeyServiceUrl()
    {
        $base_url = WEB_DIR;
        $login_url = $this->view->url('/harvard-key/auth/login');
        if(substr( $base_url, -strlen( "/admin")) == "/admin") {
            $base_url = substr($base_url, 0, strlen($base_url) - strlen("/admin"));
        }
        $return_to = $base_url . $login_url;
        return $this->_settings->get("service_url").'?return_to='.rawurlencode($return_to);
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