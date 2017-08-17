<?php
/**
 * Authentication controller.
 *
 * Provides actions that allows the user to choose whether to authenticate with Harvard Key, or the default
 * authentication (username/password).
 *
 * @package HarvardKey
 */

if (!defined('HARVARDKEY_PLUGIN_DIR')) {
    define('HARVARDKEY_PLUGIN_DIR', dirname(dirname(__FILE__)));
}

require_once HARVARDKEY_PLUGIN_DIR.'/adapters/AuthAdapter.php';
require_once(HARVARDKEY_PLUGIN_DIR.'/libraries/HarvardKey/JsonIdentityToken.php');

class HarvardKey_AuthController extends Omeka_Controller_AbstractActionController
{
    /**
     * @var Zend_Config_Ini|null Holds auth config
     */
    protected $_config = null;

    /**
     * Initializes the controller with necessary resources.
     */
    public function init() {
        $this->_auth = $this->getInvokeArg('bootstrap')->getResource('Auth');
        $this->_config = new Zend_Config_Ini(HARVARDKEY_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'auth.ini', 'auth');
    }

    /**
     * Allow the user to choose which authentication method they would like to use.
     */
    public function chooseAction() {
        $this->_handlePublicAction();
        $this->view->assign('omekaLoginUrl', $this->view->url('/harvard-key/users/login'));
        $this->view->assign('harvardKeyLoginUrl', $this->_getHarvardKeyAuthServiceUrl());
    }

    /**
     * Handle a login action using Harvard Key credentials.
     *
     * This action expects the Harvard Key credentials to be present in a cookie (signed to prevent tampering).
     */
    public function loginAction() {
        $this->_handlePublicAction();
        $this->view->assign('chooseUrl', $this->view->url('/harvard-key/auth/choose'));

        $cookie = $_COOKIE[$this->_config->get("cookie_name")];
        $secret_key = $this->_config->get("secret_key");
        $expires = intval($this->_config->get("expires", 600));

        if(!$cookie) {
            $this->view->assign("authResult", "Authentication Failed");
            $this->view->assign('authMessages', array("Cookies must be enabled to authenticate. Please ensure that you have cookies enabled in your browser and then try logging in again. If the problem persists, please contact support."));
            return;
        }

        $allowed_emails = get_option('harvardkey_emails');
        $token = new JsonIdentityToken($cookie, $secret_key, $expires);
        $authAdapter = new HarvardKey_Auth_Adapter($this->_helper->db->getDb(), $token, $allowed_emails);
        $authResult = $this->_auth->authenticate($authAdapter);
        if(!$authResult->isValid()) {
            $this->view->assign("authCls", "red");
            $this->view->assign("authResult", "Authentication Failed");
            $this->view->assign('authMessages', $authResult->getMessages());
            return;
        }

        $this->_log("auth success: logged in as {$authResult->getIdentity()}");
        $this->view->assign("authResult", "Authentication Successful");
        $this->view->assign('authMessages', array("Harvard Key credentials accepted and you have been logged in as user {$authResult->getIdentity()}. You will be redirected."));
        //queue_js_string("setTimeout(function() { window.location='".$this->view->url('/')."'; }, 2000);");
        $this->_helper->redirector->gotoUrl('/');
    }

    /**
     * Handle public actions by setting the appropriate view variables for header/footer, etc.
     */
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

    /**
     * Helper method that can redirect to the harvard key auth service that sets the cookie
     * required for the loginAction. Includes extra logic to try and prevent infinite loop of redirects.
     *
     * @return bool True if redirect header is sent, False otherwise.
     */
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
        $service_url = $this->_getHarvardKeyAuthServiceUrl();
        $this->_log("Redirecting to: $service_url");
        header("Location: $service_url");
        return true;
    }

    /**
     * Helper method to construct the URL to the auth service that handles authentication with the Harvard Key system
     * and sets the cookie containing credentials.
     *
     * @return string URL
     */
    protected function _getHarvardKeyAuthServiceUrl()
    {
        $base_url = WEB_DIR;
        $login_url = $this->view->url('/harvard-key/auth/login');
        if(substr( $base_url, -strlen( "/admin")) == "/admin") {
            $base_url = substr($base_url, 0, strlen($base_url) - strlen("/admin"));
        }
        $return_to = $base_url . $login_url;
        return $this->_config->get("url").'?return_to='.rawurlencode($return_to);
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