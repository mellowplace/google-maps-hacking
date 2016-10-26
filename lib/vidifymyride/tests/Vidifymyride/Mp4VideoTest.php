<?php
namespace Vidifymyride;

use Vidifymyride\Mp4Video;

class Mp4VideoTest extends \PHPUnit_Framework_TestCase
{
	private static $_assetDir = null;
	
	/**
	 * @var Mp4Video
	 */
	private $video = null;
	
	/**
	 * Test the full extract functionality
	 */
	public function testTagTimestamp() {
		// -7200 is -2 hours
		$this->assertEquals(1476442579, $this->video->getTagTimestamp(), 'Tag timestamp correct');
	}
	
	public function testParseMillis() {
		$this->assertEquals(843000, $this->video->durationTagToMillis('14mn 3s'));
		$this->assertEquals(180003, $this->video->durationTagToMillis('3mn 3ms'));
	}
	
	/**
	 * @expectedException \Exception
	 */
	public function testParseMillisException() {
		$this->video->durationTagToMillis('3un');
	}
	
	public function testDuration() {
		$this->assertEquals(36036, $this->video->getDuration(), 'Duration is correct');
	}
	
	public function setUp() {
		$this->video = new Mp4Video(self::$_assetDir . '/GOPR0080.MP4');
	}
	
	public static function setUpBeforeClass() {
		self::$_assetDir = realpath(dirname(__FILE__) . '/../assets');
	}
}