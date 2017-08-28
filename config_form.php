<div class="field">
    <div class="two columns alpha">
        <?php echo get_view()->formLabel('harvardkey_role', __('Role')); ?>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation">
            <?php
            echo __('Select the role you would like to assign to new users that have logged in with Harvard Key. ');
            echo __('The default role is <i>'.Inflector::humanize(HARVARDKEY_GUEST_ROLE).'</i>, which only allows users to view public content.');
            ?>
        </p>
        <?php echo get_view()->formSelect('harvardkey_role', get_option('harvardkey_role'), null, get_user_roles()); ?>
    </div>
</div>
<div class="field">
    <div class="two columns alpha">
        <?php echo get_view()->formLabel('harvardkey_emails', __('Allowed Emails')); ?>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation">
            <?php echo __('Enter a list of emails that you would like to allow to login with Harvard Key. '
            .'Users will only be allowed to login when the Harvard Key email matches an email in the list. '
            .'One email per line.'); ?>
        </p>
        <?php echo get_view()->formTextarea('harvardkey_emails', get_option('harvardkey_emails')); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">&nbsp;</div>
    <div class="inputs five columns omega">
        <a href="<?php echo get_view()->url('/harvard-key/records/browse'); ?>" class="blue button"><?php echo __('View Harvard Key Users'); ?></a>
        <a href="<?php echo get_view()->url('/harvard-key/records/delete-confirm'); ?>" class="red button"><?php echo __('Delete Harvard Key Users'); ?></a>
    </div>
</div>
