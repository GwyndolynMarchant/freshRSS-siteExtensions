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
		$dom->loadHTML('<html><head><meta content="text/html; charset=utf-8" http-equiv="Content-Type"></head><body><meta content="text/html; charset=utf-8" http-equiv="Content-Type">' . $entry->content() . '</body></html>');
		$xpath = new DomXpath($dom);

		$elementsToRemove = array();

		foreach ($xpath->query('//img[@alt="thumbnail"]') as $node) { $elementsToRemove[] = $node; }
		foreach ($xpath->query('//img[contains(@class, "enclosure-thumbnail")]') as $node) { $elementsToRemove[] = $node; }
		foreach ($xpath->query('//figcaption[contains(@class, "enclosure-description")]') as $node) { $elementsToRemove[] = $node; }
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
