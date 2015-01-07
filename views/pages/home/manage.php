<?php // echo 'Notifications for '.$user_email.'<br />'; ?>
<table width="100%" id="management_table">
<?php
$count = 1;
if (count($searches) == 0) {
	echo '<tr><td>No Notifications Found</td></tr>'."\n";
} else {
	echo '<tr class="notifications-header">';
	echo '<th class="search-item">Item</th>';
	echo '<th class="search-item"><span class="search-action">Expires</span></th>';
	echo '<th class="actions">Actions</th>';
	echo '</tr>'."\n";
	foreach ($searches as $search) {
		echo '<tr class="'.($count++ % 2 == 1 ? 'odd-row' : 'even-row').'">';
		if ($search->Active == 1) {
			echo '<td class="search-item">'.anchor($search->SearchURLParams, $search->title, 'target="_blank"').'</td>';
			echo '<td class="search-item"><span class="search-action '.($search->expiring ? 'expiring' : 'active').'">'.date('m/d/y', strtotime($search->ExpDate)).'</span></td>';
			echo '<td class="actions"><span class="search-action">'.anchor($search->links['edit'], 'Edit').'</span>';
			echo '<span class="search-action">'.anchor($search->links['renew'], 'Renew').'</span>';
			echo '<span class="search-action">'.anchor($search->links['deactivate'], 'Deactivate').'</span></td>';
		} else {
			echo '<td class="search-item">'.anchor($search->SearchURL, $search->title, 'target="_blank" class="inactive"').'</td>';
			echo '<td class="search-item"><span class="search-action expired">Expired</span></td>';
			echo '<td class="actions"><span class="search-action">'.anchor($search->links['edit'], 'Edit').'</span>';
			echo '<span class="search-action">'.anchor($search->links['renew'], 'Renew').'</span></td>';			
		}
		echo '</tr>'."\n";
	}
}
?>
</table>
<br /><strong><?php echo anchor(urlencode($user_email), 'Add More Notifications'); ?></strong><br />

<br /><span class="<?php echo ($searches_left == 0 ? 'no-' : ''); ?>searches-left">You have <?php echo $searches_left.' search'.($searches_left == 1 ? '' : 'es'); ?> remaining</span>
<br /><span class="more-searches">Want more searches? Cool! Click below.

<?php $this->load->view('modules/referrals'); ?>
