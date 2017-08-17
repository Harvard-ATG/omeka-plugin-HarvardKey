<?php
queue_css_file('harvardkey');
$pageTitle = __('Harvard Key Login');
echo head(array('bodyclass' => 'login', 'title' => $pageTitle), $header);
?>

<h1><?php echo $pageTitle; ?></h1>

<div class="<?php echo $this->authCls; ?>">
    <h3><?php echo $this->authResult; ?></h3>
    <?php foreach($this->authMessages as $msg) { ?>
        <p><?php echo $msg; ?></p>
    <?php } ?>
</div>

<p><a href="<?php echo $this->chooseUrl;?>" alt="login page">Return to the login page</a>.</p>

<?php echo foot(array(), $footer); ?>
