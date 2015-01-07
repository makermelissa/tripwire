<?php 
$form_names = array_keys($forms);
$form_name = $form_names[0];
$form = $forms[$form_name];
//echo form_open($form->action, 'name="'.$form_name.'" id="'.$form_name.'" method="'.$form->method.'"');
echo '<form name="'.$form_name.'" id="'.$form_name.'" method="'.$form->method.'">';
?>
    <h2>1. Where do you want to search?</h2>
    <div id="step1">
		<?php print_error('location'); ?>
        <?php print_error('area'); ?>
        <div id="location-input">
            <div id="area-list">
<?php	
            foreach ($areas as $area) {
                echo '<div id="areaid-'.$area->id.'" class="area-item"><div class="area-remove"></div>'.$area->value.'</div>';
            }
?>
            </div>
            <input type="text" placeholder="City, State, or Country..." title="City, State, or Country..." name="location" id="location" style="width:482px;" value="" />
            <input type="hidden" name="area" id="area" value="<?php defaultval($form, 'area'); ?>" />
        </div>
    </div>
    <h2>2. What are you searching for?</h2>
 	<?php print_error('query'); ?>
    <input type="text" name="query" placeholder="i.e. green roadbike" title="i.e. green roadbike" style="margin-bottom: 0px; width:482px" value="<?php defaultval($form, 'query'); ?>" />
    <div id="step2">
      <ul id="tabs">
        <li class="active"><a href="#for-sale"><span>For Sale</span></a></li>
        <li><a href="#jobs"><span>Jobs</span></a></li>
        <li><a href="#housing">Housing</a></li>
        <li><a href="#services"><span>Services</span></a></li>
        <li><a href="#gigs"><span>Gigs</span></a></li>
        <li><a href="#community"><span>Community</span></a></li>
        <li><a href="#personals"><span>Personals</span></a></li>
        <li><a href="#resumes"><span>Resumes</span></a></li>
      </ul>
      <input type="hidden" name="type" id="type" value="<?php defaultval($form, 'type'); ?>" />
      <br class="clearfloats" />
      <div id="for-sale" class="section">
        <div class="fieldset-left">
		<?php add_field($form->field_definitions['for-sale_category']);?>
		</div>
        <div class="fieldset-right">
		<?php add_field($form->field_definitions['for-sale_min']);?>
		<?php add_field($form->field_definitions['for-sale_max']);?>
        </div>
        <div class="clearfloats"></div>
      </div>
      <div id="jobs" class="section">
        <div class="fieldset-left">
		<?php add_field($form->field_definitions['jobs_category']);?>
		</div>
        <div class="fieldset-right">
		<?php add_field($form->field_definitions['jobs_telecommute']);?>
		<?php add_field($form->field_definitions['jobs_contract']);?>
		<?php add_field($form->field_definitions['jobs_internship']);?>
		</div>
        <div class="clearfloats"></div>
        <div class="fieldset-right">
		<?php add_field($form->field_definitions['jobs_parttime']);?>
		<?php add_field($form->field_definitions['jobs_nonprofit']);?>
        </div>
        <div class="clearfloats"></div>
      </div>
      <div id="housing" class="section">
        <div class="fieldset-left">
		<?php add_field($form->field_definitions['housing_category']);?>
		</div>
        <div class="fieldset-right">
		<?php add_field($form->field_definitions['housing_min']);?>
		<?php add_field($form->field_definitions['housing_max']);?>
		<?php add_field($form->field_definitions['housing_rooms']);?>
		</div>
        <div class="clearfloats"></div>
        <div class="fieldset-right">
		<?php add_field($form->field_definitions['housing_cats']);?>
		<?php add_field($form->field_definitions['housing_dogs']);?>
		</div>
        <div class="clearfloats"></div>
      </div>
      <div id="services" class="section">
		<?php add_field($form->field_definitions['services_category']);?>
        <div class="clearfloats"></div>
      </div>
      <div id="gigs" class="section">
        <div class="fieldset-left">
		<?php add_field($form->field_definitions['gigs_category']);?>
		</div>
        <div class="fieldset-right">
		<?php add_field($form->field_definitions['gigs_type']);?>
		</div>
        <div class="clearfloats"></div>
      </div>
      <div id="community" class="section">
		<?php add_field($form->field_definitions['community_category']);?>
        <div class="clearfloats"></div>
      </div>
      <div id="personals" class="section">
        <div class="fieldset-left">
		<?php add_field($form->field_definitions['personals_category']);?>
		</div>
        <div class="fieldset-right">
		<?php add_field($form->field_definitions['personals_min']);?>
		<?php add_field($form->field_definitions['personals_max']);?>
		</div>
        <div class="clearfloats"></div>
      </div>
      <div id="resumes" class="section">
      </div>
      <div class="section">
        <div class="fieldset-left">
        </div>
        <div class="fieldset-right">
        <?php add_field($form->field_definitions['has_image']);?>
        <?php add_field($form->field_definitions['scope']);?>
        </div>
        <div class="clearfloats"></div>
      </div>
    </div>

    <h2>3. How often should we alert you?</h2>
    <div id="step3">
 	<?php print_error('how_often'); if (!in_array($how_often, array('hour', 'day'))) $how_often = 'instant'; ?>
    <div class="howoften"><input type="radio" name="how_often" value="instant"<?php if ($how_often == 'instant') echo ' checked="checked"'; ?> />AS SOON AS ONE IS POSTED</div>
    <div class="howoften"><input type="radio" name="how_often" value="hour"<?php if ($how_often == 'hour') echo ' checked="checked"'; ?> />ONCE HOURLY</div>
    <div class="howoften"><input type="radio" name="how_often" value="day"<?php if ($how_often == 'day') echo ' checked="checked"'; ?> />ONCE DAILY</div>
    <div class="clearfloats"></div>
    </div>
    
    <div id="stepsubmit">
    <input id="submit-button" type="submit" class="medium button" title="Save" value="Save" align="center" />
    <input id="cancel-button" type="submit" class="medium button" title="Cancel" value="Cancel" align="center" name="cancel" />
    </div>

    </form>
<?php if ($form->validate == 'true') echo add_validation($form_name); ?>