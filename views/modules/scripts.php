<!-- Java Script
 ================================================== -->
<script type="text/javascript" src="/js/jquery-1.9.1.js"></script>
<script type="text/javascript" src="/js/flexslider.js"></script>
<script type="text/javascript" src="/js/jquery.isotope.min.js"></script>
<script type="text/javascript" src="/js/custom.js"></script>
<script type="text/javascript" src="/js/ender.min.js"></script>
<script type="text/javascript" src="/js/selectnav.js"></script>
<script type="text/javascript" src="/js/imagebox.min.js"></script>
<script type="text/javascript" src="/js/carousel.js"></script>
<script type="text/javascript" src="/js/twitter.js"></script>
<script type="text/javascript" src="/js/tooltip.js"></script>
<script type="text/javascript" src="/js/popover.js"></script>
<script type="text/javascript" src="/js/effects.js"></script>
<script type="text/javascript" src="/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="/js/jquery.maskedinput.min.js"></script>
<script type="text/javascript" src="/js/jquery-ui-1.10.3.custom.min.js"></script>
<script type="text/javascript" src="/js/jquery-ui-sliderAccess.js"></script>
<script type="text/javascript" src="/js/jquery.ui.touch-punch.min.js"></script>
<script type="text/javascript" src="/js/jquery-validate.min.js"></script>
<script type="text/javascript" src="/js/jquery.tabify.js"></script>
<script type="text/javascript" src="/js/jquery.zclip.min.js"></script>
<script type="text/javascript" src="/js/tripwire.js"></script>
<?php
foreach ($js_files as $file) { ?>        
	<script type="text/javascript" src="<?php echo $file; ?>"></script><?php
}
?>

<!-- CSS
 ================================================== -->
<link rel="stylesheet" type="text/css" href="/css/style.css">
<link href="http://cdn-images.mailchimp.com/embedcode/classic-081711.css" rel="stylesheet" type="text/css">
<?php 
foreach ($css_files as $file) { ?>        
	<link href="<?php echo $file; ?>" rel="stylesheet" type="text/css" /><?php 
}
?>