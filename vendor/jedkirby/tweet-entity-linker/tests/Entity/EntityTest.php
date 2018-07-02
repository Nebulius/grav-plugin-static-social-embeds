<?php

namespace Jedkirby\TweetEntityLinker\Tests\Entity;

use Jedkirby\TweetEntityLinker\Tests\Entity\Fixtures\InvalidProperties;
use Jedkirby\TweetEntityLinker\Tests\Entity\Fixtures\NoRequiredProperties;
use PHPUnit_Framework_TestCase as TestCase;

class EntityTest extends TestCase
{
    /**
     * @test
     * @expectedException \Jedkirby\TweetEntityLinker\Entity\Exception\RequiredPropertyException
     */
    public function itMustHaveRequiredProperties()
    {
        $entity = new InvalidProperties();
    }

    /**
     * @test
     */
    public function itDoesNotNeedToHaveRequiredProperties()
    {
        $entity = new NoRequiredProperties();
    }
}
