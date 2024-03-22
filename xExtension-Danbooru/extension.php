<?php

class DanbooruExtension extends Minz_Extension {

    public function install() { return true; }
    public function uninstall() { return true; }

	public function init(): void {
        $this->registerHook('entry_before_insert', array($this, 'danbooruAPI'));
	}

	public function danbooruAPI(FreshRSS_Entry $entry): FreshRSS_Entry {
		
		// Return the entry if it's not a danbooru link
		if (stripos($entry->link(), '://danbooru.donmai.us/posts') === false) { return $entry; }

		$content = "<p style='background: pink; color: red; font-weight: bold;'>ERROR</p>";

		// Get the json info for the post
        $ch = curl_init($entry->link() . ".json");
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);

		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $responseJSON = curl_exec($ch);

        if(curl_error($ch) || $httpcode != 200) {
    		$comment = "Response " . $httpcode . " -- " . curl_errno($ch) . " : " . curl_error($ch) . "<br/>" . $responseJSON;
		} else {
	        $response = json_decode($responseJSON, true);
	        $file = $response["file_url"];
	        $ext = $response["file_ext"];
	        
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
				$entry->_tags(explode(" ", $response["tag_string"]));

				// Get artist commentary
				$query = 'https://danbooru.donmai.us/artist_commentaries.json?search[post_id]=' . $response["id"];
				$ch = curl_init($query);
				curl_setopt($ch, CURLOPT_HEADER, 0);
		        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,0);
		        curl_setopt($ch, CURLOPT_USERAGENT , "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)");
				curl_setopt($ch, CURLOPT_FRESH_CONNECT,true);
				$responseJSON = curl_exec($ch);

				$response = json_decode($responseJSON, true)[0];
				if (empty($response["translated_description"])) {
					$comment = "<h2>" . $response["original_title"] . "</h2><p>" . $response["original_description"] . "</p>";
				} else {
					$comment = "<h2 alt='Translated Title'>" . $response["translated_title"] . "</h2><p>" . $response["translated_description"] . "</p>";
				}
			}
		}

		// Make the new post
		$originalHash = $entry->hash();
		$entry->_content($content . $comment);
		$entry->_hash($originalHash);

		return $entry;
	}
}
