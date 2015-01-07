<subject>TripwirePro: Deactivated Search Notice - <?php echo $item_title; ?></subject>
<body>
    The following notification has been <b>deactivated</b>: <br>
    <?php foreach($search_urls as $url => $title) { ?>
	    <a href="<?php echo $url; ?>"><?php echo $title; ?></a>.<br>
    <?php } ?>
    If you would like to reactivate it, please click the renew link below.
<hr>
    <br>
    <a href="<?php echo $links['renew']; ?>">Renew</a> this notification.<br>
    <br>
    <a href="<?php echo $links['manage']; ?>">Manage</a> all of my notifications<br>
</body>