<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once(APPPATH.'controllers/appcontroller.php');

class Ajax extends AppController {
	
    public function __construct() {
        parent::__construct();
		$user = $this->session->userdata('user');
		if (empty($user)) return FALSE;
	}

	public function index() {
		return FALSE;
	}

	public function get_location_list() {
		$this->load->model('searcharea_model');
		
		$response = array();
		if (isset($_GET['term'])){
			$q = strtolower($_GET['term']);
			$user_id = $this->getUserIDCookie();
			$results = $this->searcharea_model->find_locations($q, $user_id);
			foreach ($results as $key => $result) {
				$pos = strripos($result['item1'], $q);
				if ($pos !== FALSE) {
					$term = substr($result['item1'], $pos, strlen($q));
					if ($term !== FALSE) {
						$results[$key]['item1'] = str_replace($term, '<b>'.$term.'</b>',  $result['item1']);
					}
				}

				$value = $result['item1'];
				if (isset($result['item2']) && !empty($result['item2'])) {
					$pos = strripos($result['item2'], $q);
					if ($pos !== FALSE) {
						$term = substr($result['item2'], $pos, strlen($q));
						if ($term !== FALSE) $results[$key]['item2'] = str_replace($term, '<b>'.$term.'</b>',  $result['item2']);
					}
					$value .= ', '.$result['item2'];
				}
				$response[] = array('id' => $key, 'value' => $value, 'label' => $result['item1'], 'sublabel' => isset($result['item2']) ? $result['item2'] : '' );
			}
		}

		die(json_encode($response));
	}

	public function recaptcha_validate() {
		$this->load->helper('recaptchalib');
		$this->load->library('session');

		$challenge = $this->input->post('recaptcha_challenge_field', '');
		$response = $this->input->post('recaptcha_response_field', '');
		if (empty($challenge)) {
			log_message('error', 'Challenge field was empty in function recaptcha_validate()');
			log_message('error', 'User Agent for browser is: ' . $_SERVER['HTTP_USER_AGENT']);
		}
		if (empty($response)) {
			log_message('error', 'Response field was empty in function recaptcha_validate()');
			log_message('error', 'User Agent for browser is: ' . $_SERVER['HTTP_USER_AGENT']);
		}
		$resp = recaptcha_check_answer (RECAPTCHA_PRIVATE_KEY, $_SERVER["REMOTE_ADDR"], $challenge, $response);
		if ($resp->is_valid == 1) {
			$this->session->set_userdata('recaptcha_result', 'validated');
			die("success");
		} else {
			$this->session->unset_userdata('recaptcha_result');
			die("fail");
		}
	}

	public function send_suggestion() {
		$this->load->library('session');
		// Check the session variable to see if we are in fact validated
		$recaptcha_result = $this->session->userdata('recaptcha_result');
		// If not, return an error message
		if ($recaptcha_result != 'validated') die('error'); // Output message and exit function

		$this->load->helper('email');
		$email_address = $_POST['email'];
		if (!valid_email($email_address)) die('address_error');

		// otherwise send the mail 
		$mail_success = $this->send_email('suggest', 'dan@luckystaffing.com', $email_address, $email_address, array('suggestion'=>$_POST['suggestion']));
		if (!$mail_success) die('mail_error'); // Output message and exit function

		// and report success
		die('success');	// Output message and exit function
	}
	
	public function location_id_valid() {
		$response = array(
			'valid' => 0                
		);
		$location_id = $this->input->post('location_id', NULL);
		if (!is_null($location_id)) {
			$this->load->model('searcharea_model');
			$location = $this->searcharea_model->get($location_id);
			if ($location) $response['valid'] = 1;
		} else {
			log_message('error', 'Location ID was not passed via POST in AJAX');	
		}
		
		die(json_encode($response));
	}

	// This looks incomplete, not sure what it is here for anymore
	public function get_category_list() {
        $this->load->model('searchcategory_model');
		// Supplied variable should be type
		
		die($response);
	}
	
	private function id_exists($id, $list) {
		if (!is_array($list)) {
			log_message('error', 'Supplied list is not an array in function id_exists()');
		} else {
			foreach($list as $item) {
				if ($item['id'] == $id) return TRUE;
			}
		}

		return FALSE;
	}
	
	public function prune_list() {
		// We need to loop through the list and remove any subAreas of other areas
        $this->load->model('searcharea_model');
		$list = $this->input->post('list', array());
		if (!is_array($list)) {
			$list = array($list);
			log_message('error', 'List was not passed as an array in function prune_list()');
		}
		if (count($list) == 0) {
			log_message('error', 'List was empty');
		}
		$errors = array();
		
		// Remove any duplicates
		foreach ($list as $id1=>$item1) {
			foreach ($list as $id2=>$item2) {
				if ($id1 != $id2 && $item1['id'] == $item2['id']) {
					unset($list[$id1]);
					$errors[] = array('id'=>$id1, 'code'=>1, 'description'=>'Duplicate Entry');
				}
			}
		}
		
		// if usa is in list, we remove everything else that has usa as the country
		if ($this->id_exists('usa', $list)) {
			foreach ($list as $id=>$item) {
				if (is_numeric($item['id'])) {
					$area = $this->searcharea_model->get($item['id']);
					if ($area->country == 'usa') {
						unset($list[$id]);
						$errors[] = array('id'=>$id, 'code'=>2, 'description'=>'Removing Sub Area.');
					}
				} elseif (!is_numeric($item['id']) && $item['id'] != 'usa') {
					// It's a state (USA), so just remove it
					unset($list[$id]);
					$errors[] = array('id'=>$id, 'code'=>3, 'description'=>'Removing Sub State.');
				}
			}
		}
		
		// Next we do the same thing with states
		foreach ($list as $state) {
			// Make sure it's a state group
			if (!is_numeric($state['id']) && $state['id'] != 'usa') {
				foreach ($list as $id=>$item) {
					if (is_numeric($item['id'])) {
						$area = $this->searcharea_model->get($item['id']);
						if ($area->state == $state['id']) unset($list[$id]);
					}
				}
			}
		}
		
		// Next, we check entries like Portland (with no subName and the same city/state/country) and remove all of the Portland SubAreas (same name, but with a subArea)
		foreach($list as $city_id=>$city) {
			if (is_numeric($city['id'])) {
				$area = $this->searcharea_model->get($city['id']);
				if ($area->hasSubArea) {
					foreach ($list as $id=>$item) {
						if (is_numeric($item['id']) && $item['id'] != $area->id) {
							// If item is a sub area of the area, we delete it
							$subareas = $this->searcharea_model->get_all_using_params(array('city'=>$area->city, 'state'=>$area->state, 'country'=>$area->country, 'isSubArea' => 1, 'subName' => $item['sublabel']));
							if (count($subareas) > 0) {
								unset($list[$id]);
								$errors[] = array('id'=>$id, 'code'=>4, 'description'=>'Removing Sub Area.');
							}
						}
					}
				} elseif ($area->isSubArea) {
					foreach ($list as $id=>$item) {
						if (is_numeric($item['id']) && $item['id'] != $area->id) {
							// If item is a sub area of the area, we delete it
							$superarea = $this->searcharea_model->get_all_using_params(array('city'=>$area->city, 'state'=>$area->state, 'country'=>$area->country, 'hasSubArea' => 1, 'isSubArea' => 0));
							if (count($superarea) > 0) unset($list[$city_id]);
							$errors[] = array('id'=>$id, 'code'=>4, 'description'=>'Removing Super Area.');
						}
					}
				}
			}
		}
		
		// Finally, we return the pared down list
		die(json_encode(array('list'=>$list, 'errors'=>$errors)));
	}
}
