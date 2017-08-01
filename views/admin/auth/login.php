<?php
queue_css_file('harvardkey');
$pageTitle = __('Harvard Key Login');
echo head(array('bodyclass' => 'login', 'title' => $pageTitle), $header);
?>

<h1><?php echo $pageTitle; ?></h1>

<p><b><?php echo $this->authResult; ?></b></p>
<?php foreach($this->authMessages as $msg) { ?>
    <p><?php echo $msg; ?></p>
<?php } ?>

<p><a href="<?php echo $this->chooseUrl;?>" alt="login page">Return to the login page</a>.</p>

<?php echo foot(array(), $footer); ?>
