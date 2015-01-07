<?php 
echo $status; 
if (isset($links)) echo '<br /><br />'.anchor($links['manage'], 'Manage all of my notifications');
?>
<br /><?php echo anchor(urlencode($user_email), 'Add More Notifications'); ?>