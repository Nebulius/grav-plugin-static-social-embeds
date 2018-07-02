<?php

namespace Jedkirby\TweetEntityLinker\Entity;

class Hashtag extends AbstractEntity
{
    /**
     * @var string
     */
    const ENTITY_KEY = '#';

    /**
     * {@inhertdoc}.
     */
    public function getRequiredProperties()
    {
        return ['text'];
    }

    /**
     * {@inhertdoc}.
     */
    public function getSearchText()
    {
        return self::ENTITY_KEY . $this->data['text'];
    }

    /**
     * {@inhertdoc}.
     */
    public function getHtml()
    {
        return sprintf(
            '%s<a href="https://twitter.com/hashtag/%s" target="_blank">%s</a>',
            self::ENTITY_KEY,
            $this->data['text'],
            $this->data['text']
        );
    }
}
