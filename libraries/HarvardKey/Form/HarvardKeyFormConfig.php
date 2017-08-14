<?php
/**
 * The main plugin configuration form.
 * @package IiifItems
 * @subpackage Form
 */
class HarvardKey_Form_Config extends Omeka_Form {
    /**
     * Sets up elements for this form.
     */
    public function init() {
        // Top-level parent
        parent::init();
        $this->applyOmekaStyles();
        $this->setAutoApplyOmekaStyles(false);

        $user_roles = get_user_roles();
        $role_value = get_option('harvardkey_passcode_role');
        if(!isset($role_value)) {
            $role_value = isset($user_roles['student']) ? 'student' : 'contributor';
        }

        $this->addElement('text', 'harvardkey_passcode_value', array(
            'label' => __('Passcode'),
            'description' => __('Create a passcode that users may submit on their profile page to upgrade their role. To assign roles manually, leave this option disabled.'),
            'value' => get_option('harvardkey_passcode_value'),
            'validators' => array('NotEmpty', 'Alnum'),
        ));

        $this->addElement('select', 'harvardkey_passcode_role', array(
            'label' => __('Role'),
            'description' => __("Select the role users will be assigned when they enter the correct passcode."),
            'multiOptions' => $user_roles,
            'value' => $role_value
        ));

        $this->addElement('checkbox', 'harvardkey_passcode_enabled', array(
            'label' => __('Enable Passcode?'),
            'value' => get_option('harvardkey_passcode_enabled'),
            'required' => true
        ));
    }

}