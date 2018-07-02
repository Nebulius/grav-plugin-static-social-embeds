<?php

namespace Jedkirby\TweetEntityLinker\Tests\Entity\Fixtures;

use Jedkirby\TweetEntityLinker\Entity\AbstractEntity;

class InvalidProperties extends AbstractEntity
{
    /**
     * {@inhertDoc}.
     */
    public function getRequiredProperties()
    {
        return ['missing', 'required'];
    }

    /**
     * {@inhertDoc}.
     */
    public function getSearchText()
    {
        return '';
    }

    /**
     * {@inhertDoc}.
     */
    public function getHtml()
    {
        return '';
    }
}
