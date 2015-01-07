<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once(APPPATH.'controllers/defaultcontroller.php');

class AppController extends DefaultController {
    public function __construct() {
        parent::__construct();
		
		// Set application specific details here
		define('COOKIE_FILE', "/var/tmp/cookies.txt");
		$this->set_title('TripwirePro');
		$this->mail_info->from_address = 'do-not-reply@tripwirepro.com';
		$this->mail_info->from_name = 'TripwirePro';
		$this->data->management_id = $this->input->cookie('management_id', TRUE);
		$this->data->active_tab = '';
	}

	protected function GetSearchTitleForDisplay($search_id, $maxQueryTextLength = -1, $withLocale = FALSE, $area_id = NULL) {
		$this->load->model('search_model');
		$title = $this->search_model->getSearchParam($search_id, 'query');

		if (is_null($title)) $title = '';
		$title = trim($title);
		if ($title != '') {
			if ($maxQueryTextLength != -1 && strlen($title) > $maxQueryTextLength) {
				$title = substr($title, 0, $maxQueryTextLength);
			}
		}
		
		$this->load->model('searchcategory_model');
		$catAbb = $this->search_model->getCategoryAbbrev($search_id);
		if (!is_null($catAbb)) {
			$category = $this->searchcategory_model->get_one_using_params(array('URLCode'=>$catAbb));
			if (is_object($category)) {
				$title .= ', ' . $category->Type . " - " . $category->Category;
			} else {
				log_message('error', 'There was an error retrieving the Category on Search ID: ' . $search_id);
			}
		}
		
		if ($withLocale) {		
			$title .= ', ' . $this->getLocale($search_id, $area_id);
		}
		
		return $title;
	}
	
	protected function getLocale($search_id, $area_id = NULL) {
		$search = $this->search_model->get($search_id);
		$search_areas = $this->search_model->getAreas($search->id);
		$search_groups = $this->search_model->getGroups($search->id);
		if (!is_null($area_id)) {
			$area_titles = array($this->buildDisplayName($area_id));
		} else {
			$area_titles = $this->buildDisplayNames($search_areas);
		}
		
		$areas = array();
		foreach($search_groups as $group) {
			$areas[] = $group;
		}

		foreach ($area_titles as $area_title) {
			$areas[] = $area_title['email'];
		}
		
		return implode(", ", $areas);
	}

	protected function display($view = NULL) {
		if (empty($this->data->active_tab)) $this->data->active_tab = $this->get_function();
		
		return parent::display($view);
	}
	
	protected function setManageCookie($user_id) {
		$user_code = $this->encrypt(intval($user_id));
		$this->input->set_cookie(array('name' => 'management_id', 'value' => $user_code, 'expire' => '31536000'));
		$this->data->management_id = $user_code;
	}

	protected function getUserIDCookie() {
		$user_code = $this->input->cookie('management_id', TRUE);
		if (!$user_code) return NULL;
		$user_id = $this->decrypt($user_code);
		return $user_id;
	}
	
	protected function GetDeactivateLink($search_id) {     	
		$key = $this->encrypt(intval($search_id));
		return sprintf(base_url("deactivate/%s"), $key);
	}

	protected function GetRenewalLink($search_id) {
		$key = $this->encrypt(intval($search_id));
		return sprintf(base_url("renew/%s"), $key);
	}

	protected function GetEditLink($search_id, $type = NULL) {
		$this->load->model('search_model');
		if (is_null($type)) { 
			$search = $this->search_model->get($search_id);
			$type = $search->Type;
		}
		$key = $this->encrypt(intval($search_id));
		return sprintf(base_url("edit/%s/#%s-tab"), $key, $type);
	}

	protected function GetManageNotificationLink($user_id) {
		$key = $this->encrypt(intval($user_id));
		return sprintf(base_url("manage/%s"), $key);
	}		

	protected function GetReferralLink($user_id) {
		$key = $this->encrypt(intval($user_id));
		return sprintf(base_url("referrals/%s"), $key);
	}		

	protected function GetFacebookLink($user_id) {
		$info = $this->getShareInfo($user_id);
		$info['summary'] = "I found this awesome website that emails you as soon as new items are posted on craigslist and I wanted to share it with you.";
		$link = "http://www.facebook.com/sharer.php?s=100"
    		."&p[images][0]=".urlencode($info['image'])
			."&p[url]=".urlencode($info['url'])
			."&p[title]=".urlencode($info['title'])
			."&p[summary]=".urlencode($info['summary']);
		return $link;
	}

	protected function GetTwitterLink($user_id) {
		$info = $this->getShareInfo($user_id);
		$info['summary'] = "I found this awesome website that emails you as soon as new items are posted on craigslist at";
		$link = "http://twitter.com/intent/tweet?"
        	."text=".urlencode($info['summary'])." ".urlencode($info['url']);
		return $link;
	}

	protected function GetEmailShareInfo($user_id) {
		$info = $this->getShareInfo($user_id);
		$info['summary'] = "I found this awesome website that emails you as soon as new items are posted on craigslist and I wanted to share it with you.";
		return $info;
	}
	
	protected function getShareInfo($user_id) {
		$key = $this->encrypt('refer'.intval($user_id));
		$info = array();
		$info['title'] = "TripwirePro - The new NotiFINDER.";
		$info['url'] = sprintf(base_url("refer/%s"), $key);
		$info['image'] = base_url("images/tripwire_pro_icon.png");
		return $info;
	}

	protected function getSearchLinks($search_id, $type = NULL) {
		$links = array();
		$links['deactivate'] = $this->GetDeactivateLink($search_id);
		$links['renew'] = $this->GetRenewalLink($search_id);
		$links['edit'] = $this->GetEditLink($search_id, $type);
		return $links;
	}

	protected function getUserLinks($user_id) {
		$links = array();
		$links['manage'] = $this->GetManageNotificationLink($user_id);
		$links['facebook'] = $this->GetFacebookLink($user_id);
		$links['twitter'] = $this->GetTwitterLink($user_id);
		$links['email_share_info'] = $this->GetEmailShareInfo($user_id);
		return $links;
	}

	protected function getWebLinks($search) {
		$this->load->model('search_model');
		if (is_string($search) || is_int($search)) {
			$search = $this->search_model->get($search);
		} elseif (!is_object($search)) {
			log_message('error', print_r($search, TRUE));
		}
		$links = $this->getSearchLinks($search->id, $search->Type);
		$links = array_merge($links, $this->getUserLinks($search->UserProfileID));
		return $links;
	}
	
	// Removes any empty parameters
	// Since craigslist has changed the URLs, we need to update old ones as they come along
	protected function cleanUrl($url) {
		$urlParts = explode('?', $url, 2);
	
		if (count($urlParts) !== 2)	{
			log_message("error", 'Invalid URL: ' . $url);
			return $url;
		}
		
		list($urlPath, $urlQuery) = $urlParts;
	
		// Strip out scope if it is set to All
		$urlQuery = str_replace('&srchType=A', '', $urlQuery);
	
		// Remove any parameters without values
		$urlQuery = preg_replace('/(&\w*?=(?:(?=&)|$))/', '', $urlQuery);
		
		// Change city.craigslist.org/search/[typeURL]?query&catAbb=[cat] to
		// city.craigslist.org/search/[cat]?query
		$url = $urlPath . '?' . $urlQuery;
		$url = preg_replace('#^search/[a-z]{3}(.*?)(\?.*?)&catAbb=([a-z]{3})(.*?)$#', 'search$1/$3$2$4', $url);
		
		return $url	;
	}
	
	protected function getProxyData($url, $proxy = NULL) {
		// Randomly select a proxy server the pool of servers
		$server_list = $this->config->item('proxy_servers');
		$servers = array_map('trim', explode(',', $server_list));
		shuffle($servers);
		if (is_null($proxy) || !in_array($servers)) {
			$proxy = $servers[array_rand($servers)];
		}

		// Create a CURL call with type POST
		try {
			$post_values = array('url' => $url);
			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, 'http://' . $proxy);
			curl_setopt($ch, CURLOPT_POST, count($post_values));
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_values));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

			$xml_string = curl_exec($ch);

			if ($xml_string === FALSE) {
				log_message("error", 'Curl error while attempting to connect to ' . $proxy . ': ' . curl_error($ch));
				echo "Error retrieving data from proxy server '" . $proxy . "' Curl error: " . curl_error($ch);
				return array();
			} elseif (trim($xml_string) == "") {
				//log_message("error", 'No data was returned. You may be blocked. The URL that was used was: ' . $url);
				echo "No data was returned from proxy server '" . $proxy . "'. The URL that was used was: " . $url . "<br>\n";
				return array();
			}
			curl_close($ch);
			
			$results = array();
			$data_xml = simplexml_load_string($xml_string, NULL, LIBXML_NOCDATA);
			if ($data_xml->error) {
				// If an error is returned, display it, log it, and return array()
				echo "There was an error in getProxyData(".$url.") on ".$proxy.": " . $data_xml->error;
				log_message("error", 'Error in getProxyData('.$url.') on '.$proxy.': ' . $data_xml->error);
				return array();
			}
			
			foreach ($data_xml as $result) {
				$result = $this->xmlToArray($result);
				if (!isset($result['thumbnails'])) $result['thumbnails'] = array();
				$results[] = $result;
			}
			
			return $results;

		} catch (Exception $e) {
			return array();
		}
		
	}
	
	protected function xmlToArray($xml_object) {
        if( !is_object( $xml_object ) && !is_array( $xml_object ) )
        {
            return (string) $xml_object;
        }
        if( is_object( $xml_object ) )
        {
            $xml_object = get_object_vars( $xml_object );
        }
        return array_map(array($this, 'xmlToArray'), $xml_object );
	}
	
	protected function getAreas($search_id) {
		$this->load->model('search_model');
		$areas = array();
		$groups = $this->search_model->getGroups($search_id);
		foreach ($groups as $group) {
			$area = new stdClass;
			$area->id = $group;
			$area->value = $group.' - all';
			$area->label = $group.' - all';
			$area->sublabel = '';
			$areas[] = $area;
		}
		
		$area_ids = $this->search_model->getAreas($search_id);
		foreach ($area_ids as $area_id) {
			$display_info = $this->buildDisplayName($area_id);
			$area = new stdClass;
			$area->id = $area_id;
			$area->value = $display_info['item1'].', '.$display_info['item2'];
			$area->label = $display_info['item1'];
			$area->sublabel = $display_info['item2'];
			$areas[] = $area;
		}
		
		return $areas;
	}

	// We want to take each of the area servers and prepend it to the SearchURLParams if that exists
	protected function getSearchURLs($search_id, $use_title_as_value = TRUE) {
		$this->load->model('search_model');
		$this->load->model('searcharea_model');
		$search = $this->search_model->get($search_id);
		if (!is_null($search->SearchURLParams) && !empty($search->SearchURLParams)) {
			$area_ids = $this->search_model->getAreas($search_id);
			$urls = array();
			foreach ($area_ids as $area_id) {
				$area = $this->searcharea_model->get($area_id);
				$title = $this->GetSearchTitleForDisplay($search_id, 50, TRUE, $area_id);
				if ($use_title_as_value) {
					$urls[$area->url.$search->SearchURLParams] = $title;
				} else {
					$urls[] = $area->url.$search->SearchURLParams;
				}
			}
			
			return $urls;
		} elseif (!is_null($search->SearchURL) && !empty($search->SearchURL)) {
			return array($search->SearchURL);
		} else {
			// Houston, we have a problem
			return NULL;
		}
	}

	protected function getURLQuery($url) {
		// Return the non-host portion of the URL
		$url = str_replace('http://', '', $url);
		$query = array_pop(explode('/', $url, 2));
		return $query;
	}

	public function buildDisplayNames($area_ids) {
		$display_names = array();
		foreach ($area_ids as $area_id) {
			$display_names[$area_id] = $this->buildDisplayName($area_id);
		}
		return $display_names;
	}

	public function buildDisplayName($area_id) {
		// Lookup Record using 
		$this->load->model('searcharea_model');
		$area = $this->searcharea_model->get($area_id);
		// Process parameters from record into function params
		// Return an array with display names for: Item1, Item2, Email, Site
		$display = array();
		
		$display['site'] = "";
		$display['email'] = "";
		$display['item1'] = "";
		$display['item2'] = "";
		$display['mobile'] = "";
		if ($area->city != "") {
			$display['site'] .= $area->city;
			$display['email'] .= $area->city;
			$display['item1'] .= $area->city;
			$display['mobile'] .= $area->city;
			if ($area->isSubArea) {
				$display['site'] .= " - " . $area->subName;
				$display['email'] .= " - " . $area->subName;
				$display['mobile'] .= " - " . $area->subName;
				$display['item1'] .= " - " . $area->subName;
			} else if ($area->hasSubArea) {
				$display['site'] .= " - all";
				$display['mobile'] .= " - all";
				$display['item1'] .= " - all";
			}

			if ($area->state != "") {
				$display['site'] = sprintf("%1$-40s%2$-15s", $display['site'], $area->state);
				$display['item2'] = $area->state;
			} else {
				$display['site'] = sprintf("%1$-40s%2$-15s", $display['site'], $area->country);
				$display['mobile'] = $area->country . " - " . $display['mobile'];
				$display['item2'] = $area->country;
			}

		} else if ($area->state != "") {
			$display['site'] .= $area->state;
			$display['email'] .= $area->state;
			$display['item1'] .= $area->state;

			if ($area->hasSubArea) {
				$display['site'] .= " - all";
				$display['mobile'] .= " - all";
				$display['item1'] .= " - all";
			}

			$display['site'] = sprintf("%1$-40s%2$-15s", $display['site'], $area->state);
			$display['mobile'] = $area->state;
			$display['item2'] = $area->state;

		} else if ($area->country != "") {
			$display['site'] .= $area->country;
			$display['email'] .= $area->country;
			$display['item1'] .= $area->country;
			
			if ($area->hasSubArea) {
				$display['site'] .= " - all";
				$display['mobile'] .= " - all";
				$display['item1'] .= " - all";
			}
			
			$display['site'] = sprintf("%1$-40s%2$-15s", $display['site'], $area->country);
			$display['mobile'] = $area->country;
			$display['item2'] .= $area->country;
		}

		return $display;
	}
}
/* End of file appcontroller.php */
/* Location: ./application/controllers/appcontroller.php */
