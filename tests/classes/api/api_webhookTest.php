<?php

use Alchemy\Phrasea\Exception\RuntimeException;

class API_WebhookTest extends \PhraseanetTestCase
{
    public function testsNewApiHook()
    {
        $w = \API_Webhook::create(self::$DI['app']['phraseanet.appbox'], 'new_feed', array('w1', 'salut' => 'you'));
        $this->assertInstanceOf('\API_webhook', $w);
        $w->delete();
    }

    public function testNewApiHookObjectNotFound()
    {
        try {
            $w = new \API_Webhook(self::$DI['app']['phraseanet.appbox'], -1);
            $this->fail('It should raise an exception');
        } catch (RuntimeException $e) {

        }
    }
}
