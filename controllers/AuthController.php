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

    public function chooseAction() {
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
        $this->view->assign('omekaLoginUrl', $this->view->url('/harvard-key/users/login'));
        $this->view->assign('harvardKeyLoginUrl', $this->view->url('/harvard-key/auth/login'));
    }

    public function loginAction() {
        $this->_log("loginAction()");
        $this->_helper->viewRenderer->setNoRender();
        $cookie = $_COOKIE[$this->_settings->get("cookie_name")];
        $session = new Zend_Session_Namespace;

        if(!$cookie) {
            $msg = "Token not found in cookies. Please authenticate.";
            $this->_log($msg);
            $this->getResponse()->setBody($msg);
            $this->_redirectToHarvardKeyService();
            return;
        }

        $secret_key = $this->_settings->get("secret_key");
        $expires = intval($this->_settings->get("expires", 600));
        $token = new HarvardKeySecureToken($cookie, $secret_key, $expires);
        if(!$token->isValid()) {
            $this->_log(implode(",", $token->validationErrors()));
            $this->getResponse()->setBody("Token invalid. Please re-authenticate.");
            $this->_redirectToHarvardKeyService();
            return;
        }

        $authAdapter = new HarvardKey_Auth_Adapter($this->_helper->db->getDb(), $token);
        $authResult = $this->_auth->authenticate($authAdapter);
        if(!$authResult->isValid()) {
            $msg = "Authentication Failed";
            $this->_log($msg);
            $this->getResponse()->setBody($msg);
            $this->_helper->flashMessenger($msg, 'error');
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
        $service_url = $this->_settings->get("service_url").'?return_to='.rawurlencode(WEB_DIR . $this->view->url());
        $this->_log("Redirecting to: $service_url");
        header("Location: $service_url");
        return true;
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