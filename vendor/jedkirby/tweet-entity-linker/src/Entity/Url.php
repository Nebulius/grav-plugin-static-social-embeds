<?php

namespace Jedkirby\TweetEntityLinker\Entity;

class Url extends AbstractEntity
{
    /**
     * {@inhertdoc}.
     */
    public function getRequiredProperties()
    {
        return ['url', 'display_url'];
    }

    /**
     * {@inhertdoc}.
     */
    public function getSearchText()
    {
        return $this->data['url'];
    }

    /**
     * {@inhertdoc}.
     */
    public function getHtml()
    {
        return sprintf(
            '<a href="%s" target="_blank">%s</a>',
            $this->data['url'],
            $this->data['display_url']
        );
    }
}
