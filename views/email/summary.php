<subject>TripwirePro Summary for: <?php echo $user->Email; ?></subject>
<body>
Search Result Summary<br>
<hr>
<table width="100%" id="management_table">
<?php
$count = 1;
if (count($searches) == 0) {
	echo '<tr><td>No Notifications Found</td></tr>'."\n";
} else {
	echo '<tr>';
	echo '<th>Item</th>';
	echo '<th>Expires</th>';
	echo '<th colspan="3">Actions</th>';
	echo '</tr>'."\n";
	foreach ($searches as $search) {
		echo '<tr>';
		echo '<td>'.anchor($search->SearchURL, $search->title, 'target="_blank"').'</td>';
		echo '<td>'.date('m/d/y', strtotime($search->ExpDate)).'</td>';
		echo '<td>'.anchor($search->links['edit'], 'Edit').'</td>';
		echo '<td>'.anchor($search->links['renew'], 'Renew').'</td>';
		echo '<td>'.anchor($search->links['deactivate'], 'Deactivate').'</td>';
		echo '</tr>'."\n";
	}
}
?>
</table>
    <br>
    <a href="<?php echo $links['manage']; ?>">Manage</a> all of my notifications<br>
</body>