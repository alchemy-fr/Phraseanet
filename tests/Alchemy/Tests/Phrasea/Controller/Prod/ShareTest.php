<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

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
     * @covers Alchemy\Phrasea\Controller\Prod\Share::call
     */
    public function testMountedRouteSlash()
    {
        $url = sprintf('/prod/share/record/%d/%d/', self::$DI['record_1']->get_base_id(), self::$DI['record_1']->get_record_id());
        self::$DI['client']->request('GET', $url);
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Share::shareRecord
     * @covers Alchemy\Phrasea\Controller\Prod\Share::connect
     */
    public function testRouteSlash()
    {
        $stubbedACL = $this->stubACL();

        //has_right_on_base return true
        $stubbedACL->expects($this->any())
            ->method('has_right_on_sbas')
            ->will($this->returnValue(true));

        //has_access_to_subdef return true
        $stubbedACL->expects($this->any())
            ->method('has_access_to_subdef')
            ->will($this->returnValue(true));


        $url = sprintf('/prod/share/record/%d/%d/', self::$DI['record_1']->get_base_id(), self::$DI['record_1']->get_record_id());
        self::$DI['client']->request('GET', $url);
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
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


    /* --------- WE ARE NOT RETURNING AN ERROR ANYMORE IF USER DOES NOT HAVE ACCESS ----------*/
    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Share::shareRecord
     */
//    public function testShareRecordBadAccess()
//    {
//        $share = new ShareController(self::$DI['app']);
//        $stubbedACL = $this->getMockBuilder('\ACL')
//            ->disableOriginalConstructor()
//            ->getMock();
//        //has_access_to_subdef return false
//        $stubbedACL->expects($this->once())
//            ->method('has_access_to_subdef')
//            ->will($this->returnValue(false));
//        $aclProvider = $this->getMockBuilder('Alchemy\Phrasea\Authentication\ACLProvider')
//            ->disableOriginalConstructor()
//            ->getMock();
//        $aclProvider->expects($this->any())
//            ->method('get')
//            ->will($this->returnValue($stubbedACL));
//        self::$DI['app']['acl'] = $aclProvider;
//        try {
//            $share->shareRecord(self::$DI['record_1']->get_base_id(), self::$DI['record_1']->get_record_id());
//        } catch (HttpException $exception) {
//            $this->assertEquals(403, $exception->getStatusCode());
//            return;
//        }
//        $this->fail('An access denied exception should have been thrown.');
//    }
}
