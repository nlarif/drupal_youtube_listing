<?php

namespace Drupal\youtube_listing\Controller;

use Drupal\Core\Controller\ControllerBase;
use Google_Client;
use Google_Service_YouTube;

/**
 * Defines YoutubeListingController class.
 * Doc :
 * https://www.domsammut.com/code/php-server-side-youtube-v3-oauth-api-video-upload-guide
 * https://developers.google.com/people/quickstart/php (not used)
 * https://developers.google.com/youtube/v3/code_samples/php#retrieve_my_uploads
 * Tokens : https://console.cloud.google.com/apis/credentials/consent?project=access-playlist-forumfrds
 * 
 */
class YoutubeListingController extends ControllerBase {

  /**
   * Used for Initializing/Getting the refresh_token to avoid logging-in at each time
   */

  public function initToken() {


  if (!file_exists(dirname($_SERVER['SCRIPT_FILENAME']).'/sites/all/libraries/google-api-php-client/vendor/autoload.php')) {
      throw new \Exception('Google API PHP lib is not found');
    }
    /* Then we load Google API*/
    require_once dirname($_SERVER['SCRIPT_FILENAME']).'/sites/all/libraries/google-api-php-client/vendor/autoload.php';  

 session_start();
 
/*
 * You can acquire an OAuth 2.0 client ID and client secret from the
 * {{ Google Cloud Console }} <{{ https://cloud.google.com/console }}>
 * For more information about using OAuth 2.0 to access Google APIs, please see:
 * <https://developers.google.com/youtube/v3/guides/authentication>
 * Please ensure that you have enabled the YouTube Data API for your project.
 */

$OAUTH2_CLIENT_ID = 'xxxxxxxxxxx-xnxxxxxxx.apps.googleusercontent.com';
$OAUTH2_CLIENT_SECRET = 'xxxxxxxxxxxxxx-x';
/* Path used to redirect when getting the token*/
$pathToVideos = '/gettoken';
$redirect = filter_var('https://' . $_SERVER['HTTP_HOST'] . $pathToVideos, FILTER_SANITIZE_URL);

 
$client = new Google_Client();
$client->setClientId($OAUTH2_CLIENT_ID);
$client->setClientSecret($OAUTH2_CLIENT_SECRET);
$client->setScopes('https://www.googleapis.com/auth/youtube');
$client->setRedirectUri($redirect);
$client->setAccessType('offline');
$client->setApprovalPrompt('force');
 
// Define an object that will be used to make all API requests.
$youtube = new Google_Service_YouTube($client);
 
if (isset($_GET['code'])) {
    if (strval($_SESSION['state']) !== strval($_GET['state'])) {
        die('The session state did not match.');
    }
 
    $client->authenticate($_GET['code']);
    $_SESSION['token'] = $client->getAccessToken();
 
}
 
if (isset($_SESSION['token'])) {
    $client->setAccessToken($_SESSION['token']);
}
 
// Check to ensure that the access token was successfully acquired.
if ($client->getAccessToken()) {
    try {
        // Call the channels.list method to retrieve information about the
        // currently authenticated users channel.
        $channelsResponse = $youtube->channels->listChannels('contentDetails', array(
            'mine' => 'true',
        ));
 
        $htmlBody = '';
        foreach ($channelsResponse['items'] as $channel) {
            // Extract the unique playlist ID that identifies the list of videos
            // uploaded to the channel, and then call the playlistItems.list method
            // to retrieve that list.
            $uploadsListId = $channel['contentDetails']['relatedPlaylists']['uploads'];
 
            $playlistItemsResponse = $youtube->playlistItems->listPlaylistItems('snippet', array(
                'playlistId' => $uploadsListId
            ));
 
            $htmlBody .= "<h3>Videos in list $uploadsListId</h3><ul>";
            foreach ($playlistItemsResponse['items'] as $playlistItem) {
                $htmlBody .= sprintf('<li>%s (%s)</li>', $playlistItem['snippet']['title'],
                    $playlistItem['snippet']['resourceId']['videoId']);
            }
            $htmlBody .= '</ul>';
        }
    } catch (Google_ServiceException $e) {
        $htmlBody .= sprintf('<p>A service error occurred: <code>%s</code></p>',
            htmlspecialchars($e->getMessage()));
    } catch (Google_Exception $e) {
        $htmlBody .= sprintf('<p>An client error occurred: <code>%s</code></p>',
            htmlspecialchars($e->getMessage()));
    }
 
    $_SESSION['token'] = $client->getAccessToken();
} else {
    $state = mt_rand();
    $client->setState($state);
    $_SESSION['state'] = $state;
 
    $authUrl = $client->createAuthUrl();
    $htmlBody = "
  <h3>Authorization Required</h3>
  <p>You need to <a href=\"$authUrl\">authorise access</a> before proceeding.<p>";
}
    return [
      '#type' => 'markup',
      '#markup' => $this->t($htmlBody),
    ];
  }



  public function content() {

  
/**
 * Library Requirements
 *
 * 1. Install composer (https://getcomposer.org)
 * 2. On the command line, change to this directory (api-samples/php)
 * 3. Require the google/apiclient library
 *    $ composer require google/apiclient:~2.0
 */




$key = json_decode(file_get_contents(dirname($_SERVER['SCRIPT_FILENAME']).'/sites/all/libraries/google-api-php-client/refresh.json'),TRUE);

if (!file_exists(dirname($_SERVER['SCRIPT_FILENAME']).'/sites/all/libraries/google-api-php-client/vendor/autoload.php')) {
  throw new \Exception('Google API PHP lib is not found');
}
require_once dirname($_SERVER['SCRIPT_FILENAME']).'/sites/all/libraries/google-api-php-client/vendor/autoload.php';  
//require_once dirname($_SERVER['SCRIPT_FILENAME']).'/sites/all/libraries/google-api-php-client/src/Google/Client.php';

/*
 * You can acquire an OAuth 2.0 client ID and client secret from the
 * {{ Google Cloud Console }} <{{ https://cloud.google.com/console }}>
 * For more information about using OAuth 2.0 to access Google APIs, please see:
 * <https://developers.google.com/youtube/v3/guides/authentication>
 * Please ensure that you have enabled the YouTube Data API for your project.
 */
$OAUTH2_CLIENT_ID = 'xxxxxxxxxxx-xnxxxxxxx.apps.googleusercontent.com';
$OAUTH2_CLIENT_SECRET = 'xxxxxxxxxxxxxx-x';
$pathToVideos = '/videos';



try{
  // Client init
  $client = new Google_Client();
  $redirect = filter_var('https://' . $_SERVER['HTTP_HOST'] . $pathToVideos, FILTER_SANITIZE_URL);
  $client->setRedirectUri($redirect);
  $client->setClientId($OAUTH2_CLIENT_ID);
  $client->setAccessType('offline');
  $client->setAccessToken($key);
  $client->setScopes('https://www.googleapis.com/auth/youtube');
  $client->setClientSecret($OAUTH2_CLIENT_SECRET);
  $client->setApprovalPrompt('force');

  if ($client->getAccessToken()) {

      /**
       * Check to see if our access token has expired. If so, get a new one and save it to file for future use.
       */
      if($client->isAccessTokenExpired()) {
          $newToken = $client->getAccessToken(); //had json decode here before
          $client->refreshToken($newToken->refresh_token);    
          file_put_contents(dirname($_SERVER['SCRIPT_FILENAME']).'/sites/all/libraries/google-api-php-client/refresh.json', json_encode($client->getAccessToken()));
      }



// // Define an object that will be used to make all API requests.
$youtube = new Google_Service_YouTube($client);

    $channelsResponse = $youtube->channels->listChannels('contentDetails', array(
      'mine' => 'true',
    ));
     $htmlBody = '
    <label class="wrapper" for="states">Sélectionnez la séance que vous voulez regarder</label>
    <div class="button dropdown"> 
    <select id="playlistSelector"> 
    ';

    foreach ($channelsResponse['items'] as $channel) { 
      // Extract the unique playlist ID that identifies the list of videos
      // uploaded to the channel, and then call the playlistItems.list method
      // to retrieve that list.
      $uploadsListId = $channel['contentDetails']['relatedPlaylists']['uploads'];

      $playlistItemsResponse = $youtube->playlistItems->listPlaylistItems('snippet', array(
        'playlistId' => $uploadsListId
      ));

      $listPlaylists = $youtube->playlists->listPlaylists('snippet,contentDetails,id',array('channelId' => 'UCMjOe4OyTvaqoY04XACxaAw', 'maxResults' => 10));

      //$htmlBody .= "<h3>Videos in list $uploadsListId</h3><ul>";

        foreach ($listPlaylists['items'] as $playlistDefinition) {
          $htmlBody .= sprintf('<option value="%s">%s</option>',$playlistDefinition['id'],$playlistDefinition['snippet']['title']);
        }
        $htmlBody .= sprintf(' 
        </select>
        </div>
        <br/>
        <div class="output">
        ');

        $countPlaylistsForHidden = 0;
        foreach ($listPlaylists['items'] as $playlistDefinition) {
        $playlistItemsResponse = $youtube->playlistItems->listPlaylistItems('snippet', array('playlistId' => $playlistDefinition['id']));
          
        foreach ($playlistItemsResponse['items'] as $playlistItem) {
          if ($countPlaylistsForHidden<1){  
            $htmlBody .= sprintf('<div class="items %s"><p><iframe allow="autoplay; encrypted-media" allowfullscreen="" frameborder="0" height="315" src="https://www.youtube.com/embed/%s" width="560"></iframe></p></div>',$playlistDefinition['id'],$playlistItem['snippet']['resourceId']['videoId']);
        }
        else $htmlBody .= sprintf('<div style="display:none" class="items %s"><p><iframe allow="autoplay; encrypted-media" allowfullscreen="" frameborder="0" height="315" src="https://www.youtube.com/embed/%s" width="560"></iframe></p></div>',$playlistDefinition['id'],$playlistItem['snippet']['resourceId']['videoId']);
        }
        $countPlaylistsForHidden++;
      }
        $htmlBody .= sprintf(' 
        
        </div>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
        <script>
        $(function() {
          $(\'#playlistSelector\').change(function(){
            $(\'.items\').hide();
            $(\'.\' + $(this).val()).show();
          });
        });
        </script>
        ');


      // foreach ($playlistItemsResponse['items'] as $playlistItem) {
      //   //$htmlBody .= sprintf('<li id=%s>   <iframe allow="autoplay; encrypted-media" allowfullscreen="" frameborder="0" height="315" src="https://www.youtube.com/embed/%s" width="560"></iframe></li>', $playlistItem['snippet']['title'],$playlistItem['snippet']['resourceId']['videoId']);
      //   $htmlBody .= sprintf('<li> %s (%s) </li>', $playlistItem['snippet']['title'],$playlistItem['snippet']['resourceId']['videoId']);  
      //   print(json_encode($response));
      //   }
     // $htmlBody .= '</ul>';
    }
}

elseif ($OAUTH2_CLIENT_ID == 'REPLACE_ME') {
  $htmlBody = "
  <h3>Client Credentials Required</h3>
  <p>
    You need to set <code>\$OAUTH2_CLIENT_ID</code> and
    <code>\$OAUTH2_CLIENT_ID</code> before proceeding.
  <p>";
 }

    else{
      // @TODO Log error
      echo 'Problems creating the client';
    }
  }
    catch(Google_Service_Exception $e) {
      print "Caught Google service Exception ".$e->getCode(). " message is ".$e->getMessage();
      print "Stack trace is ".$e->getTraceAsString();
    }catch (Exception $e) {
      print "Caught Google service Exception ".$e->getCode(). " message is ".$e->getMessage();
      print "Stack trace is ".$e->getTraceAsString();
    } 
return [
  '#type' => 'markup',
  '#markup' => $this->t($htmlBody),
];
}
}
 ?>