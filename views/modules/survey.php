<!--
Create a dialog here. Not sure if we'll have the controller send the control format or if we'll just define it here (which we'll do for now).

I'm thinking it should have a tab sticking out on the side like the truckhive address book and when you click it, it would slide over to the middle of the page (or maybe just pop out and stay on the side).
The user would be able to fill out the form (not sure if this is a dialog or something else we could use jqueryui (animated modal form ?) to create. Otherwise, I could create this myself. Once they finish and click submit, an email should anonymously be submitted in the background.

-->
<script type="text/javascript">
var RecaptchaOptions = {
	theme : 'white'
};
</script>
 <div id="survey_wrapper" class="message" title="Suggest a Feature" style="background-color: white; display: none;">
  <p>Tell us a feature you would like us to work on next for TripwirePro</p>
  <form id="suggest">
  <fieldset>
    <label for="email">Email Address</label>
    <input type="text" name="suggest_email" id="suggest_email" style="width: 290px;" />
    <label for="suggestion">Suggestion</label>
    <textarea name="suggestion" id="suggestion" rows="8" cols="46" class="text ui-widget-content ui-corner-all"></textarea>
    <div id="captcha-status"></div>
    <div id="recaptcha_wrapper">
    <?php echo recaptcha_get_html(RECAPTCHA_PUBLIC_KEY); ?>
    </div>
  </fieldset>
  </form>
</div>


				