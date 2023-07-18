<?php

class DanbooruExtension extends Minz_Extension {

    /**
     * Check if we support working on this entry.
     * We do not want to parse every displayed entry, but only the DILBERT ones ;-)
     *
     * @param FreshRSS_Entry $entry
     * @return bool
     */
    protected function supports($entry)
    {
        if (
            stripos($entry->link(), '://danbooru.donmai.us/posts') === false
        ) {
            return false;
        }
        return true;
    }


	public function init(): void {
		$this->registerHook('entry_before_insert', [$this, 'insertEntry']);
	}

	public function renderEntry(FreshRSS_Entry $entry): FreshRSS_Entry {
		if (!$this->supports($entry)) { return $entry; }

		preg_match("/<p>(.*)<\/p>/", $entry->content(), $matches);
		$entry->_tags(preg_split("/\w/", $matches[1]));
		
		$dom = new DOMDocument;
        $dom->loadHTMLFile($entry->link());
        $xpath = new DOMXpath($dom);
        $comicContainer = $xpath->query("//section[id=\"content\"]");

        if (!is_null($comicContainer)) {
        	$entry->_content($entry->text() . '\n' . $comicContainer->item(0)->ownerDocument->saveHTML($node));
        }

		return $entry;
	}
}
