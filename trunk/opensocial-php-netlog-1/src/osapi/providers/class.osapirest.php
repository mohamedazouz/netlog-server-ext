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
*/

require_once "opensocial-php/src/osapi/osapi.php"; // Require the osapi library

class osapiREST {
	
	//token key and secret should be provided by Netlog
	function __construct($tokenkey, $tokensecret)
	{
		$this->showDebug = true; //echo debugs within this class
		osapiLogger::setEchoLogs(false); //echo logs from php client (handy for seeing requests + responses)

		$this->tokenkey = $tokenkey;
		$this->tokensecret = $tokensecret;
		
		date_default_timezone_set('Europe/London'); // Set the default timezone since many servers won't have this configured (NOTE: where is this needed?)
		
		ini_set('error_reporting', E_ALL | E_STRICT); 		// Report everything, better to have stuff break here than in production
		
		set_include_path(get_include_path() . PATH_SEPARATOR . '..'); // Add the osapi directory to the include path
		
		// Enable logger. (careful: in my version of opensocial php library logging is replaced by echo's)
		osapiLogger::setLevel(osapiLogger::INFO);
		osapiLogger::setAppender(new osapiFileAppender("/tmp/logs/osapi.log"));
		$this->storage = new osapiFileStorage('/tmp/osapi'); //for logging
	
		$osapi = false;
		$strictMode = false;
		$this->userId = '@me';
		$this->appId = '@app'; //'@app';
		
		// Create an identifier for the local user's session
		session_start();
		$localUserId = 007;
	    
		$this->provider = new osapiNetlogProvider(null, "http://woutersmet.staging.comcore.be");
		$this->provider->rpcEndpoint = null; //to use REST endpoint instead of rpc endpoint (which is default)
	  	$this->osapi = new osapi($this->provider, new osapiOAuth2Legged($this->tokenkey, $this->tokensecret));
	  	
	  	/* 3-legged case would be: 
	  	$this->auth = osapiOAuth3Legged::performOAuthLogin($this->tokenkey, $this->tokensecret, $this->storage, $this->provider, $this->localUserId);
		$osapi = new osapi($this->provider, $this->auth);
		*/
		
		$this->viewer = $this->getViewer();
		$this->debug("Viewer: ".$this->viewer['nickname']);		
	}
	
	function debug($string)
	{
		if ($this->showDebug)
		{
			echo '<div style="color:#333;background-color:white;">'.$string . '<br /></div>';
		}
	}
	
	function getViewer()
	{
		$this->debug( 'getting viewer info...');
		
		$batch = $this->osapi->newBatch();
		
	    $profile_fields = array(
	        'aboutMe',
	        'displayName',
	        'thumbnailUrl',
    		//'bodyType',
    		//'gender',
    		//'drinker',
    		'interests',
	        'currentLocation',
	        );
		
		//Fetch the current user.
  		$self_request_params = array(
 			'userId' =>  $this->userId,        // Person we are fetching.
  			 'groupId' => '@self',             // @self for one person.
 			 'fields' => $profile_fields       // Which profile fields to request.
 			 );
        
        $batch->add($this->osapi->people->get($self_request_params), 'self');
		$response = $batch->execute();
		
		$this->debug("Response:<br /><pre>" . print_r($response,true) . "</pre>");
		
		$result = get_object_vars($response['self']); //this is an osapiPerson object but we want assoc. array
		
		$this->debug("Friends result:<pre>" . print_r($result,true) . "</pre>");
		return $result;
	}
	
	
	function getViewerFriends($count = 10, $startIndex=0) //inspired by /examples/listfriends.php
	{
		$this->debug('getting viewer friends...');
		
		$friend_count = $count;
		 
		 // Start a batch so that many requests may be made at once.
  		$batch = $this->osapi->newBatch();

		$profile_fields = array(
	        'aboutMe',
	        'displayName',
	        'thumbnailUrl',
    		'bodyType',
    		'gender',
    		'sexualorientation',
    		'smoker',
    		'urls',
    		'status',
    		'relationshipstatus',
    		//'lookingfor',
    		//'drinker',
    		//'children',
    		'interests',
	        'currentLocation',
	        );
  		
  		$friends_request_params = array(
	      'userId' =>  $this->userId,             // Person whose friends we are fetching.
	      'groupId' => '@friends',          // @friends for the Friends group.
	      'fields' => $profile_fields,      // Which profile fields to request.
	      'count' => $friend_count          // Max friends to fetch.
	     );

		$batch->add($this->osapi->people->get($friends_request_params), 'friends');
		
		$response = $batch->execute();
		
		$this->debug("Response:<br /><pre>" . print_r($response,true) . "</pre>");
		
		$result = $response['friends']->list;
		
		foreach ($result as &$friend)
		{
			$friend = get_object_vars($friend); //convert osapiPersonObjects to arrays	
		}
		
		$this->debug("Friends result:<pre>" . print_r($result,true) . "</pre>");
		return $result;
	}
	
	//Netlog returns at most 75 friends
	public function getAllViewerFriends()
	{
		$this->debug("getting all viewer friends...");
		
		$count = 1;
		$index = 0;
		$countPerFetch = 50;
		
		$allFriends = $this->getViewerFriends($countPerFetch, $index);
		$lastCount = count($allFriends);
		$currentIteration = 0;
		$maxIterations = 10;
		while ($lastCount > 0)
		{
			if ($currentIteration >= $maxIterations)
			{
				$this->debug("Reached max of $maxIterations iterations! Stopping loop...");
				break;	
			}
			
			$currentBatch = $this->getViewerFriends($countPerFetch, $index);
			$allFriends = array_merge($allFriends, $currentBatch);
			$index += $countPerFetch;
			$currentIteration ++; //could connect this to index and vice versa	
			$lastCount = count($currentBatch);
			$this->debug("Last count: $lastCount ...");
		}
		
		return $allFriends;
	}
	
	
	function postViewerActivity($title, $body = '')
	{
		$this->debug('posting activity...');
		
		$batch = $this->osapi->newBatch();
		$activity = new osapiActivity();
		$activity->setField('title', 'osapi test activity at ' . time());
		$activity->setField('body', 'osapi test activity body');
		
		$create_params = array(
		  'userId' => $this->userId,
		  'groupId' => '@self',
		  'activity' => $activity,
		  'appId' => $this->appId
		);
		
		$batch->add($osapi->activities->create($create_params), 'createActivity');
		$response = $batch->execute();
		
		$this->debug("Response:<br /><pre>" . print_r($response,true) . "</pre>");
		
		return $response;		
	}
	
	//$userIds: int or array with ints of recipient userids
	function sendNotification($userIds, $title, $body)
	{
		if(!is_array($userIds))
		{
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
		$this->debug("Response:<br /><pre>" . print_r($response,true) . "</pre>");
		
		return $response;
	}
		
		function getViewerPhotos()
		{
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
		  $this->debug("Response:<br /><pre>" . print_r($response,true) . "</pre>");
		  
		  $result = $response['get_mediaItems']->list;
		
		foreach ($result as &$photo)
		{
			$photo = get_object_vars($photo); //convert osapiMediaItem objects to arrays
		}
		
		$this->debug("Photos result:<pre>" . print_r($result,true) . "</pre>");
		 return $result;
		}
}
?>