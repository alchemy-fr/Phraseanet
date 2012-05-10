<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class ControllerUsersTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    protected static $need_records = false;
    protected $usersParameters;

    public function createApplication()
    {
        return require __DIR__ . '/../../../../../lib/Alchemy/Phrasea/Application/Admin.php';
    }

    public function setUp()
    {
        parent::setUp();
        $this->usersParameters = array("users" => implode(';', array(self::$user->get_id(), self::$user_alt1->get_id())));
        $this->client = $this->createClient();
    }

    public function testRouteRightsPost()
    {
        $this->client->request('POST', '/users/rights/', $this->usersParameters);
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk());
    }

    public function testRouteRightsGet()
    {
        $this->client->request('GET', '/users/rights/', $this->usersParameters);
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk());
    }

    public function testRouteDelete()
    {
        $appbox = \appbox::get_instance(\bootstrap::getCore());

        $username = uniqid('user_');
        $user = User_Adapter::create($appbox, $username, "test", $username . "@email.com", false);
        $id = $user->get_id();

        $this->client->request('POST', '/users/delete/', array('users'   => $id));
        $response = $this->client->getResponse();
        $this->assertTrue($response->isRedirect());
        try {
            $user = User_Adapter::getInstance($id, $appbox);
            $user->delete();
            $this->fail("user not deleted");
        } catch (\Exception $e) {

        }
    }

    public function testRouteRightsApply()
    {
        $appbox = \appbox::get_instance(\bootstrap::getCore());

        $username = uniqid('user_');
        $user = User_Adapter::create($appbox, $username, "test", $username . "@email.com", false);

        $base_id = self::$collection->get_base_id();
        $_GET['values'] = 'canreport_' . $base_id . '=1&manage_' . self::$collection->get_base_id() . '=1&canpush_' . self::$collection->get_base_id() . '=1';
        $_GET['user_infos'] = "user_infos[email]=" . $user->get_email();

        $this->client->request('POST', '/users/rights/apply/', array('users'   => $user->get_id()));
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOK());
        $this->assertEquals("application/json", $response->headers->get("content-type"));
        $datas = json_decode($response->getContent());
        $this->assertTrue(is_object($datas));
        $datas = json_decode($response->getContent());
        $this->assertFalse($datas->error);
        $this->assertTrue($user->ACL()->has_right_on_base($base_id, "manage"));
        $this->assertTrue($user->ACL()->has_right_on_base($base_id, "canpush"));
        $this->assertTrue($user->ACL()->has_right_on_base($base_id, "canreport"));
        $user->delete();
    }

    public function testRouteRightsApplyException()
    {
        $this->markTestIncomplete();
        $_GET = array();
        $appbox = \appbox::get_instance(\bootstrap::getCore());
        $username = uniqid('user_');
        $user = User_Adapter::create($appbox, $username, "test", $username . "@email.com", false);
        $base_id = self::$collection->get_base_id();
        $_GET['values'] = 'canreport_' . $base_id . '=1&manage_' . self::$collection->get_base_id() . '=1&canpush_' . self::$collection->get_base_id() . '=1';
        $_GET['user_infos'] = "user_infos[email]=" . $user->get_email();
        $this->client->request('POST', '/users/rights/apply/', array('users'   => $user->get_id()));
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOK());
        $this->assertEquals("application/json", $response->headers->get("content-type"));
        $datas = json_decode($response->getContent());
        $this->assertTrue(is_object($datas));
        $this->assertTrue($datas->error);
        $user->delete();
    }

    public function testRouteQuota()
    {
        $base_id = array_pop(array_keys(self::$user->ACL()->get_granted_base()));
        $params = array('base_id' => $base_id, 'users'   => self::$user->get_id());

        $this->client->request('POST', '/users/rights/quotas/', $params);
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOK());
    }

    public function testRouteQuotaAdd()
    {
        $params = array(
            'base_id' => self::$collection->get_base_id()
            , 'quota'   => '1', 'droits'  => 38, 'restes'  => 15);
        $this->client->request('POST', '/users/rights/quotas/apply/', $params);
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOK());
    }

    public function testRouteQuotaRemove()
    {
        $base_id = array_pop(array_keys(self::$user->ACL()->get_granted_base()));
        $params = array('base_id' => $base_id, 'users'   => self::$user->get_id());

        $this->client->request('POST', '/users/rights/quotas/apply/', $params);
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOK());
    }

    public function testRouteRightTime()
    {
        $base_id = array_pop(array_keys(self::$user->ACL()->get_granted_base()));
        $params = array('base_id' => $base_id, 'users'   => self::$user->get_id());

        $this->client->request('POST', '/users/rights/time/', $params);
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOK());
    }

    public function testRouteRightTimeApply()
    {
        $appbox = \appbox::get_instance(\bootstrap::getCore());
        $username = uniqid('user_');
        $user = User_Adapter::create($appbox, $username, "test", $username . "@email.com", false);
        $base_id = self::$collection->get_base_id();
        $date = new \Datetime();
        $date->modify("-10 days");
        $dmin = $date->format(DATE_ATOM);
        $date->modify("+30 days");
        $dmax = $date->format(DATE_ATOM);
        $this->client->request('POST', '/users/rights/time/apply/', array('base_id' => $base_id, 'dmin'    => $dmin, 'dmax'    => $dmax, 'limit'   => 1, 'users'   => $user->get_id()));
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOK());
//    $this->assertTrue($user->ACL()->is_limited($base_id));
        $user->delete();
    }

    public function testRouteRightMask()
    {
        $base_id = array_pop(array_keys(self::$user->ACL()->get_granted_base()));
        $params = array('base_id' => $base_id, 'users'   => self::$user->get_id());

        $this->client->request('POST', '/users/rights/masks/', $params);
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOK());
    }

    public function testRouteRightMaskApply()
    {
        $this->markTestIncomplete();
        $base_id = self::$collection->get_base_id();
        $username = uniqid('user_');
        $user = User_Adapter::create($appbox, $username, "test", $username . "@email.com", false);
        $this->client->request('POST', '/users/rights/masks/apply/', array(
            'base_id' => $base_id, 'vand_and', 'vand_or', 'vxor_or', 'vxor_and'
        ));
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOK());
        $user->delete();
    }

    public function testRouteSearch()
    {
        $this->client->request('POST', '/users/search/');
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOK());
    }

    public function testRoutesearchExport()
    {
        $this->client->request('POST', '/users/search/export/');
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOK());
        $this->assertEquals("text/plain; charset=UTF-8", $response->headers->get("Content-type"));
        $this->assertEquals("attachment; filename=export.txt", $response->headers->get("content-disposition"));
    }

    public function testRouteThSearch()
    {
        $this->client->request('GET', '/users/typeahead/search/', array('term'    => 'admin'));
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOK());
        $this->assertEquals("application/json", $response->headers->get("content-type"));
    }

    public function testRouteApplyTp()
    {
        $appbox = \appbox::get_instance(\bootstrap::getCore());

        $templateName = uniqid('template_');
        $template = User_Adapter::create($appbox, $templateName, "test", $templateName . "@email.com", false);
        $template->set_template(self::$user);

        $username = uniqid('user_');
        $user = User_Adapter::create($appbox, $username, "test", $username . "@email.com", false);

        $this->client->request('POST', '/users/apply_template/', array(
            'template' => $template->get_id()
            , 'users'    => $user->get_id())
        );

        $response = $this->client->getResponse();
        $this->assertTrue($response->isRedirect());

        $template->delete();
        $user->delete();
    }

    public function testRouteCreateException()
    {
        $this->client->request('POST', '/users/create/', array('value'    => '', 'template' => '1'));
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOK());
        $this->assertEquals("application/json", $response->headers->get("content-type"));
        $datas = json_decode($response->getContent());
        $this->assertTrue(is_object($datas));
        $this->assertTrue($datas->error);
    }

    public function testRouteCreateExceptionUser()
    {
        $this->client->request('POST', '/users/create/', array('value'    => '', 'template' => '0'));
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOK());
        $this->assertEquals("application/json", $response->headers->get("content-type"));
        $datas = json_decode($response->getContent());
        $this->assertTrue(is_object($datas));
        $this->assertTrue($datas->error);
    }

    public function testRouteCreateUser()
    {
        $appbox = \appbox::get_instance(\bootstrap::getCore());

        $username = uniqid('user_');
        $user = User_Adapter::create($appbox, $username, "test", $username . "@email.com", false);

        $this->client->request('POST', '/users/create/', array('value'    => $username . "@email.com", 'template' => '0'));

        $response = $this->client->getResponse();

        $this->assertTrue($response->isOK());
        $this->assertEquals("application/json", $response->headers->get("content-type"));
        $datas = json_decode($response->getContent());
        $this->assertTrue(is_object($datas));
        $this->assertFalse($datas->error);

        try {
            $user = \User_Adapter::getInstance((int) $datas->data, $appbox);
            $user->delete();
        } catch (\Exception $e) {
            $this->fail("could not delete created user " . $e->getMessage());
        }
    }

    public function testRouteExportCsv()
    {
        $this->client->request('POST', '/users/export/csv/');
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOK());
        $this->assertRegexp("#text/csv#", $response->headers->get("content-type"));
        $this->assertRegexp("#charset=UTF-8#", $response->headers->get("content-type"));
        $this->assertEquals("attachment; filename=export.txt", $response->headers->get("content-disposition"));
    }

    public function testResetRights()
    {
        $appbox = \appbox::get_instance(self::$core);
        $username = uniqid('user_');
        $user = User_Adapter::create($appbox, $username, "test", $username . "@email.com", false);

        $user->ACL()->give_access_to_sbas(array_keys($appbox->get_databoxes()));

        foreach ($appbox->get_databoxes() as $databox) {

            $rights = array(
                'bas_manage'        => '1'
                , 'bas_modify_struct' => '1'
                , 'bas_modif_th'      => '1'
                , 'bas_chupub'        => '1'
            );

            $user->ACL()->update_rights_to_sbas($databox->get_sbas_id(), $rights);

            foreach ($databox->get_collections() as $collection) {
                $base_id = $collection->get_base_id();
                $user->ACL()->give_access_to_base(array($base_id));

                $rights = array(
                    'canputinalbum'  => '1'
                    , 'candwnldhd'     => '1'
                    , 'candwnldsubdef' => '1'
                    , 'nowatermark'    => '1'
                );

                $user->ACL()->update_rights_to_base($collection->get_base_id(), $rights);
                break;
            }
        }
//

        $this->client->request('POST', '/users/rights/reset/', array('users'   => $user->get_id()));
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOK());
        $this->assertEquals("application/json", $response->headers->get("content-type"));
        $datas = json_decode($response->getContent());
        $this->assertTrue(is_object($datas));
        $this->assertFalse($datas->error);
        $this->assertFalse($user->ACL()->has_access_to_base($base_id));
        $user->delete();
    }
}
