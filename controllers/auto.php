<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once(APPPATH.'controllers/appcontroller.php');

class Auto extends AppController {
	
	protected $isSending = FALSE;

    public function __construct() {
        parent::__construct();
		$this->set_title('Automation');
		define('LOCK_FILE', "/var/tmp/sending_notifications.lock");
		define('LOCK_TIMEOUT', 30);
	}

	public function index() {
		// This shouldn't be accessed directly
	}

	public function update_hits($search_id = NULL) {
		//return FALSE;
		$this->load->model('search_model');
		$this->load->model('searcharea_model');
		$this->load->model('hit_model');
		$this->load->model('thumbnails_model');
		// This should run every 5 or so minutes (create cron job for this)
		
		// Update any expired searches to inactive
		$this->search_model->deactivateExpiredSearches();
		
		
		if (!is_null($search_id)) {
			$search_areas = $this->search_model->getSearch($search_id);
		} else {
			// Select all active searches to perform 80 to 100
			$search_areas = $this->search_model->getNextSearches(rand(20, 40));
			//$search_areas = $this->search_model->getNextSearches(rand(80, 100));
		}

		$count = 0;

		foreach ($search_areas as $search_area) {
			$area = $this->searcharea_model->get($search_area->SearchableAreaID);
			$search = $this->search_model->get($search_area->SearchID);

			$search_url = $area->url.$search->SearchURLParams;
			$last_hit_date = $search_area->LastHitDate;
			$hits = $this->getProxyData($search_url);

			foreach ($hits as $hit) {
				// store each hit into the DB that has a date/time value later than search->LastHitDate 
				if (strtotime($hit['date']) > strtotime($last_hit_date)) {
					if (!$this->hit_model->hitExists($search->id, $hit['link'])) {
						// Add this to the newhits table
						$hit_data = array(
							'SearchID' => $search->id, 
							'AreaID' => $area->id,
							'URL' => $hit['link'],
							'Title' => $hit['title'],
							'Date' => $hit['date'],
							'Photo' => $hit['photo'],
							'NearByHit' => 0,
							);
						//var_dump($hit_data);
						$inserted_hit = $this->hit_model->add($hit_data);
						$this->thumbnails_model->addImages($inserted_hit->id, $hit['thumbnails']);
					}
				}
			}
			// Update Search->LastHitDate
			$latest_hit = $this->hit_model->getLatestHit($search->id);
			if ($latest_hit) {
				if (strtotime($latest_hit->Date) > strtotime($last_hit_date)) {
					$this->search_model->updateLastHitDate($search->id, $latest_hit->Date, $area->id);
				}
			}
			// After every 25 searches performed we start sending queuing notifications (if necessary, assuming there are new hits)
			if ($count % 25 == 0) {
				$this->push_notifications();
			}
			$count++;
			echo 'Updated '.$count.' search'.($count == 1 ? '' : 'es').'...'."<br>\n";
			$this->search_model->updateLastQueryDate($search->id, $area->id);
		}
		// push notifications for any remaining search hits
		$this->push_notifications();
	}
	
	public function notifications($override = '') {
		//if (empty($override)) return FALSE; //Allow to only run manually
		define("TIME_INCREMENT", 5);
		$this->createLock();
		
		register_shutdown_function(array($this, 'removeLock'));
		// This should run every minute
		$start_time = time();
		$this->push_notifications();
		$this->isSending = FALSE;
		// Attempt to run 10 times - once every 5 seconds
		for($i=1; $i<=10; $i++) {
			echo $i.". Sending Notifications...<br>\n";
			// Make sure we didn't take too long
			if (!$this->isSending) $this->send_notifications();
			if (time() < ($start_time + (TIME_INCREMENT * $i))) {
				time_sleep_until($start_time + (TIME_INCREMENT * $i));
			}
		}
		$this->removeLock();
	}

	private function createLock() {
		// We want to check if a lock file exists
		// If it does, check if it's over 5 minutes old
			// If so, delete it and recreate it
		// Else we want to stop the script
		echo "Checking for lock...<br>\n";
		if ($this->isLocked()) {
			echo "Current time is " . time() . "<br>\n";
			echo "File time is " . filemtime(LOCK_FILE) . "<br>\n";
			
			if (time() - filemtime(LOCK_FILE) >= LOCK_TIMEOUT) {
				$this->removeLock();
				// Try to lock again
				return $this->createLock();
			} else {
				die("Another script is still running!");
			}
		}
		
		if (touch(LOCK_FILE)) {
			echo "Lock File Created<br>\n";
			return TRUE;	// Successfully created new file!
		}
	
		// Unable to lock
		echo "Not Locked<br>\n";		
		return FALSE;
	}
	
	private function isLocked() {
		return file_exists(LOCK_FILE) && !is_dir(LOCK_FILE);
	}
	
	public function removeLock() {
		if ($this->isLocked())
		{
			unlink(LOCK_FILE);
			echo "Lock File Deleted!<br>\n";
			return TRUE;
		}
		return FALSE;	
	}
	
	public function send_notifications() {
		$this->isSending = TRUE;
		// Look up any notifications that are queued start sending
		$this->load->model('notification_model');
		$this->load->model('user_model');
		$this->load->model('search_model');
		$this->load->model('hit_model');
		$this->load->model('thumbnails_model');

		$notifications = $this->notification_model->get_all();
		//$notifications = $this->notification_model->get_all_using_params(array('UserProfileID'=>1));
		foreach ($notifications as $notification) {
			// Gather Notification Data
			$search = $this->search_model->get($notification->SearchID);
			$user = $this->user_model->get($notification->UserProfileID);
			
			if ($user) {
				$this->data->hit_list = $this->hit_model->get_all_using_params(array('SearchID'=>$search->id, 'limit'=>'25', 'order_by'=>array('Date'=>'DESC')));
				//log_message('error', 'Hits for Search ID '.$search->id.': '.print_r($this->data->hit_list, TRUE));
				//print_r('<hr>');
				//print_r($search);
				//print_r($this->data->hit_list);
				$this->data->links = $this->getWebLinks($search->id);
				$this->data->item_title = $this->GetSearchTitleForDisplay($search->id, 50, TRUE);
				$this->data->email = $user->Email;
				$this->data->search_urls = $this->getSearchURLs($search->id);
				$this->data->notify_every = $search->NotifyEvery;
				echo "Sending email to " . $user->Email . "\n";
				// Send out Notification
				switch($notification->Type) {
					case 'hit':
						if (count($this->data->hit_list) > 0) {
							// Attempt to add an Image and a Thumbnail for each (This may fail)
							var_export(count($this->data->hit_list));
							foreach ($this->data->hit_list as &$hit) {
								$hit->thumbnails = $this->thumbnails_model->getImages($hit->id);
							}
							
							// Send the notification
							$success = $this->send_notification($user->id, 'hit');
						} else {
							log_message('error', 'No hits found for Search ID:'.$search->id);
							log_message('error', print_r($notification, TRUE));
							echo 'No hits found for Search ID:'.$search->id."<br>\n";
							$this->notification_model->archiveNotification($notification->id);
							$success = FALSE;	
						}
						
						// If success, archive the notification
						if ($success) {
							$this->hit_model->archiveHits($search->id);
							$this->search_model->updateNextSendDate($search->id);
						}
						break;
					case 'renew': 
						$success = $this->send_notification($user->id, 'renew');
						if (!$success) $this->search_model->renewalFailed($search->id);
						break;
					case 'deactivated': 
						$success = $this->send_notification($user->id, 'deactivated');
						break;
					case 'expired': 
						$success = $this->send_notification($user->id, 'expired');
						if (!$success) $this->search_model->renewalFailed($search->id);
						break;
					case 'initial': 
						$success = $this->send_notification($user->id, 'initial'); 
						break;
					default:
						// We need to figure out a way to grab only valid types
						// In other words, if the type is blank, we should not include the notifications as needing to be sent out
						// And we should make note of it in the log file
						$success = FALSE;
						break;
				}
	
				if ($success) {
					// Archive Notification
					$this->notification_model->archiveNotification($notification->id);
					$this->search_model->incrementMailsSent($search->id);
				}
			} else {
				// No User Exists, so just archive notification
				$this->notification_model->archiveNotification($notification->id);
			}
		}
		$this->isSending = FALSE;
	}

	private function checkLimit() {
		$this->load->model('wanted_model');
		if ($this->wanted_model->scrapesLast24Hours() >= 100) die('sending limit hit');
	}

	public function push_notifications() {
		$this->load->model('notification_model');
		$this->load->model('hit_model');
		$this->load->model('search_model');

		// If there are any records in the NewHits DB for a Given Search ID and NextSendDate has passed, add notification
		// Get a list of all search IDs for which there are new hits on
		$newhit_search_ids = $this->hit_model->getAllSearchIDs();
		$notification_search_ids = $this->notification_model->getAllSearchIDs();
		$search_ids = array_diff($newhit_search_ids, $notification_search_ids);
		foreach ($search_ids as $search_id) {
			$search = $this->search_model->get($search_id);
			if ($search && $search->Active == 1) {
				if (strtotime($this->search_model->getNextSendDate($search->id)) <= time()) {
					$this->notification_model->addNotification('hit', $search->UserProfileID, $search->id);
				}
			} else {
				if (!$search) log_message('error', 'Search not found for Search ID "'.$search_id.'"');
				// Remove any hits for inactive searches
				$this->hit_model->archiveHits($search_id);
			}
		}
		$this->checkRenewals();
	}
	
	public function checkRenewals() {
		$this->load->model('notification_model');
		$this->load->model('search_model');
		
		// If any searches need to be renewed, we add notifications to the DB
		$expiring_searches = $this->search_model->getExpiringSearches();
		foreach ($expiring_searches as $search) {
			$this->notification_model->addNotification('renew', $search->UserProfileID, $search->id);
			$this->search_model->renewalSent($search->id);
		}
	}
}
/* End of file auto.php */
/* Location: ./application/controllers/auto.php */
