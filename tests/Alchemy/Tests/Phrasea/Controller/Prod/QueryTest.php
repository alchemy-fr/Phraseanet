<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

namespace Alchemy\Phrasea\Controller\Prod;

use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;

class QueryTest extends \PhraseanetAuthenticatedWebTestCase
{

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Query::query
     */
    public function testQuery()
    {
        if (!extension_loaded('phrasea2')) {
            $this->markTestSkipped('Phrasea2 is required for this test');
        }
        $route = '/prod/query/';

        self::$DI['app']['manipulator.user'] = $this->getMockBuilder('Alchemy\Phrasea\Model\Manipulator\UserManipulator')
            ->setConstructorArgs([self::$DI['app']['model.user-manager'], self::$DI['app']['auth.password-encoder'], self::$DI['app']['geonames.connector'], self::$DI['app']['repo.users'], self::$DI['app']['random.low']])
            ->setMethods(['logQuery'])
            ->getMock();

        self::$DI['app']['manipulator.user']->expects($this->once())->method('logQuery');

        self::$DI['client']->request('POST', $route);

        $response = self::$DI['client']->getResponse();

        $this->assertEquals('application/json', $response->headers->get('Content-type'));
        $data = json_decode($response->getContent(), true);
        $this->assertInternalType('array', $data);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Query::queryAnswerTrain
     */
    public function testQueryAnswerTrain()
    {
        if (!extension_loaded('phrasea2')) {
            $this->markTestSkipped('Phrasea2 is required for this test');
        }
        $this->authenticate(self::$DI['app']);
        self::$DI['record_2'];

        $options = new SearchEngineOptions();
        $options->onCollections(self::$DI['app']['acl']->get(self::$DI['app']['authentication']->getUser())->get_granted_base());
        $serializedOptions = $options->serialize();

        self::$DI['client']->request('POST', '/prod/query/answer-train/', [
            'options_serial' => $serializedOptions,
            'pos'            => 0,
            'query'          => ''
            ]);
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        $datas = (array) json_decode($response->getContent());
        $this->assertArrayHasKey('current', $datas);
        unset($response, $datas);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Query::queryRegTrain
     */
    public function testQueryRegTrain()
    {
       self::$DI['client']->request('POST', '/prod/query/reg-train/', [
            'pos'  => 1,
            'cont' => self::$DI['record_story_1']->get_serialize_key()
            ]);
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        unset($response);
    }
}
