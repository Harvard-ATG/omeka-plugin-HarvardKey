<div class="field">
    <div class="two columns alpha">
        <?php echo get_view()->formLabel('harvardkey_emails', __('Prescreened Emails')); ?>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation">
            <?php echo __('Enter a list of emails, one email per line. '
            .' The purpose of this list is to automatically grant new users a specific role when they login the first time.'
            .' If the user already has an account on the site, then they will be linked to that account. '); ?>
        </p>
        <?php echo get_view()->formTextarea('harvardkey_emails', get_option('harvardkey_emails')); ?>
    </div>
</div>
<div class="field">
    <div class="two columns alpha">
        <?php echo get_view()->formLabel('harvardkey_role', __('Prescreened Role')); ?>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation">
            <?php
            echo __('Select the role you would like to to assign to prescreened users. Note that ');
            echo __('The default role is <i>'.Inflector::humanize(HARVARDKEY_GUEST_ROLE).'</i>, which only allows users to view public content.');
            ?>
        </p>
        <?php echo get_view()->formSelect('harvardkey_role', get_option('harvardkey_role'), null, get_user_roles()); ?>
    </div>
</div>
<div class="field">
    <div class="two columns alpha">
        <?php echo get_view()->formLabel('harvardkey_protect', __('Require login?')); ?>
    </div>
    <div class="inputs five columns omega">
        <?php echo get_view()->formCheckbox('harvardkey_protect', 1, array('checked' => get_option('harvardkey_protect') ? true : false)); ?>
        <span class="explanation">&nbsp;Check this field to require visitors to log in before viewing any content.</span>
    </div>
</div>
<div class="field">
    <div class="two columns alpha">
        <?php echo get_view()->formLabel('harvardkey_membersonly', __('Members Only?')); ?>
    </div>
    <div class="inputs five columns omega">
        <?php echo get_view()->formCheckbox('harvardkey_membersonly', 1, array('checked' => get_option('harvardkey_membersonly') ? true : false)); ?>
        <span class="explanation">&nbsp;Check this field to only allow users to login if they have an account on this site or are listed in the prescreened emails.</span>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">&nbsp;</div>
    <div class="inputs five columns omega">
        <a href="<?php echo get_view()->url('/harvard-key/records/browse'); ?>" class="blue button"><?php echo __('View Harvard Key Users'); ?></a>
        <a href="<?php echo get_view()->url('/harvard-key/records/manage'); ?>" class="red button"><?php echo __('Manage Harvard Key Users'); ?></a>
    </div>
</div>