<?php

include __DIR__.'/vendor/autoload.php';
use Guzzle\Http\Client;

// create our http client (Guzzle)
$http = new Client('http://coop.apps.knpuniversity.com', array(
    'request.options' => array(
        'exceptions' => false,
    )
));

$request = $http->post('/token', null, [
    'client_id' => 'asdfadfs',
    'client_secret' => 'bad01e4d9044d9237995c6e23b8287bc',
    'grant_type' => 'client_credentials'
]);

$response = $request->send();
$responseBody =  $response->getBody(true);
$responseArr = json_decode($responseBody, true);
$accessToken = $responseArr['access_token'];


$request  = $http->post('/api/681/eggs-collect');
$request->addHeader('Authorization', 'Bearer '.$accessToken);
$response = $request->send();

echo $response->getBody();

echo PHP_EOL;

