<subject>TripwirePro Notification Added: <?php echo $item_title; ?></subject>
<body>
    A notification has been set up for:<br> 
    <?php foreach($search_urls as $url => $title) { ?>
	    <a href="<?php echo $url; ?>"><?php echo $title; ?></a>.<br>
    <?php } ?>
<hr>
	<br>  
    You will be notified <?php echo $notify_every == 'instant' ? 'the <strong>INSTANT</strong> there is a new posting' : '<strong>EVERY '.$notify_every.'</strong> if new postings exist'; ?>.<br>
<hr>
    <br>
    <a href="<?php echo $links['edit']; ?>">Edit</a> this notification<br>
    <a href="<?php echo $links['deactivate']; ?>">Deactivate</a> this notification<br>
    <a href="<?php echo $links['manage']; ?>">Manage</a> all of my notifications<br>
</body>