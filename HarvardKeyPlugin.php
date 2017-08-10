<?php
if (!defined('HARVARDKEY_PLUGIN_DIR')) {
    define('HARVARDKEY_PLUGIN_DIR', dirname(__FILE__));
}

require_once(HARVARDKEY_PLUGIN_DIR.'/libraries/HarvardKey/Form/HarvardKeyFormConfig.php');

class HarvardKeyPlugin extends Omeka_Plugin_AbstractPlugin
{
    /**
     * @var array Plugin hooks.
     */
    protected $_hooks = array(
        'install',
        'uninstall',
        'config',
        'config_form',
        'define_routes',
        'define_acl',
        'users_form',
        'before_save_user',
    );

    /**
     * @var array Plugin filters.
     */
    protected $_filters = array(
        'admin_whitelist'
    );

    /**
     * @var array Plugin options.
     */
    protected $_options = array(
    );

    /**
     * @var string Custom role for harvard key (viewer permissions).
     */
    protected $_harvardkey_viewer_role = "harvard_key_viewer";

    /**
     * Plugin constructor.
     *
     * Requires class autoloader, and calls parent constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Hook to plugin installation.
     *
     * Installs the options for the plugin.
     */
    public function hookInstall()
    {
        $this->_createTables();
        set_option('harvardkey_contributor_code', $this->__randomPassword());
        set_option('harvardkey_contributor_code_enabled', 0);
    }

    /**
     * Hook to plugin uninstallation.
     *
     * Uninstalls the options for the plugin.
     */
    public function hookUninstall()
    {

        $this->_dropTables();
        delete_option('harvardkey_contributor_code');
        delete_option('harvardkey_contributor_code_enabled');
    }

    /**
     * Hook to plugin configuration form submission.
     *
     * Sets options submitted by the configuration form.
     */
    public function hookConfig($args)
    {
        $csrfValidator = new Omeka_Form_SessionCsrf;
        if (!$csrfValidator->isValid($args['post'])) {
            throw Omeka_Validate_Exception(__("Invalid CSRF token."));
        }
        $data = $args['post'];
        $this->_log("hookConfig: ".var_export($data,1));

        set_option('harvardkey_contributor_code', $data['harvardkey_contributor_code']);
        set_option('harvardkey_contributor_code_enabled', $data['harvardkey_contributor_code_enabled']);
    }

    /**
     * Hook to output plugin configuration form.
     *
     * Include form from config_form.php file.
     */
    public function hookConfigForm()
    {
        require(dirname(__FILE__) . '/config_form.php');
    }

    /**
     * Hook to define routes.
     *
     * Overrides the add, login and logout actions of the UsersController to
     * our customized versions.
     */
    public function hookDefineRoutes($args)
    {
        $router = $args['router'];
        $router->addConfig(new Zend_Config_Ini(HARVARDKEY_PLUGIN_DIR .  DIRECTORY_SEPARATOR . 'routes.ini', 'routes'));
    }

    function hookDefineAcl($args)
    {
        $acl = $args['acl'];

        // Harvard key guest role inherits the same privileges as unauthenticated users (i.e. null role)
        // and will need to be promoted by an admin to do anything more in the system.
        $acl->addRole($this->_harvardkey_viewer_role);
        $acl->allow($this->_harvardkey_viewer_role, 'Users', null, new Omeka_Acl_Assert_User);
        $acl->allow($this->_harvardkey_viewer_role, 'Users', array('login', 'logout', 'forgot-password')); // not include: 'activate'
    }

    function hookUsersForm($args) {
        $user = $args['user'];
        $form = $args['form'];

        if(!$user->id) {
            return;
        }

        $code_enabled = get_option('harvardkey_contributor_code_enabled');
        if(!$code_enabled) {
            return;
        }

        if($user->role === $this->_harvardkey_viewer_role) {
            $form->addElement('text', 'harvardkey_contributor_code_input', array(
                'label' => __('Contributor Passcode'),
                'description' => __('Enter passcode to become site contributor'),
                'validators' => array(),
                'value' => '',
            ));
        }
    }

    function hookBeforeSaveUser($args) {
        $post_data = $args['post'];
        $user = $args['record'];
        $submitted_code = $post_data['harvardkey_contributor_code_input'];

        $code_enabled = get_option('harvardkey_contributor_code_enabled');
        if($code_enabled) {
            $code = get_option('harvardkey_contributor_code');
            if($submitted_code == $code) {
                $user->role = 'contributor';
                $this->_log("promoting {$user->id} to contributor because they submitted the correct passcode");
            }
        }
    }

    /**
     * Filter the admin interface whitelist.
     *
     * Allows our custom login action to be accessed without logging in.
     */
    public function filterAdminWhitelist($whitelist)
    {
        $whitelist[] = array(
            'module' => 'harvard-key',
            'controller' => 'users',
            'action' => 'login'
        );
        $whitelist[] = array(
            'module' => 'harvard-key',
            'controller' => 'auth',
            'action' => 'choose'
        );
        $whitelist[] = array(
            'module' => 'harvard-key',
            'controller' => 'auth',
            'action' => 'login'
        );

        return $whitelist;
    }

    protected function _createTables() {
        $db = $this->_db;
        $sql = <<<__SQL
        CREATE TABLE IF NOT EXISTS `$db->HarvardKeyUser` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `harvard_key_id` varchar(128) NOT NULL,
          `omeka_user_id` int(10) unsigned NULL,
          `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          `inserted` timestamp NOT NULL DEFAULT '2000-01-01 00:00:00',
          PRIMARY KEY (`id`),
          UNIQUE KEY (`harvard_key_id`),
          KEY `inserted` (`inserted`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
__SQL;
        debug($sql);
        $db->query($sql);
    }

    protected function _dropTables() {
        $db = $this->_db;
        $sql = "DROP TABLE IF EXISTS `$db->HarvardKeyUser`";
        $db->query($sql);
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

    /**
     * Generates a random password.
     *
     * @param integer $length
     * @return string
     */
    private function __randomPassword(int $length) {
        return substr(str_shuffle(strtolower(sha1(rand() . time() . 'harvardkey'))),0, $length);
    }
}
