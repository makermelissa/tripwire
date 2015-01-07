
<subject>TripwirePro: Expired Renewal Notice - <?php echo $item_title; ?></subject>
<body>
	When working on our system, we came across a bug that was causing the system to not send out renewal notifications. As a result, searches were expiring without notice after 30 days. <br>
	We are sending out a one-time email for each expired search in case you want to renew your search. If you would prefer not to renew it, then you do not need to do anything. <br>
	However, if you would like to renew this past search, click the renew link below.<br>
	<br>
    The following notification has expired and can be renewed:<br> 
    <?php foreach($search_urls as $url => $title) { ?>
	    <a href="<?php echo $url; ?>"><?php echo $title; ?></a>.<br>
    <?php } ?>
<hr>
    <br>
    <a href="<?php echo $links['renew']; ?>">Renew</a> this notification.<br>
    <a href="<?php echo $links['manage']; ?>">Manage</a> all of my notifications<br>
    <br>
	Again, you will not receive an email again for this item unless you choose to renew it.
</body>