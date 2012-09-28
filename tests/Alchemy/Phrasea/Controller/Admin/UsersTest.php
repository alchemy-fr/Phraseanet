<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class ControllerUsersTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;
    protected $usersParameters;

    public function setUp()
    {
        parent::setUp();
        $this->usersParameters = array("users" => implode(';', array(self::$DI['user']->get_id(), self::$DI['user_alt1']->get_id())));
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

        $username = uniqid('user_');
        $user = User_Adapter::create(self::$DI['app'], $username, "test", $username . "@email.com", false);
        $id = $user->get_id();

        self::$DI['client']->request('POST', '/admin/users/delete/', array('users'   => $id));
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        try {
            $user = User_Adapter::getInstance($id, self::$DI['app']);
            $user->delete();
            $this->fail("user not deleted");
        } catch (\Exception $e) {

        }
    }

    public function testRouteRightsApply()
    {

        $username = uniqid('user_');
        $user = User_Adapter::create(self::$DI['app'], $username, "test", $username . "@email.com", false);

        $base_id = self::$DI['collection']->get_base_id();
        $_GET['values'] = 'canreport_' . $base_id . '=1&manage_' . self::$DI['collection']->get_base_id() . '=1&canpush_' . self::$DI['collection']->get_base_id() . '=1';
        $_GET['user_infos'] = "user_infos[email]=" . $user->get_email();

        self::$DI['client']->request('POST', '/admin/users/rights/apply/', array('users'   => $user->get_id()));
        $response = self::$DI['client']->getResponse();
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
        $username = uniqid('user_');
        $user = User_Adapter::create(self::$DI['app'], $username, "test", $username . "@email.com", false);
        $base_id = self::$DI['collection']->get_base_id();
        $_GET['values'] = 'canreport_' . $base_id . '=1&manage_' . self::$DI['collection']->get_base_id() . '=1&canpush_' . self::$DI['collection']->get_base_id() . '=1';
        $_GET['user_infos'] = "user_infos[email]=" . $user->get_email();
        self::$DI['client']->request('POST', '/admin/users/rights/apply/', array('users'   => $user->get_id()));
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOK());
        $this->assertEquals("application/json", $response->headers->get("content-type"));
        $datas = json_decode($response->getContent());
        $this->assertTrue(is_object($datas));
        $this->assertTrue($datas->error);
        $user->delete();
    }

    public function testRouteQuota()
    {
        $keys = array_keys(self::$DI['user']->ACL()->get_granted_base());
        $base_id = array_pop($keys);
        $params = array('base_id' => $base_id, 'users'   => self::$DI['user']->get_id());

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
        $keys = array_keys(self::$DI['user']->ACL()->get_granted_base());
        $base_id = array_pop($keys);
        $params = array('base_id' => $base_id, 'users'   => self::$DI['user']->get_id());

        self::$DI['client']->request('POST', '/admin/users/rights/quotas/apply/', $params);
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOK());
    }

    public function testRouteRightTime()
    {
        $keys = array_keys(self::$DI['user']->ACL()->get_granted_base());
        $base_id = array_pop($keys);
        $params = array('base_id' => $base_id, 'users'   => self::$DI['user']->get_id());

        self::$DI['client']->request('POST', '/admin/users/rights/time/', $params);
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOK());
    }

    public function testRouteRightTimeApply()
    {
        $username = uniqid('user_');
        $user = User_Adapter::create(self::$DI['app'], $username, "test", $username . "@email.com", false);
        $base_id = self::$DI['collection']->get_base_id();
        $date = new \Datetime();
        $date->modify("-10 days");
        $dmin = $date->format(DATE_ATOM);
        $date->modify("+30 days");
        $dmax = $date->format(DATE_ATOM);
        self::$DI['client']->request('POST', '/admin/users/rights/time/apply/', array('base_id' => $base_id, 'dmin'    => $dmin, 'dmax'    => $dmax, 'limit'   => 1, 'users'   => $user->get_id()));
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOK());
//    $this->assertTrue($user->ACL()->is_limited($base_id));
        $user->delete();
    }

    public function testRouteRightMask()
    {
        $keys = array_keys(self::$DI['user']->ACL()->get_granted_base());
        $base_id = array_pop($keys);
        $params = array('base_id' => $base_id, 'users'   => self::$DI['user']->get_id());

        self::$DI['client']->request('POST', '/admin/users/rights/masks/', $params);
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOK());
    }

    public function testRouteRightMaskApply()
    {
        $this->markTestIncomplete();
        $base_id = self::$DI['collection']->get_base_id();
        $username = uniqid('user_');
        $user = User_Adapter::create(self::$DI['app'], $username, "test", $username . "@email.com", false);
        self::$DI['client']->request('POST', '/admin/users/rights/masks/apply/', array(
            'base_id' => $base_id, 'vand_and', 'vand_or', 'vxor_or', 'vxor_and'
        ));
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOK());
        $user->delete();
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
        $this->assertEquals("text/plain; charset=UTF-8", $response->headers->get("Content-type"));
        $this->assertEquals("attachment; filename=export.txt", $response->headers->get("content-disposition"));
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

        $templateName = uniqid('template_');
        $template = User_Adapter::create(self::$DI['app'], $templateName, "test", $templateName . "@email.com", false);
        $template->set_template(self::$DI['user']);

        $username = uniqid('user_');
        $user = User_Adapter::create(self::$DI['app'], $username, "test", $username . "@email.com", false);

        self::$DI['client']->request('POST', '/admin/users/apply_template/', array(
            'template' => $template->get_id()
            , 'users'    => $user->get_id())
        );

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());

        $template->delete();
        $user->delete();
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

    public function testRouteCreateUser()
    {

        $username = uniqid('user_');
        $user = User_Adapter::create(self::$DI['app'], $username, "test", $username . "@email.com", false);

        self::$DI['client']->request('POST', '/admin/users/create/', array('value'    => $username . "@email.com", 'template' => '0'));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOK());
        $this->assertEquals("application/json", $response->headers->get("content-type"));
        $datas = json_decode($response->getContent());
        $this->assertTrue(is_object($datas));
        $this->assertFalse($datas->error);

        try {
            $user = \User_Adapter::getInstance((int) $datas->data, self::$DI['app']);
            $user->delete();
        } catch (\Exception $e) {
            $this->fail("could not delete created user " . $e->getMessage());
        }
    }

    public function testRouteExportCsv()
    {
        self::$DI['client']->request('POST', '/admin/users/export/csv/');
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOK());
        $this->assertRegexp("#text/csv#", $response->headers->get("content-type"));
        $this->assertRegexp("#charset=UTF-8#", $response->headers->get("content-type"));
        $this->assertEquals("attachment; filename=export.txt", $response->headers->get("content-disposition"));
    }

    public function testResetRights()
    {
        $username = uniqid('user_');
        $user = User_Adapter::create(self::$DI['app'], $username, "test", $username . "@email.com", false);

        $user->ACL()->give_access_to_sbas(array_keys(self::$DI['app']['phraseanet.appbox']->get_databoxes()));

        foreach (self::$DI['app']['phraseanet.appbox']->get_databoxes() as $databox) {

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

        self::$DI['client']->request('POST', '/admin/users/rights/reset/', array('users'   => $user->get_id()));
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOK());
        $this->assertEquals("application/json", $response->headers->get("content-type"));
        $datas = json_decode($response->getContent());
        $this->assertTrue(is_object($datas));
        $this->assertFalse($datas->error);
        $this->assertFalse($user->ACL()->has_access_to_base($base_id));
        $user->delete();
    }

    public function testRenderDemands()
    {
        self::$DI['client']->request('GET', '/admin/users/demands/');

        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
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
