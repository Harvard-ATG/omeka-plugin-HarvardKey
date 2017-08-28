<?php
require_once(dirname(__FILE__)."/common.php");
require_once(HARVARDKEY_PLUGIN_DIR.'/libraries/HarvardKey/Form/HarvardKeyFormConfig.php');

class HarvardKeyPlugin extends Omeka_Plugin_AbstractPlugin
{
    /**
     * @var array Plugin hooks.
     */
    protected $_hooks = array(
        'install',
        'uninstall',
        'upgrade',
        'config',
        'config_form',
        'define_routes',
        'define_acl',
        'admin_users_browse_each',
        'admin_head'
    );

    /**
     * @var array Plugin filters.
     */
    protected $_filters = array(
        'admin_whitelist',
        'admin_navigation_users',

    );

    /**
     * @var array Options.
     */
    protected $_options = array(
        'harvardkey_role' => HARVARDKEY_GUEST_ROLE,
        'harvardkey_emails' => '',
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
     * Upgrade the plugin.
     *
     * @param array $args contains: 'old_version' and 'new_version'
     */
    public function hookUpgrade($args)
    {
        $oldVersion = $args['old_version'];
        $newVersion = $args['new_version'];
        $this->_log("hookUpgrade(): $oldVersion to $newVersion");
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

        foreach($data as $key => $value) {
            if(array_key_exists($key, $this->_options)) {
                set_option($key, $value);
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

    /**
     * Hook for defining or modifying ACLs.
     *
     * @param $args
     */
    function hookDefineAcl($args)
    {
        $acl = $args['acl'];

        // Guest role inherits global permissions and nothing else
        // Allow user to modify their profile.
        $acl->addRole(HARVARDKEY_GUEST_ROLE);
        $acl->allow(HARVARDKEY_GUEST_ROLE, 'Users', null, new Omeka_Acl_Assert_User);
    }

    /**
     * Hook for modifying each user on the admin browse users view.
     *
     * @param $args
     */
    public function hookAdminUsersBrowseEach($args) {
       $user = $args['user'];
       $view = $args['view'];
       $harvard_key_user = HarvardKeyUser::findByOmekaUserId($user->id);
       if($harvard_key_user) {
           $title = "Harvard key: {$harvard_key_user->harvard_key_id}";
           echo '<i class="fa fa-key harvardkey-user" aria-hidden="true" title="'.$title.'"></i>';
       }
    }

    /**
     * Hook for modifying the head section of the admin view.
     *
     */
    public function hookAdminHead() {
        queue_css_url('https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');
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

    /**
     * Hook to filter the admin navigation menu on users/edit pages.
     *
     * Remove the "Change Password" menu item for users who logged in via Harvard Key
     * and have an unuseable password entry.
     */
     public function filterAdminNavigationUsers($nav, $args) {
       $filtered_nav = array();
       foreach($nav as $item) {
         if($item['privilege'] == 'change-password') {
           $user = $item['resource'];
           if($user->password == HARVARDKEY_UNUSEABLE_PASSWORD && current_user()->role != "super") {
             continue;
           }
         }
         $filtered_nav[] = $item;
       }
       return $filtered_nav;
     }

    /**
     * Creates database tables for the plugin.
     */
    protected function _createTables() {
        $db = $this->_db;
        $sql = <<<__SQL
        CREATE TABLE IF NOT EXISTS `$db->HarvardKeyUser` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `harvard_key_id` varchar(128) NOT NULL,
          `omeka_user_id` int(10) unsigned NULL,
          `omeka_user_created` tinyint(1) NULL,
          `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          `inserted` timestamp NOT NULL DEFAULT '2000-01-01 00:00:00',
          PRIMARY KEY (`id`),
          UNIQUE KEY (`harvard_key_id`),
          KEY `inserted` (`inserted`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
__SQL;
        $this->_log($sql);
        $db->query($sql);
    }

    /**
     * Drops database tables created by the plugin.
     */
    protected function _dropTables() {
        $db = $this->_db;
        $sql = "DROP TABLE IF EXISTS `$db->HarvardKeyUser`";
        $this->_log($sql);
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
}
