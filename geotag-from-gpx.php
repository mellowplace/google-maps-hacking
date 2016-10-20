<?php
/**
 * Takes 3 arguments, a GPX file, an MP4 file and a timestamp modifier
 * for the MP4 created time.
 *
 * Based on the timestamp of the MP4 it will find the closest relevant lat/long
 * point then upload the video to youtube with that geotag
 *
 * NOTE - requires "mediainfo" CLI tool to be installed
 */
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FirePHPHandler;

set_include_path(get_include_path() . PATH_SEPARATOR . (__DIR__ . DIRECTORY_SEPARATOR . '/lib'));

require __DIR__ . '/vendor/autoload.php';
require 'functions.php';

// Create the logger
$logger = new Logger('my_logger');
// Now add some handlers
$logger->pushHandler(new StreamHandler(__DIR__.'/debug.log', Logger::DEBUG));

$gpxFile = realpath(getcwd() . DIRECTORY_SEPARATOR . $argv[1]);
$mp4FileGlob = realpath(getcwd()) . DIRECTORY_SEPARATOR . $argv[2];
$timeModifier = $argv[3];
$accessToken = $argv[4];

// GOOGLE Auth
$client = new Google_Client();
$client->setAuthConfig(__DIR__ . '/client_secret.json');
$client->addScope('https://www.googleapis.com/auth/youtube.upload');
$client->setRedirectUri('urn:ietf:wg:oauth:2.0:oob');
$client->setAccessToken($accessToken);
$client->setLogger($logger);

$youtube = new Google_Service_YouTube($client);

$gpx = new SimpleXMLElement(file_get_contents($gpxFile));

date_default_timezone_set('UTC');

$files = glob($mp4FileGlob);

$kmlFile = fopen('vids.kml', 'w');
fwrite($kmlFile, '<?xml version="1.0" encoding="UTF-8"?><kml xmlns="http://www.opengis.net/kml/2.2"><Document><name>Videos</name>');

foreach($files as $f) {
  echo $timeModifier;
  $mp4Time = getMp4TaggedDate($f) + $timeModifier;

  echo "MP4 file {$f} created time found to be: " .
    date("Y-m-d H:i:s", $mp4Time) . "\n";

  $coords = findLatLon($gpx, $mp4Time);

  // upload to Youtube
  $url = uploadYoutube($youtube, $client, 'tour-of-camis', $f, basename($f), $coords['lat'], $coords['lon']);

  writeKmlPlaceholder($kmlFile, basename($f), $url, $coords['lat'], $coords['lon']);
}

$styles = <<<EOT
<Style id='icon-video-normal'>
			<IconStyle>
				<color>ff1427A5</color>
				<scale>1.0</scale>
				<Icon>
					<href>http://www.gstatic.com/mapspro/images/stock/503-wht-blank_maps.png</href>
				</Icon>
			</IconStyle>
			<LabelStyle>
				<scale>0.0</scale>
			</LabelStyle>
		</Style>
		<Style id='icon-video-highlight'>
			<IconStyle>
				<color>ff1427A5</color>
				<scale>1.0</scale>
				<Icon>
					<href>http://www.gstatic.com/mapspro/images/stock/503-wht-blank_maps.png</href>
				</Icon>
			</IconStyle>
			<LabelStyle>
				<scale>1.0</scale>
			</LabelStyle>
		</Style>
		<StyleMap id='icon-video'>
			<Pair>
				<key>normal</key>
				<styleUrl>#icon-video-normal</styleUrl>
			</Pair>
			<Pair>
				<key>highlight</key>
				<styleUrl>#icon-video-highlight</styleUrl>
			</Pair>
		</StyleMap>
EOT;
fwrite($kmlFile, $styles);
fwrite($kmlFile, '</Document></kml>');
fclose($kmlFile);
