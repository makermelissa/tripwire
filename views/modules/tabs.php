			<!-- Tabs Navigation -->
			<ul class="tabs-nav">
            <?php if ($active_tab == 'add-new') { ?>
                <li class="active"><a href="#add-new">Add a Search</a></li>
            <?php } else { ?>
                <li class="static-tab"><a href="/">Add a Search</a></li>
			<?php } ?>
                <li><a href="#faq">FAQ</a></li>
                <li><a href="#updates">Updates</a></li>
<?php if (isset($management_id) && $management_id) { ?>
            <?php if (in_array($active_tab, array('manage', 'my-searches'))) { ?>
                <li class="active"><a href="#manage">My Searches</a></li>
            <?php } else { ?>
                <li class="static-tab"><a href="/manage/<?php echo $management_id; ?>">My Searches</a></li>
			<?php } ?>
<?php } else { ?>
	<!-- Show a tab that allows users to enter their email and receive a summary -->
    <li><a href="#manage">My Searches</a></li>
<?php } ?>                
<?php if (in_array($active_tab, array('manage', 'add-new', 'my-searches'))) { 
			// Don't draw an extra tab
	  } elseif (empty($active_tab)) { ?>                
                <li class="active"><a href="#active-tab">TripwirePro</a></li>
<?php } else { ?>                
                <li class="active"><a href="#<?php echo $active_tab; ?>"><?php echo ucwords(str_replace('-', ' ', $active_tab)); ?></a></li>
<?php } ?>                
                <li class="suggest-tab static-tab"><a href="#tab4" id="show_survey">Make a Suggestion</a></li>
			</ul>
