<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class DefaultController extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -  
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in 
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see http://codeigniter.com/user_guide/general/urls.html
	 */
	private $layout;
	private $controller, $function;
	protected $data, $mail_info;
	
    public function __construct() {
        parent::__construct();
		$this->controller = $this->router->fetch_class();
		$this->function = $this->router->fetch_method();
		$this->data = new stdClass();
		$this->data->forms = array();
		$this->data->title = 'Application';
		$this->data->js_files = array();
		$this->data->css_files = array();
		$this->data->messages = array('success'=>NULL, 'error'=>NULL, 'general'=>NULL);
		$this->mail_info = new stdClass();
		$this->mail_info->from_address = 'application@example.com';
		$this->mail_info->from_name = '';
		$this->mail_info->cc = '';
		$this->mail_info->bcc = '';
		$this->mail_info->layout = 'email_layout';
	}
	
	protected function display($view = NULL) {
		// Allow The view to default to controller/function
		if (is_null($view)) $view = $this->controller.DIRECTORY_SEPARATOR.$this->function;
		if (!isset($this->layout)) $this->set_layout('default');
		$this->data->view = 'pages/'.$view;
		$this->load->view($this->layout, $this->data);
	}
	
	protected function set_title($title) {
		$this->data->title = $title;
	}
	
	protected function set_layout($layout) {
		$this->layout = 'layouts/'.$layout.'_layout';
	}

	protected function get_controller() {
		return $this->controller;
	}
	
	protected function get_function() {
		return $this->function;
	}

	protected function add_form($form, $action = '', $method = 'post') {
		$this->data->forms[$form] = new stdClass();
		$this->data->forms[$form]->field_definitions = array();
		$this->data->forms[$form]->action = empty($action) ? '' : $action;
		$this->data->forms[$form]->method = $method == 'get' ? 'get' : 'post';
		$this->data->forms[$form]->validate = 'false';
	}
	
	protected function add_field($form, $name, $label = NULL, $rules = 'trim', $type = 'input') {
		if (!isset($this->data->forms[$form])) $this->add_form($form);
		if (is_null($label)) $type = 'hidden';
		$this->data->forms[$form]->field_definitions[$name] = array('field' => $name, 'label' => $label, 'rules' => $rules, 'type' => $type);
	}

	protected function add_options($form, $field, $options = array(), $type = 'select') {
		if (!isset($this->data->forms[$form])) $this->add_form($form);
		if (!in_array($this->get_field_type($form, $field), array('select', 'multiselect'))) {
			$this->set_field_type($form, $field, $type);
		}
		$this->data->forms[$form]->field_definitions[$field]['options'] = $options;
	}

	protected function set_field_type($form, $field, $type = 'input') {
		if (!isset($this->data->forms[$form])) $this->add_form($form);
		$this->data->forms[$form]->field_definitions[$field]['type'] = $type;
	}

	protected function set_rules($form, $field, $rules = 'trim') {
		if (!isset($this->data->forms[$form])) $this->add_form($form);
		$this->data->forms[$form]->field_definitions[$field]['rules'] = $rules;
	}

	protected function set_mask($form, $field, $mask = '') {
		if (!isset($this->data->forms[$form])) $this->add_form($form);
		$this->data->forms[$form]->field_definitions[$field]['mask'] = $mask;
	}
	
	protected function set_defaults($form, $data) {
		$field_defs = $this->get_field_definitions($form);
		foreach($field_defs as $field_name=>$field) {
			$value = is_object($data) && isset($data->$field_name) ? $data->$field_name : (is_array($data) && isset($data[$field_name]) ? $data[$field_name] : NULL);
			if (!is_null($value)) {
				if ($field['type'] == 'checkbox') $value = TRUE;
				$this->set_default($form, $field_name, $value);
			}
		}
	}
	
	protected function set_default($form, $field, $default = '') {
		if (!isset($this->data->forms[$form])) $this->add_form($form);
		$this->data->forms[$form]->field_definitions[$field]['default'] = $default;
	}

	protected function get_field_type($form, $field) {
		if (!isset($this->data->forms[$form])) $this->add_form($form);
		if (!isset($this->data->forms[$form]->field_definitions[$field]['type'])) return NULL;
		return $this->data->forms[$form]->field_definitions[$field]['type'];
	}

	protected function add_validate($form) {
		if (!isset($this->data->forms[$form])) $this->add_form($form);
		$this->data->forms[$form]->validate = 'true';
	}

	protected function get_field_definitions($form) {
		if (!isset($this->data->forms[$form])) return NULL;
		return $this->data->forms[$form]->field_definitions;
	}

	protected function error_message($message, $extra = array()) {
		$extra = $this->set_extra_defaults($extra);
		if ($extra['show_now']) {
			$this->set_message('error', $message, 'Error', $extra['ok_label']);
		} else {
			$this->session->set_flashdata('message_ok_label', $extra['ok_label']); // Set flash data message_ok_label
			$this->session->set_flashdata('message_title', 'Error'); // Set flash data general_message_title
			$this->session->set_flashdata('error_message', $message); // Set flash data error_message
		}
	}

	protected function general_message($message, $title = 'Information', $extra = array()) {
		$extra = $this->set_extra_defaults($extra);
		if ($extra['show_now']) {
			$this->set_message('general', $message, $title, $extra['ok_label']);
		} else {
			$this->session->set_flashdata('message_ok_label',  $extra['ok_label']); // Set flash data message_ok_label
			$this->session->set_flashdata('message_title', $title); // Set flash data general_message_title
			$this->session->set_flashdata('general_message', $message); // Set flash data general_message
		}
	}

	protected function success_message($message, $title = 'Success', $extra = array()) {
		$extra = $this->set_extra_defaults($extra);
		if ($extra['show_now']) {
			$this->set_message('success', $message, $title, $extra['ok_label']);
		} else {
			$this->session->set_flashdata('message_ok_label',  $extra['ok_label']); // Set flash data message_ok_label
			$this->session->set_flashdata('message_title', $title); // Set flash data success_message_title
			$this->session->set_flashdata('success_message', $message); // Set flash data success_message
		}
	}

	private function set_extra_defaults($extra = array()) {
		if (!array_key_exists('ok_label', $extra)) $extra['ok_label'] = 'Ok';
		if (!array_key_exists('show_now', $extra)) $extra['show_now'] = FALSE;
		return $extra;	
	}

	private function set_message($type, $text, $title, $button) {
		$this->data->messages[$type] = array('text'=>$text, 'title'=>$title, 'button'=>$button);
	}

	protected function send_notification($recipient_id, $template = NULL, $options = array()) {
		// We can check if template is NULL and if it is, then
		// We can use $this->function for the default
		if (is_null($template)) $template = $this->get_controller().DIRECTORY_SEPARATOR.$this->get_function();
		$user = $this->user_model->get($recipient_id);
		if (!$user) {
			$this->error_message('User does not exist.');
			return FALSE;
		}
		$email_addr = $user->Email;
		
		//return $this->email->print_debugger();
		return $this->send_email($template, $email_addr, $this->mail_info->from_address, $this->mail_info->from_name, $options, $this->mail_info->cc,  $this->mail_info->bcc);
	}
	
	protected function send_email($template, $to, $from = NULL, $from_name = NULL, $options = array(), $cc = '', $bcc = '') {
		$this->load->library('email');
		
		if (is_null($from)) $from = $this->mail_info->from_address;
		if (is_null($from_name)) $from_name = $this->mail_info->from_name;
		
		if (!empty($from_name)) {
			$this->email->from($from, $from_name);
		} else {
			$this->email->from($from);
		}

		$data = (object) array_merge($options, get_object_vars($this->data));
		$data->email_to = $to;

		if ($this->view_exists('email/'.$template)) {
			$email = $this->load->view('email/'.$template, $data, TRUE);
		} else {
			log_message('error', 'Requested Email Template "'.$template.'" not found.');
			$this->error_message('Requested Email Template "'.$template.'" not found.');
			return FALSE;	
		}
		
		
		// A Subject Tag is required
		$match_count = preg_match('|<subject>(.*?)</subject>|s', $email, $matches);
		if ($match_count >= 1) {
			$data->subject = $matches[1];
		} else {
			log_message('error', 'Subject Tag not found in template: "'.$template.'".');
			$this->error_message('Subject Tag not found in Email Template.');
			return FALSE;	
		}

		// A Body Tag is required
		$match_count = preg_match('|<body>(.*?)</body>|s', $email, $matches);
		if ($match_count >= 1) {
			$data->content = $matches[1];
		} else {
			log_message('error', 'Body Tag not found in template: "'.$template.'".');
			$this->error_message('Body Tag not found in Email Template.');
			return FALSE;	
		}
		
		// Optionally Get Text Version
		/*
		$match_count = preg_match('|<text>(.*?)</text>|s', $email, $matches);
		if ($match_count >= 1) {
			$textemail = $matches[1];
		}
		$this->email->set_alt_message($textemail);
		*/
		
		$this->email->to($to);
		if (!empty($cc)) $this->email->cc($cc);
		if (!empty($bcc)) $this->email->bcc($bcc);
		
		$this->email->subject($data->subject);

		if ($this->view_exists('layouts/'.$this->mail_info->layout)) {
			$body = $this->load->view('layouts/'.$this->mail_info->layout, $data, TRUE);
		} else {
			log_message('error', 'Specified layout "'.$template.'" not found in '.$this->get_function().'.');
			$this->error_message('Specified layout "'.$template.'" not found.');
			return FALSE;	
		}

		$this->email->message($body);
		return $this->email->send();
	}
	
	protected function view_exists($view) {
		return file_exists(FCPATH.APPPATH.'views/'.$view.'.php');
	}

	protected function js($file) {
		$this->data->js_files[] = base_url($file);
	}

	protected function css($file) {
		$this->data->css_files[] = base_url($file);
	}
	
	protected function encrypt($text) {
		if (!isset($this->Cipher)) $this->load->library('Cipher');
		$cipher = new Cipher();
		$encrypted = $cipher->encrypt($text);

		return $encrypted;
	}

	protected function decrypt($encrypted) {
		if (!isset($this->Cipher)) $this->load->library('Cipher');
		$cipher = new Cipher();
		$text = $cipher->decrypt($encrypted);

		return $text;
	}
}