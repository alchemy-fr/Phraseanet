<?php

require_once __DIR__ . '/PhraseanetPHPUnitAuthenticatedAbstract.class.inc';

class ACLTest extends PhraseanetPHPUnitAuthenticatedAbstract
{

    /**
     * @var ACL
     */
    protected static $object;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::$object = self::$user->ACL();
    }

    public static function tearDownAfterClass()
    {
        /**
         * In case first test fails
         */
        $usr_id = User_Adapter::get_usr_id_from_login('test_phpunit2');
        try
        {
            $appbox   = appbox::get_instance(\bootstrap::getCore());
            $template = User_Adapter::getInstance($usr_id, $appbox);
            $template->delete();
        }
        catch (Exception $e)
        {

        }
        $usr_id = User_Adapter::get_usr_id_from_login('test_phpunit3');
        try
        {
            $appbox = appbox::get_instance(\bootstrap::getCore());
            $cobaye = User_Adapter::getInstance($usr_id, $appbox);
            $cobaye->delete();
        }
        catch (Exception $e)
        {

        }
        parent::tearDownAfterClass();
    }

    public function testApply_model()
    {
        $appbox   = appbox::get_instance(\bootstrap::getCore());
        $template = User_Adapter::create($appbox, 'test_phpunit2', 'blabla2', 'test2@example.com', false);
        $template->set_template(self::$user);

        $base_ids = array();
        foreach ($appbox->get_databoxes() as $databox)
        {
            foreach ($databox->get_collections() as $collection)
            {
                $base_id    = $collection->get_base_id();
                $base_ids[] = $base_id;
            }
        }
        $template->ACL()->give_access_to_base($base_ids);

        $cobaye = User_Adapter::create($appbox, 'test_phpunit3', 'blabla3', 'test3@example.com', false);
        $cobaye->ACL()->apply_model($template, $base_ids);
        foreach ($appbox->get_databoxes() as $databox)
        {
            foreach ($databox->get_collections() as $collection)
            {
                $base_id = $collection->get_base_id();
                $this->assertTrue($cobaye->ACL()->has_access_to_base($base_id));
            }
        }

        $template->delete();
        $cobaye->delete();
    }

    public function testRevoke_access_from_bases()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());

        $base_ids = array();
        foreach ($appbox->get_databoxes() as $databox)
        {
            foreach ($databox->get_collections() as $collection)
            {
                $base_id = $collection->get_base_id();
                self::$user->ACL()->revoke_access_from_bases(array($base_id));
                $this->assertFalse(self::$user->ACL()->has_access_to_base($base_id));
                self::$user->ACL()->give_access_to_base(array($base_id));
                $this->assertTrue(self::$user->ACL()->has_access_to_base($base_id));
                $base_ids[] = $base_id;
            }
        }
        self::$user->ACL()->revoke_access_from_bases($base_ids);

        foreach ($base_ids as $base_id)
        {
            $this->assertFalse(self::$user->ACL()->has_access_to_base($base_id));
        }
    }

    public function testGive_access_to_base()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());

        foreach ($appbox->get_databoxes() as $databox)
        {
            foreach ($databox->get_collections() as $collection)
            {
                $base_id = $collection->get_base_id();
                $this->assertFalse(self::$user->ACL()->has_access_to_base($base_id));
                self::$user->ACL()->give_access_to_base(array($base_id));
                $this->assertTrue(self::$user->ACL()->has_access_to_base($base_id));
            }
        }
    }

    public function testGive_access_to_sbas()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());

        foreach ($appbox->get_databoxes() as $databox)
        {
            $sbas_id  = $databox->get_sbas_id();
            $base_ids = array();
            foreach ($databox->get_collections() as $collection)
            {
                $base_ids[] = $collection->get_base_id();
            }

            self::$user->ACL()->revoke_access_from_bases($base_ids);
            self::$user->ACL()->revoke_unused_sbas_rights();
            $this->assertFalse(self::$user->ACL()->has_access_to_sbas($sbas_id));
            self::$user->ACL()->give_access_to_sbas(array($sbas_id));
            $this->assertTrue(self::$user->ACL()->has_access_to_sbas($sbas_id));
        }
    }

    public function testRevoke_unused_sbas_rights()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        foreach ($appbox->get_databoxes() as $databox)
        {
            $sbas_id  = $databox->get_sbas_id();
            $base_ids = array();
            foreach ($databox->get_collections() as $collection)
            {
                $base_ids[] = $collection->get_base_id();
            }

            self::$user->ACL()->revoke_access_from_bases($base_ids);
            self::$user->ACL()->give_access_to_sbas(array($sbas_id));
            $this->assertTrue(self::$user->ACL()->has_access_to_sbas($sbas_id));
            self::$user->ACL()->revoke_unused_sbas_rights();
            $this->assertFalse(self::$user->ACL()->has_access_to_sbas($sbas_id));
        }
    }

    public function testRemove_quotas_on_base()
    {
        $this->testSet_quotas_on_base();
    }

    public function testSet_quotas_on_base()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        foreach ($appbox->get_databoxes() as $databox)
        {
            foreach ($databox->get_collections() as $collection)
            {
                $base_id = $collection->get_base_id();
                $droits  = 50;
                $restes  = 40;
                self::$user->ACL()->give_access_to_base(array($base_id));

                self::$user->ACL()->set_quotas_on_base($base_id, $droits, $restes);
                $this->assertTrue(self::$user->ACL()->is_restricted_download($base_id));

                self::$user->ACL()->remove_quotas_on_base($base_id);
                $this->assertFalse(self::$user->ACL()->is_restricted_download($base_id));

                return;
            }
        }
    }

    public function testDuplicate_right_from_bas()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());

        $first    = true;
        $base_ref = null;

        foreach ($appbox->get_databoxes() as $databox)
        {
            foreach ($databox->get_collections() as $collection)
            {
                $base_id = $collection->get_base_id();

                self::$user->ACL()->give_access_to_base(array($base_id));

                if ($first)
                {
                    self::$user->ACL()->update_rights_to_base($base_id, array('imgtools'      => true, 'chgstatus'     => true, 'canaddrecord'  => true, 'canputinalbum' => true));
                    $base_ref       = $base_id;
                }
                else
                {
                    self::$user->ACL()->duplicate_right_from_bas($base_ref, $base_id);
                }

                $this->assertTrue(self::$user->ACL()->has_right_on_base($base_id, 'imgtools'));
                $this->assertTrue(self::$user->ACL()->has_right_on_base($base_id, 'chgstatus'));
                $this->assertTrue(self::$user->ACL()->has_right_on_base($base_id, 'canaddrecord'));
                $this->assertTrue(self::$user->ACL()->has_right_on_base($base_id, 'canputinalbum'));

                $first = false;
            }
        }
    }

    public function testHas_hd_grant()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testHas_right_on_base()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());

        $rights_false = array(
          'imgtools'      => false
          , 'chgstatus'     => false
          , 'canaddrecord'  => false
          , 'canputinalbum' => false
        );

        $rights_true = array(
          'imgtools'     => true
          , 'chgstatus'    => true
          , 'canaddrecord' => true
        );


        foreach ($appbox->get_databoxes() as $databox)
        {
            foreach ($databox->get_collections() as $collection)
            {
                $base_id = $collection->get_base_id();
                self::$user->ACL()->give_access_to_base(array($base_id));
                self::$user->ACL()->update_rights_to_base($base_id, $rights_false);
                $this->assertFalse(self::$user->ACL()->has_right_on_base($base_id, 'imgtools'));
                $this->assertFalse(self::$user->ACL()->has_right_on_base($base_id, 'chgstatus'));
                $this->assertFalse(self::$user->ACL()->has_right_on_base($base_id, 'canaddrecord'));
                $this->assertFalse(self::$user->ACL()->has_right_on_base($base_id, 'canputinalbum'));
                self::$user->ACL()->update_rights_to_base($base_id, $rights_true);
                $this->assertTrue(self::$user->ACL()->has_right_on_base($base_id, 'imgtools'));
                $this->assertTrue(self::$user->ACL()->has_right_on_base($base_id, 'chgstatus'));
                $this->assertTrue(self::$user->ACL()->has_right_on_base($base_id, 'canaddrecord'));
                $this->assertFalse(self::$user->ACL()->has_right_on_base($base_id, 'canputinalbum'));
                self::$user->ACL()->update_rights_to_base($base_id, $rights_false);
                $this->assertFalse(self::$user->ACL()->has_right_on_base($base_id, 'imgtools'));
                $this->assertFalse(self::$user->ACL()->has_right_on_base($base_id, 'chgstatus'));
                $this->assertFalse(self::$user->ACL()->has_right_on_base($base_id, 'canaddrecord'));
                $this->assertFalse(self::$user->ACL()->has_right_on_base($base_id, 'canputinalbum'));
            }
        }
    }

    public function testIs_restricted_download()
    {
        $this->testSet_quotas_on_base();
    }

    public function testRemaining_download()
    {

        $appbox = appbox::get_instance(\bootstrap::getCore());
        foreach ($appbox->get_databoxes() as $databox)
        {
            foreach ($databox->get_collections() as $collection)
            {
                $base_id = $collection->get_base_id();
                $droits  = 50;
                $restes  = 40;
                self::$user->ACL()->give_access_to_base(array($base_id));

                self::$user->ACL()->set_quotas_on_base($base_id, $droits, $restes);
                $this->assertEquals(40, self::$user->ACL()->remaining_download($base_id));

                self::$user->ACL()->remove_quotas_on_base($base_id);
                $this->assertFalse(self::$user->ACL()->is_restricted_download($base_id));

                return;
            }
        }
    }

    public function testRemove_remaining()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        foreach ($appbox->get_databoxes() as $databox)
        {
            foreach ($databox->get_collections() as $collection)
            {
                $base_id = $collection->get_base_id();
                $droits  = 50;
                $restes  = 40;
                self::$user->ACL()->give_access_to_base(array($base_id));

                self::$user->ACL()->set_quotas_on_base($base_id, $droits, $restes);
                $this->assertEquals(40, self::$user->ACL()->remaining_download($base_id));
                self::$user->ACL()->remove_remaining($base_id, 1);
                $this->assertEquals(39, self::$user->ACL()->remaining_download($base_id));
                self::$user->ACL()->remove_remaining($base_id, 10);
                $this->assertEquals(29, self::$user->ACL()->remaining_download($base_id));
                self::$user->ACL()->remove_remaining($base_id, 100);
                $this->assertEquals(0, self::$user->ACL()->remaining_download($base_id));

                self::$user->ACL()->remove_quotas_on_base($base_id);
                $this->assertFalse(self::$user->ACL()->is_restricted_download($base_id));

                return;
            }
        }
    }

    public function testHas_right()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());


        $rights = array(
          'bas_modify_struct' => true
        );


        $this->assertFalse(self::$user->ACL()->has_right('bas_modify_struct'));
        $this->assertFalse(self::$user->ACL()->has_right('bas_modif_th'));

        foreach ($appbox->get_databoxes() as $databox)
        {
            self::$user->ACL()->give_access_to_sbas(array($databox->get_sbas_id()));
            self::$user->ACL()->update_rights_to_sbas($databox->get_sbas_id(), $rights);
            break;
        }

        $this->assertTrue(self::$user->ACL()->has_right('bas_modify_struct'));
        $this->assertFalse(self::$user->ACL()->has_right('bas_modif_th'));
    }

    public function testHas_right_on_sbas()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());

        $rights_false = array(
          'bas_modify_struct' => false
          , 'bas_manage'        => false
          , 'bas_chupub'        => false
          , 'bas_modif_th'      => false
        );

        $rights_true = array(
          'bas_modify_struct' => true
          , 'bas_manage'        => true
          , 'bas_chupub'        => true
          , 'bas_modif_th'      => true
        );


        foreach ($appbox->get_databoxes() as $databox)
        {
            self::$user->ACL()->give_access_to_sbas(array($databox->get_sbas_id()));
            self::$user->ACL()->update_rights_to_sbas($databox->get_sbas_id(), $rights_false);
            $this->assertFalse(self::$user->ACL()->has_right_on_sbas($databox->get_sbas_id(), 'bas_modify_struct'));
            $this->assertFalse(self::$user->ACL()->has_right_on_sbas($databox->get_sbas_id(), 'bas_manage'));
            $this->assertFalse(self::$user->ACL()->has_right_on_sbas($databox->get_sbas_id(), 'bas_chupub'));
            $this->assertFalse(self::$user->ACL()->has_right_on_sbas($databox->get_sbas_id(), 'bas_modif_th'));
            self::$user->ACL()->update_rights_to_sbas($databox->get_sbas_id(), $rights_true);
            $this->assertTrue(self::$user->ACL()->has_right_on_sbas($databox->get_sbas_id(), 'bas_modify_struct'));
            $this->assertTrue(self::$user->ACL()->has_right_on_sbas($databox->get_sbas_id(), 'bas_manage'));
            $this->assertTrue(self::$user->ACL()->has_right_on_sbas($databox->get_sbas_id(), 'bas_chupub'));
            $this->assertTrue(self::$user->ACL()->has_right_on_sbas($databox->get_sbas_id(), 'bas_modif_th'));
            self::$user->ACL()->update_rights_to_sbas($databox->get_sbas_id(), $rights_false);
            $this->assertFalse(self::$user->ACL()->has_right_on_sbas($databox->get_sbas_id(), 'bas_modify_struct'));
            $this->assertFalse(self::$user->ACL()->has_right_on_sbas($databox->get_sbas_id(), 'bas_manage'));
            $this->assertFalse(self::$user->ACL()->has_right_on_sbas($databox->get_sbas_id(), 'bas_chupub'));
            $this->assertFalse(self::$user->ACL()->has_right_on_sbas($databox->get_sbas_id(), 'bas_modif_th'));
        }
    }

    public function testGet_mask_and()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        foreach ($appbox->get_databoxes() as $databox)
        {
            foreach ($databox->get_collections() as $collection)
            {
                $base_id = $collection->get_base_id();

                self::$user->ACL()->give_access_to_base(array($base_id));
                self::$user->ACL()->update_rights_to_base($base_id, array('actif' => false));
                $this->assertFalse(self::$user->ACL()->get_mask_and($base_id));
                self::$user->ACL()->update_rights_to_base($base_id, array('actif' => true));
                $this->assertEquals('0', self::$user->ACL()->get_mask_and($base_id));
                self::$user->ACL()->update_rights_to_base($base_id, array('mask_and' => 42));
                $this->assertEquals('42', self::$user->ACL()->get_mask_and($base_id));
                self::$user->ACL()->update_rights_to_base($base_id, array('mask_and' => 1));
                $this->assertEquals('1', self::$user->ACL()->get_mask_and($base_id));
                self::$user->ACL()->update_rights_to_base($base_id, array('mask_and' => 0));
                $this->assertEquals('0', self::$user->ACL()->get_mask_and($base_id));
            }
        }
    }

    public function testGet_mask_xor()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        foreach ($appbox->get_databoxes() as $databox)
        {
            foreach ($databox->get_collections() as $collection)
            {
                $base_id = $collection->get_base_id();

                self::$user->ACL()->give_access_to_base(array($base_id));
                self::$user->ACL()->update_rights_to_base($base_id, array('actif' => false));
                $this->assertFalse(self::$user->ACL()->get_mask_xor($base_id));
                self::$user->ACL()->update_rights_to_base($base_id, array('actif' => true));
                $this->assertEquals('0', self::$user->ACL()->get_mask_xor($base_id));
                self::$user->ACL()->update_rights_to_base($base_id, array('mask_xor' => 42));
                $this->assertEquals('42', self::$user->ACL()->get_mask_xor($base_id));
                self::$user->ACL()->update_rights_to_base($base_id, array('mask_xor' => 1));
                $this->assertEquals('1', self::$user->ACL()->get_mask_xor($base_id));
                self::$user->ACL()->update_rights_to_base($base_id, array('mask_xor' => 0));
                $this->assertEquals('0', self::$user->ACL()->get_mask_xor($base_id));
            }
        }
    }

    public function testHas_access_to_base()
    {
        $appbox   = appbox::get_instance(\bootstrap::getCore());
        $base_ids = array();
        $n = 0;
        foreach ($appbox->get_databoxes() as $databox)
        {
            foreach ($databox->get_collections() as $collection)
            {
                $base_ids[] = $collection->get_base_id();
                $n ++;
            }
            self::$user->ACL()->give_access_to_sbas(array($databox->get_sbas_id()));
        }

        if ($n === 0)
            $this->fail('Not enough collection to test');

        self::$user->ACL()->give_access_to_base($base_ids);
        $bases = array_keys(self::$user->ACL()->get_granted_base());

        $this->assertEquals(count($base_ids), count($bases));


        $sql  = 'SELECT actif FROM basusr WHERE usr_id = :usr_id AND base_id = :base_id';
        $stmt = $appbox->get_connection()->prepare($sql);

        foreach ($bases as $base_id)
        {
            $stmt->execute(array(':usr_id'  => $appbox->get_session()->get_usr_id(), ':base_id' => $base_id));
            $row       = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->assertEquals(1, $row['actif']);

            $this->assertTrue(self::$user->ACL()->has_access_to_base($base_id));
            self::$user->ACL()->update_rights_to_base($base_id, array('actif' => false));

            $stmt->execute(array(':usr_id'  => $appbox->get_session()->get_usr_id(), ':base_id' => $base_id));
            $row       = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->assertEquals(0, $row['actif']);

            $this->assertFalse(self::$user->ACL()->has_access_to_base($base_id));
            self::$user->ACL()->update_rights_to_base($base_id, array('actif' => true));

            $stmt->execute(array(':usr_id'  => $appbox->get_session()->get_usr_id(), ':base_id' => $base_id));
            $row       = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->assertEquals(1, $row['actif']);

            $this->assertTrue(self::$user->ACL()->has_access_to_base($base_id));
            self::$user->ACL()->update_rights_to_base($base_id, array('actif' => false));
            $this->assertFalse(self::$user->ACL()->has_access_to_base($base_id));
        }
        self::$user->ACL()->give_access_to_base($base_ids);

        foreach ($bases as $base_id)
        {
            $this->assertTrue(self::$user->ACL()->has_access_to_base($base_id));
        }
        $stmt->closeCursor();
    }

    public function testGet_granted_base()
    {

        $appbox   = appbox::get_instance(\bootstrap::getCore());
        $base_ids = array();
        $n = 0;
        foreach ($appbox->get_databoxes() as $databox)
        {
            foreach ($databox->get_collections() as $collection)
            {
                $base_ids[] = $collection->get_base_id();
                $n ++;
            }
        }

        if ($n === 0)
            $this->fail('Not enough collection to test');

        self::$user->ACL()->give_access_to_base($base_ids);
        $bases = array_keys(self::$user->ACL()->get_granted_base());

        $this->assertEquals(count($bases), count($base_ids));
        $this->assertEquals($n, count($base_ids));

        foreach ($bases as $base_id)
        {
            try
            {
                $collection = collection::get_from_base_id($base_id);
                $this->assertTrue($collection instanceof collection);
                $this->assertEquals($base_id, $collection->get_base_id());
                unset($collection);
            }
            catch (Exception $e)
            {
                $this->fail('get granted base should returned OK collection');
            }
        }
    }

    public function testGet_granted_sbas()
    {
        $appbox   = appbox::get_instance(\bootstrap::getCore());
        $sbas_ids = array();
        $n = 0;
        foreach ($appbox->get_databoxes() as $databox)
        {
            $sbas_ids[] = $databox->get_sbas_id();
            $n ++;
        }
        self::$user->ACL()->give_access_to_sbas($sbas_ids);

        $sbas = self::$user->ACL()->get_granted_sbas();

        $this->assertEquals(count($sbas), count($sbas_ids));
        $this->assertEquals($n, count($sbas_ids));

        foreach ($sbas as $sbas_id => $databox)
        {
            try
            {
                $this->assertTrue($databox instanceof databox);
                $this->assertEquals($sbas_id, $databox->get_sbas_id());
                unset($databox);
            }
            catch (Exception $e)
            {
                $this->fail('get granted sbas should returned OK collection');
            }
        }
    }

    public function testHas_access_to_module()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());

        foreach ($appbox->get_databoxes() as $databox)
        {
            $base_ids = array();
            foreach ($databox->get_collections() as $collection)
            {
                $base_id    = $collection->get_base_id();
                $base_ids[] = $base_id;
            }
            self::$user->ACL()->revoke_access_from_bases($base_ids);
            self::$user->ACL()->revoke_unused_sbas_rights();
        }

        if (self::$user->is_admin())
            $this->assertTrue(self::$user->ACL()->has_access_to_module('admin'));
        else
            $this->assertFalse(self::$user->ACL()->has_access_to_module('admin'));
        $this->assertFalse(self::$user->ACL()->has_access_to_module('thesaurus'));
        $this->assertFalse(self::$user->ACL()->has_access_to_module('upload'));
        $this->assertFalse(self::$user->ACL()->has_access_to_module('report'));

        $found = false;
        foreach ($appbox->get_databoxes() as $databox)
        {
            foreach ($databox->get_collections() as $collection)
            {
                $base_id    = $collection->get_base_id();
                $base_ids[] = $base_id;
                self::$user->ACL()->update_rights_to_base($base_id, array('canreport' => true));
                $found      = true;
                break;
            }
            if ($found)
                break;
        }
        $this->assertTrue(self::$user->ACL()->has_access_to_module('report'));
        $this->assertFalse(self::$user->ACL()->has_access_to_module('thesaurus'));
        $this->assertFalse(self::$user->ACL()->has_access_to_module('upload'));

        foreach ($appbox->get_databoxes() as $databox)
        {
            self::$user->ACL()->update_rights_to_sbas($databox->get_sbas_id(), array('bas_modif_th' => true));
            $found         = true;
        }
        $this->assertTrue(self::$user->ACL()->has_access_to_module('report'));
        $this->assertTrue(self::$user->ACL()->has_access_to_module('thesaurus'));
        $this->assertFalse(self::$user->ACL()->has_access_to_module('upload'));


        $found = false;
        foreach ($appbox->get_databoxes() as $databox)
        {
            foreach ($databox->get_collections() as $collection)
            {
                $base_id    = $collection->get_base_id();
                $base_ids[] = $base_id;
                self::$user->ACL()->update_rights_to_base($base_id, array('canaddrecord' => true));
                $found         = true;
                break;
            }
            if ($found)
                break;
        }
        $this->assertTrue(self::$user->ACL()->has_access_to_module('report'));
        $this->assertTrue(self::$user->ACL()->has_access_to_module('thesaurus'));
        $this->assertTrue(self::$user->ACL()->has_access_to_module('upload'));
    }

    public function testis_limited()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());

        $found = false;

        foreach ($appbox->get_databoxes() as $databox)
        {
            foreach ($databox->get_collections() as $collection)
            {
                $base_id = $collection->get_base_id();

                if ( ! self::$user->ACL()->has_access_to_base($base_id))
                    continue;

                $this->assertFalse(self::$user->ACL()->is_limited($base_id));

                self::$user->ACL()->set_limits($base_id, true, new DateTime('-1 day'), new DateTime('+1 day'));

                $this->assertFalse(self::$user->ACL()->is_limited($base_id));

                self::$user->ACL()->set_limits($base_id, false, new DateTime('-1 day'), new DateTime('+1 day'));

                $this->assertFalse(self::$user->ACL()->is_limited($base_id));

                self::$user->ACL()->set_limits($base_id, true, new DateTime('+1 day'), new DateTime('+2 day'));

                $this->assertTrue(self::$user->ACL()->is_limited($base_id));

                self::$user->ACL()->set_limits($base_id, true, new DateTime('-2 day'), new DateTime('-1 day'));

                $this->assertTrue(self::$user->ACL()->is_limited($base_id));

                self::$user->ACL()->set_limits($base_id, true, new DateTime('-2 day'), new DateTime('+2 day'));

                $this->assertFalse(self::$user->ACL()->is_limited($base_id));

                self::$user->ACL()->set_limits($base_id, false, new DateTime('-2 day'), new DateTime('+2 day'));

                $this->assertFalse(self::$user->ACL()->is_limited($base_id));

                $found = true;
            }
        }

        if ( ! $found)
            $this->fail('Unable to test');
    }

    public function testget_limits()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());

        $found = false;

        foreach ($appbox->get_databoxes() as $databox)
        {
            foreach ($databox->get_collections() as $collection)
            {
                $base_id = $collection->get_base_id();

                if ( ! self::$user->ACL()->has_access_to_base($base_id))
                    continue;

                $minusone = new DateTime('-1 day');

                $plusone = new DateTime('+1 day');

                self::$user->ACL()->set_limits($base_id, true, $minusone, $plusone);

                $limits = self::$user->ACL()->get_limits($base_id);

                $this->assertEquals($limits['dmin'], $minusone);

                $this->assertEquals($limits['dmax'], $plusone);

                $minustwo = new DateTime('-2 day');

                $plustwo = new DateTime('-2 day');

                self::$user->ACL()->set_limits($base_id, true, $minustwo, $plustwo);

                $limits = self::$user->ACL()->get_limits($base_id);

                $this->assertEquals($limits['dmin'], $minustwo);

                $this->assertEquals($limits['dmax'], $plustwo);

                self::$user->ACL()->set_limits($base_id, false);

                $this->assertNull(self::$user->ACL()->get_limits($base_id));

                $found = true;
            }
        }

        if ( ! $found)
            $this->fail('Unable to test');
    }

    public function testset_limits()
    {
        $this->testget_limits();
    }

}
