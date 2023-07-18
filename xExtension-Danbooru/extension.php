<?php

class DanbooruExtension extends Minz_Extension {

    protected function supports($entry)
    {
        return (stripos($entry->link(), '://danbooru.donmai.us/posts') !== false);
    }


	public function init(): void {
        $this->registerHook('entry_before_insert', array($this, 'danbooruFix'));
        $this->registerHook('entry_before_display', array($this, 'danbooruFix'));
	}

	public function danbooruFix(FreshRSS_Entry $entry): FreshRSS_Entry {
		if (!$this->supports($entry)) { return $entry; }

		preg_match("/<p>(.*)<\/p>/", $entry->content(), $matches);
		$entry->_tags(explode(" ", $matches[1]));
		
		$dom = new DOMDocument;
        $dom->loadHTMLFile($entry->link());
        $xpath = new DOMXpath($dom);
        $content = $xpath->query("//section[id=\"content\"]");

        if (!is_null($content)) {
        	$entry->_content($entry->content() . '\n' . $content->item(0)->ownerDocument->saveHTML($node));
        } else {
        	$entry->_content("We've got a problem!");
        }

		return $entry;
	}
}
