<?php
queue_css_file('harvardkey');
$pageTitle = __('Harvard Key');
echo head(array('title' => $pageTitle));
?>

<h1>Manage Harvard Key Users</h1>

<?php echo flash(); ?>

<p>Only Omeka accounts that have been created as a result of logging in through the Harvard Key system are listed below.
Omeka accounts created manually with a username/password and later linked to a Harvard Key login are not listed.
Visit the <a href="<?php echo get_view()->url('/users'); ?>" alt="Users dashboard">Users</a> admin page to manage
those accounts.</p>

<div class="harvardkey_helpbox">
    <p><b>Activating / Deactivating Users</b>. Use this when users have created content and you want to preserve ownership, but disallow users from logging in. Deactivating these accounts will set their Omeka account status to <i>inactive</i> and prevent them from logging in.</i></p>
    <p><b>Permanently Delete Users</b>. Use this when users have not created any content and you want to reset or clear out all Omeka accounts. Note that when an Omeka user is deleted, the content they created will remain, but the ownership associated with their account is lost. Their name will only be associated with the content if it was explicitly added to a metadata field. You might use this during shopping period to allow anyone to login for that period and then reset for enrolled students.</p>
</div>

<?php if(count($records) > 0): ?>
<p>The following <?php echo count($records); ?> user account(s) may be managed:</p>

<table>
<tr>
    <th>Omeka Email</th>
    <th>Omeka User ID</th>
    <th>Harvard Key ID</th>
    <th>Account Active?</th>
</tr>
<?php foreach($records as $record): ?>
<tr>
    <td><?php echo $record['email']; ?></td>
    <td><?php echo $record['omeka_user_id']; ?></td>
    <td><?php echo $record['harvard_key_id']; ?></td>
    <td><?php echo isset($record['active']) ? ($record['active'] ? 'Yes' : 'No') : 'n/a'; ?></td>
</tr>
<?php endforeach; ?>
</table>

<div class="seven columns alpha">
    <a href="<?php echo get_view()->url('/harvard-key/records/deactivate'); ?>" alt="Deactivate accounts" class="blue button">Deactivate Users</a>
    <a href="<?php echo get_view()->url('/harvard-key/records/activate'); ?>" alt="Activate accounts" class="blue button">Activate Users</a>
</div>
<div class="three columns omega">
    <a href="<?php echo get_view()->url('/harvard-key/records/delete'); ?>" alt="Delete accounts" onclick="return confirm('Are you sure you want to *permanently delete* these Harvard Key user accounts?');" class="red button pull-right">Permanently Delete Users </a>
</div>
<?php else: ?>
<p>No Harvard Key users to delete.</p>
<?php endif; ?>

<?php echo foot(array()); ?>
