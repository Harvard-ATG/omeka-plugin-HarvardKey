<?php
$pageTitle = __('Browse Harvard Key Records') . ' ' . __('(%s total)', $total_results);
echo head(array('title'=>$pageTitle, 'bodyclass'=>'users'));
echo flash();
?>

<table>
    <thead>
    <tr>
        <th>Omeka Email</th>
        <th>Omeka User ID</th>
        <th>Harvard Key ID</th>
        <th>New User?</th>
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
        <td><?php echo $record['inserted']; ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php echo foot();?>
