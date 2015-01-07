<?php
	if (isset($meta_keywords)) {
		if (is_array($meta_keywords)) $meta_keywords = implode(', ', $meta_keywords);
		echo '<meta name="keywords" content="'.$meta_keywords.'">'."\n";
	}
	if (isset($meta_description)) {
		echo '<meta name="description" content="'.$meta_description.'">'."\n";
	}
?>