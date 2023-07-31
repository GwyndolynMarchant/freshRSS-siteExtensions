<?php

class ESixTwentyOneExtension extends Minz_Extension {

    public function install() { return true; }
    public function uninstall() { return true; }

	public function init(): void {
        $this->registerHook('entry_before_insert', array($this, 'e621API'));
        //$this->registerHook('entry_before_display', array($this, 'e621API'));
	}

	public function e621API(FreshRSS_Entry $entry): FreshRSS_Entry {
		
		// Return the entry if it's not a danbooru link
		if (stripos($entry->link(), 'e621.net/posts') === false) { return $entry; }
		$content = "<p style='background: pink; color: red; font-weight: bold;'>ERROR</p>";

		// Get the json info for the post
		sleep(1);
        $ch = curl_init($entry->link() . ".json");
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,0);
        curl_setopt($ch, CURLOPT_USERAGENT , "FreshRSS-siteExtensions/0.1 (by hellgnoll on e621)");
        curl_setopt($ch, CURLOPT_COOKIESESSION,true);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie); 
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
		curl_setopt($ch, CURLOPT_FRESH_CONNECT,true);
        $responseJSON = curl_exec($ch);

        if(curl_error($ch)) {
    		$comment = curl_error($ch);
		} else {
	        $response = json_decode($responseJSON, true)["post"];
	        $file = $response["file"]["url"];
	        $ext = $response["file"]["ext"];
	        
	        if ($response === false) { $comment = "Could not parse JSON: " . $responseJSON; }
	        else {

		        // Grab the content link
		        $content = "ERROR: Unrecognized content type - " . $ext . "<br/>" . $responseJSON;
		        if (in_array($ext, array("jpg", "jpeg", "png", "gif", "bmp", "webp"))) {
		        	// Picture
		        	$content = "<img src='$file'>";
		        } elseif (in_array($ext, array("mp4", "mov", "webm", "mkv", "ogg" ))) {
		        	// Video
		        	$content = "<video controls><source src='$file' type='video/$ext'></video>";
		        } 

		        // Explode the tag list
				$entry->_tags(array_merge(...array_values($response["tags"])));
				$comment = "<p>" . $response["description"] . "</p>";
			}
		}

		// Make the new post
		$originalHash = $entry->hash();
		$entry->_content($content . $comment);
		$entry->_hash($originalHash);

		return $entry;
	}
}
