<?php

class HarvardKey_Controller_Plugin_Protect extends Zend_Controller_Plugin_Abstract
{
    /**
     * Controller/Action list for admin actions that do not require being logged-in
     *
     * @var string
     */
    protected $_whitelist = array(
        array('controller' => 'users', 'action' => 'login'),
        array('controller' => 'error', 'action' => 'error'),
        array('module' => 'harvard-key', 'controller' => 'users', 'action' => 'login'),
        array('module' => 'harvard-key', 'controller' => 'auth', 'action' => 'login'),
        array('module' => 'harvard-key', 'controller' => 'auth', 'action' => 'choose'),
    );

    /**
     * Require login when attempting to access the site.
     * Called before dispatching.
     *
     * @param Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        if ($this->_requireLogin($request)) {

            // Deal with the login stuff
            require_once 'Zend/Auth.php';
            require_once 'Zend/Session.php';

            if (!($auth = $this->getAuth())) {
                throw new RuntimeException('Auth object must be available when routing requests!');
            }

            if (!$auth->hasIdentity()) {
                // capture the intended controller / action for the redirect
                $session = new Zend_Session_Namespace;
                $session->redirect = $request->getPathInfo() .
                    (!empty($_GET) ? '?' . http_build_query($_GET) : '');

                // finally, send to a login page
                $this->getRedirector()->goto('login', 'users', 'default');
            }
        }
    }

    /**
     * Return the redirector action helper.
     *
     * @return Zend_Controller_Action_Helper_Redirector
     */
    public function getRedirector()
    {
        return Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
    }

    /**
     * Return the auth object.
     *
     * @return Zend_Auth
     */
    public function getAuth()
    {
        return Zend_Auth::getInstance();
    }

    /**
     * Determine whether or not the request requires an authenticated
     * user.
     *
     * @return boolean
     */
    private function _requireLogin($request)
    {
        $harvardkey_restrict_access = get_option('harvardkey_restrict_access');
        if(!$harvardkey_restrict_access || $harvardkey_restrict_access == "public") {
            return false;
        }

        $action = $request->getActionName();
        $controller = $request->getControllerName();
        $module = $request->getModuleName();

        foreach ($this->_whitelist as $entry) {
            // Any whitelist entry that omits the module will be assumed to be
            // talking about the default module.
            if (!array_key_exists('module', $entry)) {
                $entry['module'] = 'default';
            }

            $inWhitelist = ($entry['controller'] == $controller) && ($entry['action'] == $action);

            // Module name is not always defined in the request.
            if ($module !== null) {
                $inWhitelist = $inWhitelist && ($entry['module'] == $module);
            }

            if ($inWhitelist) {
                return false;
            }
        }

        return true;
    }
}
