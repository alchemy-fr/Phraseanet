<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

namespace Alchemy\Phrasea\Controller\Prod;

use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;

class QueryTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Query::query
     */
    public function testQuery()
    {
        $route = '/prod/query/';

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
        $auth = new \Session_Authentication_None(self::$DI['user']);
        self::$DI['app']->openAccount($auth);
        self::$DI['record_24'];

        $options = new SearchEngineOptions();
        $options->onCollections(self::$DI['app']['phraseanet.user']->ACL()->get_granted_base());
        $serializedOptions = $options->serialize();

        self::$DI['client']->request('POST', '/prod/query/answer-train/', array(
            'options_serial' => $serializedOptions,
            'pos'            => 0,
            'query'          => ''
            ));
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
       self::$DI['client']->request('POST', '/prod/query/reg-train/', array(
            'pos'  => 1,
            'cont' => self::$DI['record_story_1']->get_serialize_key()
            ));
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        unset($response);
    }
}
