<?php
require('class.osapirest3legged.php'); //smooths out working with opensocial php library a bit
include 'randomPicFetcher.php'; //for if you do not know what to fill in as pic url to upload
include 'Header.php';

$token = array();
$code = 1;
if (isset($_SESSION['token'])) {
    $token = array();
    $token['key'] = $_SESSION['token']->key;
    $token['secret'] = $_SESSION['token']->secret;
    $code = 200;
    
} else {
    $token = "No Session Found";
    $code = 400;
}
$response = array();
$response['code'] = $code;
$response['token'] = $token;
echo json_encode($response);
unset($_SESSION['token']);
?>
