<?php
/**
 * Link controller.
 *
 * @package HarvardKey
 */

if (!defined('HARVARDKEY_PLUGIN_DIR')) {
    define('HARVARDKEY_PLUGIN_DIR', dirname(dirname(__FILE__)));
}

class HarvardKey_RecordsController extends Omeka_Controller_AbstractActionController
{

    public function init()
    {
        parent::init();
        $this->_helper->db->setDefaultModelName('HarvardKeyUser');
    }

    public function preDispatch()
    {
        $user = $this->getCurrentUser();
        if($user->role != "super") {
            throw new Omeka_Controller_Exception_403('You do not have permission to access this pag (requires super user access).');
        }
    }

    public function browseAction()
    {
        $this->view->header = "header";
        $this->view->footer = "footer";
        $records = $this->_getRecords(array('only_created' => false));
        $this->view->assign(array('records' => $records, 'total_results' => count($records)));
    }

    public function deleteConfirmAction()
    {
        $this->view->header = "header";
        $this->view->footer = "footer";
        $this->view->users_to_delete = $this->_getRecords(array('only_created' => true));
    }

    public function deleteAction()
    {
        $records = $this->_getRecords(array('only_created' => true));
        $total = count($records);

        $db = get_db();
        $queries = array();
        $user_table = $db->getTableName('User');
        $harvard_key_table = $db->getTableName('HarvardKeyUser');

        $queries[] = <<<__SQL
DELETE FROM `$user_table` WHERE EXISTS (SELECT 0 FROM `$harvard_key_table` h WHERE h.omeka_user_id = `$user_table`.id AND h.omeka_user_created = 1);
__SQL;
        $queries[] = <<<__SQL
DELETE FROM `$harvard_key_table` WHERE omeka_user_created = 1;
__SQL;
        foreach($queries as $sql) {
            $db->query($sql);
        }

        $this->_helper->flashMessenger("Successfully deleted $total Harvard Key users.", 'success');
        $this->_helper->redirector->gotoUrl('/harvard-key/records/delete-confirm');
    }

    protected function _getRecords($options=null)
    {
        $default_options = array('sortby' => 'email', 'only_created' => false);
        if(is_array($options)) {
            $options = array_merge($default_options, $options);
        } else {
            $options = $default_options;
        }

        # TODO: refactor into a single query with a JOIN on the omeka users table
        $harvard_key_users = $this->_helper->db->getTable('HarvardKeyUser')->findAll();
        $omeka_users_by_id = $this->_getOmekaUsersIndexedById();

        $records = array();
        foreach($harvard_key_users as $harvard_key_user) {
            if($options['only_created'] && !$harvard_key_user->omeka_user_created) {
                continue;
            }
            $email = null;
            if(isset($omeka_users_by_id[$harvard_key_user->omeka_user_id])) {
                $omeka_user= $omeka_users_by_id[$harvard_key_user->omeka_user_id];
                $email = $omeka_user->email;
            }
            $records[] = array(
                'email'              => $email,
                'omeka_user_created' => $harvard_key_user->omeka_user_created,
                'omeka_user_id'      => $harvard_key_user->omeka_user_id,
                'harvard_key_id'     => $harvard_key_user->harvard_key_id,
                'inserted'           => $harvard_key_user->inserted,
                'id'                 => $harvard_key_user->id,
            );
        }

        $sortby = $options['sortby'];
        usort($records, function($a, $b) use ($sortby) {
            if(isset($a[$sortby]) && isset($b[$sortby])) {
                return strnatcmp($a[$sortby], $b[$sortby]);
            }
            return $a['id'] - $b['id'];
        });

        return $records;
    }

    protected function _getOmekaUsersIndexedById()
    {
        $omeka_users = $this->_helper->db->getTable('User')->findAll();
        $omeka_users_by_id = array();
        foreach($omeka_users as $omeka_user) {
            $omeka_users_by_id[$omeka_user->id] = $omeka_user;
        }
        return $omeka_users_by_id;
    }
}
