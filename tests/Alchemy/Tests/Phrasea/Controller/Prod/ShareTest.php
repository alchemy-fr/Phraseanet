<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Controller\Prod\ShareController;
use Alchemy\Phrasea\ControllerProvider\Prod\Share;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @group functional
 * @group legacy
 * @group authenticated
 * @group web
 */
class ShareTest extends \PhraseanetAuthenticatedWebTestCase
{
    protected $client;

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Share::shareRecord
     * @covers Alchemy\Phrasea\Controller\Prod\Share::connect
     */
    public function testRouteSlashALL()
    {
        $this->_RouteSlash("all", [0=>true, 1=>true, 2=>true, 3=>true]);
    }

    public function testRouteSlashPublishers()
    {
        $this->_RouteSlash("publishers", [0=>false, 1=>true, 2=>false, 3=>true]);
    }

    public function testRouteSlashNone()
    {
        $this->_RouteSlash("none", [0=>false, 1=>false, 2=>false, 3=>false]);
    }

    private function _RouteSlash($setting, $expected)
    {
        $app = $this->getApplication();

        $_conf = $app['conf'];
        $app['conf'] = $this->getMockBuilder('Alchemy\Phrasea\Core\Configuration\PropertyAccess')
            ->disableOriginalConstructor()
            ->getMock();
        $app['conf']
            ->expects($this->any())
            ->method('get')
            ->will($this->returnCallback(function ($param, $default) use ($_conf, $setting) {
                switch ($param) {
                    case ['registry', 'actions', 'social-tools']:
                        return $setting;
                }
                return $_conf->get($param, $default);
            }));

        $result = [];
        foreach($expected as $flags=>$v) {
            $stubbedACL = $this->stubACL();

            // "has_right_on_sbas" IS checked by the route->before(), the url will return 403
            $stubbedACL->expects($this->any())
                ->method('has_right_on_sbas')
                ->will($this->returnValue(($flags & 1) ? true:false));

            // but "has_access_to_subdef" IS NOT checked (the url will return a 200 with a message "no subdef to share")
            $stubbedACL->expects($this->any())
                ->method('has_access_to_subdef')
                ->will($this->returnValue(($flags & 2) ? true:false));

            $url = sprintf('/prod/share/record/%d/%d/', self::$DI['record_1']->get_base_id(), self::$DI['record_1']->get_record_id());
            self::$DI['client']->request('GET', $url);

            $result[$flags] = self::$DI['client']->getResponse()->isOk();
        }
        $this->assertEquals($expected, $result);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Share::shareRecord
     */
    public function testShareRecord()
    {
        $share = new ShareController(self::$DI['app']);

        /** @var \record_adapter $record_1 */
        $record_1 = self::$DI['record_1'];

        $response = $share->shareRecord($record_1->getBaseId(), $record_1->getRecordId());
        $this->assertTrue($response->isOk());
    }
}
