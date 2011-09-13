<?php

error_reporting(E_ALL);
ini_set("display_errors", 1);

/*
  --
  -- Table structure for table `OPENSOCIALCLIENT`
  --

  CREATE TABLE IF NOT EXISTS `OPENSOCIALCLIENT` (
  `os_key` varchar(255) NOT NULL,
  `os_value` text NOT NULL,
  `dateadded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `Index_key` (`os_key`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 */

$dbData = Array('host' => 'localhost',
    'user' => 'root',
    'pass' => 'azouz',
    'db' => 'netlog',
    'table' => 'OPENSOCIALCLIENT');
require('class.osapirest3legged.php'); //smooths out working with opensocial php library a bit
include 'randomPicFetcher.php'; //for if you do not know what to fill in as pic url to upload
include 'Header.php';




function printResult($string) {
    debug('<strong>result</strong>: <br /><pre class="result">' . print_r($string, true) . '</pre>');
}

function displayFriend($friend) {
    echo '<div class="friendContainer">';
    echo '<input type="checkbox" name="friendids[]" value="' . $friend['id'] . '" />';
    echo '<img class="friendAvatar" src="' . $friend['thumbnailUrl'] . '" />&nbsp;<a target="new" href="' . $friend['profileUrl'] . '">' . $friend['nickname'] . '</a></div>';
}


$os = new osapiREST($OAUTHKEY, $OAUTHSECRET, $lan, $userid, $dbData, false);

echo  "Done!"
?>