<?php
if ( ! function_exists('form_email')) {
	function form_email($data = '', $value = '', $extra = '') {
		$defaults = array('type' => 'email', 'name' => (( ! is_array($data)) ? $data : ''), 'value' => $value);
		return "<input "._parse_form_attributes($data, $defaults).$extra." />";
	}
}

function print_error($field, $return = FALSE) {
	$error = form_error($field, '<div class="form-error">', '</div>');
	if ($return) return $error;
	echo $error;
}

function defaultval($form, $field) {
	if (!isset($form->field_definitions[$field]['default'])) {
		echo '';
	} else {
		echo $form->field_definitions[$field]['default'];
	}
}

function social_icon($type, $url) {
	switch ($type) {
		case 'twitter': $icon = '/images/social_icons/twitter.png'; break;
		case 'facebook': $icon = '/images/social_icons/facebook.png'; break;
		case 'linkedin': $icon = '/images/social_icons/linkedin.png'; break;
		default: $icon = '/images/social_icons/'.$type.'.png'; break;
	}
	return '<a href="'.$url.'"><img src="'.$icon.'" border="0"></a>';
}

/*
		case 'checkbox':
		case 'radio':
		case 'button':
*/
function add_textarea($label, $field, $required = FALSE, $default = '') {
    $value = form_textarea($field, is_null($default) ? '' : set_value($field, $default), $required ? 'data-required' : '');
	echo format_field($label, $field, $value, $required);
}

function add_radio($label, $field, $options = array(), $default = '') {
	//echo 'Label: '.$label.', Field: '.$field;
	$value = '';
	foreach ($options as $option=>$text) {
    	$value .= form_radio($field, $option, $default == $option);
		$value .= '&nbsp;'.$text;
	}
	echo format_field($label, $field, $value);
}

function add_email($label, $field, $required = FALSE, $default = '', $mask = '', $extra_tags = '') {
	$extra = ($required ? 'data-required' : '').(!empty($mask) ? 'class="'.$mask.'-mask"' : '');
	if (!empty($extra_tags)) $extra .= ' '.$extra_tags;
    $value = form_email($field, is_null($default) ? '' : set_value($field, $default), $extra);
	echo format_field($label, $field, $value, $required);
}

function add_input($label, $field, $required = FALSE, $default = '', $mask = '', $extra_tags = '') {
	$extra = ($required ? 'data-required' : '').(!empty($mask) ? 'class="'.$mask.'-mask"' : '');
	if (!empty($extra_tags)) $extra .= ' '.$extra_tags;
    $value = form_input($field, is_null($default) ? '' : set_value($field, $default), $extra);
	echo format_field($label, $field, $value, $required);
}

function add_generic($type, $label, $field, $required = FALSE, $default = '', $mask = '', $extra_tags = '') {
	$extra = ($required ? 'data-required' : '').(!empty($mask) ? 'class="'.$mask.'-mask"' : '');
	if (!empty($extra_tags)) $extra .= ' '.$extra_tags;
    $value = '<input type="'.$type.'" name="'.$field.'" value="'.(is_null($default) ? '' : set_value($field, $default)).(!empty($extra) ? ' '.$extra : '').'" />';
	echo format_field($label, $field, $value, $required);
}

function add_checkbox($label, $field, $checked = FALSE) {
	//echo 'Label: '.$label.', Field: '.$field;
    $value = form_checkbox($field, 'yes', $checked);
	echo format_field($label, $field, $value);
}

function add_password($label, $field, $required = FALSE, $mask = '', $extra_tags = '') {
	$extra = ($required ? 'data-required' : '').(!empty($mask) ? 'class="'.$mask.'-mask"' : '');
	if (!empty($extra_tags)) $extra .= ' '.$extra_tags;
	echo format_field($label, $field, form_password($field, '', $extra), $required);
}

function add_select($label, $field, $options = array(), $required = FALSE, $default = '') {
	$value = form_dropdown($field, $options, $default, $required ? 'data-required' : '');
	echo format_field($label, $field, $value, $required);
}

function add_multiselect($label, $field, $options = array(), $required = FALSE, $default = array()) {
	if (!is_array($default) && empty($default)) $default = array(); // Create an empty array if an empty string is passed
	if (!is_array($default)) $default = array($default); // Otherwise, encapsulate data in an array if it not one already
	$value = form_multiselect($field.'[]', $options, $default, $required ? 'data-required' : '');
	echo format_field($label, $field, $value, $required);
}

function add_submit($label, $value) {
	$output = indent(form_submit($value, $label));
	echo encapsulate_field($output);
}

function add_reset($label, $value) {
	$output = indent(form_reset($value, $label));
	echo encapsulate_field($output);
}

function format_field($label, $field, $value, $required = FALSE) {
	$output = '';
	if (!is_null($label)) $output = indent(make_label($label, $required), FALSE);
	$output .= indent(print_error($field, TRUE));
    $output .= indent($value);
	$output = encapsulate_field($output);
	return $output;
}

function indent($value, $include_nl = TRUE, $tabs = 1) {
	$spaces = '';
	for ($i=0; $i<$tabs; $i++) $spaces .= '    ';
	return $spaces.$value.($include_nl ? "\n" : '');
}

function make_label($value, $required = FALSE) {
    return '<label>'.$value.':'.($required ? '<span>*</span>' : '').'</label>';
}

function encapsulate_field($value) {
    $encapsulate_output = '<div class="formfield">'."\n";
    $encapsulate_output .= $value;
    $encapsulate_output .= '</div>'."\n";
	return $encapsulate_output;
}

function add_field($field) {
	$rules = explode('|', $field['rules']);
	$required = in_array('required', $rules);
	$default = isset($field['default']) && !is_null($field['default']) ? $field['default'] : '';
	$mask = isset($field['mask']) ? $field['mask'] : '';
	switch($field['type']) {
		case 'input': add_input($field['label'], $field['field'], $required, $default, $mask); break;
		case 'password': add_password($field['label'], $field['field'], $required, $mask); break;
		case 'select': add_select($field['label'], $field['field'], $field['options'], $required, $default); break;
		case 'textarea': add_textarea($field['label'], $field['field'], $required, $default); break;
		case 'multiselect': add_multiselect($field['label'], $field['field'], $field['options'], $required, $default); break;
		case 'submit': add_submit($field['label'], $field['field']); break;
		case 'reset': add_reset($field['label'], $field['field']); break;
		case 'hidden': form_hidden($field['field'], $default); break;
		case 'checkbox': add_checkbox($field['label'], $field['field'], $default === TRUE); break;
		case 'radio': add_radio($field['label'], $field['field'], $field['options'], $default); break;
		case 'email': add_email($field['label'], $field['field'], $required, $default, $mask); break;
		default: add_generic($field['type'], $field['label'], $field['field'], $required, $default, $mask); break;
		//case 'button':
	}
}

function add_fields($definition) {
	foreach ($definition as $field) {
		add_field($field);
	}
}

function add_validation($form) {
	$output = '';
	$output .= '<script type="text/javascript">'."\n";
	$output .= indent("$('[id]').validate({");
		$output .= indent("onChange: true,", TRUE, 2);
		$output .= indent("onKeyUp: true,", TRUE, 2);
		$output .= indent("eachValidField: function() {", TRUE, 2);
			$output .= indent("$(this).closest('div').removeClass('error').addClass('success');", TRUE, 3);
		$output .= indent("},", TRUE, 2);
		$output .= indent("eachInvalidField: function() {", TRUE, 2);
			$output .= indent("$(this).closest('div').removeClass('success').addClass('error');", TRUE, 3);
		$output .= indent("}", TRUE, 2);
	$output .= indent("});");
	$output .= '</script>'."\n";	

	return $output;	
}
?>