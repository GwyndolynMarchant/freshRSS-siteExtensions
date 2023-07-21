<?php

class DanbooruExtension extends Minz_Extension {

	public function init(): void {
        $this->registerHook('entry_before_insert', array($this, 'danbooruFix'));
	}

	public function danbooruFix(FreshRSS_Entry $entry): FreshRSS_Entry {
		
		// Return the entry if it's not a danbooru link
		if (stripos($entry->link(), '://danbooru.donmai.us/posts') === false) { return $entry; }
		
		// Explode the tag list from the content of the feed
		preg_match("/<p>(.*)<\/p>/", $entry->content(), $matches);
		$entry->_tags(explode(" ", $matches[1]));
		
		// Load the actual page
		libxml_use_internal_errors(true);
        $dom = new DOMDocument;
        $dom->loadHTMLFile($entry->link());
        libxml_use_internal_errors(false);
        $xpath = new DOMXpath($dom);

        $html = "Couldn't load page";
        $container = $xpath->query("//section[@id='content']");
        if (!is_null($container)) {
        	$content = $container->item(0);
        	if ($content !== null) {
                $html = $content->ownerDocument->saveHTML($node);
            }

        // Setting the content to the original post + the scraped page content - this is so we have video previews
        $originalHash = $entry->hash();
        $entry->_content($entry->content() . '\n' . $html);
        $entry->_hash($originalHash);

		return $entry;
	}
}
