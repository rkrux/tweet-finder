<?php

/**
 * Trivial Twitter API class to accept a request and display filtered tweets satisfying some condition
 */
class TwitterApi {
    
    private $consumer_key;
    private $consumer_secret;
    private $oauth_access_token;
    private $oauth_access_token_secret;

    private $base_url;
    private $request_method;
    private $query;
    
    private $base_string;
    private $oauth;
    private $auth_header;

    /**
     * Creates the API object and initializes the variables.
     *
     * @param array $credentials Set of credentials of the user
     * 
     * @throws Exception When cURL isn't installed or incorrect credentials are sent
     */
    public function __construct($credentials) {
        if(!in_array('curl', get_loaded_extensions())){
            throw new Exception("Install cURL and its PHP extension to run the application.");
        }
        if (!isset($credentials['consumerKey']) || !isset($credentials['consumerSecret'])
            || !isset($credentials['oauthAccessToken']) || !isset($credentials['oauthAccessTokenSecret'])) {
            throw new Exception('Your credentials are incorrect or incomplete.');
        }
        $this->consumer_key = $credentials['consumerKey'];
        $this->consumer_secret = $credentials['consumerSecret'];
        $this->oauth_access_token = $credentials['oauthAccessToken'];
        $this->oauth_access_token_secret = $credentials['oauthAccessTokenSecret'];       
    }
    
    /**
     * Initializes other variables. Subject to change for every request call.
     *
     * @param string $base_url Base url of API request
     * @param string $request_method Type of API request
     * @param string $query Query parameters of the request
     */
    private function initializeParameters($base_url, $request_method, $query) {
        $this->base_url = $base_url;
        $this->request_method = $request_method;
        $this->query = array(
            'q' => $query,
            'count' => 30
        );
    }
    
    /**
     * Creates base string for Oauth signature
     * 
     * @param string $parameters Associative array of keys and values of request  
     * 
     * @return string $base Base string 
     */
    private function createBaseString($parameters) {
        $key_val = array();
        ksort($parameters);
        foreach($parameters as $key => $value){
            $key_val[] = rawurlencode($key) . '=' . rawurlencode($value);
        }
        $this->base_string = ($this->request_method . '&' . rawurlencode($this->base_url) . '&' . rawurlencode(implode('&', $key_val)));
    }
    
    /**
     * Build the Oauth object using class variables and parameters of API request
     * Essential for cURL request later
     *
     * @return array $oauth
     */
    private function createOauth() {
        $this->oauth = array(
            'oauth_consumer_key' => $this->consumer_key,
            'oauth_nonce' => time(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_token' => $this->oauth_access_token,
            'oauth_timestamp' => time(),
            'oauth_version' => '1.0'
        );
        foreach ($this->query as $key => $value) {
            $this->oauth[$key] = $value;
        }
        $this->createBaseString($this->oauth);
        $secret_key = rawurlencode($this->consumer_secret) . '&' . rawurlencode($this->oauth_access_token_secret);
        $oauth_signature = base64_encode(hash_hmac('sha1', $this->base_string, $secret_key, true));
        $this->oauth['oauth_signature'] = $oauth_signature;
    }
    
    /**
     * Created Authorization header used by cURL
     * 
     * @return string $auth_header Comma separated header used in cURL request   
     */
    private function createAuthHeader() {     
        $this->createOauth();
        $this->auth_header = 'Authorization: OAuth ';
        $values = array();
        foreach($this->oauth as $key => $value) {
            if (in_array($key, array('oauth_consumer_key', 'oauth_nonce', 'oauth_signature',
                'oauth_signature_method', 'oauth_timestamp', 'oauth_token', 'oauth_version'))) {
                $values[] = "$key=\"" . rawurlencode($value) . "\"";
            }
        }
        $this->auth_header .= implode(', ', $values);
    }
    
    /**
     * Executes the actual request to Twitter API and receives JSON data
     *
     * @throws Exception When an error occurs during cURL reuqest
     * 
     * @return string $result JSON response from Twitter API
     */
    private function executeRequest() {
        $this->createAuthHeader();
        $param = array();
        foreach ($this->query as $key => $value) {
            $param[] = rawurlencode($key) . '=' . rawurlencode($value);
        }
        $url = $this->base_url . '?' . implode('&', $param);
        print_r($url);
        $header = array($this->auth_header, 'Expect:');
        $options = array( CURLOPT_HTTPHEADER => $header,
                      CURLOPT_HEADER => false,
                      CURLOPT_URL => $url,
                      CURLOPT_RETURNTRANSFER => true,
                      CURLOPT_SSL_VERIFYPEER => false);
        $feed = curl_init();
        curl_setopt_array($feed, $options);
        $result = curl_exec($feed);
        if (($error = curl_error($feed)) !== '') {
            curl_close($feed);
            throw new Exception($error);
        }
        curl_close($feed);
        return $result;
    }

    /**
     * Displays the tweets satisfying the conditions of retweeted atlest once
     * 
     * @param array $result JSON encoded response from the Twitter API
     */
    private function filterResult($result) {
        $decode = json_decode($result, true);
        if(isset($decode['errors'])){
            print_r($result);
            echo "\n";
            return;
        }
        $index = 1;
        if(isset($decode['statuses'])){
            echo PHP_EOL;
            foreach ($decode['statuses'] as $tweet) {
                    echo "TWEET NO. " . $index++ . " => ";
                    echo $tweet['text'] . "\nBY " . $tweet['user']['name'] . ' ON ' . $tweet['created_at'] . PHP_EOL . PHP_EOL;
            }
        }
    }
    
    /**
     * Public function called from the class object. Calls other private functions to perform the task
     * 
     * @param string $base_url Base url of API request
     * @param string $request_method Type of API request
     * @param string $query Query parameters of the request
     */
    public function makeRequest($base_url, $request_method, $query) {
        $this->initializeParameters($base_url, $request_method, $query);
        $result = $this->executeRequest();
        $this->filterResult($result);
    }
    
}