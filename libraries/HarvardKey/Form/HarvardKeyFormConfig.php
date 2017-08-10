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

        $this->addElement('text', 'harvardkey_contributor_code', array(
            'label' => __('Contributor Passcode'),
            'description' => __('Define a passcode that users may enter to become contributors after they have authenticated with the Harvard Key System. To assign roles manually, leave this option disabled.'),
            'value' => get_option('harvardkey_contributor_code'),
            'validators' => array('NotEmpty', 'Alnum'),
        ));

        $this->addElement('checkbox', 'harvardkey_contributor_code_enabled', array(
            'label' => __('Enable Contributor Passcode?'),
            'value' => get_option('harvardkey_contributor_code_enabled'),
        ));
    }

}