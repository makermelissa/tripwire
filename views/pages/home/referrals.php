<br /><strong><?php echo anchor('', 'Add More Notifications'); ?></strong><br />

<br /><? if (isset($links)) echo '<br /><br />'.anchor($links['manage'], 'Manage all of my notifications');?>
<br /><span class="more-searches">Want more searches? Cool! Click below.

<?php $this->load->view('modules/referrals'); ?>
