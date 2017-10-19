<?php

// CURL CLASS FROM UKM-API
// SEE //github.com/UKMNorge/UKMapi

class UKMCURL {
	var $timeout = 12;
	var $headers = false;
	var $content = true;
	var $postdata = false;
	
	public function __construct() {

	}
	
	public function timeout($timeout) {
		$this->timeout = $timeout;
	}
	
	public function post($postdata) {
		$this->postdata = $postdata;
	}
	
	public function headersOnly() {
		$this->headers = true;
		$this->content = false;
		$this->timeout(2);
	}

	public function request($url) {
		$this->url = $url;
		
		$this->curl = curl_init();
		curl_setopt($this->curl, CURLOPT_URL, $this->url);
		curl_setopt($this->curl, CURLOPT_REFERER, $_SERVER['PHP_SELF']);
		curl_setopt($this->curl, CURLOPT_USERAGENT, "UKMNorge API");
		curl_setopt($this->curl, CURLOPT_HEADER, $this->headers);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl, CURLOPT_TIMEOUT, $this->timeout);
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false); // WHOA, PLEASE DON'T

		// Is this a post-request?
		if( $this->postdata ) {
			curl_setopt($this->curl, CURLOPT_POST, true);
			curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->postdata);
		}


		// Get only headers
		if(!$this->content) {
			curl_setopt($this->curl, CURLOPT_HEADER, 1); 
			curl_setopt($this->curl, CURLOPT_NOBODY, 1); 
		}

		
		if(isset($this->port))
			curl_setopt($this->curl, CURLOPT_PORT, $this->port);
			
		$this->result = curl_exec($this->curl);
	
		if($this->content)
			$this->_analyze();
		else {
			$info = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
			curl_close($this->curl);
			return $info;
		}
	
		curl_close($this->curl);
		
		return $this->data;
	}
	
	private function _analyze() {
		$this->_isJson();
		$this->_isSerialized();
		if(!isset($this->data)) {
			if($this->result === 'false')
				$this->data = false;
			elseif($this->result === 'true')
				$this->data = true;
			else
				$this->data = $this->result;
		}
	}
	
	private function _isJson() {
		$decoded = @json_decode($this->result);
		$this->is_json = is_object($decoded);
		
		if($this->is_json)
			$this->data = $decoded;
	}
	
	private function _isSerialized() {
		$data = @unserialize($this->result);
		if ($this->result === 'b:0;' || $data !== false) {
			$this->data = $data;
		}
	}
}

$UKMCURL = new UKMCURL();
?>