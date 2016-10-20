<?php

function getMp4TaggedDate($file) {
  $metaXml = array();
  exec("mediainfo --Output='XML' {$file}", $metaXml);

  $rawOutput = implode("\n",$metaXml);
  $meta = new SimpleXMLElement($rawOutput);

  $taggedDate = $meta->xpath("//track[@type='Video']/Tagged_date");

  return strtotime($taggedDate[0]);
}

function findLatLon(SimpleXMLElement $gpxElement, $time) {
  $points = $gpxElement->trk->trkseg->children();

  $pointDiff = array();

  foreach($points as $p) {
    $pointDiff[abs($time - strtotime($p->time))] = $p;
  }

  ksort($pointDiff);

  foreach($pointDiff as $p) {
    // we only want the first element
    return array(
      'time' => $p->time,
      'lat' => $p->attributes()->lat,
      'lon' => $p->attributes()->lon
    );
  }
}

function writeKmlPlaceholder($kmlFile, $name, $videoUrl, $lat, $lon) {
  $placeholder = <<<END
  <Placemark>
    <name>{$name}</name>
    <description>Youtube video - {$videoUrl}</description>
    <styleUrl>#icon-video</styleUrl>
    <ExtendedData>
      <Data name='gx_media_links'>
        <value>{$videoUrl}</value>
      </Data>
    </ExtendedData>
    <Point>
      <coordinates>{$lon},{$lat},0</coordinates>
    </Point>
  </Placemark>
END;
  fwrite($kmlFile, $placeholder);
}

/**
 * TODO - limit of 50 videos on this function, if there are more it may
 *        think the video has changed when it has not.
 * TODO - "has video changed" is a simple filesize comparison - if we know
 *        how to calculate the etag then this might be a better option
 *
 * @return array ['id' => string|null, 'changed' => bool]
 */
function hasYoutubeVideoChanged(Google_Service_YouTube $youtube,
    Google_Client $client, $mapName, $fileName) {

    $searchResponse = $youtube->search->listSearch('id', array(
        'type' => 'video',
        'q' => "Cycling: ${mapName}",
        'maxResults' => '50',
        'forMine' => true
    ));

    $videoResults = array();
    foreach ($searchResponse['items'] as $searchResult) {
      array_push($videoResults, $searchResult['id']['videoId']);
    }

    $videoIds = join(',', $videoResults);

    # Call the videos.list method to retrieve location details for each video.
    $videosResponse = $youtube->videos->listVideos('snippet,fileDetails', array(
    'id' => $videoIds,
    ));

    $videos = '';

    $videoId = null;
    $changed = true;

    // Display the list of matching videos.
    foreach ($videosResponse['items'] as $videoResult) {
      if(in_array(basename($fileName), $videoResult['snippet']['tags'])) {
        // found the video check file size
        $videoId = $videoResult['id'];
        // not reliable!
        // if($videoResult['fileDetails']['fileSize']==filesize($fileName)) {
            $changed = false;
        // }

        break;
      }
    }

    return array(
      'id' => $videoId,
      'changed' => $changed
    );
}

/**
 * TODO - make it so if the video is changed it just replaces it
 */
function uploadYoutube(Google_Service_YouTube $youtube, Google_Client $client,
    $mapName, $videoFile, $videoName, $lat, $lon) {

    $changed = hasYoutubeVideoChanged($youtube, $client, $mapName, $videoFile);
    if($changed['changed']==false) {
      // video unchanged
      return 'http://www.youtube.com/embed/' . $changed['id'];
    } elseif ($changed['changed']==true && $changed['id']!=null) {
      // delete the old video
      $youtube->videos->delete($changed['id']);
    }

    $snippet = new Google_Service_YouTube_VideoSnippet();
    $snippet->setTitle($videoName);
    $snippet->setDescription("Cycling: ${mapName}");

    // Numeric video category. See
    // https://developers.google.com/youtube/v3/docs/videoCategories/list
    $snippet->setCategoryId("22");
    $snippet->setTags(array(basename($videoFile), $mapName));

    // Set the video's status to "public". Valid statuses are "public",
    // "private" and "unlisted".
    $status = new Google_Service_YouTube_VideoStatus();
    $status->privacyStatus = "unlisted";

    $geoPoint = new Google_Service_YouTube_GeoPoint();
    $geoPoint->setAltitude(1);
    $geoPoint->setLatitude(floatval($lat));
    $geoPoint->setLongitude(floatval($lon));

    $details = new Google_Service_YouTube_VideoRecordingDetails();
    $details->setLocation($geoPoint);
    // Specify the size of each chunk of data, in bytes. Set a higher value for
    // reliable connection as fewer chunks lead to faster uploads. Set a lower
    // value for better recovery on less reliable connections.
    $chunkSizeBytes = 1024 * 1024 * 2; // 2 MB

    // Associate the snippet and status objects with a new video resource.
    $video = new Google_Service_YouTube_Video();
    $video->setSnippet($snippet);
    $video->setStatus($status);
    $video->setRecordingDetails($details);

    // Setting the defer flag to true tells the client to return a request which can be called
    // with ->execute(); instead of making the API call immediately.
    $client->setDefer(true);
    // Create a request for the API's captions.insert method to create and upload a caption.
    $insertRequest = $youtube->videos->insert("status,snippet,recordingDetails", $video);

    // Create a MediaFileUpload object for resumable uploads.
    $media = new Google_Http_MediaFileUpload(
        $client,
        $insertRequest,
        'video/*',
        null,
        true,
        $chunkSizeBytes
    );
    $media->setFileSize(filesize($videoFile));

    $totalChunks = ceil(filesize($videoFile)/$chunkSizeBytes);

    // Read the caption file and upload it chunk by chunk.
    $status = false;
    $handle = fopen($videoFile, "rb");
    $chunkCount = 1;
    while (!$status && !feof($handle)) {
      echo "Uploading chunk {$chunkCount} of {$totalChunks}\n";
      $chunk = fread($handle, $chunkSizeBytes);
      $status = $media->nextChunk($chunk);
      $chunkCount++;
    }

    fclose($handle);

    // If you want to make other calls after the file upload, set setDefer back to false
    $client->setDefer(false);

    return 'http://www.youtube.com/embed/' . $status['id'];
}
