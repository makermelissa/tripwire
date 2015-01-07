<h2>Receive Summary</h2>
<p>Enter your email address to receive a summary of your current searches:</p>
<form method="post" action="/summary" id="summary-form">
<?php echo form_error('email'); ?>
<input type="email" name="summary-email" id="summary-email" style="float: left; width: 300px;" data-required>
<input type="submit" class="medium button" title="Send It!" value="Send It!" style="margin-left: 20px; margin-top: 3px;">
</form>
<?php add_validation('summary-form'); ?>