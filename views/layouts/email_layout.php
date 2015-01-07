<html xmlns="http://www.w3.org/1999/xhtml">
<head>
   <title><?php echo $subject; ?></title>
</head>
<body>
    <!-- Content Starts Here -->
    <?php echo $content; ?>
    <!-- Content Ends Here -->
    <br>
    <br>
    Thank you for using TripwirePro. <?php echo anchor("/".urlencode($email_to), "Add some more!"); ?><br>
<?php /*    <br>
    ______________________________________<br>
    Download mobile app for&nbsp;
	<?php echo anchor("app/".$email."/iphone", "iPhone"); ?>
    &nbsp;&nbsp;|&nbsp;&nbsp;
	<?php echo anchor("app/".$email."/android", "Android"); ?><br> */ ?>
    <p></p>
</body></html>