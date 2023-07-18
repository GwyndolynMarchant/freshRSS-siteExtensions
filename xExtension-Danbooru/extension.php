<?php

class DanbooruExtension extends Minz_Extension {

    protected function supports($entry)
    {
        return (stripos($entry->link(), '://danbooru.donmai.us/posts') !== false);
    }


	public function init(): void {
        $this->registerHook('entry_before_insert', array($this, 'danbooruFix'));
	}

	public function danbooruFix(FreshRSS_Entry $entry): FreshRSS_Entry {
		if (!$this->supports($entry)) { return $entry; }

		preg_match("/<p>(.*)<\/p>/", $entry->content(), $matches);
		$entry->_tags(explode(" ", $matches[1]));
		
		libxml_use_internal_errors(true);
        $dom = new DOMDocument;
        $dom->loadHTMLFile($entry->link());
        libxml_use_internal_errors(false);
        $xpath = new DOMXpath($dom);
        $content = $xpath->query("//section[@id='content']");

        if (!is_null($content)) {
        	$entry->_content($entry->content() . '\n' . $content->item(0)->ownerDocument->saveHTML($node));
        } else {
        	$entry->_content("We've got a problem!");
        }

		return $entry;
	}

	    /**
     * Embed the Comic image into the entry, if the feed is from Dilbert AND the image can be found in
     * the origin sites content.
     *
     * @param FreshRSS_Entry $entry
     * @return mixed
     */
    public function embedDilbert($entry)
    {
        if (!$this->supports($entry)) { return $entry; }

        libxml_use_internal_errors(true);
        $dom = new DOMDocument;
        $dom->loadHTMLFile($entry->link());
        libxml_use_internal_errors(false);
        $xpath = new DOMXpath($dom);

        $comicContainer = $xpath->query("//div[contains(@class, 'comic-item-container')]");

        if (!is_null($comicContainer)) {
            $comicContainer = $comicContainer->item(0);

            $originalHash = $entry->hash();

            // add some meta info
            $entry->_author($comicContainer->getAttribute('data-creator'));
            $entry->_title($entry->title() . ' - ' . $comicContainer->getAttribute('data-title'));

            // Lets not only focus on the image, but keep the link to the original page as well.
            // This is only fair for Scott, who brings us these famous comic strips since so many years!
            $comicLinks = $xpath->query("//a[@class='img-comic-link']", $comicContainer);
            if (!is_null($comicLinks)) {
                $node = $comicLinks->item(0);

                // the image URL is now started protocol relative, so we need to prepend https, otherwise
                // reading the articles via the API might fail. In a browser you will not see the problem, only
                // when articles are displayed inside an app that does not support these protocol-less URLs.
                $image = $xpath->query("//img", $node);
                if (!is_null($image)) {
                    $image = $image->item(0);
                    if (!is_null($image)) {
                        $source = $image->getAttribute('src');
                        if (strpos($source, '//') === 0) {
                            $image->setAttribute('src', 'https:' . $source);
                        }
                    }
                }

                $iconWithLink = $node->ownerDocument->saveHTML($node);
                $entry->_content($iconWithLink);
            }

            $entry->_hash($originalHash);
        }

        return $entry;
    }
}
