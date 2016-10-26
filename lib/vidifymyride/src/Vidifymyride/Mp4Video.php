<?php
namespace Vidifymyride;

class Mp4Video {
	
	/**
	 * @var \SimpleXMLElement
	 */
	private $xmlInfo = null;
	
	/**
	 * @param string $file path to MP4 file
	 */
	public function __construct($file) {
		
		if(!file_exists($file)) {
			throw new \Exception("Video file: {$file} not found");
		}
		
		$metaXml = array();
		exec("mediainfo --Output='XML' {$file}", $metaXml);
		
		$rawOutput = implode("\n",$metaXml);
		
		$this->xmlInfo = new \SimpleXMLElement($rawOutput);
	}
	
	/**
	 * Get's the timestamp of the tag (which is normally the recorded date)
	 * @return long
	 */
	public function getTagTimestamp() {
		$taggedDate = $this->xmlInfo->xpath("//track[@type='Video']/Tagged_date");
	
		return strtotime($taggedDate[0]);
	}
	
	public function getDuration() {
		$taggedDuration = $this->xmlInfo->xpath("//track[@type='Video']/Duration");
		return $this->durationTagToMillis($taggedDuration[0]);
	}
	
	/**
	 * Converts a GoPro duration tag like "14mn 3s 12ms" to milliseconds
	 * @param string $duration
	 */
	public function durationTagToMillis($duration) {
		$parts = explode(' ', $duration);
		
		$millis = 0;
		
		foreach($parts as $p) {
			if($this->endsWith($p, 'mn')) {
				$millis += intval(substr($p, 0, -2)) * 60 * 1000;
			}
			elseif($this->endsWith($p, 'ms')) {
				$millis += intval(substr($p, 0, -2));
			}
			// the order is important here - must check ends with ms before s
			elseif($this->endsWith($p, 's')) {
				$millis += intval(substr($p, 0, -1)) * 1000;
			}
			
			else {
				throw new \Exception("Cannot parse duration string: {$duration}");
			}
		}
		
		return $millis;
	}
	
	private function endsWith($haystack, $needle)
	{
		$length = strlen($needle);
		if ($length == 0) {
			return true;
		}
	
		return (substr($haystack, -$length) === $needle);
	}
}