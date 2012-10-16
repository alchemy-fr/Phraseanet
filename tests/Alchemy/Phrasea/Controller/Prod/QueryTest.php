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

        $auth = new \Session_Authentication_None(self::$DI['user']);
        self::$DI['app']->openAccount($auth);

        $options = new \searchEngine_options();
        $serializedOptions = serialize($options);
        self::$DI['record_story_1'];
        $response = self::$DI->request('POST', '/prod/query/answer-train/', array(
            'options_serial' => $serializedOptions,
            'pos'            => 1,
            'query'          => ''
            ));

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
        $request = Request::create('/prod/query/reg-train/', 'POST', array(
                'pos'  => 1,
                'cont' => self::$DI['record_story_1']->get_serialize_key()
            ));

        $response = self::$DI->request('POST', '/prod/query/reg-train/', array(
            'pos'  => 1,
            'cont' => self::$DI['record_story_1']->get_serialize_key()
            ));
        $this->assertTrue($response->isOk());
        unset($request, $response);
    }
}
