<?php

namespace Jedkirby\TweetEntityLinker\Entity;

/**
 * @codeCoverageIgnore
 */
interface EntityInterface
{
    /**
     * Return properties to validate on loading of the entity.
     *
     * @return array
     */
    public function getRequiredProperties();

    /**
     * Return the text to search for.
     *
     * @return string
     */
    public function getSearchText();

    /**
     * Return a pattern to be used by a regular expression for a search.
     *
     * @return string
     */
    public function getSearchPattern();

    /**
     * Return the text to replace.
     *
     * @return string
     */
    public function getHtml();
}
