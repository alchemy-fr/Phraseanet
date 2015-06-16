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
        $this->assertCount(1, $events);
    }
}
