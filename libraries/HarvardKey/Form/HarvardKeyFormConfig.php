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
        unset($user_roles['super']);

        $this->addElement('select', 'harvardkey_passcode_role', array(
            'label' => __('Role'),
            'description' => __("Select the role users will be assigned when they enter the correct passcode."),
            'multiOptions' => $user_roles,
            'value' => get_option('harvardkey_role'),
        ));
    }

}
