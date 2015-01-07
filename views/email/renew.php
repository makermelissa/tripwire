<subject>TripwirePro: 7 Day Expiration Notice - <?php echo $item_title; ?></subject>
<body>
    The following notification will <b>expire in 7 days</b>: <br>
    <?php foreach($search_urls as $url => $title) { ?>
	    <a href="<?php echo $url; ?>"><?php echo $title; ?></a>.<br>
    <?php } ?>
    If you would like to extend it, please click the renew link below.
<hr>
    <br>
    <a href="<?php echo $links['renew']; ?>">Renew</a> this notification.<br>
<hr>
    <br>
    <a href="<?php echo $links['edit']; ?>">Edit</a> this notification<br>
    <a href="<?php echo $links['deactivate']; ?>">Deactivate</a> this notification<br>
    <a href="<?php echo $links['manage']; ?>">Manage</a> all of my notifications<br>
</body>