<?php

class DeviantartExtension extends Minz_Extension {

    public function install() { return true; }
    public function uninstall() { return true; }

	public function init(): void {
        $this->registerHook('check_url_before_add', array($this, 'deviantartRedirect'));
	}

	public function deviantartRedirect($url) : Url {
		$rx = "/^(?:\w+\:\/\/)?www.deviantart.com\/([^\/]+)\/?.*$/";
		$res = preg_match($rx, $url, $matches);
		if ($res === false) return $url;
		$uname = $matches[1];
		return "https://backend.deviantart.com/rss.xml?q=gallery:$uname";
	}
}
