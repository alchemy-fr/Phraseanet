<?php

namespace Alchemy\Tests\Phrasea\Core\Configuration;

use Alchemy\Phrasea\Cache\ArrayCache;
use Alchemy\Phrasea\Core\Configuration\AccessRestriction;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Tests\Phrasea\MockArrayConf;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class AccessRestrictionTest extends \PhraseanetTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('provideVariousConfiguration')]
    public function testRestrictionConfigurations(callable $conf, $restricted, $collectionAvailable, $databoxAvailable)
    {
        $databoxes = self::$DI['app']->getDataboxes();
        $databox = current($databoxes);
        $collections = $databox->get_collections();
        $collection = current($collections);

        $conf = new MockArrayConf($conf($databox));
        $logger = $this->createLoggerMock();

        $restriction = new AccessRestriction(new PropertyAccess($conf), self::$DI['app']['phraseanet.appbox'], $logger);
        $this->assertEquals($restricted, $restriction->isRestricted());
        $this->assertSame($collectionAvailable, $restriction->isCollectionAvailable($collection));
        $this->assertSame($databoxAvailable, $restriction->isDataboxAvailable($databox));
    }

    public static function provideVariousConfiguration()
    {
        return [
            [function () { return []; }, false, true, true],
            [function () { return ['databoxes' => []]; }, false, true, true],
            [function ($databox) { return ['databoxes' => [['id' => $databox->get_sbas_id(), 'collections' => 244]]]; }, true, false, true],
            [function () { return ['databoxes' => [['id' => 25, 'collections' => 244]]]; }, true, false, false],
        ];
    }
}
