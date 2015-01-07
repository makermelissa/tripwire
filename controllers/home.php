<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once(APPPATH.'controllers/appcontroller.php');

class Home extends AppController {

	public function index($email = NULL) {
		$form = 'search_form';
		$this->set_field_defs($form);
		$this->add_validate($form);
		
        $data = $this->input->post();
		$error = FALSE;
		if ($data) {
			$this->data->areas = json_decode($data['area'], FALSE);
			$this->data->how_often = $data['how_often'];
			$this->form_validation->set_rules($this->get_field_definitions($form)); 		
			if ($this->form_validation->run()) {
				$this->load->model('searcharea_model');
				$this->load->model('search_model');
				$this->load->model('user_model');
				$existing_user = $this->user_model->emailExists($data['email']);
				$user_id = $this->user_model->getUserIDByEmail($data['email']);

				if (!is_array($this->data->areas) || count($this->data->areas) == 0) {
					$this->error_message('You must choose a valid location.');
				} elseif (empty($data['type'])) {
					$this->error_message('Please select a category.');
				} else {
					$search_areas = array();
					foreach ($this->data->areas as $area) {
						if (is_numeric($area->id)) {
							$area_formats = $this->searcharea_model->buildDisplayName($area->id);
							$area_record = array('SearchableAreaID' => $area->id,
												 'AreaName' => $area_formats['email']);
						} else {
							$area_record = array('SearchableGroup' => $area->id,
												 'AreaName' => $area->id);
						}
						$search_areas[] = $area_record;
					}
					
					$record = array('UserProfileID' => $user_id,
									'SearchableAreaID' => NULL,
									'SearchURLParams' => $this->getSearchUrl($data),
									'Active' => 1,
									'Email' => 1,
									'Text' => 0,
									'NotifyEvery' => $data['how_often'],
									'RenewalEmailSent' => 0,
									'TotalEmailsSent' => 0,
									'Type' => $data['type'], 
									'AreaName' => NULL,
									'ExpDate' => $this->search_model->getExpDate());
					$user = $this->user_model->get($user_id);
					if ($user->NumSearchesAllowed > 0) {
						$this->load->model('notification_model');
						$search = $this->search_model->add($record);
						$this->search_model->setAreas($search->id, $search_areas);
						$this->user_model->removeAllowedSearch($user_id);
						if ($this->session->userdata('referrer_id') && !$existing_user) {
							$referrer_id = $this->session->userdata('referrer_id');
							$this->user_model->addAllowedSearch($referrer_id, 5);
						}
						$this->notification_model->addNotification('initial', $search->UserProfileID, $search->id);
						$this->success_message('Your search has been added. Thank you for using TripwirePro.', 'Success', array('ok_label'=>'Awesome'));
						redirect($this->GetManageNotificationLink($user_id));
					} else {
						$this->error_message('You have reached your maximum number of concurrent searches. To create new searches, <a style="text-decoration: underline;" href="'.$this->GetManageNotificationLink($user_id).'" target="_blank">remove existing ones</a>.', array('show_now'=>TRUE));
					}
				}
			}
		} else {
			// Set Defaults
			$data = array();
			$data['type'] = 'for-sale';
			$areas = array();
			$data['area'] = str_replace('"', '&quot;', json_encode($areas));
			$this->data->areas = $areas;
			$this->data->how_often = 'instant';
			if (!is_null($email)) $data['email'] = urldecode($email);
		}
		$this->set_defaults($form, $data);
		//print_r($this->get_field_definitions($form));
		$this->data->meta_keywords = array('all craigslist',
										   'all of craigslist',
										   'black ops 2',
										   'craigslist',
										   'craigslist posting',
										   'craigslist search',
										   'craigslist search all',
										   'craigslist tools',
										   'craigslist.org',
										   'notifinder',
										   'notifinder.com',
										   'rv with trailer',
										   'search all craigslist',
										   'search craigslist',
										   'trailer for rv',
										   'craigslist search engine');

		$this->data->active_tab = 'add-new';
		$this->set_title('TripwirePro - Add Notification');
		$this->display();
	}
	
	public function edit($search_code) {
		$this->load->model('user_model');
		$form = 'search_form';
		$this->set_field_defs($form, FALSE);
		$this->add_validate($form);
		$search_id = $this->searchDecode($search_code);
		if ($search_id !== FALSE) {
			$data = $this->input->post();
			
			$this->load->model('search_model');
			$this->load->model('searcharea_model');
			$this->data->areas = $this->getAreas($search_id);
			
			$error = FALSE;
			if ($data) {
				$this->data->areas = json_decode($data['area'], FALSE);
				$search = $this->search_model->get($search_id);
				if (isset($data['cancel'])) redirect($this->GetManageNotificationLink($search->UserProfileID));
				 
				$this->form_validation->set_rules($this->get_field_definitions($form)); 		
				if ($this->form_validation->run()) {
					$search_areas = array();
					foreach ($this->data->areas as $area) {
						if (is_numeric($area->id)) {
							$area_formats = $this->searcharea_model->buildDisplayName($area->id);
							$area_record = array('SearchableAreaID' => $area->id,
												 'AreaName' => $area_formats['email']);
						} else {
							$area_record = array('SearchableGroup' => $area->id,
												 'AreaName' => $area->id);
						}
						$search_areas[] = $area_record;
					}

					$search_url = $this->getSearchUrl($data);
					if (is_null($search_url)) {
						$this->error_message('You must choose at least 1 valid location.');
					} elseif (empty($search_url)) {
						$this->error_message('Please select a category.');
					} else {
						$record = array('SearchURLParams' => $search_url,
										'Active' => 1,	// Reactivate if inactive
										'NotifyEvery' => $data['how_often'],
										'RenewalEmailSent' => 0,
										'Type' => $data['type'],
										'ExpDate' => $this->search_model->getExpDate());
						$search = $this->search_model->update($search_id, $record);
						
						$this->search_model->setAreas($search_id, $search_areas);
						$this->success_message('Your search was successfully updated. Thank you for using TripwirePro.', 'Edit Search', array('ok_label'=>'Awesome'));
						redirect($this->GetManageNotificationLink($search->UserProfileID));
					}
				}
			}

			//$this->data->links = $this->getWebLinks($search_id);
			$search_data = $this->getSearchData($search_id);
			$this->data->how_often = $search_data['how_often'];
			$this->set_defaults($form, $search_data);

			$search = $this->search_model->get($search_id);
			$this->data->user_email = $this->user_model->email($search->UserProfileID);
			$this->setManageCookie($search->UserProfileID);
		}

		$this->set_title('TripwirePro - Edit Notification');
		$this->display();
	}

	public function stats($password) {
		if ($password != 'garriott2000') {
			log_message("error", "Incorrect Password attempt from ".$_SERVER['REMOTE_ADDR'].".");
			die('Wrong Password');
		}
		log_message("debug", "Password successfully entered from ".$_SERVER['REMOTE_ADDR'].".");
		
		$html = '';
		$this->load->model('user_model');
		$this->load->model('search_model');
		$this->load->model('searchcategory_model');

		$categories = $this->searchcategory_model->get_category_types();
		foreach($categories as $key=>$category) {
			$this->data->categories[] = array('category'=>$category,
												'active'=>$this->search_model->active_count(str_replace(' ', '-', $category)),
												'inactive'=>$this->search_model->inactive_count(str_replace(' ', '-', $category)));
		}

		$users = $this->user_model->get_all_using_params(array('id !='=>array(1,15,30,32)));
		if ($users) {
			foreach ($users as $user) {
				$this->data->users[$user->id] = array('email'=>$user->Email, 'searches'=>array());
				$searches = $this->search_model->get_all_using_params(array('UserProfileID'=>$user->id));
				foreach ($searches as $search) {
					$search_data = array('search' => $search, 'data' => $this->getSearchData($search->id));
					$this->data->users[$user->id]['searches'][] = $search_data;
				}
			}
		} else {
			$this->data->users = FALSE;
		}
		
		// For states, we will request the area from search_model as distinct ordered by count descending
		// For each:
		//		Print the Area Name
		//		Preferable list as side by side
		$this->data->states = $this->search_model->state_summary();

		$this->set_title('TripwirePro - Stats');
		$this->display();
	}

	
	public function refer($referrer = NULL) {
		// Referrer should be encoded so it is something like "refer87" with 87 being the user id of the referrer
		$referrer_id = !is_null($referrer) ? $this->decrypt($referrer) : NULL;
		if (substr_compare($referrer_id, 'refer', 0, 5) === 0) {
			$referrer_id = substr($referrer_id, 5);
			$this->session->set_userdata('referrer_id', $referrer_id);
		}

		redirect('');	
	}
	
	public function renew($search_code) {
		$search_id = $this->searchDecode($search_code);
		if ($search_id !== FALSE) {
			$this->load->model('search_model');
			$this->load->model('user_model');
			$search = $this->search_model->get($search_id);
			$this->data->links = $this->getWebLinks($search_id);
			$this->search_model->renewSearch($search_id);
			$this->data->status = 'Your search was successfully renewed.';
			$this->data->user_email = $this->user_model->email($search->UserProfileID);
			$this->setManageCookie($search->UserProfileID);
		}

		$this->set_title('TripwirePro - Renew Notification');
		$this->display();
	}
	
	public function deactivate($search_code) {
		$search_id = $this->searchDecode($search_code);
		$this->data->needs_confirmation = TRUE;
		if ($search_id !== FALSE) {
			$data = $this->input->post();	
			$search = $this->search_model->get($search_id);
			if ($data) {
				if (isset($data['cancel'])) redirect($this->GetManageNotificationLink($search->UserProfileID));
				$this->data->needs_confirmation = FALSE;
			}
			$this->load->model('search_model');
			$this->load->model('user_model');

			$this->data->user_email = $this->user_model->email($search->UserProfileID);
			$this->setManageCookie($search->UserProfileID);
			$this->data->links = $this->getWebLinks($search_id);
			$active = $search->Active;
			if ($active) {
				if ($this->data->needs_confirmation) {
					$this->data->status = 'Are you sure you want to deactivate this search?';
					$this->data->url_key = $this->encrypt(intval($search_id));
					$this->data->search_title = $this->GetSearchTitleForDisplay($search_id, 50, TRUE);
					$this->data->search_url = $search->SearchURL;
				} else {
					$num_removed = $this->search_model->deactivateSearch($search_id);
					$this->user_model->addAllowedSearch($search->UserProfileID);
					$this->load->model('notification_model');
					$this->notification_model->addNotification('deactivated', $search->UserProfileID, $search_id);
					$this->success_message('Your search was successfully deactivated. You have been credited back a search.', 'Success', array('ok_label'=>'Awesome'));
					redirect($this->GetManageNotificationLink($search->UserProfileID));
				}
			} else {
				$this->data->status = 'The requested search has already been deactivated.';
				$this->data->needs_confirmation = FALSE;
			}
		}

		$this->set_title('TripwirePro - Deactivate Notification');
		$this->display();
	}
	
	public function survey() {
		$this->set_title('TripwirePro - Survey Test');
		$this->display();
	}
	
	public function manage($user_code) {	
		$user_id = $this->decrypt($user_code);
		$this->load->model('search_model');
		$this->load->model('user_model');
		$user = $this->user_model->get($user_id);
		if ($user_id > 0) {
			$this->setManageCookie($user_id);
		} else {
			$this->data->management_id = $user_code;
		}
		$this->data->searches_left = $user->NumSearchesAllowed;
		$this->data->user_email = $user->Email;
		$this->data->searches = $this->search_model->getSearchList($user_id);
		foreach ($this->data->searches as &$search) {
			$search->title = $this->GetSearchTitleForDisplay($search->id, 50, TRUE);
			$search->links = $this->getWebLinks($search);
			$search->expiring = $this->search_model->expiring($search->id) ? 1 : 0;
		}
		$this->data->links = $this->getUserLinks($user_id); 
		$this->data->active_tab = 'manage';
		$this->set_title('TripwirePro - Manage Notifications');
		$this->display();
	}

	public function scrape_test($somevariable = '') {
		$url = "http://portland.craigslist.org/mlt/zip/4077914119.html";
		print_r($this->getThumbs($url));
	}
	
	public function summary() {
		$this->load->model('search_model');
		$form = 'summary_form';
		$this->add_validate($form);
		$this->add_field($form, 'summary-email', 'Email', 'trim|required|valid_email', 'email');
        $data = $this->input->post();
		if ($data) {
			// 1. Run Form Validation
			$this->form_validation->set_rules($this->get_field_definitions($form)); 		
			if ($this->form_validation->run()){
				// Check if the email exists in the database
				if ($this->user_model->emailExists($data['summary-email'])) {
					// If so, we look up the user ID
					$user_id = $this->user_model->getUserIDByEmail($data['summary-email']);

					// Then we queue a summary notification for the user
					if ($user_id > 0) {
						$user = $this->user_model->get($user_id);
						$this->data->user = $user;
						// Get a list of searches along with link urls
						$this->data->searches = $this->search_model->get_all_using_params(array('UserProfileID'=>$user_id));
						$this->data->links = $this->getUserLinks($user_id);
						foreach ($this->data->searches as &$search) {
							$search->title = $this->GetSearchTitleForDisplay($search->id, 50, TRUE);
							$search->links = $this->getWebLinks($search->id);
							$search->expiring = $this->search_model->expiring($search->id) ? 1 : 0;
						}
						$this->send_email('summary', $user->Email);
						
						// Display a success message
						$this->success_message('Email sent! Please check your inbox.', 'Success', array('ok_label'=>'Awesome'));
					} else {
						$this->error_message('There were no searches found for this email address.');
					}
				} else {
					$this->error_message('There were no searches found for this email address.');
				}
			}
		}
		$this->data->active_tab = 'my-searches';
		$this->display();
	}

	private function searchDecode($search_code) {
		$search_id = $this->decrypt($search_code);
		if ($search_id != intval($search_id)) {
			$this->data->status = 'An invalid URL code supplied. Please check your URL and try again.';
			return FALSE;
		} else {
			$this->load->model('search_model');
			$search = $this->search_model->get($search_id);
			if (!$search) {
				$this->data->status = 'The requested search was not found.';
				return FALSE;
			} else {
				return $search_id;
			}
		}
		return FALSE;
	}

	protected function getSearchUrl($data, $area = NULL) {
		//if (empty($data['area']) && is_null($area)) return NULL;
		$jobs_attributes = array();
		if (isset($data['jobs_telecommute']) && $data['jobs_telecommute'] == 'yes') $jobs_attributes['addOne'] = 'telecommuting';
		if (isset($data['jobs_contract']) && $data['jobs_contract'] == 'yes') $jobs_attributes['addTwo'] = 'contract';
		if (isset($data['jobs_internship']) && $data['jobs_internship'] == 'yes') $jobs_attributes['addThree'] = 'internship';
		if (isset($data['jobs_parttime']) && $data['jobs_parttime'] == 'yes') $jobs_attributes['addFour'] = 'part-time';
		if (isset($data['jobs_nonprofit']) && $data['jobs_nonprofit'] == 'yes') $jobs_attributes['addFive'] = 'non-profit';
								 
		$housing_attributes = array();
		if (isset($data['housing_cats']) && $data['housing_cats'] == 'yes') $housing_attributes['addTwo'] = 'purrr';
		if (isset($data['housing_dogs']) && $data['housing_dogs'] == 'yes') $housing_attributes['addThree'] = 'wooof';

		$gigs_attributes = array();
		if ($data['gigs_type'] == 'forpay') $gigs_attributes['addThree'] = 'forpay';
		if ($data['gigs_type'] == 'nopay') $gigs_attributes['addThree'] = 'nopay';

		if (is_null($area)) $area = $data['area'];
		switch($data['type']) {
			case 'for-sale': $url = $this->getForSaleUrl($data['query'], $data['for-sale_category'], $area, $data['for-sale_min'], $data['for-sale_max']); break;
			case 'jobs': $url = $this->getJobsUrl($data['query'], $data['jobs_category'], $area, $jobs_attributes); break;
			case 'housing': $url = $this->getHousingUrl($data['query'], $data['housing_category'], $area, $data['housing_rooms'], $data['housing_min'], $data['housing_max'], $housing_attributes); break;
			case 'services': $url = $this->getServicesUrl($data['query'], $data['services_category'], $area); break;
			case 'gigs': $url = $this->getGigsUrl($data['query'], $data['gigs_category'], $area, $gigs_attributes); break;
			case 'community': $url = $this->getCommunityUrl($data['query'], $data['community_category'], $area); break;
			case 'personals': $url = $this->getPersonalsUrl($data['query'], $data['personals_category'], $area, $data['personals_min'], $data['personals_max']); break;
			case 'resumes': $url = $this->getResumeUrl($data['query'], $area); break;
			default: return '';
		}
		
		if (isset($data['scope']) && $data['scope'] != 'A') $url .= '&srchType='.$data['scope'];
		if (isset($data['has_image']) && $data['has_image'] == 'yes') $url .= '&hasPic=1';
		
		return $url;
	}

	protected function getForSaleUrl($query, $categoryID, $locationID, $min, $max) {
		$this->load->model('searcharea_model');
		$this->load->model('searchcategory_model');
		$catUrl = $this->searchcategory_model->get($categoryID)->URLCode;
		$loc = $this->searcharea_model->get($locationID);

		$url = "search/" . $catUrl . 
			"?query=" . urlencode(trim($query)) . 
			$this->param('minAsk', $min) .
			$this->param('maxAsk', $max);

		return $url;
	}

	protected function getJobsUrl($query, $categoryID, $locationID, $attributes) {
		$this->load->model('searcharea_model');
		$this->load->model('searchcategory_model');
		$catUrl = $this->searchcategory_model->get($categoryID)->URLCode;
		$loc = $this->searcharea_model->get($locationID);

		$url = "search/" . $catUrl . 
			"?query=" . urlencode(trim($query)) . 
			$this->buildAttributes($attributes);

		return $url;
	}

	protected function getHousingUrl($query, $categoryID, $locationID, $rooms, $min, $max, $attributes) {
		$this->load->model('searcharea_model');
		$this->load->model('searchcategory_model');
		$catUrl = $this->searchcategory_model->get($categoryID)->URLCode;
		$loc = $this->searcharea_model->get($locationID);

		$url = "search/" . $catUrl . 
			"?query=" . urlencode(trim($query)) . 
			$this->param('minAsk', $min) .
			$this->param('maxAsk', $max) .
			$this->param('bedrooms', $rooms) .
			$this->buildAttributes($attributes);

		return $url;
	}

	protected function getServicesUrl($query, $categoryID, $locationID) {
		$this->load->model('searcharea_model');
		$this->load->model('searchcategory_model');
		$catUrl = $this->searchcategory_model->get($categoryID)->URLCode;
		$loc = $this->searcharea_model->get($locationID);

		$url = "search/" . $catUrl . 
			"?query=" . urlencode(trim($query));

		return $url;
	}

	protected function getGigsUrl($query, $categoryID, $locationID, $attributes) {
		$this->load->model('searcharea_model');
		$this->load->model('searchcategory_model');
		$catUrl = $this->searchcategory_model->get($categoryID)->URLCode;
		$loc = $this->searcharea_model->get($locationID);

		$url = "search/" . $catUrl . 
			"?query=" . urlencode(trim($query)) . 
			$this->buildAttributes($attributes);

		return $url;
	}

	protected function getCommunityUrl($query, $categoryID, $locationID) {
		$this->load->model('searcharea_model');
		$this->load->model('searchcategory_model');
		$catUrl = $this->searchcategory_model->get($categoryID)->URLCode;
		$loc = $this->searcharea_model->get($locationID);

		$url = "search/" . $catUrl . 
			"?query=" . urlencode(trim($query));
		return $url;
	}

	protected function getPersonalsUrl($query, $categoryID, $locationID, $min, $max) {
		$this->load->model('searcharea_model');
		$this->load->model('searchcategory_model');
		$catUrl = $this->searchcategory_model->get($categoryID)->URLCode;
		$loc = $this->searcharea_model->get($locationID);

		$url = "search/" . $catUrl . 
			"?query=" . urlencode(trim($query)) . 
			$this->param('minAsk', $min) .
			$this->param('maxAsk', $max);

		return $url;
	}

	protected function getEventsUrl($query, $categoryID, $locationID) {
		$this->load->model('searcharea_model');
		$this->load->model('searchcategory_model');
		$catUrl = $this->searchcategory_model->get($categoryID)->URLCode;
		$loc = $this->searcharea_model->get($locationID);

		$url = "search/" . $catUrl . 
			"?query=" . urlencode(trim($query));

		return $url;
	}

	protected function getResumeUrl($query, $locationID) {
		$this->load->model('searcharea_model');
		$this->load->model('searchcategory_model');
		$catUrl = "res";
		$loc = $this->searcharea_model->get($locationID);

		$url = "search/" . $catUrl . 
			"?query=" . urlencode(trim($query));

		return $url;
	}

	protected function buildBeginningURL($area, $typeCode) {
		$rtn = $area->url . "search/" . $typeCode;

		if ($area->isSubArea)
			$rtn .= "/" . $area->subNameCode;
		return $rtn;
	}
	
	private function param($name, $value) {
		return is_null($value) || $value === '' ? '' : "&".$name."=".$value;
	}
	
	private function buildAttributes($attributes, $add_leading_amp = TRUE) {
		$attr_array = array();
		foreach ($attributes as $name=>$value) {
			$attr_array[] = $name.'='.$value;	
		}
		
		$result = implode('&', $attr_array);
		if ($add_leading_amp) $result = '&'.$result;
		return $result;
	}
	
	public function valid_location($area_data) {
		$data = json_decode(str_replace('&quot;', '"', $area_data), FALSE);
		if (empty($data) || !is_array($data) || count($data) == 0) {
			$this->form_validation->set_message('valid_location', 'The location you entered was invalid.');
			return FALSE;
		}			
		return TRUE;					 
	}

	public function valid_limit($area_data) {
		$data = json_decode(str_replace('&quot;', '"', $area_data), FALSE);
		if (is_array($data) && count($data) > 20) {
			$this->form_validation->set_message('valid_limit', 'You entered more than 20 cities.');
			return FALSE;
		}			
		return TRUE;					 
	}

	private function set_field_defs($form, $include_email = TRUE) {
		$this->load->model('searchcategory_model');
		$this->add_field($form, 'location', 'Location', 'trim');
		$this->add_field($form, 'query', 'Search Description', 'trim|required');
		$this->add_field($form, 'area', NULL, 'callback_valid_location|callback_valid_limit');
		$this->add_field($form, 'type', 'Search Type');

		$this->add_field($form, 'for-sale_category', NULL);	// Dropdown
		$this->add_options($form, 'for-sale_category', $this->searchcategory_model->category_list('for sale'));
		$this->add_field($form, 'for-sale_min', 'Min Price');		// Textbox
		$this->add_field($form, 'for-sale_max', 'Max Price');		// Textbox
		if ($include_email) $this->add_field($form, 'email', 'Email Address', 'trim|required|valid_email', 'email');

		$this->add_field($form, 'jobs_category', NULL);			// Dropdown
		$this->add_options($form, 'jobs_category', $this->searchcategory_model->category_list('jobs'));
		$this->add_field($form, 'jobs_telecommute', 'Telecommute', '', 'checkbox');		// Checkbox
		$this->add_field($form, 'jobs_contract', 'Contract', '', 'checkbox');			// Checkbox
		$this->add_field($form, 'jobs_internship', 'Internship', '', 'checkbox');		// Checkbox
		$this->add_field($form, 'jobs_parttime', 'Part Time', '', 'checkbox');			// Checkbox
		$this->add_field($form, 'jobs_nonprofit', 'Non Profit', '', 'checkbox');		// Checkbox

		$this->add_field($form, 'housing_category', NULL);	// Dropdown
		$this->add_options($form, 'housing_category', $this->searchcategory_model->category_list('housing'));
		$this->add_field($form, 'housing_min', 'Min Price');		// Textbox
		$this->add_field($form, 'housing_max', 'Max Price');		// Textbox
		$this->add_field($form, 'housing_rooms', 'Rooms');			// Checkbox
		$rooms = array_merge(array('0+'), range(1, 8));
		$this->add_options($form, 'housing_rooms', array_combine($rooms, $rooms));
		$this->add_field($form, 'housing_cats', 'Cats', '', 'checkbox');	// Checkbox
		$this->add_field($form, 'housing_dogs', 'Dogs', '', 'checkbox');	// Checkbox

		$this->add_field($form, 'services_category', NULL);	// Dropdown
		$this->add_options($form, 'services_category', $this->searchcategory_model->category_list('services'));
		
		$this->add_field($form, 'gigs_category', NULL);		// Dropdown
		$this->add_options($form, 'gigs_category', $this->searchcategory_model->category_list('gigs'));
		$this->add_field($form, 'gigs_type', NULL);		// Radio
		$this->add_options($form, 'gigs_type', array('forpay' => 'Paid', 'nopay' => 'Unpaid', 'all' => 'All Gigs'), 'radio');
		$this->set_default($form, 'gigs_type', 'all');

		$this->add_field($form, 'community_category', NULL);	// Dropdown
		$this->add_options($form, 'community_category', $this->searchcategory_model->category_list('community'));
		
		$this->add_field($form, 'personals_category', NULL);	// Dropdown
		$this->add_options($form, 'personals_category', $this->searchcategory_model->category_list('personals'));
		$this->add_field($form, 'personals_min', 'Min Age');	// Textbox
		$this->add_field($form, 'personals_max', 'Max Age');	// Textbox

		$this->add_field($form, 'scope', 'Search');		// Radio
		$this->add_options($form, 'scope', array('T' => 'Title', 'A' => 'Entire Post'), 'radio');
		$this->set_default($form, 'scope', 'A');

		$this->add_field($form, 'has_image', 'Has Image', '', 'checkbox');	// Checkbox		
	}
	
	private function getItem($collection, $item) {
		if (is_array($collection) && isset($collection[$item])) return $collection[$item];
		if (is_object($collection) && isset($collection->$item)) return $collection->$item;
		return '';
	}

	private function getSearchData($search_id) {
		$this->load->model('search_model');
		$this->load->model('searcharea_model');
		$this->load->model('searchcategory_model');
		
		$search = $this->search_model->get($search_id);
		if (!is_null($search->SearchURLParams) && !empty($search->SearchURLParams)) {
			$url = parse_url('http://craigslist.org/'.$search->SearchURLParams);  // Temporary
		} elseif (!is_null($search->SearchURL) && !empty($search->SearchURL)) {
			$url = parse_url($search->SearchURL);
		} else {
			// Houston, we have a problem
			return NULL;
		}
		if (!$url['query']) {
			echo 'Param Null: '.(is_null($search->SearchURLParams) ? 'yes' : 'no')."\n";
			echo 'Param Empty: '.(empty($search->SearchURLParams) ? 'yes' : 'no')."\n";
			print_r($search);
			print_r($url);
		}
		
		//print_r($url_parts);
		$parameters = array();
		parse_str($url['query'], $parameters);
		$data = array();
		$data['area'] = str_replace('"', '&quot;', json_encode($this->getAreas($search_id)));
		$data['location'] = $this->getLocale($search_id);
		$data['query'] = str_replace('"', '&quot;', urldecode($this->getItem($parameters, 'query')));
		$data['type'] = $search->Type;
		$data['how_often'] = $search->NotifyEvery;
		
		$data['scope'] = $this->getItem($parameters, 'srchType');
		if ($this->getItem($parameters, 'hasPic') == '1') $data['has_image'] = 'yes';
		$cat_code = $this->searchcategory_model->getCategoryIdByCode($this->getItem($parameters, 'catAbb'));
		
		switch($search->Type) {
			case 'for-sale':
				$data['for-sale_category'] = $cat_code;
				$data['for-sale_min'] = $this->search_model->getSearchParam($search_id, 'minAsk'); 
				$data['for-sale_max'] = $this->search_model->getSearchParam($search_id, 'maxAsk'); 
				break;
			case 'jobs': 
				$data['jobs_category'] = $cat_code;
				if ($this->getItem($parameters, 'addOne') == 'telecommuting') $data['jobs_telecommute'] = 'yes';
				if ($this->getItem($parameters, 'addTwo') == 'contract') $data['jobs_contract'] = 'yes';
				if ($this->getItem($parameters, 'addThree') == 'internship') $data['jobs_internship'] = 'yes';
				if ($this->getItem($parameters, 'addFour') == 'part-time') $data['jobs_parttime'] =  'yes';
				if ($this->getItem($parameters, 'addFive') == 'non-profit') $data['jobs_nonprofit'] =  'yes';
				break;
			case 'housing':
				$data['housing_category'] = $cat_code; 
				$data['housing_rooms'] = $this->search_model->getSearchParam($search_id, 'bedrooms');
				$data['housing_min'] = $this->search_model->getSearchParam($search_id, 'minAsk'); 
				$data['housing_max'] = $this->search_model->getSearchParam($search_id, 'maxAsk'); 
				if ($this->getItem($parameters, 'addTwo') == 'purrr') $data['housing_cats'] =  'yes';
				if ($this->getItem($parameters, 'addThree') == 'wooof') $data['housing_dogs'] = 'yes';
				break;
			case 'services':
				$data['services_category'] = $cat_code;
				break;
			case 'gigs':
				$gig_type = $this->getItem($parameters, 'addThree');
				$data['gigs_type'] = $gig_type == 'forpay' ? 'forpay' : $gig_type == 'nopay' ? 'nopay' : 'all';
				$data['gigs_category'] = $cat_code;
				$gigtype = $this->getItem($parameters, 'addThree');
				$data['gigs_type'] = in_array($gigtype, array('forpay', 'nopay')) ? $gigtype : 'all';
				break;
			case 'community':
				$data['community_category'] = $cat_code;
				break;
			case 'personals':
				$data['personals_category'] = $cat_code; 
				$data['personals_min'] = $this->search_model->getSearchParam($search_id, 'minAsk'); 
				$data['personals_max'] = $this->search_model->getSearchParam($search_id, 'maxAsk'); 
				break;
		}
		return $data;
	}

	protected function getParamFromURL($url, $param) {
		$url_parts = parse_url($url);
		$parameters = array();
		parse_str($url_parts['query'], $parameters);
		
		if (array_key_exists($param, $parameters)) {
			return $parameters[$param];
		}
		
		return '';
	}
	
	private function getMail($url) {
		$this->load->library('Simple_html_dom');
		$html = new Simple_html_dom;
		
		# load the URL 
		$html->load_file($url);
		
		# get an element representing the second paragraph
		$elements = $html->find("a");
		$search_str = 'mailto:';
		foreach ($elements as $e) {
			$href = $e->href;
			if (substr_compare($search_str, $href, 0, strlen($search_str), TRUE) === 0) {
				break;	
			}
		}
  		
		$mail_link = substr($href, strlen($search_str));
		$mail['email'] = urldecode(strstr($mail_link, '?', TRUE));
		$mail['params'] = array();
		$params = explode('&amp;', substr(strstr($mail_link, '?'), 1));
		foreach ($params as &$param) {
			$parts = explode('=', urldecode($param), 2);
			$mail['params'][$parts[0]] = trim($parts[1]);
		}
		
		return $mail;
	}
}
/* End of file home.php */
/* Location: ./application/controllers/home.php */