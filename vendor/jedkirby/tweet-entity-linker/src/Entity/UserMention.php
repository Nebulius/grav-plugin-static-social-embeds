<?php

namespace Jedkirby\TweetEntityLinker\Entity;

class UserMention extends AbstractEntity
{
    /**
     * @var string
     */
    const ENTITY_KEY = '@';

    /**
     * {@inhertdoc}.
     */
    public function getRequiredProperties()
    {
        return ['screen_name'];
    }

    /**
     * {@inhertdoc}.
     */
    public function getSearchText()
    {
        return self::ENTITY_KEY . $this->data['screen_name'];
    }

    /**
     * {@inhertdoc}.
     */
    public function getHtml()
    {
        return sprintf(
            '%s<a href="https://twitter.com/%s" target="_blank">%s</a>',
            self::ENTITY_KEY,
            $this->data['screen_name'],
            $this->data['screen_name']
        );
    }
}
