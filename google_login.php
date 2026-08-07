<?php

require_once 'vendor/autoload.php';

session_start();


$client = new Google_Client();


$client->setAuthConfig(
    'client_secret.json'
);


$client->setRedirectUri(
    'https://vishthefishjr.me/oauth_callback.php'
);


$client->addScope(
    Google_Service_Slides::PRESENTATIONS
);


$client->addScope(
    Google_Service_Drive::DRIVE_FILE
);


// Important: keep login active
$client->setAccessType('offline');


$url = $client->createAuthUrl();


echo "

<a href='$url'>

Connect Google Account

</a>

";

?>