<?php

class DanbooruExtension extends Minz_Extension {

    public function install() { return true; }
    public function uninstall() { return true; }

	public function init(): void {
        $this->registerHook('entry_before_insert', array($this, 'danbooruFix'));
	}

	public function danbooruFix(FreshRSS_Entry $entry): FreshRSS_Entry {
		
		// Return the entry if it's not a danbooru link
		if (stripos($entry->link(), '://danbooru.donmai.us/posts') === false) { return $entry; }
		
		// Explode the tag list from the content of the feed
		preg_match("/<p>(.*)<\/p>/", $entry->content(), $matches);
		$entry->_tags(explode(" ", $matches[1]));
		
		$html = "Danbooru page recognized but loading failed.";

		// Load the actual page
		libxml_use_internal_errors(true);
        $dom = new DOMDocument;
        $dom->loadHTMLFile($entry->link());
        libxml_use_internal_errors(false);
        if ($dom === false) { $html = "Couldn't load DOM."; }
        else {
        	$content = $dom->getElementById("content");
        	if ($content === null) { $html = "Could not find content in DOM. Full page fallback."; }
        	else { $html = $dom->saveHTML($dom->getElementById("content")); }
    	}

        // Setting the content to the original post + the scraped page content - this is so we have video previews
        $originalHash = $entry->hash();
        $entry->_content($entry->content() . '</br>' . $html);
        $entry->_hash($originalHash);

		return $entry;
	}
}
