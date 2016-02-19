<?php

require_once 'TwitterApi.php';

/*
 * Parameters to be sent to the class object
 */
$url = 'https://api.twitter.com/1.1/search/tweets.json';
$request_method = 'GET';

/*
 * Read credentials from JSON file and decode them in associative arrays
 */
$contents = file_get_contents('credentials.json');
$credentials = json_decode($contents, true);

/*
 * Create Twitter API class object and pass request parameters
 */
$api_client = new TwitterApi($credentials);

while (true) {
	echo "Enter your search query. Type 'QUIT' to exit. -> ";
	$query = read_stdin();
	if (!strcmp($query, "QUIT"))
		break;
	$query = '?q=#' . $query;
	$api_client->makeRequest($url, $request_method, $query);
	echo '-------------------------------------------------------------------------------------------------' . PHP_EOL . PHP_EOL;
}

/*
 * Function to read user input
 */
function read_stdin() {
	$file = fopen("php://stdin", "r");
    $input = fgets($file, 128);
    $input = trim($input);
    fclose($file);
    return $input;
}

