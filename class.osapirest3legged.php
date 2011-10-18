<?php

//does os rest calls using the opensocial php client library:
//http://code.google.com/p/opensocial-php-client/
//inspired by the __init__.php and other files in /examples/ folder of the library

/*
  ISSUES I HAD:
  - troubles with writing tmp folder for logging because of 'open_basedir restriction' setting on my server.
  --Solved by: modifying log() in osapi/logger/osapilogger.php to do simply echo's (with static param $echoLogs to switch on/off
  - troubles with some cURL set_option not possible because my server config.
  -- solved by: commenting that set_option line in osapi/io/osapicurlsomething.php

  - troubles with 3-legged storing stuff in text files (again the open_basedir stuff)
  -- solved by: creating new MySQL storage class with getters and setters like the other 'storages'.

  - some changes in osapinetlogprovider.php
  -- extra prerequestprocessing for POST requests only
  -- some flexibility in specifying the API (for testing)

  - friends will only be fatched from the same Netlog language version
 */

//FOR 3-LEGGED, UserId means your 'local' unique userid, use session_id() if your site does not have 'users'

set_include_path(get_include_path() . PATH_SEPARATOR . '..'); // Add the osapi directory to the include path

date_default_timezone_set('Europe/London'); // Set the default timezone since many servers won't have this configured

require_once "opensocial-php-netlog-1/src/osapi/osapi.php"; // Require the osapi library

class osapiREST {

    //token key and secret should be provided by Netlog
    //dbData should have keys 'host', 'user', 'pass', 'db', 'table' or FALSE if you choose to use file storage instead of MySQL
    function __construct($tokenkey, $tokensecret, $language, $localUserId, $dbData, $showDebug = false, $accesstoken=null) {
        $this->showDebug = $showDebug; //echo debugs within this class
        osapiLogger::setEchoLogs($showDebug); //echo logs from php client (handy for seeing detailed requests + responses)
        $useFileStorage = false; //else we'll use my custom mysql storage class thingie

        $this->tokenkey = $tokenkey;
        $this->tokensecret = $tokensecret;
        $this->localUserId = $localUserId; // identifier for the local user's session
        $this->language = $language;

        ini_set('error_reporting', E_ALL | E_STRICT);   // Report everything, better to have stuff break here than in production
        set_include_path(get_include_path() . PATH_SEPARATOR . '..'); // Add the osapi directory to the include path

        $this->appId = '@app'; //'@app';

        $this->provider = new osapiNetlogProvider();
        $this->provider->rpcEndpoint = null; //!!! use REST endpoint instead of rpc endpoint (which is default and will lead to error)

        $this->debug("Creating 3 legged osapi object ...");

        //for logging and maintaining session for 3-legged
        if ($useFileStorage) {
            $folder = '/os/tmp/osapi';
            $this->storage = new osapiFileStorage($folder);
        } else {
            $this->storage = new osapiMySQLStorage(/* $dbData['host'], $dbData['user'], $dbData['pass'], $dbData['db'], $dbData['table'] */);
        }

        $this->auth = osapiOAuth3Legged::performOAuthLogin($this->tokenkey, $this->tokensecret, $this->storage, $this->provider, $this->localUserId, null, $accesstoken);
        $this->osapi = new osapi($this->provider, $this->auth);
        $this->userId = '@me';

        $this->viewer = $this->getViewer();
        $initText = "Init osapi object 3-legged... Userid " . $this->userId . " - App ID " . $this->appId;
        $storageInfo = $useFileStorage ? "Using file storage in folder $folder" : "Using MySQL storage with host " . $dbData['host'] . " and db " . $dbData['db'];
        if (array_key_exists("response", $this->viewer)) {
            echo "";
        } else {
            $viewerInfo = "Viewer: " . $this->viewer['nickname'] . " with userid " . $this->viewer['id'];
            $this->debug($initText . '<br>' . $storageInfo . '<br>' . $viewerInfo);
        }
    }

    function debug($string) {
        if ($this->showDebug) {
            echo '<div style="color:#333;background-color:white;">' . $string . '<br /></div>';
        }
    }

    function getViewer() {
        $this->debug('getting viewer info...');

        $batch = $this->osapi->newBatch();

        $profile_fields = array(
            'aboutMe',
            'name',
            'displayName',
            'thumbnailUrl',
            'gender',
            'profilevisitors',
            'notifications'
        );

        //Fetch the current user.
        $self_request_params = array(
            'userId' => $this->userId, // Person we are fetching.
            'groupId' => '@self', // @self for one person.
            'fields' => $profile_fields       // Which profile fields to request.
        );

        $batch->add($this->osapi->people->get($self_request_params), 'self');
        $response = $batch->execute();

        $this->debug("Response:<br /><pre>" . print_r($response, true) . "</pre>");

        $result = get_object_vars($response['self']); //this is an osapiPerson object but we want assoc. array

        $this->debug("Viewer result:<pre>" . print_r($result, true) . "</pre>");

        return $result;
    }

    function getFriendDetails($id) {
        $this->debug('getting viewer info...');

        $batch = $this->osapi->newBatch();

        $profile_fields = array(
            'aboutMe',
            'name',
            'displayName',
            'thumbnailUrl',
            'gender',
        );

        //Fetch the current user.
        $self_request_params = array(
            'userId' => $id, // Person we are fetching.
            'groupId' => '@self', // @self for one person.
            'fields' => $profile_fields       // Which profile fields to request.
        );

        $batch->add($this->osapi->people->get($self_request_params), 'self');
        $response = $batch->execute();

        $this->debug("Response:<br /><pre>" . print_r($response, true) . "</pre>");

        $result = get_object_vars($response['self']); //this is an osapiPerson object but we want assoc. array

        $this->debug("Viewer result:<pre>" . print_r($result, true) . "</pre>");
        return $result;
    }

    function getViewerFoF() { //inspired by /examples/listfriends.php
        $this->debug("getting viewer friends of friends...");

        // Start a batch so that many requests may be made at once.
        $batch = $this->osapi->newBatch();

        $profile_fields = array(
            //'aboutMe',
            //'nickname',
            'displayName',
                //'thumbnailUrl',
                //'gender',
        );

        $friends_request_params = array(
            'userId' => $this->userId, // Person whose friends we are fetching.
            'groupId' => '@friendsoffriends', // @friends for the Friends group.
            'fields' => $profile_fields, // Which profile fields to request.
        );

        $batch->add($this->osapi->people->get($friends_request_params), 'friends');

        $response = $batch->execute();

        $this->debug("Response:<br /><pre>" . print_r($response, true) . "</pre>");
    }

    function getViewerFriends($count = 10, $startIndex=0) { //inspired by /examples/listfriends.php
        $this->debug("getting $count viewer friends starting from index $startIndex...");

        $friend_count = $count;

        // Start a batch so that many requests may be made at once.
        $batch = $this->osapi->newBatch();

        $profile_fields = array(
            //'aboutMe',
            'nickname',
            'displayName',
            'thumbnailUrl',
            'gender',
        );

        $friends_request_params = array(
            'userId' => $this->userId, // Person whose friends we are fetching.
            'groupId' => '@friends', // @friends for the Friends group.
            'fields' => $profile_fields, // Which profile fields to request.
            'count' => $friend_count, // Max friends to fetch.
            'startIndex' => $startIndex
        );

        $batch->add($this->osapi->people->get($friends_request_params), 'friends');

        $response = $batch->execute();

        $this->debug("Response:<br /><pre>" . print_r($response, true) . "</pre>");

        $result = $response['friends']->list;

        foreach ($result as &$friend) {
            $friend = get_object_vars($friend); //convert osapiPersonObjects to arrays
        }

        $this->debug("Friends result:<pre>" . print_r($result, true) . "</pre>");
        return $result;
    }

    //Netlog returns at most 75 friends
    public function getAllViewerFriends() {
        $this->debug("getting all viewer friends...");

        $count = 1;
        $index = 0;
        $countPerFetch = 50;

        $allFriends = $this->getViewerFriends($countPerFetch, $index);
        $lastCount = count($allFriends);
        $this->debug("Last count: $lastCount ...");
        $currentIteration = 0;
        $maxIterations = 3;
        while ($lastCount >= $countPerFetch) {
            if ($currentIteration >= $maxIterations) {
                $this->debug("Reached max of $maxIterations iterations! Stopping loop...");
                break;
            }

            $index += $countPerFetch;
            $currentBatch = $this->getViewerFriends($countPerFetch, $index);

            $allFriends = array_merge($allFriends, $currentBatch);
            $currentIteration++; //could connect this to index and vice versa

            $lastCount = count($currentBatch);
            $this->debug("Last count: $lastCount ...");
        }

        return $allFriends;
    }

    function getViewerFriendsActivities() {
        $this->debug('getting viewer friend activities...');

        $batch = $this->osapi->newBatch();

        $friend_params = array(
            'userId' => $this->userId,
            'groupId' => '@friends',
            'count' => 200
        );
        $batch->add($this->osapi->activities->get($friend_params), 'friendActivities');

        $response = $batch->execute();

        $this->debug("Response:<br /><pre>" . print_r($response, true) . "</pre>");

        return $response;
    }

    //$userIds: int or array with ints of recipient userids
    function sendNotification($userIds, $title, $body) {
        if (!is_array($userIds)) {
            $userIds = array($userIds);
        }

        $batch = $this->osapi->newBatch();

        // Create a message
        $message = new osapiMessage(
                        $userIds,
                        $body,
                        $title,
                        'NOTIFICATION'
        );
        $create_params = array(
            'userId' => $this->userId,
            'groupId' => '@self',
            'message' => $message
        );

        $batch->add($this->osapi->messages->create($create_params), 'createMessage');
        $response = $batch->execute();
        $this->debug("Response:<br /><pre>" . print_r($response, true) . "</pre>");

        return $response;
    }

    //$userIds: int or array with ints of recipient userids
    function sendVoiceChatInvite($userIds, $channelId, $title) {
        if (!is_array($userIds)) {
            $userIds = array($userIds);
        }

        $batch = $this->osapi->newBatch();

        // Create a message
        $message = new osapiMessage(
                        $userIds,
                        $channelId, //would be body, but in this context we put the 'channel id' of the vivox channel here
                        $title,
                        'voicechatInvite'
        );
        $create_params = array(
            'userId' => $this->userId,
            'groupId' => '@self',
            'message' => $message
        );

        $batch->add($this->osapi->messages->create($create_params), 'createMessage');
        $response = $batch->execute();
        $this->debug("Response:<br /><pre>" . print_r($response, true) . "</pre>");

        return $response;
    }

    //post activity that the viewer's friends see in their 'friend logs'. Body can contain HTML tags like <img>, <a> and <br>
    function postViewerActivity($title, $body) {
        $this->debug("Posting viewer activity with title: $title and body: $body");

        $batch = $this->osapi->newBatch();

        $activity = new osapiActivity();
        $activity->setField('title', $title);
        $activity->setField('body', $body);

        $this->debug("Created OsapiActivity object: " . print_r($activity, true));

        $create_params = array(
            'userId' => '@me', //$this->userId,
            'groupId' => '@self',
            'activity' => $activity,
            'appId' => '@app' //$this->appId
        );

        $batch->add($this->osapi->activities->create($create_params), 'createActivity');

        $response = $batch->execute();
        $this->debug("Response:<br /><pre>" . print_r($response, true) . "</pre>");

        return $response;
    }

    function getViewerPhotos() {
        $this->debug('getting viewer photos...');
        $user_params = array(
            'userId' => $this->userId,
            'groupId' => '@self',
            //'count' => 8,
            //'startIndex' => 0
            'albumId' => 0, //gets them from all albums
        );
        $batch = $this->osapi->newBatch();
        $batch->add($this->osapi->mediaItems->get($user_params), 'get_mediaItems');
        $response = $batch->execute();
        $this->debug("Response:<br /><pre>" . print_r($response, true) . "</pre>");

        $result = $response['get_mediaItems']->list;

        foreach ($result as &$photo) {
            $photo = get_object_vars($photo); //convert osapiMediaItem objects to arrays
        }

        $this->debug("Photos result:<pre>" . print_r($result, true) . "</pre>");
        return $result;
    }

    function uploadUserPicture($picUrl, $asAvatar = false) {
        $this->debug("Uploading pic at $picUrl ...");
        $batch = $this->osapi->newBatch();
        $data = file_get_contents($picUrl);

        $user_params = array(
            'userId' => '@me',
            'groupId' => '@self',
            //'albumId'           =>       $albumId, //if 0 will add to no album
            'type' => 'IMAGE',
            'mediaItem' => $data,
            'contentType' => 'image/jpg',
        );

        if ($asAvatar) {
            $user_params['setAsAvatar'] = true;
        }

        $batch->add($this->osapi->mediaItems->uploadContent($user_params), 'upload_mediaItem');
        $response = $batch->execute();

        $this->debug("Response:<br /><pre>" . print_r($response, true) . "</pre>");

        return $response;
    }

    function insertOrUpdateViewerAppData($key, $value) {
        $this->debug("setting persistent data - key : $key - value: $value (will update if already set)... ");

        $batch = $this->osapi->newBatch();

        $data = Array($key => $value);  //could in principle contain more key-value pairs
        // Create some app data for the current user
        $create_params = array(
            'userId' => '@me',
            'groupId' => '@self',
            'appId' => $this->appId,
            'data' => $data
        );
        $batch->add($this->osapi->appData->create($create_params), 'createAppData');
        $response = $batch->execute();

        $this->debug("Response:<br /><pre>" . print_r($response, true) . "</pre>");

        return $response;
    }

    //I'll just get them all... Note that data is stored as an array with key the Netlog user id
    function getAllViewerAppData() {
        $this->debug("getting persistent data for viewer (all keys) ... ");
        $batch = $this->osapi->newBatch();

        // Get the current user's app data
        $app_data_self_params = array(
            'userId' => $this->userId,
            'groupId' => '@self',
            'appId' => $this->appId,
        );

        $batch->add($this->osapi->appData->get($app_data_self_params), 'appdataSelf');
        $response = $batch->execute();

        $this->debug("Response:<br /><pre>" . print_r($response, true) . "</pre>");

        $responseArray = get_object_vars($response['appdataSelf']); //this is an osapiPerson object but we want assoc. array
        $result = isset($responseArray['list'][$this->viewer['id']]) ? $responseArray['list'][$this->viewer['id']] : false;

        return $result;
    }

    function getViewerFriendsAppData($keys) {
        $this->debug("getting persistent data for viewer friends - key : $key ... ");

        if (!is_array($key)) {
            $keys = array($keys);
        }

        $batch = $this->osapi->newBatch();
        // Get the app data for the user's friends
        $app_data_friends_params = array(
            'userId' => $this->userId,
            'groupId' => '@friends',
            'appId' => $this->appId,
            'data' => $keys);
        $batch->add($this->osapi->appData->get($app_data_friends_params), 'appdataFriends');

        $responseArray = get_object_vars($response['appdataFriends']); //this is an osapiPerson object but we want assoc. array
        $result = isset($responseArray['list']) ? $responseArray['list'] : false;

        return $result;
    }

}

?>