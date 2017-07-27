<?php
queue_css_file('harvardkey');
$pageTitle = __('Log In');
echo head(array('bodyclass' => 'login', 'title' => $pageTitle), $header);
?>

<h1><?php echo $pageTitle; ?></h1>

<?php echo flash(); ?>

<div class="harvardkey_choose">
    <a href="<?php echo $this->harvardKeyLoginUrl; ?>"  class="harvardkey_choose_box harvardkey_choose_primary">
        <i class="fa fa-key" aria-hidden="true"></i> Continue with Harvard Key System
    </a>
    <a href="<?php echo $this->omekaLoginUrl; ?>" class="harvardkey_choose_box harvardkey_choose_secondary">
        Login with username/password
    </a>
</div>

<?php echo foot(array(), $footer); ?>
