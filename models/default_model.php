<?php
defined( 'BASEPATH' ) or die( 'No direct script access allowed' );

class Default_model extends CI_Model {
	
	protected $id_field = 'id';
	protected $columns = array();
	
    public function __construct() {
        parent::__construct();
    }
    
    public function get_tablename() {
        return $this->tablename;
    }

    public function set_tablename($table_name) {
        $this->tablename = $table_name;
    }
    
    public function add($data, $table = NULL) {
		if (is_null($table)) $table = $this->get_tablename();
		$data = $this->trim_data($data, $table);
        $inserted = $this->db->insert($table, $data);
        if ($inserted) {
            return $this->get($this->db->insert_id(), $table);
        } else {
            return array();
        }
    }
 
    public function update($id, $data, $table = NULL) {
		if (is_null($table)) $table = $this->get_tablename();
		$data = $this->trim_data($data, $table);
        $this->db->where($this->id_field, $id);
        $updated = $this->db->update($table, $data);
        if ($updated) {
            return $this->get($id, $table);
        } else {
            return array();
        }
    }
	
	public function optimize($table = NULL) {
		if (is_null($table)) $table = $this->get_tablename();
		$this->load->dbutil();
		$this->dbutil->optimize_table($table);
	}

/*
	// UNTESTED
    public function update_using_params($data, $params = array(), $table = NULL) {
		if (is_null($table)) $table = $this->get_tablename();
		$data = $this->trim_data($data, $table);
        $this->process_params($params);
        $updated = $this->db->update($table, $data);
        if ($updated) {
            return $this->get($id, $table);
        } else {
            return array();
        }
    }
*/
    public function delete($id, $table = NULL) {
		if (is_null($table)) $table = $this->get_tablename();
		$this->db->where($this->id_field, $id);
        $result = $this->db->delete($table);
        return $this->db->affected_rows();
    }

    public function delete_using_params($params = array(), $table = NULL) {
		if (is_null($table)) $table = $this->get_tablename();
        $this->process_params($params);
        $result = $this->db->delete($table);
        return $this->db->affected_rows();
    }
    
    public function get($id = 0, $table = NULL) {
		if (is_null($table)) $table = $this->get_tablename();
        if ($id == 0) {
            $r = $this->get_fields($table);
			$result = new stdClass;
            foreach ($r as $f) {
                $result->{$f->Field} = '';
            }
			return $this->process_results($result);
        } else {
            $this->db->where($this->id_field, $id);
            $query = $this->db->get($table);
            if ($query->num_rows() == 0) {
                return array();
            } else {
                return $this->process_results($query->row());    
            }
        }
    }
	
	
	// Move item to specified table
	public function move($id, $table) {
		$data = $this->get($id);
		unset($data->{$this->id_field});
		
		$this->db->trans_start();
		//if (count($this->get($id, $table)) == 0) {
			$this->db->insert($table, $data);
		//}
		$this->delete($id);
		$this->db->trans_complete();
	}

	// Move item to specified table using parameters
	public function move_using_params($table, $params = array()) {
		$rows = $this->get_all_using_params($params);
		foreach ($rows as $data) {
			$this->move($data->{$this->id_field}, $table);
		}
	}

    public function get_all($table = NULL) {
		if (is_null($table)) $table = $this->get_tablename();
        $query = $this->db->get($table);
        $result = $this->process_results($query->result());
        return $result;
    }
    
    public function get_all_using_params($params = array(), $columns = array(), $table = NULL) {
		if (is_null($table)) $table = $this->get_tablename();
		$this->columns = $columns;
        $this->process_params($params);
 		$params = $this->trim_data($params, $table);
       
        $query = $this->db->get($table);
        if ($query->num_rows() > 0) {
            return $this->process_results($query->result());
        } else {
            return array();
        } 
    }
    
    public function get_one_using_params($params = array(), $columns = array(), $table = NULL) {
		if (is_null($table)) $table = $this->get_tablename();
		$this->columns = $columns;
        $this->process_params($params);
        
        $query = $this->db->get($table);
        if ($query->num_rows() > 0) {
			$result = $query->row();
			if (is_array($result)) $result = $result[0];
            return $result;
        } else {
            return FALSE;
        } 
    }
    
	public function search_using_params($params = array(), $table = NULL) {
		if (is_null($table)) $table = $this->get_tablename();
		$params = $this->trim_data($params, $table);
		// Use LIKE statements instead of where and remove any blank parameters.
		foreach ($params as $key=>$value) {
			if ($value != '') {
				if (is_array($value)) {
					$where_arr = array();
					foreach ($value as $item) {
						$where_arr[] = "`".$key."`='".$item."'";
					}
					$this->db->where('('.implode(' OR ', $where_arr).')', NULL, FALSE);
				} else {
					$this->db->like($key, $value);
				}
			}
		}

        $query = $this->db->get($table);
		//echo $this->db->last_query();
        if ($query->num_rows() > 0) {
            return $this->process_results($query->result());
        } else {
            return array();
        } 
	}

    public function process_params($params) {
        foreach ($params as $key => $value) {
			if (is_int($key) && $value == 'distinct') $key = 'distinct';
            switch($key) {
                case 'group_by':
                    foreach ($value as $gvalue) {
                        $this->db->group_by($gvalue);
                    }
                    break;
                case 'order_by':
                    foreach ($value as $okey => $ovalue) {
                        $this->db->order_by($okey, $ovalue);
                    }
                    break;
                case 'limit':
                    $this->db->limit($value);
                    break;
                case 'distinct':
                    $this->db->distinct();
                    break;
                case 'password':
                    $this->db->where($key,md5($value));
                    break;
                default:
					if (stripos($key, 'like:') === 0) {
						$this->db->like(substr($key, 5),$value);
					} else {
						if (is_array($value)) {
							if (substr($key, -3) == ' !=') {
								$this->db->where_not_in(substr($key, 0, strlen($key) - 3), $value);
							} else {
								$this->db->where_in($key, $value);
							}
						} else {
	                    	$this->db->where($key,$value);
						}
					}
                    break;
            }
        }
		
		if ($this->columns != '*' && !is_null($this->columns)) {
			$columns = $this->columns;	// Copy so we don't mess up the original
			if (is_array($columns)) $columns = implode(',', $columns);
			$this->db->select($columns);
		}
    }
    
    public function get_fields($table = NULL) {
		if (is_null($table)) $table = $this->get_tablename();
        $query = $this->db->query('show columns from ' . $table);
        $result = $query->result();
        return $result;
    }
	
	protected function trim_data($data, $table = NULL) {
		// We want to only use actual DB fields and automatically weed out other data
		$fields = array();
		$dbfields = $this->get_fields($table);
		foreach ($dbfields as $field) {
			$fields[] = $field->Field;
		}

		foreach ($data as $index=>$item) {
			if (!in_array($index, $fields)) {
				unset($data[$index]);	
			}
		}
		return $data;
	}
	
	// Placeholder Function for post-processing
	protected function process_results($result) {
		return $result;	
	}
	
	protected function hoursToSeconds($hours) {
		return $hours * 60 * 60;
	}

	protected function daysToSeconds($days) {
		return $this->hoursToSeconds($days * 24);
	}
	
	protected function timestamp($date) {
		return date('Y-m-d H:i:s', $date);	
	}

	protected function obj_to_array($obj, $column, $key = NULL) {
		$results = array();
		foreach ($obj as $row) {
			is_null($key) ? ($results[] = $row->$column) : ($results[$row->$key] = $row->$column);
		}
		
		return $results;
	}    
}