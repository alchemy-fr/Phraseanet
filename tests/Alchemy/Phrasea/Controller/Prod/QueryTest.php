<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

use Alchemy\Phrasea\Controller\Prod\Query;
use Symfony\Component\HttpFoundation\Request;

class QueryTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Query::queryAnswerTrain
     */
    public function testQueryAnswerTrain()
    {
        $this->markTestSkipped('Unable to create phrasea session');

        //populate phrasea_session
        $auth = new \Session_Authentication_None(self::$DI['user']);
        self::$DI['app']->openAccount($auth);

        $query = new Query();
        $options = new \searchEngine_options();
        $serializedOptions = serialize($options);

        $request = Request::create('/prod/query/answer-train/', 'POST', array(
                'options_serial' => $serializedOptions,
                'pos'            => 1,
                'query'          => 'cats and dogs'
            ));

        $response = $query->queryAnswerTrain(self::$DI['app'], $request);

        $this->assertTrue($response->isOk());
        $datas = (array) json_decode($response->getContent());
        $this->assertArrayHasKey('current', $datas);
        unset($query, $request, $response, $datas);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Query::queryRegTrain
     */
    public function testQueryRegTrain()
    {
        $query = new Query();
        $request = Request::create('/prod/query/reg-train/', 'POST', array(
                'pos'  => 1,
                'cont' => self::$DI['record_story_1']->get_serialize_key()
            ));

        $response = $query->queryRegTrain(self::$DI['app'], $request);
        $this->assertTrue($response->isOk());
        unset($query, $request, $response);
    }
}
