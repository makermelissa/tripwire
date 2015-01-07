<?php
defined( 'BASEPATH' ) or die( 'No direct script access allowed' );
require_once(APPPATH.'models/default_model.php');

class Search_model extends Default_Model {
	protected $tablename = 'Search';

	public function __construct() {
		parent::__construct();
	}
	
	public function UpdateNotifyEvery($search_id, $notifyevery) {
		$this->update($search_id, array('NotifyEvery', $notifyevery));
	}
	
	public function delete($id) {
		$result = parent::delete($id);
		$this->removeAreas($id);
        return $result;
    }

	public function updateLastHitDate($search_id, $date, $area_id) {
		$data = array('LastHitDate' => $date);
		//if (is_null($area_id)) {
		//	$this->update($search_id, $data);
		//} else {
			$this->db->where('SearchID', $search_id);
			$this->db->where('SearchableAreaID', $area_id);
			$this->db->update('SearchAreas', $data);
		//}
	}
	
	public function updateLastQueryDate($search_id, $area_id) {
		$data = array('LastQueryDate' => $this->timestamp(time()));
		//if (is_null($area_id)) {
		//	$this->update($search_id, $data);
		//} else {
			$this->db->where('SearchID', $search_id);
			$this->db->where('SearchableAreaID', $area_id);
			$this->db->update('SearchAreas', $data);
		//}
	}
	
	public function getExpDate($date = NULL) {
		if (is_null($date)) {
			$phpdate = time();
		} else {
			$phpdate = strtotime($date);
		}
		$expdate = $phpdate + $this->daysToSeconds(30); // 30 Days
		
		return $this->timestamp($expdate);
	}
	
	public function getNextSendDate($search_id) {
		$search = $this->get($search_id);
		return $this->nextSendDate($search->NotifyEvery, $search->NextSendDate);
	}
	
	public function nextSendDate($how_often, $last_send = NULL) {
		$php_last_send = is_null($last_send) ? 0 : strtotime($last_send);

		$interval_seconds = $this->hoursToSeconds($this->getInterval($how_often));
		$php_next_send = $php_last_send + $interval_seconds;
		
		return $this->timestamp($php_next_send);
	}

	public function updateNextSendDate($search_id) {
		$search = $this->get($search_id);
		$nextSendDate = $this->timestamp($this->hoursToSeconds($search->NotifyEvery) + time());
		$this->update($search->id, array('NextSendDate'=>$nextSendDate));
	}
	
	public function getAreas($search_id) {
		$areas = array();
		$data = $this->get_all_using_params(array('SearchID'=>$search_id, 'IsGroup'=>0), NULL, 'SearchAreas');
		foreach ($data as $row) {
			$areas[] = $row->SearchableAreaID;
		}
		return $areas;
	}
	
	public function getGroups($search_id) {
		$groups = array();
		$data = $this->get_all_using_params(array('SearchID'=>$search_id), NULL, 'SearchGroups');
		foreach ($data as $row) {
			$groups[] = $row->GroupArea;
		}
		return $groups;
	}

	public function setAreas($search_id, $areas) {
		$this->delete_using_params(array('SearchID'=>$search_id), 'SearchAreas'); // Delete all current areas for provided search
		$this->delete_using_params(array('SearchID'=>$search_id), 'SearchGroups'); // Delete all current areas for provided search
		
		// Loop through areas and add each as a record in the SearchAreas table
		foreach ($areas as $area) {
			if (isset($area['SearchableAreaID'])) {
				$area['SearchID'] = $search_id;
				$this->add($area, 'SearchAreas'); //add new entry for this area
			} elseif (isset($area['SearchableGroup'])) {
				$this->addGroup($search_id, $area);
			}
		}
	}
	
	public function addGroup($search_id, $group) {
		// We need to determine if this is a state or country
		
		$areas = $this->get_all_using_params(array('isSubArea'=>0, 'country'=>$group['SearchableGroup']), NULL, 'SearchableArea');
		if (count($areas) == 0) {
			$areas = $this->get_all_using_params(array('isSubArea'=>0, 'state'=>$group['SearchableGroup']), NULL, 'SearchableArea');
		}

		// We will get the members of the group (the areas that are not subAreas) and add each area to the specified Search
		foreach($areas as $area) {
			if ($area->city != "") {
				$area_name = $area->city;
			} else if ($area->state != "") {
				$area_name = $area->state;
			} else if ($area->country != "") {
				$area_name = $area->country;
			}
			if ($area->hasSubArea) $area_name .= ' - all';
			$area_record = array('SearchID'=>$search_id, 'SearchableAreaID'=>$area->id, 'AreaName'=>$area_name , 'IsGroup'=>1);
			$this->add($area_record, 'SearchAreas'); //add new entry for this area
		}
		
		// Then we'll add the group to the SearchGroups table
		$this->add(array('SearchID'=>$search_id, 'GroupArea'=>$group['SearchableGroup']), 'SearchGroups');
	}

	public function removeAreas($search_id) {
		$main_table = $this->get_tablename();	// Temporarily store the main table name to avoid hardcoding
		$this->set_tablename('SearchAreas');
		$this->delete_using_params(array('SearchID'=>$search_id)); // Delete all current areas for provided search
		$this->set_tablename($main_table);	// Revert back to main table for model
	}

	public function getInterval($how_often) {
		switch($how_often) {
			case "hour": return 1;
			case "day": return 24;
			case "week": return 168;
			default: return 0;
		}
	}
	
	public function deactivateExpiredSearches()	{
		// We need to retrieve all of the users and the count for each of expired searches
		// and credit them back the available searches
		
		// SELECT DISTINCT `UserProfileID`, COUNT(`UserProfileID`) AS ExpiredSearches FROM `Search` WHERE`Active`='1' AND `ExpDate` <= NOW()
		// For each row, update user NumSearchesAllowed with NumSearchesAllowed + ExpiredSearches
		$this->db->select('UserProfileID, COUNT(UserProfileID) AS ExpiredSearches')->distinct()->from('Search')->where('Active', 1)->where('ExpDate <= NOW()');
		$query = $this->db->get();
		$user_expired_searches = $query->result();
		foreach($user_expired_searches as $result) {
			// Update user
			$this->db->where('id', $result->UserProfileID);
			$this->db->update('UserProfile', 'NumSearchesAllowed = NumSearchesAllowed + '.$result->ExpiredSearches);
		}
		
		// Set all Expired Searches to Inactive
		// Update `Search` set `Active`='0' WHERE `ExpDate` <= NOW() AND `Active`='1'
		$this->db->where('Active', 1);
		$this->db->where('ExpDate <= NOW()');
		$this->db->update($this->get_tablename(), array('Active' => 0)); 
	}
	
	public function getSearchParam($search_id, $param_name) {
		$search = $this->get($search_id);
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
		$url['query'] = urldecode($url['query']);
		$query = array();
		parse_str($url['query'], $query);
		
		if (array_key_exists($param_name, $query)) {
			return $query[$param_name];
		}
		
		return NULL;
	}
	
	public function renewalSent($search_id)	{
		$this->update($search_id, array('RenewalEmailSent' => 1)); 
	}

	public function expiring($search_id) {
		$next_week = $this->timestamp(time() + $this->daysToSeconds(7));
		$search = $this->get($search_id);
		if ($search->Active == 0) return TRUE;
		if (strtotime($search->ExpDate) <= strtotime($next_week)) return TRUE;

		return FALSE;
	}
	
	public function getCategoryAbbrev($search_id) {
		$search = $this->get($search_id);
		if (is_null($search->SearchURLParams)) {
			return NULL;
		}
		
		preg_match('/\/(\w{3})\?query/', $search->SearchURLParams, $matches);
		if (count($matches) < 2) {
			//var_export($search);
			return NULL;
		} else {
			return $matches[1];
		}
	}

	public function renewalFailed($search_id) {
		$this->update($search_id, array('RenewalEmailSent' => 0)); 
	}
	
	public function incrementMailsSent($search_id) {
		$this->db->where($this->id_field, $search_id);
		$this->db->update($this->get_tablename(), 'TotalEmailsSent = TotalEmailsSent + 1'); 
	}

	public function deactivateSearch($search_id) {
		$this->update($search_id, array('Active' => 0));
		return $this->db->affected_rows(); 
	}

	public function renewSearch($search_id) {
		$this->update($search_id, array('Active' => 1, 
										'ExpDate'=>$this->getExpDate(),
										'RenewalEmailSent' => 0));
		return $this->db->affected_rows(); 
	}
	
	public function	getExpiringSearches() {
		//ExpDate <= time() + [7 days] AND RenewalEmailSent = 0 AND Active = 1
		$next_week = $this->timestamp(time() + $this->daysToSeconds(7));
		return $this->get_all_using_params(array('ExpDate <='=>$next_week, 'Active'=>1, 'RenewalEmailSent'=>0));
	}

	public function	getExpiredSearches() {
		return $this->get_all_using_params(array('ExpDate <='=>$this->timestamp(time()), 'RenewalEmailSent'=>0));
	}
	
	public function inactive_count($type) {
		$result = $this->get_one_using_params(array('distinct', 'Active'=>0, 'Type'=>$type), array('COUNT(`UserProfileID`) AS InactiveSearches'));
		return $result ? $result->InactiveSearches : 0;
	}

	public function active_count($type) {
		$result = $this->get_one_using_params(array('distinct', 'Active'=>1, 'Type'=>$type), array('COUNT(`UserProfileID`) AS ActiveSearches'));
		return $result ? $result->ActiveSearches : 0;
	}
	
	public function state_summary() {
		//SELECT `AreaName`, COUNT(*) AS `AreaCount` FROM `Search` WHERE `Active`=1 GROUP BY `AreaName` ORDER BY `AreaCount` DESC



		/*SELECT `SearchAreas`.`AreaName`, COUNT(`SearchAreas`.`AreaName`) AS `AreaCount` 
		FROM `Search` 
		INNER JOIN `SearchAreas` ON `Search`.`id` = `SearchAreas`.`SearchID`
		WHERE `Search`.`Active`=1 GROUP BY `SearchAreas`.`AreaName` ORDER BY `AreaCount` DESC*/

		$this->db->select('SearchAreas.AreaName, COUNT(SearchAreas.AreaName) AS AreaCount')->from('Search')->join('SearchAreas', 'Search.id = SearchAreas.SearchID')->where('Search.Active', 1)->group_by('SearchAreas.AreaName')->order_by('AreaCount', 'desc');
		$query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $this->process_results($query->result());
        } else {
            return array();
        } 
		
		//return $this->get_all_using_params(array('Search.Active'=>1, 'group_by'=>array('SearchAreas.AreaName'), 'order_by'=>array('AreaCount'=>'desc')), array('AreaName', 'COUNT(*) AS AreaCount'), 'SearchAreas');
	}
	
	public function getSearch($search_id) {
		$this->db->select('*, SearchAreas.SearchableAreaID AS AreaID')->from('Search')->join('SearchAreas', 'Search.id = SearchAreas.SearchID', 'left')->where('Search.id', $search_id)->order_by('SearchAreas.LastQueryDate', 'asc');
		$query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $this->process_results($query->result());
        } else {
            return array();
        } 
	}
	
	public function getSearchList($user_id) {
/*		$this->db->select('Search.*, SearchableArea.url')->
			from('Search')->
			join('SearchAreas', 'Search.id = SearchAreas.SearchID')->
			join('SearchableArea', 'SearchAreas.SearchableAreaID = SearchableArea.id')->
			where('Search.UserProfileID', $user_id)->
			order_by('Search.Active', 'DESC');*/
		// Grab all relevant searches
		$this->db->select('*')->
			from('Search')->
			where('UserProfileID', $user_id)->
			order_by('Active', 'DESC');
			
		$query = $this->db->get();
		//echo $this->db->last_query();
		
		if ($query->num_rows() > 0) {
            return $this->process_results($query->result());
        } else {
            return array();
        }
	}
	
	public function getNextSearches($count) {
		$this->db->select('SearchAreas.*')->from('SearchAreas')->join('Search', 'SearchAreas.SearchID = Search.id', 'right')->where('Search.Active', 1)->order_by('SearchAreas.LastQueryDate', 'asc')->limit($count);
		$query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $this->process_results($query->result());
        } else {
            return array();
        } 

	}
}
?>