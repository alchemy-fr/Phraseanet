<?php

namespace Alchemy\Tests\Phrasea\Model\Repositories;

/**
 * @group functional
 * @group legacy
 */
class WebhookEventRepositoryTest extends \PhraseanetTestCase
{
    public function testFindUnprocessedEvents()
    {
        $events = self::$DI['app']['orm.em']->getRepository('Phraseanet:WebhookEvent')->findUnprocessedEvents();
        // I have no clue as to why this magic number is here, probably best to discard test
        $this->assertCount(65, $events);
    }
}
