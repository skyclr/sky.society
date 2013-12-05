<?php

/**
 * Class to work with network
 */
class network { 

	public static 
		$requestTypes 		= array("POST", "GET", "HEAD"),
		$socks 				= array(),

		/**
		 * List if internal networks
		 * @var array
		 */
		$internalNetworks 	= array(
			'10.0.0.0'    => '10.255.255.255',
			'172.16.0.0'  => '172.31.255.255',
			'192.168.0.0' => '192.168.255.255',
			'127.0.0.0'   => '127.0.0.1'
		);

	/**
	 * Convert text to pUnicode
	 * @param string $uri String to be converted
	 * @return string Converted string
	 * @throws systemErrorException
	 */
	public static function punycode($uri) {


		# Include
		require_once sky::location("external") . 'idna.php';


		# converter
		$idn = new idna_convert(array('idn_version' => 2008));


		# Convert uri
		$result = $idn->encode_uri($uri);
		if($result == false) $result = $idn->encode($uri);


		# If any error occupied
		if($result === false) 
			throw new systemErrorException("Punycode convert error: ".$idn->get_last_error());


		# Return
		return $result;

	}

	/**
	 * Make russian text from converted string
	 * @param string $uri Converted string
	 * @return string Real string
	 * @throws systemErrorException
	 */
	public static function depunycode($uri) {


		# Include
		require_once sky::location("external") . 'idna.php';


		# Create converter
		$idn = new idna_convert(array('idn_version' => 2008));


		# Convert uri
		$result = $idn->decode($uri);


		# If any error occupied
		if($result === false) 
			throw new systemErrorException("Punycode convert error: ".$idn->get_last_error());


		# Return
		return $result;

	}

	/**
	 * Preforms curl request
	 * @param String     $url     URI of page to perform request
	 * @param Array|bool $options Array of options to perform request
	 * @throws userErrorException
	 * @throws systemErrorException
	 * @return array
	 */
	public static function curlRequest($url, $options = false) {


		# Empty check
		if(empty($url))
			throw new userErrorException("URL для выполнения запроса не задан");

		
		# Default 0 socks index
		if(!isset($options['socks'])) 
			$options['socks'] = false;
		
		
		# Request
		if(!isset($options['request']) || !in_array($options['request'], self::$requestTypes))
			$options['request'] = "GET";
			
		
		# Setting headers
		if(isset($options['headers'])) $headers = $options['headers'];
		else {
			$headers = Array(
				"Accept: image/gif, image/x-xbitmap, image/jpeg, image/pjpeg, application/x-shockwave-flash, application/vnd.ms-excel, application/msword, */*",
				"Connection: Close",
				"Accept-Language: en-us",
				"Accept-Charset: iso-8859-1,*,utf-8",
				"Pragma: no-cache"
			);
		}
			
		
		# Initializing curl
		$curl = curl_init();

		
		# Set curl parameters
		$parameters = array(
			CURLOPT_URL 			=> self::punycode($url),
			CURLOPT_CUSTOMREQUEST 	=> $options['request'],
			CURLOPT_RETURNTRANSFER	=> 1,
			CURLOPT_SSL_VERIFYPEER	=> !empty($options["ssl"]),
			CURLOPT_SSL_VERIFYHOST 	=> !empty($options["ssl"]) ? 2 : false,
			CURLOPT_HEADER			=> true,
			CURLOPT_RETURNTRANSFER	=> true,
			CURLOPT_HTTPHEADER	 	=> $headers,
			CURLOPT_USERAGENT		=> "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)",
			CURLOPT_ENCODING		=> "",
			CURLOPT_IPRESOLVE		=> CURL_IPRESOLVE_V4
		);

		
		# Use socks proxy
		if(is_numeric($options['socks'])) {
			$parameters[CURLOPT_PROXYTYPE] 	= CURLPROXY_SOCKS5;
			$parameters[CURLOPT_PROXY]		= self::$socks[$options["socks"]];
		}

		
		# Include headers
		if ($options['request'] == "HEAD") 
			$parameters[CURLOPT_HEADER] = 1;


		# Slow request
		if(isset($options['timeout']) && isset($options['connectTimeout'])) {
			$parameters[CURLOPT_TIMEOUT]        = $options['timeout']; // overall exec time
			$parameters[CURLOPT_CONNECTTIMEOUT] = $options['connectTimeout']; // connection timeout
		} elseif(isset($options['slow'])) {
			$parameters[CURLOPT_TIMEOUT]        = 12; // overall exec time
			$parameters[CURLOPT_CONNECTTIMEOUT] = 60; // connection timeout
		} else {
			$parameters[CURLOPT_TIMEOUT]        = 20; // overall exec time
			$parameters[CURLOPT_CONNECTTIMEOUT] = 10; // connection timeout
		}


		# POST data
		if(!empty($options['post'])) {

			# Post data usage flag
			$parameters[CURLOPT_POST] = 1;

			# Set parameter
			$parameters[CURLOPT_POSTFIELDS] = $options['post'];

		}


		# Proxy
		if (isset($options['proxy']['ip'])) {
			$parameters[CURLOPT_HTTPPROXYTUNNEL] = 1; 
			$parameters[CURLOPT_PROXY] 			 =  $options['proxy']['ip']; 
			if (isset($options['proxy']['auth'])) 
				$parameters[CURLOPT_PROXYUSERPWD] = $options['proxy']['auth'];
		}
		
		
		# Cookie
		if (isset($options['cookie'])) {
			$parameters[CURLOPT_COOKIEJAR] 	= $options['cookie'];
			$parameters[CURLOPT_COOKIEFILE] = $options['cookie'];
		}
		
		
		# Set parameters
		if(curl_setopt_array($curl, $parameters) === false)
			throw new systemErrorException("Can't set curl parameters");		
			
		
		# Execute
		$returned = curl_exec($curl);
		
		
		# If some error
		if($returned === false) {
			
			# Can't resolve
			if(curl_errno($curl) == 6) 
				throw new userErrorException("Невозможно определить адрес сервера '$url'.");
			
			# Inactive
			if(curl_errno($curl) == 7) 
				throw new userErrorException("Сервер '$url' не активен.");

			# Inactive
			if(curl_errno($curl) == 3)
				throw new userErrorException("Ваш URI('$url') имеет неверный формат.");

			# Unknown error
			throw new systemErrorException("CURL error: ".curl_errno($curl));

		}
		
		
		# Resource moved
		if(empty($options["noRedirect"]) && (curl_getinfo($curl, CURLINFO_HTTP_CODE) == "301" || curl_getinfo($curl, CURLINFO_HTTP_CODE) == "302")) {
			$headers = get_headers($url, 1);
			return self::curlRequest($headers["Location"], $options);
		}
		
		
		# Fetching headers
		$headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
		$headers = self::parseHeaders(substr($returned, 0, $headerSize - 4));
	
		
		# Fetch body
		$result = substr($returned, $headerSize);


		# Return in array
		return array("response" => $result, "headers" => $headers, "info" => curl_getinfo($curl));
			
	}

	/**
	 * Would parse headers data
	 * //TODO::write code
	 * @param string $data Headers data
	 * @return mixed
	 */
	static function parseHeaders($data) {

		$return = array();
		$data = explode("\r\n", $data);

		foreach($data as $i => $d) {
			if(!$i) continue;
			$data = explode(": ", $d, 2);
			if(count($data) == 2)
			$return[$data[0]] = $data[1];
		}

		return $return;

	}
	
	# Get user's real IP address
	static function checkUserIp(){


	    # Check for predefined variables
	    if(!isset($_SERVER['HTTP_X_FORWARDED_FOR']) || empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
	        if(!isset($_SERVER['HTTP_X_REAL_IP']) || empty($_SERVER['HTTP_X_REAL_IP'])) 
	            return false;
	        $ip = $_SERVER['HTTP_X_REAL_IP'];    
	    } else 
	        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];    


	    # Set ip string to int    
	    $ip = sprintf("%u\n", ip2long($ip));


	    # Check for net affiliation of ip
	    foreach (self::$internalNetworks as $firstIp => $lastIp) {

	        # Set ip string to int
	        $firstIp = sprintf("%u\n", ip2long($firstIp));
	        $lastIp  = sprintf("%u\n", ip2long($lastIp));

	        # Ignoring internal nets
	        if (($firstIp <= $ip) && ($ip <= $lastIp))
	            return false;
	    }
	        
	    return $ip;            
	}

	/**
	 * get IP address by host url
	 * @param string $baseURL String contains url
	 * @throws userErrorException
	 * @return int
	 * @internal param String $url Url which is host parameter of parse_url
	 */
	public static function getIpByAddress($baseURL) {

		# Fetch Url IP
		$url = self::punycode($baseURL);

		
		# Remove https and https
		if(mb_strpos($url, "https://", 0, "utf-8") === 0)
			$url = mb_substr($url, 8, mb_strlen($url, "utf-8"), "utf-8");
		elseif(mb_strpos($url, "http://", 0, "utf-8") === 0)
			$url = mb_substr($url, 7, mb_strlen($url, "utf-8"), "utf-8");
		
		
		# Remove last slash
		if($url[mb_strlen($url, "utf-8")-1] == "/")
			$url = mb_substr($url, 0, mb_strlen($url, "utf-8") - 1, "utf-8");

		# Exec trace command
		exec("/usr/bin/dig $url A +short | /usr/bin/tail -1", $urlIp);

		# If no result
		if (!sizeof($urlIp))
			throw new userErrorException("Невозможно определить IP-адрес по url: ".$baseURL);

		# Convert
		$urlIpInt = ip2long($urlIp[0]);
		
		# Validate IP
		if ($urlIpInt[0] == -1 || $urlIp[0] != long2ip($urlIpInt)) 
			throw new userErrorException("Невозможно определить IP-адрес по url – ".$url);

		# Return
		return $urlIpInt;
	
	}

}
