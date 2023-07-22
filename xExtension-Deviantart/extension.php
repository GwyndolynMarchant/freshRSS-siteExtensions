<?php

class DeviantartExtension extends Minz_Extension {

    public function install() { return true; }
    public function uninstall() { return true; }

	public function init(): void {
        $this->registerHook('check_url_before_add', array($this, 'deviantartRedirect'));
        $this->registerHook('entry_before_display', array($this, 'deviantartCleanup'));
	}

	public function deviantartRedirect($url) : string {
		$rx = "/^(?:\w+\:\/\/)?www.deviantart.com\/([^\/]+)\/?.*$/";
		$res = preg_match($rx, $url, $matches);
		if ($res === false) return $url;
		$uname = $matches[1];
		return "https://backend.deviantart.com/rss.xml?q=gallery:$uname";
	}

	public function deviantartCleanup(FreshRSS_Entry $entry): FreshRSS_Entry {
		$dom = new DOMDocument;
		$dom->loadHTML($entry->content());
		$xpath = new DomXpath($dom);

		foreach ($xpath->query('//img[@alt="thumbnail"]') as $node) {
    		$node->parentNode->removeChild($node);
		}

		foreach ($xpath->query('//img[has(@class, "enclosure-thumbnail")]') as $node) {
    		$node->parentNode->removeChild($node);
		}

		foreach ($xpath->query('//figcaption[has(@class, "enclosure-description")]') as $node) {
    		$node->parentNode->removeChild($node);
		}

		// Make the new post
		$originalHash = $entry->hash();
		$entry->_content($dom->saveHTML());
		$entry->_hash($originalHash);

		return $entry;
	}
}
