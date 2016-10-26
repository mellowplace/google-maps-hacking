<?php
namespace Vidifymyride;

/**
 * Gets the GPX track from a GPX file related to the video.
 */
class ExtractGpxTrack {

  private $gpxFile = null;

  /**
   * @var Mp4Video
   */
  private $video = null;

  /**
   * The difference (in milliseconds) between the video time and the GPX
   * track time
   *
   * @var long
   */
  private $timeDrift = 0;
  
  /**
   * @param string $gpxFile Path to the GPX file
   * @param Mp4Video $video The MP4 file
   * @param long $timeDrift The difference between the video time and the GPX tracks (default 0)
   */
  public function __construct($gpxFile, Mp4Video $video, $timeDrift=0) {
    $this->gpxFile = $gpxFile;
    $this->video = $video;
    $this->timeDrift = $timeDrift;
  }
  
  /**
   * @return GpsTrackPoint[] Array of GpsTrackPoint objects relevant to the given video
   */
  public function extractGpxTrack() {
  	$recordedTimestamp = $this->video->getTagTimestamp();
  	
  	$gpx = new \SimpleXMLElement(file_get_contents($this->gpxFile));
  	$points = $this->findLatLon($gpx, $recordedTimestamp + $this->timeDrift, $this->video->getDuration());
  	
  	return $points;
  }
  
  private function findLatLon(\SimpleXMLElement $gpxElement, $time, $durationMillis) {
  	$points = $gpxElement->trk->trkseg->children();
  
  	$pointDiff = array();
  
  	foreach($points as $p) {
  		$pointDiff[abs($time - strtotime($p->time))] = $p;
  	}
  
  	$firstPoint = null;
  	ksort($pointDiff);
  
  	foreach($pointDiff as $p) {
  		// we only want the first element
  		$firstPoint = $p;
  		break;
  	}
  	
  	$inTrack = false;
  	
  	$tracks = array(
  			new GpsTrackPoint(
  					floatval($firstPoint->attributes()->lon),
  					floatval($firstPoint->attributes()->lat),
  					floatval($firstPoint->ele),
  					$firstPoint->time
  			)
  	);
  	
  	foreach($points as $p) {
  		if($p==$firstPoint) {
  			$inTrack = true;
  			continue;
  		}
  		
  		if($inTrack) {
  			if((strtotime($p->time) * 1000) <= ($tracks[0]->getTimestamp() * 1000) + $durationMillis) {
  				$tracks[] = new GpsTrackPoint(
	  					floatval($p->attributes()->lon),
	  					floatval($p->attributes()->lat),
	  					floatval($p->ele),
	  					$p->time
	  			);
  			}
  			else {
  				break; // gone past the track info
  			}
  		}
  	}
  	
  	return $tracks;
  }
}
