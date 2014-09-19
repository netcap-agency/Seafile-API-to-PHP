<?php
/*
*	Seafile API - PHP class wrapper
*	Perform PUT,GET,POST,DELETE request to your seafile server
*	@author : ben@netcap.fr
*	@copyright : opensource
*
*
*	Seafile - Next-generation Open Source Cloud Storage
*	http://seafile.com/
*
*	Seafile API
*	http://manual.seafile.com/develop/web_api.html
*
*	CURL function from rest_curl_client.php
*	@author : davidmpaz
*	https://github.com/davidmpaz/rest-curlclient-php
*
*
*/
class SeafileAPI {

	# Seafile user mail to perform REST
	private $seafile_user;
	# Seafile user pass to perform REST
	private $seafile_pass;
	# Seafile url
	private $seafile_url;
	# Seafile full url with port
	private $seafile_full_url;
	# Seafile port - default 8000
	private $seafile_port = 8000;
	# Seafile private token
	private $seafile_token;
	# Seafile static error code
	private $seafile_status_message = array(
		'200'	=>	'OK',
		'201'	=>	'CREATED',
		'202'	=>	'ACCEPTED',
		'301'	=>	'MOVED_PERMANENTLY',
		'400'	=>	'BAD_REQUEST',
		'403'	=>	'FORBIDDEN',
		'404'	=>	'NOT_FOUND',
		'409'	=>	'CONFLICT',
		'429'	=>	'TOO_MANY_REQUESTS',
		'440'	=>	'REPO_PASSWD_REQUIRED',
		'441'	=>	'REPO_PASSWD_MAGIC_REQUIRED',
		'500'	=>	'INTERNAL_SERVER_ERROR',
		'520'	=>	'OPERATION_FAILED'
	);
	# Seafile response code
	public $seafile_code;
	# Seafile response message
	public $seafile_status;
	# Current curl object
	private $handle;
	# Current curl options
	private $http_options = array();
	# Curl response
	private $response_object;
	# Convert response to array instead of object - default false
	public $response_object_to_array = false;
	# Curl info
	public $response_info;
	
	
	/*
	*	Instanciate seafile class
	*
	*	@param string $url
	*	@param string $user
	*	@param string $password
	*	@param int $port
	*	@throws Exception
	*
	*/
	function __construct($option = array()){
	
		if(!is_callable('curl_init'))
			throw new Exception("Curl extension is required");
	
		if(isset($option['url']) && !empty($option['url']) && filter_var($option['url'], FILTER_VALIDATE_URL))
			$this->seafile_url = $option['url'];
		else
			throw new Exception("Error Seafile URL is missing or bad URL format");
			
			
		if(isset($option['user']) && !empty($option['user']) && filter_var($option['user'], FILTER_VALIDATE_EMAIL))
			$this->seafile_user = filter_var(strtolower(trim(preg_replace('/\\s+/', '', $option['user']))), FILTER_SANITIZE_EMAIL);
		else
			throw new Exception("Error Seafile user is missing or bad email format");
			
			
		if(isset($option['password']) && !empty($option['password']))
			$this->seafile_pass = $option['password'];
		else
			throw new Exception("Error Seafile user password is required");
			
			
		if(isset($option['port']) && !empty($option['port']) && is_int($option['port']))
			$this->seafile_port = (int) $option['port'];
			
		$this->seafile_full_url = $this->seafile_url.':'.$this->seafile_port;
		
		/*
		*	Default curl config
		*/
		$this->http_options[CURLOPT_RETURNTRANSFER] = true;
		$this->http_options[CURLOPT_FOLLOWLOCATION] = false;
		
		/*
		*	Return seafile token
		*/
		$this->getToken();
	}
	
	/*
	*	Return Seafile token
	*	
	*	@return - The Seafile token
	*/
	private function getToken(){
		$data = $this->decode($this->post($this->seafile_full_url.'/api2/auth-token/', array(
			'username'=> $this->seafile_user, 
			'password'=> $this->seafile_pass
		)));
		$this->seafile_token = (string)$data->token;
	}
	
	
	/*
	*	Ping Seafile server when logged
	*	
	*	@return array - The account infos
	*/
	public function ping(){
		return $this->decode($this->get($this->seafile_full_url.'/api2/auth/ping/', array(
			CURLOPT_HTTPHEADER => array('Authorization: Token '.$this->seafile_token)
		)));
	}
	
	
	/*
	*	Return Seafile account information
	*	
	*	@return array - The available libraries
	*/
	public function checkAccountInfo(){
		return $this->decode($this->get($this->seafile_full_url.'/api2/account/info/', array(
			CURLOPT_HTTPHEADER => array('Authorization: Token '.$this->seafile_token)
		)));
	}
	
	
	/*
	*	Return Seafile default library
	*	
	*	@return array - Default library infos
	*/
	public function getDefaultLibrary(){
	
		return $this->decode($this->get($this->seafile_full_url.'/api2/default-repo/', array(
			CURLOPT_HTTPHEADER => array('Authorization: Token '.$this->seafile_token)
		)));
	}
	
	
	/*
	*	List Seafile libraries
	*	
	*	@return array - The available libraries
	*/
	public function listLibraries(){
		return $this->decode($this->get($this->seafile_full_url.'/api2/repos/', array(
			CURLOPT_HTTPHEADER => array('Authorization: Token '.$this->seafile_token)
		)));
	}
	
	
	/*
	*	Return Seafile library infos
	*	
	*	@param string $library_id
	*	@return array - The library infos
	*/
	public function getLibraryInfo($library_id){
		return $this->decode($this->get($this->seafile_full_url.'/api2/repos/'.$library_id.'/', array(
			CURLOPT_HTTPHEADER => array('Authorization: Token '.$this->seafile_token)
		)));
	}
	
	
	/*
	*	Return Seafile libraries infos
	*	
	*	@param array $libraries
	*	@return array - The libraries infos
	*/
	public function getLibrariesInfo($libraries){
		if(is_array($libraries)){
		
			$return = array();
			foreach($libraries as $lib):
				$return[] = $this->decode($this->get($this->seafile_full_url.'/api2/repos/'.$lib->id.'/', array(
					CURLOPT_HTTPHEADER => array('Authorization: Token '.$this->seafile_token)
				)));
			endforeach;
			return $return;
			
		}else{
			return $this->getLibraryInfo($libraries[0]->id);
		}
	}
	
	
	/*
	*	Create a new library
	*	
	*	@param array $libraries
	*	@return array - The libra
	*/
	public function createLibrary($name, $desc, $password = false){
	
		if($password){
			$post = array(
				'name' 		=> $name,
				'desc' 			=> $desc,
				'passwd'		=> $password
			);
		}else{
			$post = array(
				'name' 		=> $name,
				'desc' 			=> $desc
			);
		}
	
		return $this->decode($this->post($this->seafile_full_url.'/api2/repos/', $post,
			array(
				CURLOPT_HTTPHEADER => array('Authorization: Token '.$this->seafile_token)
			)
		));
	}
	
	
	/*
	*	List Seafile directory entries
	*	
	*	@param string $library_id - Seafile directory id
	*	@return array - The files in driectory
	*/
	public function listDirectoryEntries($library_id){
	
		return $this->decode($this->get($this->seafile_full_url.'/api2/repos/'.$library_id.'/dir/', array(
			CURLOPT_HTTPHEADER => array('Authorization: Token '.$this->seafile_token)
		)));
	}
	
	/*
	*	List Seafile directories entries
	*	
	*	@param array $library_id - Seafile directory ids
	*	@return array - The files in driectory
	*/
	public function listDirectoriesEntries($library_id){
	
		if(is_array($library_id)){
		
			$return = array();
			foreach($library_id as $dir):
				$return[] = $this->decode($this->get($this->seafile_full_url.'/api2/repos/'.$dir->id.'/dir/', array(
					CURLOPT_HTTPHEADER => array('Authorization: Token '.$this->seafile_token)
				)));
			endforeach;
			return $return;
	
		}else{
		
			return $this->listDirectoryEntries($library_id[0]->id);
			
		}
	}
	
	
	/*
	*	Create a new directory in a library
	*	
	*	@param string $library_id - Seafile library id
	*	@param string $directory_name - New directory name
	*	@return array - The link for new directory
	*/
	public function createNewDirectory($library_id, $directory_name){

		return $this->decode($this->post($this->seafile_full_url.'/api2/repos/'.$library_id.'/dir/?p=/'.urlencode($directory_name), array(
				'operation' => 'mkdir'
			),
			array(
				CURLOPT_HTTPHEADER => array('Authorization: Token '.$this->seafile_token)
			)
		));
	}
	
	
	/*
	*	Rename a directory in a library
	*	
	*	@param string $library_id - Seafile library id
	*	@param string $directory_name - Old directory name
	*	@param string $directory_new_name - New directory name
	*	@return array - The link for new directory
	*/
	public function renameDirectory($library_id, $directory_name, $directory_new_name){

		return $this->decode($this->post($this->seafile_full_url.'/api2/repos/'.$library_id.'/dir/?p=/'.urlencode($directory_name), array(
				'operation' 	=> 'rename',
				'newname'	=> $directory_new_name
			),
			array(
				CURLOPT_HTTPHEADER => array('Authorization: Token '.$this->seafile_token)
			)
		));
	}
	
	
	/*
	*	Download link for Seafile file
	*	
	*	@param string $library_id - Seafile library id
	*	@param string $file_name - Seafile file name
	*	@return string - The link for download
	*/
	public function downloadFile($library_id, $file_name){
	
		return $this->decode($this->get($this->seafile_full_url.'/api2/repos/'.$library_id.'/file/?p=/'.$file_name, array(
			CURLOPT_HTTPHEADER => array('Authorization: Token '.$this->seafile_token)
		)));
		
	}
	
	
	/*
	*	Download links for Seafile files
	*	
	*	@param string $library_id - Seafile library id
	*	@param array $file_name - Seafile files
	*	@return string - The link for download
	*/
	public function downloadFiles($library_id, $files){
	
		if(is_array($files)){
		
			$return = array();
			foreach($files as $file):
			$return[] = $this->decode($this->get($this->seafile_full_url.'/api2/repos/'.$library_id.'/file/?p=/'.$file->name, array(
				CURLOPT_HTTPHEADER => array('Authorization: Token '.$this->seafile_token)
			)));
			endforeach;
			return $return;
			
		}else{
			return $this->downloadFile($library_id[0]->id, $files[0]->name);
		}
		
	}
	
	
	
	/*
	*	Return debugged data
	*
	*/
	public function debug($data){
		if(is_array($data)) {
			print "<pre>";
			print_r($data);
			print "</pre>";
		}else{
			print "<pre>";
			var_dump($data);
			print "</pre>";
		}
	}
	
	/*
	*	Decode answer to object format instead of json
	*
	*	@param array $data - The json encoded response
	*	@param bool $this->response_object_to_array default false - If true return array from json instead of object
	*
	*/
	public function decode($data){
	
		if(!$this->response_object_to_array)
			return json_decode($data);
		else
			return json_decode($data, true);
	}
	
	
	/*
	*	Analyse curl answer
	*
	*	@param array $res The curl object
	*	@throws Exception
	*
	*/
	private function http_parse_message($res) {

		if(! $res)
			throw new Exception(curl_error($this->handle), -1);

		$this->response_info = curl_getinfo($this->handle);
		$code = $this->response_info['http_code'];
		
		$this->seafile_code = $code;
		$this->seafile_status = $this->seafile_status_message[$code];

		if($code == 404)
			throw new Exception($this->seafile_code. ' - '.$this->seafile_status . ' - ' .curl_error($this->handle));

		if($code >= 400 && $code <=600)
			throw new Exception($this->seafile_code. ' - '.$this->seafile_status . ' - ' .'Server response status was: ' . $code . ' with response: [' . $res . ']', $code);

		if(!in_array($code, range(200,207)))
			throw new Exception($this->seafile_code. ' - '.$this->seafile_status . ' - ' .'Server response status was: ' . $code . ' with response: [' . $res . ']', $code);
	}
	
	/*
	*	Perform a GET call to server
	* 
	*	Additionaly in $response_object and $response_info are the 
	*	response from server and the response info as it is returned 
	*	by curl_exec() and curl_getinfo() respectively.
	* 
	*	@param string $url The url to make the call to.
	*	@param array $http_options Extra option to pass to curl handle.
	*	@return string The response from curl if any
	*/
	public function get($url, $http_options = array()) {
		
		$http_options = $http_options + $this->http_options;
		$this->handle = curl_init($url);

		if(! curl_setopt_array($this->handle, $http_options))
			throw new Exception("Error setting cURL request options");

		
		$this->response_object = curl_exec($this->handle);
		$this->http_parse_message($this->response_object);

		curl_close($this->handle);
		return $this->response_object;
	}

	/*
	*	Perform a POST call to the server
	* 
	*	Additionaly in $response_object and $response_info are the 
	*	response from server and the response info as it is returned 
	*	by curl_exec() and curl_getinfo() respectively.
	* 
	*	@param string $url The url to make the call to.
	*	@param string|array The data to post. Pass an array to make a http form post.
	*	@param array $http_options Extra option to pass to curl handle.
	*	@return string The response from curl if any
	*/
	public function post($url, $fields = array(), $http_options = array()) {
		
		$http_options = $http_options + $this->http_options;
		$http_options[CURLOPT_POST] = true;
		$http_options[CURLOPT_POSTFIELDS] = $fields;
		
		if(is_array($fields))
			$http_options[CURLOPT_HTTPHEADER] = array('Content-Type: multipart/form-data');
			
		
		$this->handle = curl_init($url);

		if(! curl_setopt_array($this->handle, $http_options))
			throw new Exception("Error setting cURL request options.");
		
		$this->response_object = curl_exec($this->handle);
		$this->http_parse_message($this->response_object);

		curl_close($this->handle);
		return $this->response_object;
	}

	/*
	*	Perform a PUT call to the server
	* 
	*	Additionaly in $response_object and $response_info are the 
	*	response from server and the response info as it is returned 
	*	by curl_exec() and curl_getinfo() respectively.
	* 
	*	@param string $url The url to make the call to.
	*	@param string|array The data to post.
	*	@param array $http_options Extra option to pass to curl handle.
	*	@return string The response from curl if any
	*/
	public function put($url, $data = '', $http_options = array()) {
		
		$http_options = $http_options + $this->http_options;
		$http_options[CURLOPT_CUSTOMREQUEST] = 'PUT';
		$http_options[CURLOPT_POSTFIELDS] = $data;
		$this->handle = curl_init($url);

		if(! curl_setopt_array($this->handle, $http_options))
			throw new Exception("Error setting cURL request options.");

		$this->response_object = curl_exec($this->handle);
		$this->http_parse_message($this->response_object);

		curl_close($this->handle);
		return $this->response_object;
	}

	/*
	* Perform a DELETE call to server
	* 
	* Additionaly in $response_object and $response_info are the 
	* response from server and the response info as it is returned 
	* by curl_exec() and curl_getinfo() respectively.
	* 
	* @param string $url The url to make the call to.
	* @param array $http_options Extra option to pass to curl handle.
	* @return string The response from curl if any
	*/
	public function delete($url, $http_options = array()) {
		
		$http_options = $http_options + $this->http_options;
		$http_options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
		$this->handle = curl_init($url);

		if(! curl_setopt_array($this->handle, $http_options))
			throw new Exception("Error setting cURL request options.");

		$this->response_object = curl_exec($this->handle);
		$this->http_parse_message($this->response_object);

		curl_close($this->handle);
		return $this->response_object;
	}

}