<?php
$pageTitle = __('Browse Harvard Key Records') . ' ' . __('(%s total)', $total_results);
echo head(array('title'=>$pageTitle, 'bodyclass'=>'users'));
echo flash();
?>

<p>The table below shows all Omeka accounts that are linked to Harvard Key logins. A new Omeka user account is automatically created
when a user logs in for the first time, unless that user's email address matches an existing Omeka account. In that case, no
account is created and the Harvard Key is simply linked to the account.</p>

<table>
    <thead>
    <tr>
        <th>Omeka Email</th>
        <th>Omeka User ID</th>
        <th>Harvard Key ID</th>
        <th>New User?</th>
        <th>Account Active?</th>
        <th>Date Created</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach($records as $i => $record): ?>
    <tr class="<?php echo ($i%2?'even':'odd'); ?>">
        <td><?php echo html_escape($record['email']); ?></td>
        <td><?php echo $record['omeka_user_id']; ?></td>
        <td><?php echo $record['harvard_key_id']; ?></td>
        <td><?php echo $record['omeka_user_created'] ? 'Yes' : 'No'; ?></td>
        <td><?php echo isset($record['active']) ? ($record['active'] ? 'Yes' : 'No') : 'n/a'; ?></td>
        <td><?php echo $record['inserted']; ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php echo foot();?>
