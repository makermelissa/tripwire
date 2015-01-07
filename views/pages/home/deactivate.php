<?php 
echo $status; 
if ($needs_confirmation) {
	echo '<br /><strong>'.anchor($search_url, $search_title, 'target="_blank"').'</strong>';
?>
<form name="deactivate_form" id="deactivate_form" method="post">
<br />
    <input id="submit-button" type="submit" class="medium button" title="Yes" value="Yes" align="center" name="yes" />
    <input id="cancel-button" type="submit" class="medium button" title="No" value="No" align="center" name="cancel" />
</form>
<?php 
} ?><br /><? if (isset($links)) echo '<br /><br />'.anchor($links['manage'], 'Manage all of my notifications');?>
<br /><?php echo anchor(urlencode($user_email), 'Add More Notifications'); ?>