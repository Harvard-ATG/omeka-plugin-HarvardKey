<?php
if (!defined('HARVARDKEY_PLUGIN_DIR')) {
    define('HARVARDKEY_PLUGIN_DIR', dirname(__FILE__));
}

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
        'define_acl'
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
        $this->_installOptions();
        $this->_createTables();
    }

    /**
     * Hook to plugin uninstallation.
     *
     * Uninstalls the options for the plugin.
     */
    public function hookUninstall()
    {
        $this->_uninstallOptions();
        $this->_dropTables();
    }

    /**
     * Hook to plugin configuration form submission.
     *
     * Sets options submitted by the configuration form.
     */
    public function hookConfig($args)
    {
        foreach (array_keys($this->_options) as $option) {
            if (isset($args['post'][$option])) {
                set_option($option, $args['post'][$option]);
            }
        }
    }

    /**
     * Hook to output plugin configuration form.
     *
     * Include form from config_form.php file.
     */
    public function hookConfigForm()
    {
        #include 'config_form.php';
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
        $role_name = "harvard_key_viewer";
        $acl->addRole($role_name);
        $acl->allow($role_name, 'Users', null, new Omeka_Acl_Assert_User);
        $acl->allow($role_name, 'Users', array('login', 'logout')); // Not including: forgot-password, activate
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
}
