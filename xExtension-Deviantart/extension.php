<?php

class DeviantartExtension extends Minz_Extension {

    public function install() { return true; }
    public function uninstall() { return true; }

	public function init(): void {
        $this->registerHook('check_url_before_add', array($this, 'deviantartRedirect'));
        $this->registerHook('entry_before_insert', array($this, 'deviantartCleanup'));
	}

	public function deviantartRedirect($url) : string {
		if (strpos($url, "deviantart") === false) { return $url; }
		else {
			$rx = "/^(?:\w+\:\/\/)?www.deviantart.com\/([^\/]+)\/?.*$/";
			$res = preg_match($rx, $url, $matches);
			if ($res === false) { return $url; }
			else {
				$uname = $matches[1];
				return "https://backend.deviantart.com/rss.xml?q=gallery:$uname";
			}
		}
	}

	public function deviantartCleanup(FreshRSS_Entry $entry): FreshRSS_Entry {
		$dom = new DOMDocument;
		$dom->loadHTML('<html><head><meta content="text/html; charset=utf-8" http-equiv="Content-Type"/></head><body>' . $entry->content() . '</body></html>');
		$xpath = new DomXpath($dom);

		$elementsToRemove = array();

		foreach ($xpath->query('//img[@alt="thumbnail"]', null, false) as $node) { $elementsToRemove[] = $node; }
		foreach ($xpath->query('//img[contains(@class, "enclosure-thumbnail")]', null, false) as $node) { $elementsToRemove[] = $node; }
		foreach ($xpath->query('//figcaption[contains(@class, "enclosure-description")]', null, false) as $node) { $elementsToRemove[] = $node; }
		foreach ($elementsToRemove as $rem) {
			$rem->parentNode->removeChild($rem);
		}

		// Make the new post
		$originalHash = $entry->hash();
		$entry->_content($dom->saveHTML());
		$entry->_hash($originalHash);

		return $entry;
	}
}
