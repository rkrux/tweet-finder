<?php

require_once 'TwitterApi.php';

/*
 * Parameters to be sent to the class object
 */
$url = 'https://api.twitter.com/1.1/search/tweets.json';
$request_method = 'GET';
$query = '?q=#ASOT';

/*
 * Read credentials from JSON file and decode them in associative arrays
 */
$contents = file_get_contents('credentials.json');
$credentials = json_decode($contents, true);

/*
 * Create Twitter API class object and pass request parameters
 */
$api_client = new TwitterApi($credentials);
$api_client->makeRequest($url, $request_method, $query);

