<?php
namespace Vidifymyride;

use Vidifymyride\ExtractGpxTrack;
use Vidifymyride\Mp4Video;

class ExtractGpxTrackTest extends \PHPUnit_Framework_TestCase
{
	private static $_assetDir = null;
	
	private $video = null;
	
	/**
	 * Test the full extract functionality
	 */
	public function testExtract() {
		
		// -7200 is -2 hours
		$extract = new ExtractGpxTrack(self::$_assetDir . '/route.gpx', $this->video, -7200);
		$trackPoints = $extract->extractGpxTrack();
		
		$this->assertEquals(37, sizeof($trackPoints), '36 related track points found');
		
		// make sure first point has been identified properly
		$this->assertEquals(2.925424, $trackPoints[0]->getLongitude(), 'First longitude point correct');
		$this->assertEquals(39.7165, $trackPoints[0]->getLatitude(), 'First latitude point correct');
		$this->assertEquals(61, $trackPoints[0]->getElevation(), 'Elevation correct');
		
		
		// make sure the last point is good
		$this->assertEquals(2.9264270, $trackPoints[36]->getLongitude(), 'First longitude point correct');
		$this->assertEquals(39.7152860, $trackPoints[36]->getLatitude(), 'First latitude point correct');
		$this->assertEquals(60.8, $trackPoints[36]->getElevation(), 'Elevation correct');
	}
	
	public function setUp() {
		$this->video = new Mp4Video(self::$_assetDir . '/GOPR0080.MP4');
	}
	
	public static function setUpBeforeClass() {
		self::$_assetDir = realpath(dirname(__FILE__) . '/../assets');
	}
}