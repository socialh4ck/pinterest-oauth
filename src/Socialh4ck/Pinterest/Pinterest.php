<?php namespace Socialh4ck\Pinterest;

/**
 * Pinterest API class
 * API Documentation: http://Pinterest.com/developer/
 * Class Documentation: https://github.com/cosenary/Pinterest-PHP-API/tree/dev
 * 
 * @author Christian Metz
 * @since 30.10.2011
 * @copyright Christian Metz - MetzWeb Networks 2011-2014
 * @version 2.1
 * @license BSD http://www.opensource.org/licenses/bsd-license.php
 */
class Pinterest {

	/**
	 * The API base URL
	 */
	const API_URL = 'https://api.pinterest.com/v2/';

	/**
	 * The API OAuth URL
	 */
	const API_OAUTH_URL = 'https://api.pinterest.com/oauth';

	/**
	 * The OAuth token URL
	 */
	const API_OAUTH_TOKEN_URL = 'https://api.pinterest.com/oauth/access_token';

	/**
	 * The Pinterest API Key
	 * 
	 * @var string
	 */
	private $_apikey;

	/**
	 * The Pinterest OAuth API secret
	 * 
	 * @var string
	 */
	private $_apisecret;

	/**
	 * The callback URL
	 * 
	 * @var string
	 */
	private $_callbackurl;

	/**
	 * The user access token
	 * 
	 * @var string
	 */
	private $_accesstoken;

	/**
	 * Available scopes
	 * 
	 * @var array
	 */
	private $_scopes = array('basic', 'likes', 'comments', 'relationships');

	/**
	 * Available actions
	 * 
	 * @var array
	 */
	private $_actions = array('follow', 'unfollow', 'block', 'unblock', 'approve', 'deny');

	/**
	 * Access Token Setter
	 * 
	 * @param object|string $data
	 * @return void
	 */
	public function setAccessToken($data) {
		(true === is_object($data)) ? $token = $data->access_token : $token = $data;
		$this->_accesstoken = $token;
	}

	/**
	 * Access Token Getter
	 * 
	 * @return string
	 */
	public function getAccessToken() {
		return $this->_accesstoken;
	}

	/**
	 * API-key Setter
	 * 
	 * @param string $apiKey
	 * @return void
	 */
	public function setApiKey($apiKey) {
		$this->_apikey = $apiKey;
	}

	/**
	 * API Key Getter
	 * 
	 * @return string
	 */
	public function getApiKey() {
		return $this->_apikey;
	}

	/**
	 * API Secret Setter
	 * 
	 * @param string $apiSecret 
	 * @return void
	 */
	public function setApiSecret($apiSecret) {
		$this->_apisecret = $apiSecret;
	}

	/**
	 * API Secret Getter
	 * 
	 * @return string
	 */
	public function getApiSecret() {
		return $this->_apisecret;
	}

	/**
	 * API Callback URL Setter
	 * 
	 * @param string $apiCallback
	 * @return void
	 */
	public function setApiCallback($apiCallback) {
		$this->_callbackurl = $apiCallback;
	}

	/**
	 * API Callback URL Getter
	 * 
	 * @return string
	 */
	public function getApiCallback() {
		return $this->_callbackurl;
	}
	
	/**
	 * Default constructor
	 *
	 * @param array|string $config          Pinterest configuration data
	 * @return void
	 */
	public function __construct($config) {
		if (true === is_array($config)) {
			// if you want to access user data
			$this->setApiKey($config['config']['apiKey']);
			$this->setApiSecret($config['config']['apiSecret']);
			$this->setApiCallback($config['config']['apiCallback']);
		} else if (true === is_string($config)) {
			// if you only want to access public data
			$this->setApiKey($config);
		} else {
			throw new Exception("Error: __construct() - Configuration data is missing.");
		}
	}

	/**
	 * Generates the OAuth login URL
	 *
	 * @param array [optional] $scope       Requesting additional permissions
	 * @return string                       Pinterest OAuth login URL
	 */
	public function getLoginUrl($scope = array('basic')) {
		if (is_array($scope) && count(array_intersect($scope, $this->_scopes)) === count($scope)) {
			return self::API_OAUTH_URL . '?client_id=' . $this->getApiKey() . '&redirect_uri=' . urlencode($this->getApiCallback()) . '&scope=' . implode('+', $scope) . '&response_type=code';
		} else {
			throw new Exception("Error: getLoginUrl() - The parameter isn't an array or invalid scope permissions used.");
		}
	}

	/**
	 * Get the OAuth data of a user by the returned callback code
	 *
	 * @param string $code                  OAuth2 code variable (after a successful login)
	 * @param boolean [optional] $token     If it's true, only the access token will be returned
	 * @return mixed
	 */
	public function getOAuthToken($code, $token = false) {
		$apiData = array(
			'grant_type'    => 'authorization_code',
			'client_id'     => $this->getApiKey(),
			'client_secret' => $this->getApiSecret(),
			'redirect_uri'  => $this->getApiCallback(),
			'code'          => $code
		);

		$result = $this->_makeOAuthCall($apiData);
		return (false === $token) ? $result : $result->access_token;
	}

	/**
	 * The call operator
	 *
	 * @param string $function              API resource path
	 * @param array [optional] $params      Additional request parameters
	 * @param boolean [optional] $auth      Whether the function requires an access token
	 * @param string [optional] $method     Request type GET|POST
	 * @return mixed
	 */
	protected function _makeCall($function, $auth = false, $params = null, $method = 'GET') {
		if (false === $auth) {
			// if the call doesn't requires authentication
			$authMethod = '?client_id=' . $this->getApiKey();
		} else {
			// if the call needs an authenticated user
			if (true === isset($this->_accesstoken)) {
				$authMethod = '?access_token=' . $this->getAccessToken();
			} else {
				throw new Exception("Error: _makeCall() | $function - This method requires an authenticated users access token.");
			}
		}

		if (isset($params) && is_array($params)) {
			$paramString = '&' . http_build_query($params);
		} else {
			$paramString = null;
		}

		$apiCall = self::API_URL . $function . $authMethod . (('GET' === $method) ? $paramString : null);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $apiCall);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		if ('POST' === $method) {
			curl_setopt($ch, CURLOPT_POST, count($params));
			curl_setopt($ch, CURLOPT_POSTFIELDS, ltrim($paramString, '&'));
		} else if ('DELETE' === $method) {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
		}

		$jsonData = curl_exec($ch);
		if (false === $jsonData) {
			throw new Exception("Error: _makeCall() - cURL error: " . curl_error($ch));
		}
		curl_close($ch);

		return json_decode($jsonData);
	}

	/**
	 * The OAuth call operator
	 *
	 * @param array $apiData                The post API data
	 * @return mixed
	 */
	private function _makeOAuthCall($apiData) {
		$apiHost = self::API_OAUTH_TOKEN_URL;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $apiHost);
		curl_setopt($ch, CURLOPT_POST, count($apiData));
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($apiData));
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$jsonData = curl_exec($ch);
		if (false === $jsonData) {
			throw new Exception("Error: _makeOAuthCall() - cURL error: " . curl_error($ch));
		}
		curl_close($ch);

		return json_decode($jsonData);
	}

	/**
	 * GET Activity
	 *
	 * @param Array $params
	 * @return mixed
	 */
	public function getActivity($params = array()) {
		return $this->_makeCall('/activity/', false, $params);
	}
	
	/**
	 * GET All
	 *
	 * @param integer [optional] $limit     Limit of returned results
	 * @param integer [optional] $page      Pages of returned results
	 * @return mixed
	 */
	public function getAll($limit = 36, $page = 0) {
		return $this->_makeCall('/all/', true, array('limit' => $limit, 'page' => $page));
	}
	
	/**
	 * GET Popular
	 *
	 * @param integer [optional] $limit     Limit of returned results
	 * @param integer [optional] $page      Pages of returned results
	 * @return mixed
	 */
	public function getPopular($limit = 36, $page = 0) {
		return $this->_makeCall('/popular/', true, array('limit' => $limit, 'page' => $page));
	}

	/**
	 * GET New Boards
	 *
	 * @param Array $params
	 * @return mixed
	 */
	public function getNewBoards($params = array()) {
		return $this->_makeCall('/newboards/', false, $params);
	}
	
	/**
	 * GET Boards
	 *
	 * @param Array $params
	 * @return mixed
	 */
	public function getBoards($params = array()) {
		return $this->_makeCall('/boards/', false, $params);
	}
	
	/**
	 * GET Categories
	 *
	 * @param integer [optional] $limit     Limit of returned results
	 * @param integer [optional] $page      Pages of returned results
	 * @return mixed
	 */
	public function getCategories($limit = 36, $page = 0) {
		return $this->_makeCall('/boards/categories/', true, array('limit' => $limit, 'page' => $page));
	}
	
}
