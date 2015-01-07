<!DOCTYPE html>
<!--[if lt IE 7 ]><html class="ie ie6" lang="en"><![endif]-->
<!--[if IE 7 ]><html class="ie ie7" lang="en"><![endif]-->
<!--[if IE 8 ]><html class="ie ie8" lang="en"><![endif]-->
<!--[if (gte IE 9)|!(IE)]><!--><html lang="en"><!--<![endif]-->
<head>

<!-- Basic Page Needs
 ================================================== -->
<meta charset="utf-8">
<title><?php echo $title; ?></title>
<?php $this->load->view('modules/meta'); ?>
<!-- Mobile Specific
 ================================================== -->
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

<?php $this->load->view('modules/scripts'); ?>
</head>
<body>
<?php $this->load->view('modules/messages'); ?>
<?php $this->load->view('modules/survey'); ?>
<!-- Header -->
<header>
<div id="header">

	<!-- 960 Container Start -->
	<div class="container ie-dropdown-fix">
		<!-- Logo -->
		<div class="sixteen columns">
			<a href="/"><img src="/images/logo.png" alt="" id="logo"/></a>
		</div>
	</div>
	<!-- 960 Container End -->

</div>
</header>
<!-- End Header -->

<!-- 960 Container -->
<section>
<div class="container">
		<!-- Tabs -->
		<div class="tab-bar">
			<?php $this->load->view('modules/tabs'); ?>			
			<!-- Tabs Content -->
			<div class="content_box tabs-container">
				<div class="tab-content" id="<?php echo $active_tab; ?>">
					<?php $this->load->view($view); ?>
				</div>
					
				<div class="tab-content" id="faq">
					<?php $this->load->view('modules/faq'); ?>			
				</div>

				<div class="tab-content" id="updates">
					<?php $this->load->view('modules/mailchimp'); ?>			
				</div>
                
<?php if (!isset($management_id) || !$management_id || $active_tab == 'my-searches') { ?>
				<div class="tab-content" id="manage">
					<?php $this->load->view('modules/summary'); ?>			
				</div>
<?php } ?>
			</div>
			
		</div>
</div>
</section>
<!-- End 960 Container -->

<!--  Footer Copyright-->
<footer>
<div id="footer-bottom">

	<!-- 960 Container -->
	<div class="container">
				
		<div class="sixteen columns">
			<div id="copyright">© Copyright <?php echo date("Y"); ?> by <span>TripwirePro</span>. All Rights Reserved.</div>
		</div>
	</div>
	<!-- End 960 Container -->
	
</div>
<!--  Footer Copyright End -->

<!-- Back To Top Button -->
<div id="backtotop"><a href="#"></a></div>
</footer>

<!-- Imagebox Build -->
<script src="/js/imagebox.build.js"></script>
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-42550926-1', 'tripwirepro.com');
  ga('send', 'pageview');

</script>
</body>
</html>