<?php

namespace Alchemy\Tests\Phrasea\Controller\Admin;

class UsersTest extends \PhraseanetAuthenticatedWebTestCase
{
    protected $client;
    protected $usersParameters;

    public function setUp()
    {
        parent::setUp();
        $this->usersParameters = array("users" => implode(';', array(self::$DI['user']->getId(), self::$DI['user_alt1']->getId())));
    }

    public function testRouteRightsPost()
    {
        self::$DI['client']->request('POST', '/admin/users/rights/', $this->usersParameters);
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
    }

    public function testRouteRightsGet()
    {
        self::$DI['client']->request('GET', '/admin/users/rights/', $this->usersParameters);
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
    }

    public function testRouteDelete()
    {
        $user = self::$DI['app']['manipulator.user']->createUser(uniqid('user_'), "test");
        self::$DI['client']->request('POST', '/admin/users/delete/', array('users'   => $user->getId()));
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $this->assertNull(self::$DI['app']['manipulator.user']->getRepository()->find($user->getId()));
    }

    public function testRouteDeleteCurrentUserDoesNothing()
    {
        self::$DI['client']->request('POST', '/admin/users/delete/', array('users'   => self::$DI['user']->getId()));
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());

        $this->assertNotNull(self::$DI['app']['manipulator.user']->getRepository()->findByLogin(self::$DI['user']->getLogin()));
    }

    public function testRouteRightsApply()
    {
        $this->mockNotificationDeliverer('Alchemy\Phrasea\Notification\Mail\MailSuccessEmailUpdate', 2);

        $user = self::$DI['app']['manipulator.user']->createUser(uniqid('user_'), "test");

        $base_id = self::$DI['collection']->get_base_id();
        $_GET['values'] = 'canreport_' . $base_id . '=1&manage_' . $base_id . '=1&canpush_' . $base_id . '=1';
        $_GET['user_infos'] = "user_infos[email]=" . $user->getEmail();

        self::$DI['client']->request('POST', '/admin/users/rights/apply/', [
            'users' => $user->getId(),
            'values' => 'canreport_' . $base_id . '=1&manage_' . self::$DI['collection']->get_base_id() . '=1&canpush_' . self::$DI['collection']->get_base_id() . '=1',
            'user_infos' => "user_infos[email]=" . $user->get_email(),
        ]);
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOK());
        $this->assertEquals("application/json", $response->headers->get("content-type"));
        $datas = json_decode($response->getContent());
        $this->assertTrue(is_object($datas));
        $datas = json_decode($response->getContent());
        $this->assertFalse($datas->error);
        $this->assertTrue(self::$DI['app']['acl']->get($user)->has_right_on_base($base_id, "manage"));
        $this->assertTrue(self::$DI['app']['acl']->get($user)->has_right_on_base($base_id, "canpush"));
        $this->assertTrue(self::$DI['app']['acl']->get($user)->has_right_on_base($base_id, "canreport"));
        self::$DI['app']['model.user-manager']->delete($user);
    }

    public function testRouteRightsApplyException()
    {
        $this->markTestIncomplete();
        $_GET = array();
        $user = self::$DI['app']['manipulator.user']->createUser(uniqid('user_'), "test");
        $base_id = self::$DI['collection']->get_base_id();
        $_GET['values'] = 'canreport_' . $base_id . '=1&manage_' . self::$DI['collection']->get_base_id() . '=1&canpush_' . self::$DI['collection']->get_base_id() . '=1';
        $_GET['user_infos'] = "user_infos[email]=" . $user->getEmail();
        self::$DI['client']->request('POST', '/admin/users/rights/apply/', array('users'   => $user->getId()));
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOK());
        $this->assertEquals("application/json", $response->headers->get("content-type"));
        $datas = json_decode($response->getContent());
        $this->assertTrue(is_object($datas));
        $this->assertTrue($datas->error);
        self::$DI['app']['model.user-manager']->delete($user);
    }

    public function testRouteQuota()
    {
        $keys = array_keys(self::$DI['app']['acl']->get(self::$DI['user'])->get_granted_base());
        $base_id = array_pop($keys);
        $params = array('base_id' => $base_id, 'users'   => self::$DI['user']->getId());

        self::$DI['client']->request('POST', '/admin/users/rights/quotas/', $params);
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOK());
    }

    public function testRouteQuotaAdd()
    {
        $params = array(
            'base_id' => self::$DI['collection']->get_base_id()
            , 'quota'   => '1', 'droits'  => 38, 'restes'  => 15);
        self::$DI['client']->request('POST', '/admin/users/rights/quotas/apply/', $params);
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOK());
    }

    public function testRouteQuotaRemove()
    {
        $keys = array_keys(self::$DI['app']['acl']->get(self::$DI['user'])->get_granted_base());
        $base_id = array_pop($keys);
        $params = array('base_id' => $base_id, 'users'   => self::$DI['user']->getId());

        self::$DI['client']->request('POST', '/admin/users/rights/quotas/apply/', $params);
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOK());
    }

    public function testRouteRightTime()
    {
        $keys = array_keys(self::$DI['app']['acl']->get(self::$DI['user'])->get_granted_base());
        $base_id = array_pop($keys);
        $params = array('base_id' => $base_id, 'users'   => self::$DI['user']->getId());

        self::$DI['client']->request('POST', '/admin/users/rights/time/', $params);
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOK());
    }

    public function testRouteRightTimeSbas()
    {
        $sbas_id = self::$DI['record_1']->get_databox()->get_sbas_id();
        $params = array('sbas_id' => $sbas_id, 'users'   => self::$DI['user']->getId());

        self::$DI['client']->request('POST', '/admin/users/rights/time/sbas/', $params);
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOK());
    }

    public function testRouteRightTimeApply()
    {
        $user = self::$DI['app']['manipulator.user']->createUser(uniqid('user_'), "test");
        $base_id = self::$DI['collection']->get_base_id();
        $date = new \Datetime();
        $date->modify("-10 days");
        $dmin = $date->format(DATE_ATOM);
        $date->modify("+30 days");
        $dmax = $date->format(DATE_ATOM);
        self::$DI['client']->request('POST', '/admin/users/rights/time/apply/', array('base_id' => $base_id, 'dmin'    => $dmin, 'dmax'    => $dmax, 'limit'   => 1, 'users'   => $user->getId()));
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOK());
        self::$DI['app']['model.user-manager']->delete($user);
    }

    public function testRouteRightTimeApplySbas()
    {
        $user = self::$DI['app']['manipulator.user']->createUser(uniqid('user_'), "test");
        $sbas_id = self::$DI['record_1']->get_databox()->get_sbas_id();
        $date = new \Datetime();
        $date->modify("-10 days");
        $dmin = $date->format(DATE_ATOM);
        $date->modify("+30 days");
        $dmax = $date->format(DATE_ATOM);
        self::$DI['client']->request('POST', '/admin/users/rights/time/apply/', array('sbas_id' => $sbas_id, 'dmin'    => $dmin, 'dmax'    => $dmax, 'limit'   => 1, 'users'   => $user->getId()));
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOK());
        self::$DI['app']['model.user-manager']->delete($user);
    }

    public function testRouteRightTimeApplyWithtoutBasOrSbas()
    {
        $user = self::$DI['app']['manipulator.user']->createUser(uniqid('user_'), "test");
        $date = new \Datetime();
        $date->modify("-10 days");
        $dmin = $date->format(DATE_ATOM);
        $date->modify("+30 days");
        $dmax = $date->format(DATE_ATOM);
        self::$DI['client']->request('POST', '/admin/users/rights/time/apply/', array('dmin'    => $dmin, 'dmax'    => $dmax, 'limit'   => 1, 'users'   => $user->getId()));
        $response = self::$DI['client']->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
        self::$DI['app']['model.user-manager']->delete($user);
    }

    public function testRouteRightMask()
    {
        $keys = array_keys(self::$DI['app']['acl']->get(self::$DI['user'])->get_granted_base());
        $base_id = array_pop($keys);
        $params = array('base_id' => $base_id, 'users'   => self::$DI['user']->getId());

        self::$DI['client']->request('POST', '/admin/users/rights/masks/', $params);
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOK());
    }

    public function testRouteRightMaskApply()
    {
        $this->markTestIncomplete();
        $base_id = self::$DI['collection']->get_base_id();
        $user = self::$DI['app']['manipulator.user']->createUser(uniqid('user_'), "test");
        self::$DI['client']->request('POST', '/admin/users/rights/masks/apply/', array(
            'base_id' => $base_id, 'vand_and', 'vand_or', 'vxor_or', 'vxor_and'
        ));
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOK());
        self::$DI['app']['model.user-manager']->delete($user);
    }

    public function testRouteSearch()
    {
        self::$DI['client']->request('POST', '/admin/users/search/');
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOK());
    }

    public function testRoutesearchExport()
    {
        self::$DI['client']->request('POST', '/admin/users/search/export/');
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOK());
        $this->assertEquals("text/csv; charset=UTF-8", $response->headers->get("Content-type"));
        $this->assertEquals("attachment; filename=export.csv", $response->headers->get("content-disposition"));
    }

    public function testRouteThSearch()
    {
        self::$DI['client']->request('GET', '/admin/users/typeahead/search/', array('term'    => 'admin'));
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOK());
        $this->assertEquals("application/json", $response->headers->get("content-type"));
    }

    public function testRouteApplyTp()
    {
        $template = self::$DI['app']['manipulator.user']->createUser(uniqid('template_'), "test");
        $template->setModel(self::$DI['user']);

        $user = self::$DI['app']['manipulator.user']->createUser(uniqid('user_'), "test");

        self::$DI['client']->request('POST', '/admin/users/apply_template/', array(
            'template' => $template->getId()
            , 'users'    => $user->getId())
        );

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        self::$DI['app']['model.user-manager']->delete($template);
        self::$DI['app']['model.user-manager']->delete($user);
    }

    public function testRouteCreateException()
    {
        self::$DI['client']->request('POST', '/admin/users/create/', array('value'    => '', 'template' => '1'));
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOK());
        $this->assertEquals("application/json", $response->headers->get("content-type"));
        $datas = json_decode($response->getContent());
        $this->assertTrue(is_object($datas));
        $this->assertTrue($datas->error);
    }

    public function testRouteCreateExceptionUser()
    {
        self::$DI['client']->request('POST', '/admin/users/create/', array('value'    => '', 'template' => '0'));
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOK());
        $this->assertEquals("application/json", $response->headers->get("content-type"));
        $datas = json_decode($response->getContent());
        $this->assertTrue(is_object($datas));
        $this->assertTrue($datas->error);
    }

    public function testRouteCreateUserAndValidateEmail()
    {
        $this->mockNotificationDeliverer('Alchemy\Phrasea\Notification\Mail\MailRequestEmailConfirmation');

        self::$DI['client']->request('POST', '/admin/users/create/', array(
            'value'         => uniqid('user_') . "@email.com",
            'template'      => '0',
            'validate_mail' => true,
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOK());
        $this->assertEquals("application/json", $response->headers->get("content-type"));
        $datas = json_decode($response->getContent());
        $this->assertTrue(is_object($datas));
        $this->assertFalse($datas->error);

        $this->assertNotNull($user = (self::$DI['app']['manipulator.user']->getRepository((int) $datas->data)));
        self::$DI['app']['model.user-manager']->delete($user);
    }

    public function testRouteCreateUserAndSendCredentials()
    {
        $this->mockNotificationDeliverer('Alchemy\Phrasea\Notification\Mail\MailRequestPasswordSetup');
        $username = uniqid('user_');

        self::$DI['client']->request('POST', '/admin/users/create/', array(
            'value'            => $username . "@email.com",
            'template'         => '0',
            'send_credentials' => true,
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOK());
        $this->assertEquals("application/json", $response->headers->get("content-type"));
        $datas = json_decode($response->getContent());
        $this->assertTrue(is_object($datas));
        $this->assertFalse($datas->error);

        $this->assertNotNull($user = (self::$DI['app']['manipulator.user']->getRepository((int) $datas->data)));
        self::$DI['app']['model.user-manager']->delete($user);
    }

    public function testRouteExportCsv()
    {
        self::$DI['client']->request('POST', '/admin/users/export/csv/');
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOK());
        $this->assertRegexp("#text/csv#", $response->headers->get("content-type"));
        $this->assertRegexp("#charset=UTF-8#", $response->headers->get("content-type"));
        $this->assertEquals("attachment; filename=export.csv", $response->headers->get("content-disposition"));
    }

    public function testResetRights()
    {
        $user = self::$DI['app']['manipulator.user']->createUser(uniqid('user_'), "test");

        self::$DI['app']['acl']->get($user)->give_access_to_sbas(array_keys(self::$DI['app']['phraseanet.appbox']->get_databoxes()));

        foreach (self::$DI['app']['phraseanet.appbox']->get_databoxes() as $databox) {

            $rights = array(
                'bas_manage'        => '1'
                , 'bas_modify_struct' => '1'
                , 'bas_modif_th'      => '1'
                , 'bas_chupub'        => '1'
            );

            self::$DI['app']['acl']->get($user)->update_rights_to_sbas($databox->get_sbas_id(), $rights);

            foreach ($databox->get_collections() as $collection) {
                $base_id = $collection->get_base_id();
                self::$DI['app']['acl']->get($user)->give_access_to_base(array($base_id));

                $rights = array(
                    'canputinalbum'  => '1'
                    , 'candwnldhd'     => '1'
                    , 'candwnldsubdef' => '1'
                    , 'nowatermark'    => '1'
                );

                self::$DI['app']['acl']->get($user)->update_rights_to_base($collection->get_base_id(), $rights);
                break;
            }
        }

        self::$DI['client']->request('POST', '/admin/users/rights/reset/', array('users'   => $user->getId()));
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOK());
        $this->assertEquals("application/json", $response->headers->get("content-type"));
        $datas = json_decode($response->getContent());
        $this->assertTrue(is_object($datas));
        $this->assertFalse($datas->error);
        $this->assertFalse(self::$DI['app']['acl']->get($user)->has_access_to_base($base_id));
        self::$DI['app']['model.user-manager']->delete($user);
    }

    public function testRenderDemands()
    {
        self::$DI['client']->request('GET', '/admin/users/demands/');

        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    public function testPostDemands()
    {
        $id = self::$DI['user_alt1']->get_id();
        $baseId = self::$DI['collection']->get_base_id();
        $param = sprintf('%s_%s', $id, $baseId);

        $appbox = self::$DI['app']['phraseanet.appbox'];

        $stmt = $this->getMock('PDOStatement');

        $stmt->expects($this->any())
            ->method('fetchAll')
            ->will($this->returnValue([
                'usr_id' => $id,
                'base_id' => $baseId,
                'en_cours' => 1,
                'refuser' => 0,
            ]));

        $pdo = $this->getMock('PDOMock');

        $pdo->expects($this->any())
            ->method('prepare')
            ->will($this->returnValue($stmt));

        $appbox = $this->getMockBuilder('\appbox')
            ->setMethods(['get_connection'])
            ->disableOriginalConstructor()
            ->getMock();

        $appbox->expects($this->any())
            ->method('get_connection')
            ->will($this->returnValue($pdo));

        $this->mockNotificationDeliverer('Alchemy\Phrasea\Notification\Mail\MailSuccessEmailUpdate');

        self::$DI['client']->request('POST', '/admin/users/demands/', [
            'template' => [],
            'accept' => [$param],
            'accept_hd' => [$param],
            'watermark' => [$param],
        ]);

        self::$DI['app']['phraseanet.appbox'] = $appbox;

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
    }

    public function testRenderImportFile()
    {
        self::$DI['client']->request('GET', '/admin/users/import/file/');

        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    public function testGetExampleCSVFile()
    {
        self::$DI['client']->request('GET', '/admin/users/import/example/csv/');

        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    public function testGetExampleRtfFile()
    {
        self::$DI['client']->request('GET', '/admin/users/import/example/rtf/');

        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }
}
