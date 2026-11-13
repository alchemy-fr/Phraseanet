<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
#[\PHPUnit\Framework\Attributes\Group('authenticated')]
#[\PHPUnit\Framework\Attributes\Group('web')]
class MoveCollectionTest extends \PhraseanetAuthenticatedWebTestCase
{
    protected $client;

    /**
     * Default route test
     */
    public function testRouteSlash()
    {
        self::$DI['client']->request('POST', '/prod/records/movecollection/', ['lst' => self::$DI['record_1']->get_serialize_key()]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testApply()
    {

        self::$DI['client']->request('POST', '/prod/records/movecollection/apply/', ['lst'     => self::$DI['record_1']->get_serialize_key(), 'base_id' => self::$DI['collection']->get_base_id()]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }
}
