<?php

class ACLTest extends \PhraseanetAuthenticatedTestCase
{
    /**
     * @var ACL
     */
    private static $object;

    public static function tearDownAfterClass()
    {
        self::resetUsersRights(self::$DI['app'], self::$DI['user']);
        self::$object = null;

        parent::tearDownAfterClass();
    }

    public function setup()
    {
        parent::setUp();

        if (!self::$object) {
            self::$object = self::$DI['app']['acl']->get(self::$DI['user']);
        }
    }

    public function testHasAccesToRecord()
    {
        $this->assertTrue(self::$object->has_status_access_to_record(self::$DI['record_1']));
    }

    public function testHasAccesToRecordStatus()
    {
        self::$DI['record_1']->set_binary_status(str_repeat('0', 32));
        self::$object->set_masks_on_base(self::$DI['record_1']->get_base_id(), '10000', '10000', '0', '0');
        self::$DI['record_1']->set_binary_status('10000');
        $this->assertFalse(self::$object->has_status_access_to_record(self::$DI['record_1']));
        self::$DI['record_1']->set_binary_status('00000');
        $this->assertTrue(self::$object->has_status_access_to_record(self::$DI['record_1']));
        self::$object->set_masks_on_base(self::$DI['record_1']->get_base_id(), '10000', '10000', '10000', '10000');
        $this->assertFalse(self::$object->has_status_access_to_record(self::$DI['record_1']));
        self::$DI['record_1']->set_binary_status('10000');
        $this->assertTrue(self::$object->has_status_access_to_record(self::$DI['record_1']));
        self::$object->set_masks_on_base(self::$DI['record_1']->get_base_id(), '0', '0', '0', '0');
        $this->assertTrue(self::$object->has_status_access_to_record(self::$DI['record_1']));
        self::$DI['record_1']->set_binary_status(str_repeat('0', 32));
        $this->assertTrue(self::$object->has_status_access_to_record(self::$DI['record_1']));
    }

    public function testHasAccesToRecordFailsOnBase()
    {
        $this->assertFalse(self::$object->has_access_to_record(self::$DI['record_no_access']));
    }

    public function testHasAccesToRecordFailsOnStatus()
    {
        $this->assertFalse(self::$object->has_access_to_record(self::$DI['record_no_access_by_status']));
    }

    public function testApplyModel()
    {
        $base_ids = [self::$DI['collection']->get_base_id()];
        self::$DI['app']['acl']->get(self::$DI['user_template'])->give_access_to_base($base_ids);

        foreach ($base_ids as $base_id) {
            self::$DI['app']['acl']->get(self::$DI['user_template'])->set_limits($base_id, 0);
        }

        self::$DI['app']['acl']->get(self::$DI['user_1'])->apply_model(self::$DI['user_template'], $base_ids);

        foreach ($base_ids as $base_id) {
            $this->assertTrue(self::$DI['app']['acl']->get(self::$DI['user_1'])->has_access_to_base($base_id));
        }

        foreach ($base_ids as $base_id) {
            $this->assertNull(self::$DI['app']['acl']->get(self::$DI['user_1'])->get_limits($base_id));
        }
    }

    public function testApplyModelWithTimeLimit()
    {
        $base_ids = [self::$DI['collection']->get_base_id()];
        self::$DI['app']['acl']->get(self::$DI['user_template'])->give_access_to_base($base_ids);

        $limit_from = new \DateTime('-1 day');
        $limit_to = new \DateTime('+1 day');

        foreach ($base_ids as $base_id) {
            self::$DI['app']['acl']->get(self::$DI['user_template'])->set_limits($base_id, 1, $limit_from, $limit_to);
        }

        self::$DI['app']['acl']->get(self::$DI['user_2'])->apply_model(self::$DI['user_template'], $base_ids);

        foreach ($base_ids as $base_id) {
            $this->assertTrue(self::$DI['app']['acl']->get(self::$DI['user_2'])->has_access_to_base($base_id));
        }
        foreach ($base_ids as $base_id) {
            $this->assertEquals(['dmin' => $limit_from, 'dmax' => $limit_to], self::$DI['app']['acl']->get(self::$DI['user_2'])->get_limits($base_id));
        }
    }

    public function testRevokeAndGiveAccessFromBases()
    {
        $baseId = self::$DI['collection']->get_base_id();
        $this->assertTrue(self::$object->has_access_to_base($baseId));
        self::$object->revoke_access_from_bases([$baseId]);
        $this->assertFalse(self::$object->has_access_to_base($baseId));
        self::$object->give_access_to_base([$baseId]);
    }

    public function testGive_access_to_sbas()
    {

        foreach (self::$DI['app']['phraseanet.appbox']->get_databoxes() as $databox) {
            $sbas_id = $databox->get_sbas_id();
            $base_ids = [];
            foreach ($databox->get_collections() as $collection) {
                $base_ids[] = $collection->get_base_id();
            }

            self::$object->revoke_access_from_bases($base_ids);
            self::$object->revoke_unused_sbas_rights();
            $this->assertFalse(self::$object->has_access_to_sbas($sbas_id));
            self::$object->give_access_to_sbas([$sbas_id]);
            $this->assertTrue(self::$object->has_access_to_sbas($sbas_id));
        }
    }

    public function testRevoke_unused_sbas_rights()
    {
        foreach (self::$DI['app']['phraseanet.appbox']->get_databoxes() as $databox) {
            $sbas_id = $databox->get_sbas_id();
            $base_ids = [];
            foreach ($databox->get_collections() as $collection) {
                $base_ids[] = $collection->get_base_id();
            }

            self::$object->revoke_access_from_bases($base_ids);
            self::$object->give_access_to_sbas([$sbas_id]);
            $this->assertTrue(self::$object->has_access_to_sbas($sbas_id));
            self::$object->revoke_unused_sbas_rights();
            $this->assertFalse(self::$object->has_access_to_sbas($sbas_id));
        }
    }

    public function testRemove_quotas_on_base()
    {
        $this->testSet_quotas_on_base();
    }

    public function testSet_quotas_on_base()
    {
        foreach (self::$DI['app']['phraseanet.appbox']->get_databoxes() as $databox) {
            foreach ($databox->get_collections() as $collection) {
                $base_id = $collection->get_base_id();
                $droits = 50;
                $restes = 40;
                self::$object->give_access_to_base([$base_id]);

                self::$object->set_quotas_on_base($base_id, $droits, $restes);
                $this->assertTrue(self::$object->is_restricted_download($base_id));

                self::$object->remove_quotas_on_base($base_id);
                $this->assertFalse(self::$object->is_restricted_download($base_id));

                return;
            }
        }
    }

    public function testDuplicate_right_from_bas()
    {

        $first = true;
        $base_ref = null;

        foreach (self::$DI['app']['phraseanet.appbox']->get_databoxes() as $databox) {
            foreach ($databox->get_collections() as $collection) {
                $base_id = $collection->get_base_id();

                self::$object->give_access_to_base([$base_id]);

                if ($first) {
                    self::$object->update_rights_to_base($base_id, ['imgtools'      => true, 'chgstatus'     => true, 'canaddrecord'  => true, 'canputinalbum' => true]);
                    $base_ref = $base_id;
                } else {
                    self::$object->duplicate_right_from_bas($base_ref, $base_id);
                }

                $this->assertTrue(self::$object->has_right_on_base($base_id, 'imgtools'));
                $this->assertTrue(self::$object->has_right_on_base($base_id, 'chgstatus'));
                $this->assertTrue(self::$object->has_right_on_base($base_id, 'canaddrecord'));
                $this->assertTrue(self::$object->has_right_on_base($base_id, 'canputinalbum'));

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

    public function testHasRightOnBase()
    {
        $rights_false = [
            'imgtools'      => false,
            'chgstatus'     => false,
            'canaddrecord'  => false,
            'canputinalbum' => false,
        ];

        $rights_true = [
            'imgtools'     => true,
            'chgstatus'    => true,
            'canaddrecord' => true,
        ];

        foreach (self::$DI['app']['phraseanet.appbox']->get_databoxes() as $databox) {
            foreach ($databox->get_collections() as $collection) {
                $base_id = $collection->get_base_id();
                self::$object->give_access_to_base([$base_id]);
                self::$object->update_rights_to_base($base_id, $rights_false);
                $this->assertFalse(self::$object->has_right_on_base($base_id, 'imgtools'));
                $this->assertFalse(self::$object->has_right_on_base($base_id, 'chgstatus'));
                $this->assertFalse(self::$object->has_right_on_base($base_id, 'canaddrecord'));
                $this->assertFalse(self::$object->has_right_on_base($base_id, 'canputinalbum'));
                self::$object->update_rights_to_base($base_id, $rights_true);
                $this->assertTrue(self::$object->has_right_on_base($base_id, 'imgtools'));
                $this->assertTrue(self::$object->has_right_on_base($base_id, 'chgstatus'));
                $this->assertTrue(self::$object->has_right_on_base($base_id, 'canaddrecord'));
                $this->assertFalse(self::$object->has_right_on_base($base_id, 'canputinalbum'));
                self::$object->update_rights_to_base($base_id, $rights_false);
                $this->assertFalse(self::$object->has_right_on_base($base_id, 'imgtools'));
                $this->assertFalse(self::$object->has_right_on_base($base_id, 'chgstatus'));
                $this->assertFalse(self::$object->has_right_on_base($base_id, 'canaddrecord'));
                $this->assertFalse(self::$object->has_right_on_base($base_id, 'canputinalbum'));
            }
        }
    }

    /**
     * @covers \ACL::get_order_master_collections
     * @covers \ACL::set_order_master
     */
    public function testGetSetOrder_master()
    {
        $appbox = self::$DI['app']['phraseanet.appbox'];
        $acl = self::$object;

        foreach ($appbox->get_databoxes() as $databox) {
            foreach ($databox->get_collections() as $collection) {
                $acl->set_order_master($collection, false);
            }
        }
        $this->assertEquals(0, count($acl->get_order_master_collections()));

        $tbas = [];
        foreach ($appbox->get_databoxes() as $databox) {
            foreach ($databox->get_collections() as $collection) {
                $bid = $collection->get_base_id();
                if (!$acl->has_access_to_base($bid)) {
                    $acl->give_access_to_base([$bid]);
                }
                $acl->set_order_master($collection, true);
                $tbas[] = $bid;
            }
        }
        $tbas = array_diff($tbas, array_map(function (\collection $collection) {
            return $collection->get_base_id();
        }, $acl->get_order_master_collections()));
        $this->assertEquals(0, count($tbas));
    }

    public function testIs_restricted_download()
    {
        $this->testSet_quotas_on_base();
    }

    public function testRemaining_download()
    {
        $base_id = self::$DI['collection']->get_base_id();
        $droits = 50;
        $restes = 40;
        self::$object->give_access_to_base([$base_id]);

        self::$object->set_quotas_on_base($base_id, $droits, $restes);
        $this->assertEquals(40, self::$object->remaining_download($base_id));

        self::$object->remove_quotas_on_base($base_id);
        $this->assertFalse(self::$object->is_restricted_download($base_id));

        return;
    }

    public function testRemove_remaining()
    {
        $base_id = self::$DI['collection']->get_base_id();
        $droits = 50;
        $restes = 40;
        self::$object->give_access_to_base([$base_id]);

        self::$object->set_quotas_on_base($base_id, $droits, $restes);
        $this->assertEquals(40, self::$object->remaining_download($base_id));
        self::$object->remove_remaining($base_id, 1);
        $this->assertEquals(39, self::$object->remaining_download($base_id));
        self::$object->remove_remaining($base_id, 10);
        $this->assertEquals(29, self::$object->remaining_download($base_id));
        self::$object->remove_remaining($base_id, 100);
        $this->assertEquals(0, self::$object->remaining_download($base_id));

        self::$object->remove_quotas_on_base($base_id);
        $this->assertFalse(self::$object->is_restricted_download($base_id));
    }

    public function testHasRight()
    {
        $databox = self::$DI['collection']->get_databox();
        self::$object->give_access_to_sbas([$databox->get_sbas_id()]);
        self::$object->update_rights_to_sbas($databox->get_sbas_id(), [
            'bas_modify_struct' => false,
            'bas_modif_th'      => false,
        ]);

        $this->assertFalse(self::$object->has_right('bas_modify_struct'));
        $this->assertFalse(self::$object->has_right('bas_modif_th'));

        self::$object->update_rights_to_sbas($databox->get_sbas_id(), [
            'bas_modify_struct' => true,
        ]);

        $this->assertTrue(self::$object->has_right('bas_modify_struct'));
        $this->assertFalse(self::$object->has_right('bas_modif_th'));
    }

    public function testHasRightOnSbas()
    {
        $rights_false = [
            'bas_modify_struct' => false,
            'bas_manage'        => false,
            'bas_chupub'        => false,
            'bas_modif_th'      => false,
        ];

        $rights_true = [
            'bas_modify_struct' => true,
            'bas_manage'        => true,
            'bas_chupub'        => true,
            'bas_modif_th'      => true,
        ];

        foreach (self::$DI['app']['phraseanet.appbox']->get_databoxes() as $databox) {
            self::$object->give_access_to_sbas([$databox->get_sbas_id()]);
            self::$object->update_rights_to_sbas($databox->get_sbas_id(), $rights_false);
            $this->assertFalse(self::$object->has_right_on_sbas($databox->get_sbas_id(), 'bas_modify_struct'));
            $this->assertFalse(self::$object->has_right_on_sbas($databox->get_sbas_id(), 'bas_manage'));
            $this->assertFalse(self::$object->has_right_on_sbas($databox->get_sbas_id(), 'bas_chupub'));
            $this->assertFalse(self::$object->has_right_on_sbas($databox->get_sbas_id(), 'bas_modif_th'));
            self::$object->update_rights_to_sbas($databox->get_sbas_id(), $rights_true);
            $this->assertTrue(self::$object->has_right_on_sbas($databox->get_sbas_id(), 'bas_modify_struct'));
            $this->assertTrue(self::$object->has_right_on_sbas($databox->get_sbas_id(), 'bas_manage'));
            $this->assertTrue(self::$object->has_right_on_sbas($databox->get_sbas_id(), 'bas_chupub'));
            $this->assertTrue(self::$object->has_right_on_sbas($databox->get_sbas_id(), 'bas_modif_th'));
            self::$object->update_rights_to_sbas($databox->get_sbas_id(), $rights_false);
            $this->assertFalse(self::$object->has_right_on_sbas($databox->get_sbas_id(), 'bas_modify_struct'));
            $this->assertFalse(self::$object->has_right_on_sbas($databox->get_sbas_id(), 'bas_manage'));
            $this->assertFalse(self::$object->has_right_on_sbas($databox->get_sbas_id(), 'bas_chupub'));
            $this->assertFalse(self::$object->has_right_on_sbas($databox->get_sbas_id(), 'bas_modif_th'));
        }
    }

    public function testGet_mask_and()
    {
        foreach (self::$DI['app']['phraseanet.appbox']->get_databoxes() as $databox) {
            foreach ($databox->get_collections() as $collection) {
                $base_id = $collection->get_base_id();

                self::$object->give_access_to_base([$base_id]);
                self::$object->update_rights_to_base($base_id, ['actif' => false]);
                $this->assertFalse(self::$object->get_mask_and($base_id));
                self::$object->update_rights_to_base($base_id, ['mask_and' => 42]);
                $this->assertEquals('42', self::$object->get_mask_and($base_id));
                self::$object->update_rights_to_base($base_id, ['mask_and' => 1]);
                $this->assertEquals('1', self::$object->get_mask_and($base_id));
                self::$object->update_rights_to_base($base_id, ['mask_and' => 0]);
                $this->assertEquals('0', self::$object->get_mask_and($base_id));
            }
        }
    }

    public function testGet_mask_xor()
    {
        foreach (self::$DI['app']['phraseanet.appbox']->get_databoxes() as $databox) {
            foreach ($databox->get_collections() as $collection) {
                $base_id = $collection->get_base_id();

                self::$object->give_access_to_base([$base_id]);
                self::$object->update_rights_to_base($base_id, ['actif' => false]);
                $this->assertFalse(self::$object->get_mask_xor($base_id));
                self::$object->update_rights_to_base($base_id, ['actif' => true]);
                self::$object->update_rights_to_base($base_id, ['mask_xor' => 42]);
                $this->assertEquals('42', self::$object->get_mask_xor($base_id));
                self::$object->update_rights_to_base($base_id, ['mask_xor' => 1]);
                $this->assertEquals('1', self::$object->get_mask_xor($base_id));
                self::$object->update_rights_to_base($base_id, ['mask_xor' => 0]);
                $this->assertEquals('0', self::$object->get_mask_xor($base_id));
            }
        }
    }

    public function testHas_access_to_base()
    {
        $base_ids = [];
        $n = 0;
        foreach (self::$DI['app']['phraseanet.appbox']->get_databoxes() as $databox) {
            foreach ($databox->get_collections() as $collection) {
                $base_ids[] = $collection->get_base_id();
                $n ++;
            }
            self::$object->give_access_to_sbas([$databox->get_sbas_id()]);
        }

        if ($n === 0)
            $this->fail('Not enough collection to test');

        self::$object->give_access_to_base($base_ids);
        $bases = array_keys(self::$object->get_granted_base());

        $this->assertEquals(count($base_ids), count($bases));

        $sql = 'SELECT actif FROM basusr WHERE usr_id = :usr_id AND base_id = :base_id';
        $stmt = self::$DI['app']['phraseanet.appbox']->get_connection()->prepare($sql);

        foreach ($bases as $base_id) {
            $stmt->execute([':usr_id'  => self::$DI['app']['authentication']->getUser()->getId(), ':base_id' => $base_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->assertEquals(1, $row['actif']);

            $this->assertTrue(self::$object->has_access_to_base($base_id));
            self::$object->update_rights_to_base($base_id, ['actif' => false]);

            $stmt->execute([':usr_id'  => self::$DI['app']['authentication']->getUser()->getId(), ':base_id' => $base_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->assertEquals(0, $row['actif']);

            $this->assertFalse(self::$object->has_access_to_base($base_id));
            self::$object->update_rights_to_base($base_id, ['actif' => true]);

            $stmt->execute([':usr_id'  => self::$DI['app']['authentication']->getUser()->getId(), ':base_id' => $base_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->assertEquals(1, $row['actif']);

            $this->assertTrue(self::$object->has_access_to_base($base_id));
            self::$object->update_rights_to_base($base_id, ['actif' => false]);
            $this->assertFalse(self::$object->has_access_to_base($base_id));
        }
        self::$object->give_access_to_base($base_ids);

        foreach ($bases as $base_id) {
            $this->assertTrue(self::$object->has_access_to_base($base_id));
        }
        $stmt->closeCursor();
    }

    public function testGet_granted_base()
    {
        $base_ids = [];
        $n = 0;
        foreach (self::$DI['app']['phraseanet.appbox']->get_databoxes() as $databox) {
            foreach ($databox->get_collections() as $collection) {
                $base_ids[] = $collection->get_base_id();
                $n ++;
            }
        }

        if ($n === 0)
            $this->fail('Not enough collection to test');

        self::$object->give_access_to_base($base_ids);
        $bases = array_keys(self::$object->get_granted_base());

        $this->assertEquals(count($bases), count($base_ids));
        $this->assertEquals($n, count($base_ids));

        foreach ($bases as $base_id) {
            try {
                $collection = collection::get_from_base_id(self::$DI['app'], $base_id);
                $this->assertTrue($collection instanceof collection);
                $this->assertEquals($base_id, $collection->get_base_id());
                unset($collection);
            } catch (Exception $e) {
                $this->fail('get granted base should returned OK collection');
            }
        }
    }

    public function testGet_granted_sbas()
    {
        $sbas_ids = [];
        $n = 0;
        foreach (self::$DI['app']['phraseanet.appbox']->get_databoxes() as $databox) {
            $sbas_ids[] = $databox->get_sbas_id();
            $n ++;
        }
        self::$object->give_access_to_sbas($sbas_ids);

        $sbas = self::$object->get_granted_sbas();

        $this->assertEquals(count($sbas), count($sbas_ids));
        $this->assertEquals($n, count($sbas_ids));

        foreach ($sbas as $sbas_id => $databox) {
            try {
                $this->assertTrue($databox instanceof databox);
                $this->assertEquals($sbas_id, $databox->get_sbas_id());
                unset($databox);
            } catch (Exception $e) {
                $this->fail('get granted sbas should returned OK collection');
            }
        }
    }

    public function testHas_access_to_module()
    {
        foreach (self::$DI['app']['phraseanet.appbox']->get_databoxes() as $databox) {
            $base_ids = [];
            foreach ($databox->get_collections() as $collection) {
                $base_id = $collection->get_base_id();
                $base_ids[] = $base_id;
            }
            self::$object->revoke_access_from_bases($base_ids);
            self::$object->revoke_unused_sbas_rights();
        }

        if (self::$object->is_admin())
            $this->assertTrue(self::$object->has_access_to_module('admin'));
        else
            $this->assertFalse(self::$object->has_access_to_module('admin'));
        $this->assertFalse(self::$object->has_access_to_module('thesaurus'));
        $this->assertFalse(self::$object->has_access_to_module('upload'));
        $this->assertFalse(self::$object->has_access_to_module('report'));

        $found = false;
        foreach (self::$DI['app']['phraseanet.appbox']->get_databoxes() as $databox) {
            foreach ($databox->get_collections() as $collection) {
                $base_id = $collection->get_base_id();
                $base_ids[] = $base_id;
                self::$object->update_rights_to_base($base_id, ['canreport' => true]);
                $found = true;
                break;
            }
            if ($found)
                break;
        }
        $this->assertTrue(self::$object->has_access_to_module('report'));
        $this->assertFalse(self::$object->has_access_to_module('thesaurus'));
        $this->assertFalse(self::$object->has_access_to_module('upload'));

        foreach (self::$DI['app']['phraseanet.appbox']->get_databoxes() as $databox) {
            self::$object->update_rights_to_sbas($databox->get_sbas_id(), ['bas_modif_th' => true]);
            $found = true;
        }
        $this->assertTrue(self::$object->has_access_to_module('report'));
        $this->assertTrue(self::$object->has_access_to_module('thesaurus'));
        $this->assertFalse(self::$object->has_access_to_module('upload'));

        $found = false;
        foreach (self::$DI['app']['phraseanet.appbox']->get_databoxes() as $databox) {
            foreach ($databox->get_collections() as $collection) {
                $base_id = $collection->get_base_id();
                $base_ids[] = $base_id;
                self::$object->update_rights_to_base($base_id, ['canaddrecord' => true]);
                $found = true;
                break;
            }
            if ($found)
                break;
        }
        $this->assertTrue(self::$object->has_access_to_module('report'));
        $this->assertTrue(self::$object->has_access_to_module('thesaurus'));
        $this->assertTrue(self::$object->has_access_to_module('upload'));
    }

    public function testis_limited()
    {

        $found = false;

        foreach (self::$DI['app']['phraseanet.appbox']->get_databoxes() as $databox) {
            foreach ($databox->get_collections() as $collection) {
                $base_id = $collection->get_base_id();

                if ( ! self::$object->has_access_to_base($base_id))
                    continue;

                $this->assertFalse(self::$object->is_limited($base_id));
                self::$object->set_limits($base_id, true, new DateTime('-1 day'), new DateTime('+1 day'));
                $this->assertFalse(self::$object->is_limited($base_id));
                self::$object->set_limits($base_id, false, new DateTime('-1 day'), new DateTime('+1 day'));
                $this->assertFalse(self::$object->is_limited($base_id));
                self::$object->set_limits($base_id, true, new DateTime('+1 day'), new DateTime('+2 day'));
                $this->assertTrue(self::$object->is_limited($base_id));
                self::$object->set_limits($base_id, true, new DateTime('-2 day'), new DateTime('-1 day'));
                $this->assertTrue(self::$object->is_limited($base_id));
                self::$object->set_limits($base_id, true, new DateTime('-2 day'), new DateTime('+2 day'));
                $this->assertFalse(self::$object->is_limited($base_id));
                self::$object->set_limits($base_id, false, new DateTime('-2 day'), new DateTime('+2 day'));
                $this->assertFalse(self::$object->is_limited($base_id));
                $found = true;
            }
        }

        if ( ! $found)
            $this->fail('Unable to test');
    }

    public function testget_limits()
    {

        $found = false;

        foreach (self::$DI['app']['phraseanet.appbox']->get_databoxes() as $databox) {
            foreach ($databox->get_collections() as $collection) {
                $base_id = $collection->get_base_id();

                if ( ! self::$object->has_access_to_base($base_id))
                    continue;

                $minusone = new DateTime('-1 day');
                $plusone = new DateTime('+1 day');
                self::$object->set_limits($base_id, true, $minusone, $plusone);
                $limits = self::$object->get_limits($base_id);
                $this->assertEquals($limits['dmin'], $minusone);
                $this->assertEquals($limits['dmax'], $plusone);
                $minustwo = new DateTime('-2 day');
                $plustwo = new DateTime('-2 day');
                self::$object->set_limits($base_id, true, $minustwo, $plustwo);
                $limits = self::$object->get_limits($base_id);
                $this->assertEquals($limits['dmin'], $minustwo);
                $this->assertEquals($limits['dmax'], $plustwo);
                self::$object->set_limits($base_id, false);
                $this->assertNull(self::$object->get_limits($base_id));
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
