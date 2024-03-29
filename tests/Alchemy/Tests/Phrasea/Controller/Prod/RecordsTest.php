<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Border\File;
use Alchemy\Phrasea\Model\Entities\Basket;
use Alchemy\Phrasea\Model\Entities\BasketElement;
use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;

/**
 * @group functional
 * @group legacy
 * @group authenticated
 * @group web
 * @todo Test Alchemy\Phrasea\Controller\Prod\Export::exportMail
 */
class RecordsTest extends \PhraseanetAuthenticatedWebTestCase
{
    protected $client;

    public function testWhatCanIDelete()
    {
        self::$DI['client']->request('POST', '/prod/records/delete/what/', ['lst'     => self::$DI['record_1']->get_serialize_key()]);
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        unset($response);
    }

    public function testDoDeleteRecords()
    {
        $file = new File(self::$DI['app'], self::$DI['app']['mediavorus']->guess(__DIR__ . '/../../../../../files/cestlafete.jpg'), self::$DI['collection']);
        $record = \record_adapter::createFromFile($file, self::$DI['app']);
        $randomValue = $this->setSessionFormToken('prodDeleteRecord');

        $response = $this->XMLHTTPRequest('POST', '/prod/records/delete/', ['lst' => $record->getId(), 'prodDeleteRecord_token'  => $randomValue]);

        $datas = (array) json_decode($response->getContent());
        $this->assertContains($record->getId(), $datas);
        try {
            new \record_adapter(self::$DI['app'], $record->getDataboxId(), $record->getRecordId());
            $this->fail('Record not deleted');
        } catch (\Exception $e) {

        }
    }

    public function testRenewUrl()
    {
        $file = new File(self::$DI['app'], self::$DI['app']['mediavorus']->guess(__DIR__ . '/../../../../../files/cestlafete.jpg'), self::$DI['collection']);
        $record = \record_adapter::createFromFile($file, self::$DI['app']);
        $response = $this->XMLHTTPRequest('POST', '/prod/records/renew-url/', ['lst' => $record->getId()]);
        $datas = (array) json_decode($response->getContent());
        $this->assertTrue(count($datas) > 0);
        $record->delete();
    }

    public function testGetRecordDetailNotAjax()
    {
        self::$DI['client']->request('POST', '/prod/records/');

        $this->assertBadResponse(self::$DI['client']->getResponse());
    }

    public function testGetRecordDetailAsGuest()
    {
        $this->authenticate(self::$DI['app'], self::$DI['user_guest']);

        $basket = new Basket();
        $basket->setUser(self::$DI['user_guest']);
        $basket->setName('test');

        self::$DI['app']['orm.em']->persist($basket);

        $element = new BasketElement();
        $element->setRecord(self::$DI['record_1']);
        $element->setBasket($basket);
        $basket->addElement($element);

        self::$DI['app']['orm.em']->persist($element);
        self::$DI['app']['orm.em']->flush();

        $response = $this->XMLHTTPRequest('POST', '/prod/records/', [
            'env' => 'BASK',
            'pos' => 0,
            'query' => '',
            'cont' => $basket->getId(),
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('desc', $data);
        $this->assertArrayHasKey('html_preview', $data);
        $this->assertArrayHasKey('current', $data);
        $this->assertArrayHasKey('others', $data);
        $this->assertArrayHasKey('history', $data);
        $this->assertArrayHasKey('popularity', $data);
        $this->assertArrayHasKey('tools', $data);
        $this->assertArrayHasKey('pos', $data);
        $this->assertArrayHasKey('title', $data);
    }

    public function testGetRecordDetailResult()
    {
        $app = $this->mockElasticsearchResult(self::$DI['record_1']);
        $this->authenticate($app);

        $options = new SearchEngineOptions(self::$DI['app']['repo.collection-references']);
        $acl = $app->getAclForUser($app->getAuthenticatedUser());
        $searchableBasesIds = $acl->getSearchableBasesIds();
        $options->onBasesIds($searchableBasesIds);
        $serializedOptions = $options->serialize();

        $response = $this->XMLHTTPRequest('POST', '/prod/records/', [
            'env' => 'RESULT',
            'options_serial' => $serializedOptions,
            'pos' => 0,
            'query' => ''
        ]);

        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('desc', $data);
        $this->assertArrayHasKey('html_preview', $data);
        $this->assertArrayHasKey('current', $data);
        $this->assertArrayHasKey('others', $data);
        $this->assertArrayHasKey('history', $data);
        $this->assertArrayHasKey('popularity', $data);
        $this->assertArrayHasKey('tools', $data);
        $this->assertArrayHasKey('pos', $data);
        $this->assertArrayHasKey('title', $data);
    }

    public function testGetRecordDetailREG()
    {
        $this->authenticate(self::$DI['app']);
        self::$DI['record_story_1'];

        $response = $this->XMLHTTPRequest('POST', '/prod/records/', [
            'env' => 'REG',
            'pos' => 0,
            'query' => '',
            'cont' => self::$DI['record_story_1']->get_serialize_key()
        ]);

        $data = json_decode($response->getContent());
        $this->assertObjectHasAttribute('desc', $data);
        $this->assertObjectHasAttribute('html_preview', $data);
        $this->assertObjectHasAttribute('current', $data);
        $this->assertObjectHasAttribute('others', $data);
        $this->assertObjectHasAttribute('history', $data);
        $this->assertObjectHasAttribute('popularity', $data);
        $this->assertObjectHasAttribute('tools', $data);
        $this->assertObjectHasAttribute('pos', $data);
        $this->assertObjectHasAttribute('title', $data);
    }

    public function testGetRecordDetailBasket()
    {
        $this->authenticate(self::$DI['app']);
        $basket = self::$DI['app']['orm.em']->find('Phraseanet:Basket', 1);

        $response = $this->XMLHTTPRequest('POST', '/prod/records/', [
            'env' => 'BASK',
            'pos' => 0,
            'query' => '',
            'cont' => $basket->getId()
        ]);

        $data = json_decode($response->getContent());

        $this->assertObjectHasAttribute('desc', $data);
        $this->assertObjectHasAttribute('html_preview', $data);
        $this->assertObjectHasAttribute('current', $data);
        $this->assertObjectHasAttribute('others', $data);
        $this->assertObjectHasAttribute('history', $data);
        $this->assertObjectHasAttribute('popularity', $data);
        $this->assertObjectHasAttribute('tools', $data);
        $this->assertObjectHasAttribute('pos', $data);
        $this->assertObjectHasAttribute('title', $data);

        unset($response, $data);
    }

    public function testGetRecordDetailFeed()
    {
        $this->authenticate(self::$DI['app']);

        self::$DI['app']['notification.deliverer'] = $this->getMockBuilder('Alchemy\Phrasea\Notification\Deliverer')
            ->disableOriginalConstructor()
            ->getMock();

        $feed = self::$DI['app']['orm.em']->find('Phraseanet:Feed', 1);
        $feedEntry = $feed->getEntries()->first();

        $response = $this->XMLHTTPRequest('POST', '/prod/records/', [
            'env' => 'FEED',
            'pos' => 0,
            'query' => '',
            'cont' => $feedEntry->getId()
        ]);

        $data = json_decode($response->getContent());
        $this->assertObjectHasAttribute('desc', $data);
        $this->assertObjectHasAttribute('html_preview', $data);
        $this->assertObjectHasAttribute('current', $data);
        $this->assertObjectHasAttribute('others', $data);
        $this->assertObjectHasAttribute('history', $data);
        $this->assertObjectHasAttribute('popularity', $data);
        $this->assertObjectHasAttribute('tools', $data);
        $this->assertObjectHasAttribute('pos', $data);
        $this->assertObjectHasAttribute('title', $data);

        unset($response, $data);
    }
}
