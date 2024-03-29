<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
require('class.osapirest3legged.php'); //smooths out working with opensocial php library a bit
include 'randomPicFetcher.php'; //for if you do not know what to fill in as pic url to upload
include 'Header.php';

$key = $_POST['key'];
$secret = $_POST['secret'];
$accessToken = new OAuthConsumer($key, $secret);
$os = new osapiREST($OAUTHKEY, $OAUTHSECRET, $lan, $userid, $dbData, $debug, $accessToken);

$code = 200;
$response = array();
$result;
$function = $_POST['function'];
switch ($function) {
    case 1: {// get user information
            $result = $os->getViewer();
            //echo json_encode($result["profilevisitors"][0]["visitorid"]);
            //echo $result["profilevisitors"][$i]["visitorid"];
            /* $visitorid = $result["profilevisitors"][0]["visitorid"];
              $temp = $os->getFriendDetails($visitorid);
              $result["profilevisitors"][0]["visitorid"] = $temp; */
            if ($result["profilevisitors"]) {
                for ($i = 0; $i < sizeof($result["profilevisitors"]); $i++) {
                    $visitorid = $result["profilevisitors"][$i]["visitorid"];
                    $temp = $os->getFriendDetails($visitorid);
                    $result["profilevisitors"][$i]["visitorid"] = $temp;
                }
            } else {
                $result["profilevisitors"] = array();
            }
            if (!array_key_exists("notifications", $result)) {
                $result["notifications"] = array();
            }
            $code = 200;
        }break;
    case 2: {// get user friend list
            $result = $os->getAllViewerFriends();
            $code = 200;
        } break;
    case 3: {  // get user notification
            $result = $os->getViewerFriendsActivities();
            if (property_exists($result["friendActivities"], "response")) {
                $temp = array();
                $temp['list'] = array();
                $result["friendActivities"] = $temp;
            } else {
                foreach ($result["friendActivities"]->list as $i) {
                    $i->userId = $os->getFriendDetails($i->userId);
                }
            }


            $code = 200;
        }break;
    case 4: {  // upload photo
            move_uploaded_file($_FILES['fileToUpload']['tmp_name'],
                    "uploads/" . $_FILES['fileToUpload']["name"]);
            echo "<script>console.log('" . $_FILES['fileToUpload']["name"] . "')</script>";
            $file = "uploads/" . $_FILES['fileToUpload']["name"];
            $result = $os->uploadUserPicture($file);
            $myFile = $file;
            unlink($myFile);
            $code = 200;
        }break;
    case 5: { //get user friend details
            $friend_id = $_POST['friend_id'];
            $result = $os->getFriendDetails($friend_id);
            $code = 200;
        }break;
    case 6: { //get user friend details
            $friend_id = $_POST['friend_id'];
            $title = $_POST['title'];
            $body = $_POST['body'];
            $result = $os->sendNotification($friend_id, $title, $body);
            $code = 200;
        }break;
    default: {
            $code = 400;
            $result = "Invalid Function ";
        }
}

$response['code'] = $code;
$response['result'] = $result;
echo json_encode($response);
?>
