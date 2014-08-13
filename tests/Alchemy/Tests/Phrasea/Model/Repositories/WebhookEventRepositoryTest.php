<?php

namespace Alchemy\Tests\Phrasea\Model\Repositories;

class WebhookEventRepositoryTest extends \PhraseanetTestCase
{
    public function testFindUnprocessedEvents()
    {
        $events = self::$DI['app']['EM']->getRepository('Phraseanet:WebhookEvent')->findUnprocessedEvents();
        $this->assertCount(1, $events);
    }
}
