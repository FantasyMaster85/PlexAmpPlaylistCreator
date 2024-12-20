<?php





/* ----------------START USER DEFINED VARIABLES SECTION---------------------------
----------------------------------------------------------------------------------
----------------------------------------------------------------------------------*/


/* NOTE - THIS MAY NOT WORK IF YOU DON'T ALLOW INSECURE CONNECTIONS TO YOUR PLEX SERVER, I ALSO HAVEN'T TESTED THIS RUNNING THIS SCRIPT OUTSIDE THE LAN THE PLEX SERVER RESIDES ON */






/* PLEX SERVER IP AND PORT - must be in this format IP:PORT - NO TRAILING SLASH, NO HTTP://
IP address and port listed are just for example, please make sure to correct it
*/
$plexServerIP = "192.168.1.1:32400";




/* Your Plex token */
$xPlexToken = "TOKENHEREINSIDETHEQUOTES";




/* The playlist name that the script will be creating/deleting "on the fly"  */
$playlistName = "Automagically Created";





/* PLEXAMP PLAYER IP AND PORT - must be in this format IP:PORT - NO TRAILING SLASH, NO HTTP://

This can be retrieved by opening PlexAmp on the device you want to have the "current play queue" turned into a playlist.  You open PlexAmp on that device, open "settings" by selecting the "gear wheel", then selecting "playback" and lastly selecting "remote control".  The IP and Port will be listed there as long as it's enabled.

The IP address MAY OR MAY NOT BE the same as your Plex server.  It's critical that whatever IP address you put here, is the IP address of the PLEXAMP PLAYER that will be playing back the music.  So for example, if you have PlexAmp installed on your phone and on your desktop, and and you open PlexAmp on your phone and choose to "cast to" / "control" your Desktop PlexAmp installation from your phone, and on your phone you then choose "library radio" so that "library radio" begins playback on your Desktop PlexAmp installation, then the IP address here should be that of your Desktop.  What would then happen, is when this script is triggered, it will grab the current play queue of PlexAmp on that desktop PlexAmp installation, and turn it into a playlist called "automagically created" or whatever the name you've set is. */
$plexampPlayerIpPort = "192.168.1.1:32500";










/* ---------------- END USER DEFINED VARIABLES SECTION -----------------------------
------------------------------------------------------------------------------------
------------------------------------------------------------------------------------
*/








/* --------------------  EVERYTHING BELOW IS THE WORKING CODE - SHOULD NOT BE MODIFIED UNLESS YOU WANT TO MODIFY FUNCTIONALITY ------------------- */












function getMachineID($plexampPlayerIpPort) {
    // Define the URL for the GET request
    $url = "http://$plexampPlayerIpPort/player/timeline/poll?wait=0&includeMetadata=1&commandID=2&type=music";

    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);

    // Execute the cURL request and store the response
    $response = curl_exec($ch);

    // Check for cURL errors
    if (curl_errno($ch)) {
        echo 'cURL error: ' . curl_error($ch);
        return null;
    }

    // Close the cURL session
    curl_close($ch);

    // Parse the XML response
    $xml = simplexml_load_string($response);
    if ($xml === false) {
        echo "Failed to parse XML.";
        return null;
    }

    // Extract the machineIdentifier value from the XML
    foreach ($xml->Timeline as $timeline) {
        if (isset($timeline['machineIdentifier'])) {
            return (string)$timeline['machineIdentifier'];
        }
    }

    // If no machineIdentifier is found, return null
    echo "No machineIdentifier found in the response.";
    return null;
}


// Function to check if user defined playlist exists and return its rating key
function getPlaylistRatingKey($url, $playlistName) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'cURL error: ' . curl_error($ch);
        exit;
    }

    curl_close($ch);

    $xml = simplexml_load_string($response);
    if ($xml === false) {
        echo "Failed to parse XML.";
        exit;
    }

    foreach ($xml->Playlist as $playlist) {
        if (strcasecmp((string)$playlist['title'], $playlistName) === 0) {
            return (string)$playlist['ratingKey'];
        }
    }
    return null;
}

// Function to delete defined playlist by rating key
function deletePlaylist($ratingKey, $plexServerIP, $xPlexToken) {
    $url = "http://$plexServerIP/playlists/$ratingKey?X-Plex-Token=$xPlexToken";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'cURL error during DELETE request: ' . curl_error($ch);
        exit;
    }

    curl_close($ch);
}

// Function to create a new user defined playlist and return its rating key
function createPlaylist($plexServerIP, $xPlexToken, $machineID, $playlistName) {
	$playlistNameEncoded = urlencode($playlistName);
    $url = "http://$plexServerIP/playlists?title=$playlistNameEncoded&smart=0&type=audio&uri=server%3A%2F%2F$machineID%2Fcom.plexapp.plugins.libraryundefined&includeFields=thumbBlurHash";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "accept: application/json",
        "accept-encoding: gzip, deflate, br, zstd",
        "accept-language: en-US,en;q=0.9",
        "connection: keep-alive",
        "content-length: 0",
        "x-plex-token: $xPlexToken",
    ]);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'cURL error during POST request: ' . curl_error($ch);
        exit;
    }

    curl_close($ch);
}

// Main logic
$queryUrl = "http://$plexServerIP/playlists?playlistType=audio&includeCollections=1&includeExternalMedia=1&includeAdvanced=1&includeMeta=1&X-Plex-Token=$xPlexToken";
$ratingKey = getPlaylistRatingKey($queryUrl, $playlistName);

if ($ratingKey !== null) {
    echo "Found playlist to delete with rating key: $ratingKey. Deleting...\n";
    deletePlaylist($ratingKey, $plexServerIP, $xPlexToken);
}

echo "Creating playlist...\n";
$machineID = getMachineID($plexampPlayerIpPort);
$newRatingKey = createPlaylist($plexServerIP, $xPlexToken, $machineID, $playlistName);
$newRatingKey =  getPlaylistRatingKey($queryUrl, $playlistName);

echo "Newly created playlist rating key: $newRatingKey\n";


function getPlayQueueID($plexampPlayerIpPort) {
    // Define the URL for the GET request
    $url = "http://$plexampPlayerIpPort/player/timeline/poll?wait=0&includeMetadata=1&commandID=2&type=music";

    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);

    // Execute the cURL request and store the response
    $response = curl_exec($ch);

    // Check for cURL errors
    if (curl_errno($ch)) {
        echo 'cURL error: ' . curl_error($ch);
        return null;
    }

    // Close the cURL session
    curl_close($ch);

    // Parse the XML response
    $xml = simplexml_load_string($response);
    if ($xml === false) {
        echo "Failed to parse XML.";
        return null;
    }

    // Extract the playQueueID value from the XML
    foreach ($xml->Timeline as $timeline) {
        if (isset($timeline['playQueueID'])) {
            return (string)$timeline['playQueueID'];
        }
    }

    // If no playQueueID is found, return null
    echo "No playQueueID found in the response.";
    return null;
}

function addToPlaylist($newRatingKey, $playQueueID, $plexServerIP, $xPlexToken) {
    // Define the URL for the PUT request
    $url = "http://$plexServerIP/playlists/$newRatingKey/items?playQueueID=$playQueueID&includeFields=thumbBlurHash";

    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "accept: application/json",
        "accept-encoding: gzip, deflate, br, zstd",
        "accept-language: en-US,en;q=0.9",
        "connection: keep-alive",
        "content-length: 0",
        "x-plex-token: $xPlexToken",
    ]);

    // Execute the cURL request
    $response = curl_exec($ch);

    // Check for cURL errors
    if (curl_errno($ch)) {
        echo 'cURL error during PUT request: ' . curl_error($ch);
        return false;
    }

    // Close the cURL session
    curl_close($ch);

    // Return the response for debugging or confirmation
    return $response;
}

//begin 
$playQueueID = getPlayQueueID($plexampPlayerIpPort);
if ($playQueueID !== null) {
    $result = addToPlaylist($newRatingKey, $playQueueID, $plexServerIP, $xPlexToken);
    if ($result) {
        echo "Successfully added to playlist\n";
    } else {
        echo "Failed to add to playlist.";
    }
} else {
    echo "PlayQueueID not found. Cannot add to playlist.";
}

?>
