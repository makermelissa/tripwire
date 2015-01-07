<?php
function drawMenu() {
	$xml_path = (defined('ROOT') ? (ROOT . DS) : '' ) . 'include/navigation.xml';
	$menuXML = simplexml_load_file($xml_path);
	foreach ($menuXML as $menu_item) {
		drawMenuItem($menu_item);
	}
}
function drawMenuItem($menu_item, $check_underline = true) {
	//print_r($menu_item);
	$attributes = $menu_item->attributes();
	$current = '';
	if ($check_underline) {
		if (formatURI((string)$attributes->link) == $_SERVER['REQUEST_URI']) {
			$current = ' id="current"';
		} elseif (count($menu_item->children()) > 0) {
			if (checkChildURI($menu_item->children())) {
				$current = ' id="current"';
			}
		}
	}
	if (!isset($attributes->hide) || $attributes->hide != "true") {
		echo '<li><a href="'.(string)$attributes->link.'"'.$current.'>'.(string)$attributes->label.'</a>';
		if (count($menu_item->children()) > 0) {
			echo "\n\t<ul>\n\t\t";
			foreach($menu_item->children() as $submenu_item) {
				drawMenuItem($submenu_item, false);
			}
			echo "\t</ul>\n";
		}
		echo "</li>\n";
	}
}

function checkChildURI($menu_items) {
	foreach ($menu_items as $menu_item) {
		$attributes = $menu_item->attributes();
		$current_uri = formatURI((string)$attributes->link);
		if (formatURI((string)$attributes->link) == $_SERVER['REQUEST_URI']) {
			return TRUE;
		} elseif (count($menu_item->children()) > 0) {
			return checkChildURI($menu_item->children());
		} else {
			return FALSE;	
		}
	}
}

function formatURI($uri) {
	return substr_compare($uri, "/", 0, 1) != 0 ? '/'.$uri : $uri;
}
?>