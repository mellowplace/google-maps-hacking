<?php
/*
 * Gets the OAuth code to manage your Youtube vids
 */
require __DIR__ . '/vendor/autoload.php';

$client = new Google_Client();
$client->setAuthConfig(__DIR__ . '/client_secret.json');
$client->addScope('https://www.googleapis.com/auth/youtube.force-ssl');
$client->setRedirectUri('urn:ietf:wg:oauth:2.0:oob');
echo "Please visit: " . $client->createAuthUrl() . "\n";
$code = readline("Enter the auth code: ");

$token = $client->fetchAccessTokenWithAuthCode($code);

echo "Your token is: \n";
print_r($token);
