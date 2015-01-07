<subject>TripwirePro Result: <?php echo $item_title; ?></subject>
<body>
Latest results for:<br>
    <?php foreach($search_urls as $url => $title) { ?>
	    <a href="<?php echo $url; ?>"><?php echo $title; ?></a>.<br>
    <?php } ?>
<hr>
<br>
<?php
	$count = 1;
	foreach ($hit_list as $hit) {
		echo $count.') <a href="'.$hit->URL.'">'.$hit->Title.'</a><br>';
		if (isset($hit->Photo) && !empty($hit->Photo)) {
			echo '<img height="225" src="'.$hit->Photo.'">&nbsp;<br><br>';
		}
		
		if (isset($hit->thumbnails)) {
			if (count($hit->thumbnails) > 0) {
				//echo '<img src="'.$hit->thumbnails[0].'">&nbsp;';
			/*foreach($hit->thumbnails as $thumbnail) {
				echo '<img src="'.$thumbnail.'">&nbsp;';
			}*/
				//echo '<br><br>';
			}
		}
		$count++;
	}	
?>
<hr>
    <br>
    <a href="<?php echo $links['edit']; ?>">Edit</a> this notification<br>
    <a href="<?php echo $links['deactivate']; ?>">Deactivate</a> this notification<br>
    <a href="<?php echo $links['manage']; ?>">Manage</a> all of my notifications<br>
</body>