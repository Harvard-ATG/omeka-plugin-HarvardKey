<?php
queue_css_file('harvardkey');
$pageTitle = __('Harvard Key');
echo head(array('title' => $pageTitle));
?>

<h1>Delete Harvard Key Users</h1>

<?php echo flash(); ?>

<div style="background-color: #d8d8d8; padding: 1em; margin: 1em 0; line-height: 1.5em;">
Please note that this action is for batch deleting Omeka accounts that have been created as a result of logging in through the Harvard Key system.
Omeka accounts created manually with a username/password and subsequently linked to a Harvard Key login via email address will be preserved and
omitted from the list below. Visit the <a href="<?php echo get_view()->url('/users'); ?>" alt="Users dashboard">Users</a> admin page
to delete those accounts manually.
</div>

<?php if(count($users_to_delete) > 0): ?>
<p>Please confirm that you would like to delete the following <?php echo count($users_to_delete); ?> user account(s):</p>

<table>
<tr>
    <th>Omeka Email</th>
    <th>Omeka User ID</th>
    <th>Harvard Key ID</th>
</tr>
<?php foreach($users_to_delete as $user): ?>
<tr>
    <td><?php echo $user['email']; ?></td>
    <td><?php echo $user['omeka_user_id']; ?></td>
    <td><?php echo $user['harvard_key_id']; ?></td>
</tr>
<?php endforeach; ?>
</table>
<a href="<?php echo get_view()->url('/harvard-key/records/delete'); ?>" alt="Delete accounts" class="red button">Permanently Delete Accounts</a>
<?php else: ?>
<p>No Harvard Key users to delete.</p>
<?php endif; ?>

<?php echo foot(array()); ?>
