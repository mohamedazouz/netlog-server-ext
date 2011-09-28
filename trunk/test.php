<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
require('class.osapirest3legged.php'); //smooths out working with opensocial php library a bit
include 'randomPicFetcher.php'; //for if you do not know what to fill in as pic url to upload
include 'Header.php';


//{"code":200,"token":{"key":"c5c9d9e7-d4e9-c1df-afea-ceddd2f9e4d8","secret":"f987a6601d09553ceb1f0bbed5665eb2"}}
//{"code":200,"token":{"key":"fad3e7d1-ffce-c5d4-a3ca-f8d3feccf5e3","secret":"17feefcf4555d82fc5f9b905c5d8fcae"}}
$key = "fad3e7d1-ffce-c5d4-a3ca-f8d3feccf5e3"; //$_POST['key'];
$secret = "17feefcf4555d82fc5f9b905c5d8fcae"; // $_POST['secret'];

$accessToken = new OAuthConsumer($key, $secret);
$os = new osapiREST($OAUTHKEY, $OAUTHSECRET, $lan, $userid, $dbData, $debug, $accessToken);

$code = 200;
$response = array();
$result;
$functionName;
for ($i = 1; $i < 7; $i++) {
    switch ($i) {
        case 1: {// get user information
                $result = $os->getViewer();
                $functionName="getViewer()";
                $code = 200;
            }break;
        case 2: {// get user friend list
                $result = $os->getAllViewerFriends();
                $functionName="getAllViewerFriends()";
                $code = 200;
            } break;
        case 3: {  // get user notification
                $result = $os->getViewerFriendsActivities();
                $functionName="getViewerFriendsActivities()";
                $code = 200;
            }break;
        case 4: {  // upload photo
                $result = $os->getViewerFoF();
                $functionName="getViewerFoF()";
                $code = 200;
            }break;
        case 5: { //get user friend details
            echo "Display All friend Via <b>getFriendDetails</b> <br>";
                $friend_id = '169212791';
                $result = $os->getFriendDetails($friend_id);
                $response['code'] = $code;
                $response['result'] = $result;
                echo $i . json_encode($response);
                echo "<br><br>------------------------------<br><br>";
                $friend_id = '48686546';
                $result = $os->getFriendDetails($friend_id);
                $response['code'] = $code;
                $response['result'] = $result;
                echo $i . json_encode($response);
                echo "<br><br>------------------------------<br><br>";
                $friend_id = '172836915';
                $result = $os->getFriendDetails($friend_id);
                $response['code'] = $code;
                $response['result'] = $result;
                echo $i . json_encode($response);
                echo "<br><br>------------------------------<br><br>";
                $friend_id = '169319402';
                $result = $os->getFriendDetails($friend_id);
                $response['code'] = $code;
                $response['result'] = $result;
                echo $i . json_encode($response);
                echo "<br><br>------------------------------<br><br>";
                $friend_id = '169212791';
                $result = $os->getFriendDetails($friend_id);
                $response['code'] = $code;
                $response['result'] = $result;
                echo $i . json_encode($response);
                echo "<br><br>------------------------------<br><br>";
                $friend_id = '169284042';
                $result = $os->getFriendDetails($friend_id);
                $response['code'] = $code;
                $response['result'] = $result;
                echo $i . json_encode($response);
                echo "<br><br>------------------------------<br><br>";
                $friend_id = '170215236';
                $result = $os->getFriendDetails($friend_id);
                $code = 200;
                $functionName="getFriendDetails()";
            }break;
        case 6: { //get user friend details
            $result = $os->getViewerFriends();
            $functionName="getViewerFriends()";
                $code = 200;
            }break;
    }
    $response['code'] = $code;
    $response['result'] = $result;
    echo "<b>".$functionName." </b><br>" . json_encode($response);
    echo "<br><br><br><br>";
}
?>
