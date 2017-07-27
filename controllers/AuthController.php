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
        $this->_helper->viewRenderer->setNoRender();
        $cookie = $this->_getCookie();
        $service_url = $this->_settings->get("service_url").'?return_to='.rawurlencode(WEB_DIR . $this->view->url());

        if(is_null($cookie)) {
            $msg = "No cookie. Please authenticate: $service_url";
            $this->_debug($msg);
            $this->getResponse()->setBody($msg);
            //header("Location: $url");
            return;
        }

        $token = new HarvardKeySecureToken($cookie, $this->_settings->get("secret_key"));
        $token->expires(intval($this->_settings->get("expires", 600)));

        $authAdapter = new HarvardKey_Auth_Adapter($this->_helper->db->getDb(), $token);
        $authResult = $this->_auth->authenticate($authAdapter);
        if(!$authResult->isValid()) {
            $msg = "Authentication Failed";
            if($token->isExpired()) {
                $msg = "Expired token. Please authenticate: $service_url";
            }
            $this->_debug($msg);
            $this->getResponse()->setBody($msg);
            return;
        }

        $this->_debug("Authentication Successful! Logged in as {$authResult->getIdentity()}");
        $session = new Zend_Session_Namespace;
        if ($session->redirect) {
            $this->_helper->redirector->gotoUrl($session->redirect);
        } else {
            $this->_helper->redirector->gotoUrl('/');
        }
    }

    protected function _getCookie()
    {
        $cookieName = $this->_settings->get("cookie_name");
        if(array_key_exists($cookieName, $_COOKIE)) {
            $cookieValue = $_COOKIE[$cookieName];
            $this->_debug("got cookie $cookieName = $cookieValue");
        } else {
            $cookieValue = null;
            $this->_debug("failed to get cookie $cookieName from cookie jar: ".var_export($_COOKIE, 1));
        }
        return $cookieValue;
    }

    protected function _debug(string $msg)
    {
        debug(get_class($this) . ": $msg");
        return $this;
    }
}