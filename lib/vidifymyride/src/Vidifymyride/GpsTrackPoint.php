<?php
namespace Vidifymyride;

class GpsTrackPoint {
	/**
	 * Longitude of point as a decimal
	 * @var float
	 */
	private $lon;
	
	/**
	 * Latitude of point as a decimal
	 * @var float
	 */
	private $lat;
	
	/**
	 * Elevation in meters
	 * @var float
	 */
	private $elevation;
	
	/**
	 * Unix timestamp of point
	 * @var long
	 */
	private $timestamp;
	
	public function __construct($lon, $lat, $elevation, $datetime) {
		$this->lon = $lon;
		$this->lat = $lat;
		$this->elevation = $elevation;
		$this->timestamp = strtotime($datetime);
	}
	
	public function getLatitude() {
		return $this->lat;
	}
	
	public function getLongitude() {
		return $this->lon;
	}
	
	public function getTimestamp() {
		return $this->timestamp;
	}
	
	/**
	 * @return float Elevation (in meters)
	 */
	public function getElevation() {
		return $this->elevation;
	}
}