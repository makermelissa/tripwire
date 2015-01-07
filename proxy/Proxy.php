<?php
defined( 'BASEPATH' ) or die( 'No direct script access allowed' );
require 'RSSParser.php';
require 'Simple_html_dom.php';

class Proxy {
	
	// Variables
	private $user_agents = array(
		"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/35.0.1916.153 Safari/537.36",
		"Mozilla/5.0 (Windows NT 6.2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/28.0.1464.0 Safari/537.36",
		"Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2062.120 Safari/537.36",
		"Mozilla/5.0 (Windows NT 5.1; rv:31.0) Gecko/20100101 Firefox/31.0",
		"Mozilla/5.0 (Windows NT 6.1; WOW64; rv:29.0) Gecko/20120101 Firefox/29.0",
		"Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:25.0) Gecko/20100101 Firefox/29.0",
		"Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.2; Trident/6.0)",
		"Mozilla/5.0 (Windows NT 6.3; Trident/7.0; rv:11.0) like Gecko",
		"Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.3; Trident/7.0; .NET4.0E; .NET4.0C)",
		"Opera/9.80 (Windows NT 6.0) Presto/2.12.388 Version/12.14",
		"Mozilla/5.0 (Windows NT 6.0; rv:2.0) Gecko/20100101 Firefox/4.0 Opera 12.14",
		"Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.0) Opera 12.14",
		"Opera/9.80 (Macintosh; Intel Mac OS X 10.6.8; U; fr) Presto/2.9.168 Version/11.52",
		"Mozilla/5.0 (iPad; CPU OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A5355d Safari/8536.25",
		"Mozilla/43.0 (Windows NX 48.3; de-DE; rv:56.0) Gecko/20320816 Firefox/43.0",
		"Mozilla/5.0 (compatible; MSIE 10.0; Linux x86_64; SV1; Trident/3.0)",
	);
	
	public $cdata_fields = array('title', 'description');
	
	public $as_xml = TRUE;
	
	// Public Functions	
	public function getXMLData($url) {
		$my_rss_parser = new RSSParser();
		$xml_parser = xml_parser_create();
		xml_set_object($xml_parser, $my_rss_parser);
		xml_set_element_handler($xml_parser, "startElement", "endElement");
		xml_set_character_data_handler($xml_parser, "characterData");
	
		// Fetch craigslist feed
		$url .= '&format=rss';
		try {
			$xml_string = $this->retrieveData($url);
			if (is_a($xml_string, "Exception")) {
				return $this->error("Error retrieving RSS data. Exception: " . $e->getMessage());
			} elseif ($xml_string === FALSE) {
				return $this->error("Error reading RSS data. Curl error: " . curl_error($ch));
			} elseif (trim($xml_string) == "") {
				return $this->error("No data was returned. You may be blocked.");
			} elseif (strpos($xml_string, '<title>craigslist | Page Not Found</title>') !== FALSE) {
				return $this->error("Incorrectly forwarded. Make sure the URL has been updated: ".$url);
			} elseif (substr($xml_string, 0, 5) != '<?xml') {
				return $this->error('<![CDATA[' . $xml_string . ']]>');
			}
			xml_parse($xml_parser, $xml_string, TRUE);
			xml_parser_free($xml_parser);
		} catch (Exception $e) {
			return $this->error("Error processing RSS data. Exception: " . $e->getMessage());
		}
	
		// retrieve the items parsed
		$results = $my_rss_parser->getItems();
		$temp_results = $results;
		try {
			// Attempt to get the thumbnails
			// Tack them onto results
			foreach ($temp_results as &$result) {
				usleep(rand(100, 300));	// Wait a random amount of time
				$html_data = $this->retrieveData($result['link']);
				// We should add some code to deal with any errors returned
				$result['thumbnails'] = $this->getThumbs($html_data);
				$result['photo'] = $this->getPhoto($html_data);
				//var_export($html_data);
			}
			$results = $temp_results;
		} catch (Exception $e) {
			// Ignoring thumbnails then
		}
	
		return $this->formatData($results);
	}

	public function getThumbs($html_data) {
		$images = array();
		$html = new Simple_html_dom;
		# load the data 
		$html->load($html_data);

		# get an element representing the second paragraph
		$elements = $html->find("img");
		$search_str = '50x50c.jpg';
		foreach ($elements as $e) {
			$src = $e->src;
			if (stristr($src, $search_str) !== FALSE) {
				$images[] = $src;	
			}
		}
			
		return $images;
	}
	
	public function getPhoto($html_data) {
		$images = array();
		$html = new Simple_html_dom;
		
		# load the data 
		$html->load($html_data);
		
		# get an element representing the second paragraph
		$tray = $html->find(".tray", 0);
		if (is_null($tray)) return 'tray';
		$slide = $tray->first_child();
		if (is_null($slide)) return 'slide';
		$img = $slide->first_child();
		if (is_null($img)) return 'img';
		return $img->getAttribute('src');
	}

	// Protected Functions
	// Retrieve the raw data
	protected function retrieveData($url) {
		try {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			$cookie_file = "cookie1.txt";
			curl_setopt($ch, CURLOPT_COOKIESESSION, TRUE);
			curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
			curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
			curl_setopt($ch, CURLOPT_HEADER, TRUE);
			if (count($user_agents) >= 1) {
				$agent = $user_agents[array_rand($user_agents)];
				curl_setopt($ch, CURLOPT_USERAGENT, $agent); // makes our request look like it was made by random browsers
			}

			do {
				curl_setopt($ch, CURLOPT_URL, $url);
				$xml_string = curl_exec($ch);
				$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				if (in_array($http_code, array('301', '302'))) {
					$location = $this->getRedirect($xml_string);	
					if (!empty($location)) $url = $location;
				} else {
					// We need to strip the header out
					return $this->stripHeader($xml_string);	
				}
			} while(in_array($http_code, array('301', '302')));
			
			return $http_code;
		} catch (Exception $e) {
			return $e;
		}
	
		return FALSE;
	}
	
	protected function getRedirect($header) {
		$matches = array();
		$success = preg_match('/Location: ?(.*)/i', $header, $matches);
		if ($success === FALSE) return NULL; // Error
		if ($success === 0) return ''; // Not Found
		$location = $matches[1];
		if (substr($location, 0, 2) == '//') {
			// We'll need to modify this is they decide to go with https
			$location = 'http:' . $location; 
		}
		return $location;
	}
	
	protected function stripHeader($xml) {
		return preg_replace('/HTTP.*<\?xml/s',"<?xml", $xml);	
	}
	
	protected function error($message) {
		return $this->as_xml ? $this->errorToXml($message) : $message;
	}
	
	protected function errorToXml($message) {
		$xml_string = '<?xml version="1.0"?>' . "\n";
		$xml_string .= "<results>\n";
		$xml_string .= "\t<error>".$message."</error>\n";	
		$xml_string .= "</results>";
		
		return $xml_string;
	}
	
	protected function formatData($data) {
		return $this->as_xml ? $this->dataToXml($data) : $data;
	}
	
	protected function dataToXml($data) {
		$xml_string = '<?xml version="1.0"?>' . "\n";
		$xml_string .= "<results>\n";
		foreach ($data as $result) {
			$xml_string .= "\t<result>\n";
			foreach ($result as $name => $value) {
				if (in_array($name, $this->cdata_fields)) $value = '<![CDATA[' . $value . ']]>';
				if (is_array($value)) {
					foreach($value as $thumbnail) {
						$xml_string .= "\t\t<".$name.">".$thumbnail."</".$name.">\n";
					}
				} else {
					$xml_string .= "\t\t<".$name.">".$value."</".$name.">\n";
				}
			}
			$xml_string .= "\t</result>\n";	
		}
		$xml_string .= "</results>";
		
		return $xml_string;
	}
}
?>