<?php
defined( 'BASEPATH' ) or die( 'No direct script access allowed' );
require_once(APPPATH.'models/default_model.php');

class Searcharea_model extends Default_Model {
    protected $tablename = 'SearchableArea';

    public function __construct() {
        parent::__construct();
    }
	
	public function get($id = 0) {
		return parent::get($id);
	}
	
	public function getLocationName($area_id) {
		$display = $this->buildDisplayName($area_id);
		return $display['item1'].', '.$display['item2'];
	}

	public function location_list($state) {
		$locations = array();
		$data = $this->get_all_using_params(array('state'=>$state));
		foreach ($data as $row) {
			$locations[$row->industry_name] = $row->industry_name;
		}
		
		return $locations;
	}
	
	public function find_locations($term, $user_id = NULL) {
		$locations = array();

		if ($user_id == 1 || $user_id == 15) {
			$locations['usa'] = array('item1' => 'usa - all');
		}
		//SELECT DISTINCT `state` FROM `SearchableArea` WHERE `country` = 'usa'
		$states = $this->get_all_using_params(array('distinct', 'country'=>'usa', 'like:state'=>$term), array('state'));
		foreach ($states as $state) {
			$locations[$state->state] = array('item1' => $state->state.' - all');
		}

		$this->db->like('city', $term);
		$this->db->or_like('subName', $term);
		$this->db->or_like('state', $term);
		$this->db->or_like('country', $term);
		$results = $this->get_all();
		foreach ($results as $result) {
			$display = $this->buildDisplayName($result->id);
			$locations[$result->id] = $display;
		}
		
		
		return $locations;
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
		$area = $this->get($area_id);
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

		//$display['site'] = str_replace(" ", "&nbsp;", $display['site']);
		return $display;
	}
}