<?php

namespace Alchemy\Tests\Phrasea\Core\Configuration;

use Alchemy\Phrasea\Cache\ArrayCache;
use Alchemy\Phrasea\Core\Configuration\AccessRestriction;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Tests\Phrasea\MockArrayConf;

class AccessRestrictionTest extends \PhraseanetTestCase
{
    /**
     * @dataProvider provideVariousConfiguration
     */
    public function testRestrictionConfigurations($conf, $restricted, array $collAccess, array $collNoAccess, array $databoxAccess, array $databoxNoAccess)
    {
        $conf = new MockArrayConf($conf);
        $logger = $this->createLoggerMock();

        $restriction = new AccessRestriction(new ArrayCache(), new PropertyAccess($conf), self::$DI['app']['phraseanet.appbox'], $logger);
        $this->assertEquals($restricted, $restriction->isRestricted());

        foreach ($collAccess as $coll) {
            $this->assertTrue($restriction->isCollectionAvailable($coll));
        }
        foreach ($collNoAccess as $coll) {
            $this->assertFalse($restriction->isCollectionAvailable($coll));
        }

        foreach ($databoxAccess as $databox) {
            $this->assertTrue($restriction->isDataboxAvailable($databox));
        }
        foreach ($databoxNoAccess as $databox) {
            $this->assertFalse($restriction->isDataboxAvailable($databox));
        }
    }

    public function provideVariousConfiguration()
    {
        $app = $this->loadApp();

        $databoxes = $app['phraseanet.appbox']->get_databoxes();
        $databox = current($databoxes);
        $collections = $databox->get_collections();
        $collection = current($collections);
        $conf1 = [];
        $conf2 = ['databoxes' => []];
        $conf3 = ['databoxes' => [['id' => $databox->get_sbas_id(), 'collections' => 244]]];
        $conf4 = ['databoxes' => [['id' => 25, 'collections' => 244]]];

        return [
            [$conf1, false, [$collection], [], [$databox], []],
            [$conf2, false, [$collection], [], [$databox], []],
            [$conf3, true, [], [$collection], [$databox], []],
            [$conf4, true, [], [$collection], [], [$databox]],
        ];
    }
}
