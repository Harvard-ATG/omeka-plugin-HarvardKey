<?php
queue_js_file('login');
$pageTitle = __('Log In');
echo head(array('bodyclass' => 'login', 'title' => $pageTitle), $header);
?>
<h1>Omeka</h1>

<h2><?php echo link_to_home_page(option('site_title'), array("title" => __('Go to the public site'))); ?></h2>

<?php echo flash(); ?>

<div class="eight columns alpha offset-by-one">
<?php echo $this->form->setAction($this->url('harvard-key/users/login')); ?>
</div>    

<p id="forgotpassword">
<?php echo link_to('users', 'forgot-password', __('(Lost your password?)')); ?>
</p>

<?php echo foot(array(), $footer); ?>

