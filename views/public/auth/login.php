<?php
queue_css_file('harvardkey');
$pageTitle = __('Harvard Key Login');
echo head(array('bodyclass' => 'login', 'title' => $pageTitle), $header);
?>

<h1><?php echo $pageTitle; ?></h1>

<?php echo flash(); ?>

<?php echo foot(array(), $footer); ?>
