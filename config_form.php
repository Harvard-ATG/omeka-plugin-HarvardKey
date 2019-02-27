<h2>Add users</h2>
<p class="explanation">
    <?php echo __('Enter a list of emails, one email per line. This list will be checked when a user logs in with Harvard Key, and if the email matches, then an account will be created with the selected role.'); ?>
</p>
<div class="field">
    <div class="two columns alpha">
        <?php echo get_view()->formLabel('harvardkey_emails', __('Emails')); ?>
    </div>
    <div class="inputs five columns omega">
        <?php echo get_view()->formTextarea('harvardkey_emails', get_option('harvardkey_emails')); ?>
    </div>
</div>
<div class="field">
    <div class="two columns alpha">
        <?php echo get_view()->formLabel('harvardkey_role', __('Role')); ?>
    </div>
    <div class="inputs five columns omega">
        <?php echo get_view()->formSelect('harvardkey_role', get_option('harvardkey_role'), null, get_user_roles()); ?>
    </div>
</div>

<hr>

<h2>Restrict access to site</h2>

<p class="explanation">By default, anyone can visit and view your site on the web. Here you can restrict access to only members of the Harvard community, or to registered users on this site.</p>


<div class="field" style="overflow: auto;">
    <div class="five columns alpha">
        <p><strong>Who can access this site?</strong></p>
        <?php echo get_view()->formRadio('harvardkey_restrict_access', get_option('harvardkey_restrict_access'), array('class'=>''), array('public'=> ' Anyone', 'harvardkey_users_only'=> ' Harvard community','site_users_only'=> ' Site users'),""); ?>
    </div>
</div>

<hr>

<h2>Manage harvard key users</h2>

<div class="field">
    <div class="inputs five columns omega">
        <a href="<?php echo get_view()->url('/harvard-key/records/browse'); ?>" class="blue button"><?php echo __('View Harvard Key Users'); ?></a>
        <a href="<?php echo get_view()->url('/harvard-key/records/manage'); ?>" class="red button"><?php echo __('Manage Harvard Key Users'); ?></a>
    </div>
</div>