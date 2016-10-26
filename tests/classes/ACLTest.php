<?php

/**
 * @group functional
 * @group legacy
 */
class ACLTest extends \PhraseanetTestCase
{
    /** @var ACL */
    private $object;

    public function setup()
    {
        parent::setUp();

        self::resetUsersRights(self::$DI['app'], self::$DI['user']);
        $this->object = self::$DI['app']->getAclForUser(self::$DI['user']);
    }

    public function tearDown()
    {
        $this->object = null;

        parent::tearDown();
    }

    public function testHasAccesToRecord()
    {
        $this->assertTrue($this->object->has_status_access_to_record(self::$DI['record_1']));
    }

    public function testHasAccessToRecordStatus()
    {
        $record1 = $this->getRecord1();

        $record1->setStatus(str_repeat('0', 32));
        $this->object->set_masks_on_base($record1->getBaseId(), '10000', '10000', '0', '0');

        $record1->setStatus('10000');
        $this->assertFalse($this->object->has_status_access_to_record($record1));

        $record1->setStatus('00000');
        $this->assertTrue($this->object->has_status_access_to_record($record1));

        $this->object->set_masks_on_base($record1->getBaseId(), '10000', '10000', '10000', '10000');
        $this->assertFalse($this->object->has_status_access_to_record($record1));

        $record1->setStatus('10000');
        $this->assertTrue($this->object->has_status_access_to_record($record1));

        $this->object->set_masks_on_base($record1->getBaseId(), '0', '0', '0', '0');
        $this->assertTrue($this->object->has_status_access_to_record($record1));

        $record1->setStatus(str_repeat('0', 32));
        $this->assertTrue($this->object->has_status_access_to_record($record1));
    }

    public function testHasAccesToRecordFailsOnBase()
    {
        $this->markTestIncomplete('Check access fail in not allowed collection');
    }

    public function testHasAccesToRecordFailsOnStatus()
    {
        $this->markTestIncomplete('Check access fail if status restriction');
    }

    public function testApplyModel()
    {
        $base_ids = [self::$DI['collection']->get_base_id()];
        self::$DI['app']->getAclForUser(self::$DI['user_template'])->give_access_to_base($base_ids);

        foreach ($base_ids as $base_id) {
            self::$DI['app']->getAclForUser(self::$DI['user_template'])->set_limits($base_id, 0);
        }

        self::$DI['app']->getAclForUser(self::$DI['user_1'])->apply_model(self::$DI['user_template'], $base_ids);

        foreach ($base_ids as $base_id) {
            $this->assertTrue(self::$DI['app']->getAclForUser(self::$DI['user_1'])->has_access_to_base($base_id));
        }

        foreach ($base_ids as $base_id) {
            $this->assertNull(self::$DI['app']->getAclForUser(self::$DI['user_1'])->get_limits($base_id));
        }
    }

    public function testApplyModelWithTimeLimit()
    {
        $base_ids = [self::$DI['collection']->get_base_id()];
        self::$DI['app']->getAclForUser(self::$DI['user_template'])->give_access_to_base($base_ids);

        $limit_from = new \DateTime('-1 day');
        $limit_to = new \DateTime('+1 day');

        foreach ($base_ids as $base_id) {
            self::$DI['app']->getAclForUser(self::$DI['user_template'])->set_limits($base_id, 1, $limit_from, $limit_to);
        }

        self::$DI['app']->getAclForUser(self::$DI['user_2'])->apply_model(self::$DI['user_template'], $base_ids);

        foreach ($base_ids as $base_id) {
            $this->assertTrue(self::$DI['app']->getAclForUser(self::$DI['user_2'])->has_access_to_base($base_id));
        }
        foreach ($base_ids as $base_id) {
            $this->assertEquals(['dmin' => $limit_from, 'dmax' => $limit_to], self::$DI['app']->getAclForUser(self::$DI['user_2'])->get_limits($base_id));
        }
    }

    public function testRevokeAndGiveAccessFromBases()
    {
        $baseId = self::$DI['collection']->get_base_id();
        $this->assertTrue($this->object->has_access_to_base($baseId));
        $this->object->revoke_access_from_bases([$baseId]);
        $this->assertFalse($this->object->has_access_to_base($baseId));
        $this->object->give_access_to_base([$baseId]);
    }

    public function testGive_access_to_sbas()
    {
        /** @var Databox $databox */
        foreach (self::$DI['app']->getDataboxes() as $databox) {
            $sbas_id = $databox->get_sbas_id();
            $base_ids = [];
            foreach ($databox->get_collections() as $collection) {
                $base_ids[] = $collection->get_base_id();
            }

            $this->object->revoke_access_from_bases($base_ids);
            $this->object->revoke_unused_sbas_rights();
            $this->assertFalse($this->object->has_access_to_sbas($sbas_id));
            $this->object->give_access_to_sbas([$sbas_id]);
            $this->assertTrue($this->object->has_access_to_sbas($sbas_id));
        }
    }

    public function testRevoke_unused_sbas_rights()
    {
        /** @var Databox $databox */
        foreach (self::$DI['app']->getDataboxes() as $databox) {
            $sbas_id = $databox->get_sbas_id();
            $base_ids = [];
            foreach ($databox->get_collections() as $collection) {
                $base_ids[] = $collection->get_base_id();
            }

            $this->object->revoke_access_from_bases($base_ids);
            $this->object->give_access_to_sbas([$sbas_id]);
            $this->assertTrue($this->object->has_access_to_sbas($sbas_id));
            $this->object->revoke_unused_sbas_rights();
            $this->assertFalse($this->object->has_access_to_sbas($sbas_id));
        }
    }

    public function testRemove_quotas_on_base()
    {
        $this->testSet_quotas_on_base();
    }

    public function testSet_quotas_on_base()
    {
        /** @var Databox $databox */
        foreach (self::$DI['app']->getDataboxes() as $databox) {
            foreach ($databox->get_collections() as $collection) {
                $base_id = $collection->get_base_id();
                $droits = 50;
                $restes = 40;
                $this->object->give_access_to_base([$base_id]);

                $this->object->set_quotas_on_base($base_id, $droits, $restes);
                $this->assertTrue($this->object->is_restricted_download($base_id));

                $this->object->remove_quotas_on_base($base_id);
                $this->assertFalse($this->object->is_restricted_download($base_id));

                return;
            }
        }
    }

    public function testDuplicate_right_from_bas()
    {
        $first = true;
        $base_ref = null;

        /** @var Databox $databox */
        foreach (self::$DI['app']->getDataboxes() as $databox) {
            foreach ($databox->get_collections() as $collection) {
                $base_id = $collection->get_base_id();

                $this->object->give_access_to_base([$base_id]);

                if ($first) {
                    $this->object->update_rights_to_base(
                        $base_id,
                        [
                            \ACL::IMGTOOLS      => true,
                            \ACL::CHGSTATUS     => true,
                            \ACL::CANADDRECORD  => true,
                            \ACL::CANPUTINALBUM => true
                        ]
                    );
                    $base_ref = $base_id;
                } else {
                    $this->object->duplicate_right_from_bas($base_ref, $base_id);
                }

                $this->assertTrue($this->object->has_right_on_base($base_id, \ACL::IMGTOOLS));
                $this->assertTrue($this->object->has_right_on_base($base_id, \ACL::CHGSTATUS));
                $this->assertTrue($this->object->has_right_on_base($base_id, \ACL::CANADDRECORD));
                $this->assertTrue($this->object->has_right_on_base($base_id, \ACL::CANPUTINALBUM));

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
            \ACL::IMGTOOLS      => false,
            \ACL::CHGSTATUS     => false,
            \ACL::CANADDRECORD  => false,
            \ACL::CANPUTINALBUM => false,
        ];

        $rights_true = [
            \ACL::IMGTOOLS     => true,
            \ACL::CHGSTATUS    => true,
            \ACL::CANADDRECORD => true,
        ];

        /** @var Databox $databox */
        foreach (self::$DI['app']->getDataboxes() as $databox) {
            foreach ($databox->get_collections() as $collection) {
                $base_id = $collection->get_base_id();
                $this->object->give_access_to_base([$base_id]);

                $this->object->update_rights_to_base(
                    $base_id,
                    $rights_false
                );
                $this->assertFalse($this->object->has_right_on_base($base_id, \ACL::IMGTOOLS));
                $this->assertFalse($this->object->has_right_on_base($base_id, \ACL::CHGSTATUS));
                $this->assertFalse($this->object->has_right_on_base($base_id, \ACL::CANADDRECORD));
                $this->assertFalse($this->object->has_right_on_base($base_id, \ACL::CANPUTINALBUM));

                $this->object->update_rights_to_base(
                    $base_id,
                    $rights_true
                );
                $this->assertTrue($this->object->has_right_on_base($base_id, \ACL::IMGTOOLS));
                $this->assertTrue($this->object->has_right_on_base($base_id, \ACL::CHGSTATUS));
                $this->assertTrue($this->object->has_right_on_base($base_id, \ACL::CANADDRECORD));
                $this->assertFalse($this->object->has_right_on_base($base_id, \ACL::CANPUTINALBUM));

                $this->object->update_rights_to_base(
                    $base_id,
                    $rights_false
                );
                $this->assertFalse($this->object->has_right_on_base($base_id, \ACL::IMGTOOLS));
                $this->assertFalse($this->object->has_right_on_base($base_id, \ACL::CHGSTATUS));
                $this->assertFalse($this->object->has_right_on_base($base_id, \ACL::CANADDRECORD));
                $this->assertFalse($this->object->has_right_on_base($base_id, \ACL::CANPUTINALBUM));
            }
        }
    }

    /**
     * @covers \ACL::get_order_master_collections
     * @covers \ACL::set_order_master
     */
    public function testGetSetOrder_master()
    {
        /** @var Appbox $appbox */
        $appbox = self::$DI['app']['phraseanet.appbox'];
        $acl = $this->object;

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

        foreach ($appbox->get_databoxes() as $databox) {
            foreach ($databox->get_collections() as $collection) {
                $acl->set_order_master($collection, true);
            }
        }
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
        $this->object->give_access_to_base([$base_id]);

        $this->object->set_quotas_on_base($base_id, $droits, $restes);
        $this->assertEquals(40, $this->object->remaining_download($base_id));

        $this->object->remove_quotas_on_base($base_id);
        $this->assertFalse($this->object->is_restricted_download($base_id));

        return;
    }

    public function testRemove_remaining()
    {
        $base_id = self::$DI['collection']->get_base_id();
        $droits = 50;
        $restes = 40;
        $this->object->give_access_to_base([$base_id]);

        $this->object->set_quotas_on_base($base_id, $droits, $restes);
        $this->assertEquals(40, $this->object->remaining_download($base_id));
        $this->object->remove_remaining($base_id, 1);
        $this->assertEquals(39, $this->object->remaining_download($base_id));
        $this->object->remove_remaining($base_id, 10);
        $this->assertEquals(29, $this->object->remaining_download($base_id));
        $this->object->remove_remaining($base_id, 100);
        $this->assertEquals(0, $this->object->remaining_download($base_id));

        $this->object->remove_quotas_on_base($base_id);
        $this->assertFalse($this->object->is_restricted_download($base_id));
    }

    public function testHasRight()
    {
        /** @var Databox $databox */
        $databox = self::$DI['collection']->get_databox();
        $this->object->give_access_to_sbas([$databox->get_sbas_id()]);
        $this->object->update_rights_to_sbas(
            $databox->get_sbas_id(),
            [
                \ACL::BAS_MODIFY_STRUCT  => false,
                \ACL::BAS_MODIF_TH       => false
            ]
        );

        $this->assertFalse($this->object->has_right(\ACL::BAS_MODIFY_STRUCT ));
        $this->assertFalse($this->object->has_right(\ACL::BAS_MODIF_TH));

        $this->object->update_rights_to_sbas(
            $databox->get_sbas_id(),
            [
                \ACL::BAS_MODIFY_STRUCT  => true
            ]
        );

        $this->assertTrue($this->object->has_right(\ACL::BAS_MODIFY_STRUCT ));
        $this->assertFalse($this->object->has_right(\ACL::BAS_MODIF_TH));
    }

    public function testHasRightOnSbas()
    {
        $rights_false = [
            \ACL::BAS_MODIFY_STRUCT => false,
            \ACL::BAS_MANAGE        => false,
            \ACL::BAS_CHUPUB        => false,
            \ACL::BAS_MODIF_TH      => false
        ];

        $rights_true = [
            \ACL::BAS_MODIFY_STRUCT => true,
            \ACL::BAS_MANAGE        => true,
            \ACL::BAS_CHUPUB        => true,
            \ACL::BAS_MODIF_TH      => true
        ];

        /** @var Databox $databox */
        foreach (self::$DI['app']->getDataboxes() as $databox) {
            $this->object->give_access_to_sbas([$databox->get_sbas_id()]);

            $this->object->update_rights_to_sbas(
                $databox->get_sbas_id(),
                $rights_false
            );
            $this->assertFalse($this->object->has_right_on_sbas($databox->get_sbas_id(), \ACL::BAS_MODIFY_STRUCT));
            $this->assertFalse($this->object->has_right_on_sbas($databox->get_sbas_id(), \ACL::BAS_MANAGE));
            $this->assertFalse($this->object->has_right_on_sbas($databox->get_sbas_id(), \ACL::BAS_CHUPUB));
            $this->assertFalse($this->object->has_right_on_sbas($databox->get_sbas_id(), \ACL::BAS_MODIF_TH));

            $this->object->update_rights_to_sbas(
                $databox->get_sbas_id(),
                $rights_true
            );
            $this->assertTrue($this->object->has_right_on_sbas($databox->get_sbas_id(), \ACL::BAS_MODIFY_STRUCT));
            $this->assertTrue($this->object->has_right_on_sbas($databox->get_sbas_id(), \ACL::BAS_MANAGE));
            $this->assertTrue($this->object->has_right_on_sbas($databox->get_sbas_id(), \ACL::BAS_CHUPUB));
            $this->assertTrue($this->object->has_right_on_sbas($databox->get_sbas_id(), \ACL::BAS_MODIF_TH));

            $this->object->update_rights_to_sbas(
                $databox->get_sbas_id(),
                $rights_false
            );
            $this->assertFalse($this->object->has_right_on_sbas($databox->get_sbas_id(), \ACL::BAS_MODIFY_STRUCT));
            $this->assertFalse($this->object->has_right_on_sbas($databox->get_sbas_id(), \ACL::BAS_MANAGE));
            $this->assertFalse($this->object->has_right_on_sbas($databox->get_sbas_id(), \ACL::BAS_CHUPUB));
            $this->assertFalse($this->object->has_right_on_sbas($databox->get_sbas_id(), \ACL::BAS_MODIF_TH));
        }
    }

    public function testGet_mask_and()
    {
        /** @var Databox $databox */
        foreach (self::$DI['app']->getDataboxes() as $databox) {
            foreach ($databox->get_collections() as $collection) {
                $base_id = $collection->get_base_id();

                $this->object->give_access_to_base([$base_id]);
                $this->object->update_rights_to_base(
                    $base_id,
                    [
                        \ACL::ACTIF => false
                    ]
                );
                $this->assertFalse($this->object->get_mask_and($base_id));
                $this->object->update_rights_to_base(
                    $base_id,
                    [
                        'mask_and' => 42
                    ]
                );
                $this->assertEquals(42, $this->object->get_mask_and($base_id));
                $this->object->update_rights_to_base(
                    $base_id,
                    [
                        'mask_and' => 1
                    ]
                );
                $this->assertEquals(1, $this->object->get_mask_and($base_id));
                $this->object->update_rights_to_base(
                    $base_id,
                    [
                        'mask_and' => 0
                    ]
                );
                $this->assertEquals(0, $this->object->get_mask_and($base_id));
            }
        }
    }

    public function testGet_mask_xor()
    {
        /** @var Databox $databox */
        foreach (self::$DI['app']->getDataboxes() as $databox) {
            foreach ($databox->get_collections() as $collection) {
                $base_id = $collection->get_base_id();

                $this->object->give_access_to_base([$base_id]);
                $this->object->update_rights_to_base(
                    $base_id,
                    [
                        \ACL::ACTIF => false
                    ]
                );
                $this->assertFalse($this->object->get_mask_xor($base_id));
                $this->object->update_rights_to_base(
                    $base_id,
                    [
                        \ACL::ACTIF => true
                    ]
                );
                $this->object->update_rights_to_base(
                    $base_id,
                    [
                        'mask_xor' => 42
                    ]
                );
                $this->assertEquals('42', $this->object->get_mask_xor($base_id));
                $this->object->update_rights_to_base(
                    $base_id,
                    [
                        'mask_xor' => 0
                    ]
                );
                $this->assertEquals('1', $this->object->get_mask_xor($base_id));
                $this->object->update_rights_to_base(
                    $base_id,
                    [
                        'mask_xor' => 0
                    ]
                );
                $this->assertEquals('0', $this->object->get_mask_xor($base_id));
            }
        }
    }

    public function testHas_access_to_base()
    {
        $base_ids = [];
        $n = 0;

        /** @var Databox $databox */
        foreach (self::$DI['app']->getDataboxes() as $databox) {
            foreach ($databox->get_collections() as $collection) {
                $base_ids[] = $collection->get_base_id();
                $n ++;
            }
            $this->object->give_access_to_sbas([$databox->get_sbas_id()]);
        }

        if ($n === 0) {
            $this->fail('Not enough collection to test');
        }

        $this->object->give_access_to_base($base_ids);
        $bases = array_keys($this->object->get_granted_base());

        $this->assertEquals(count($base_ids), count($bases));

        $sql = 'SELECT actif FROM basusr WHERE usr_id = :usr_id AND base_id = :base_id';
        $stmt = self::$DI['app']->getApplicationBox()->get_connection()->prepare($sql);

        foreach ($bases as $base_id) {
            $stmt->execute([':usr_id'  => self::$DI['user']->getId(), ':base_id' => $base_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->assertEquals(1, $row['actif']);

            $this->assertTrue($this->object->has_access_to_base($base_id));
            $this->object->update_rights_to_base(
                $base_id,
                [
                    \ACL::ACTIF => false
                ]
            );

            $stmt->execute([':usr_id'  => self::$DI['user']->getId(), ':base_id' => $base_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->assertEquals(0, $row['actif']);

            $this->assertFalse($this->object->has_access_to_base($base_id));
            $this->object->update_rights_to_base(
                $base_id,
                [
                    \ACL::ACTIF => true
                ]
            );

            $stmt->execute([':usr_id'  => self::$DI['user']->getId(), ':base_id' => $base_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->assertEquals(1, $row['actif']);

            $this->assertTrue($this->object->has_access_to_base($base_id));
            $this->object->update_rights_to_base(
                $base_id,
                [
                    \ACL::ACTIF => false
                ]
            );
            $this->assertFalse($this->object->has_access_to_base($base_id));
        }
        $this->object->give_access_to_base($base_ids);

        foreach ($bases as $base_id) {
            $this->assertTrue($this->object->has_access_to_base($base_id));
        }
        $stmt->closeCursor();
    }

    public function testGet_granted_base()
    {
        $base_ids = [];
        $n = 0;

        /** @var Databox $databox */
        foreach (self::$DI['app']->getDataboxes() as $databox) {
            foreach ($databox->get_collections() as $collection) {
                $base_ids[] = $collection->get_base_id();
                $n ++;
            }
        }

        if ($n === 0)
            $this->fail('Not enough collection to test');

        $this->object->give_access_to_base($base_ids);
        $bases = array_keys($this->object->get_granted_base());

        $this->assertEquals(count($bases), count($base_ids));
        $this->assertEquals($n, count($base_ids));

        foreach ($bases as $base_id) {
            try {
                $collection = collection::getByBaseId(self::$DI['app'], $base_id);
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

        /** @var Databox $databox */
        foreach (self::$DI['app']->getDataboxes() as $databox) {
            $sbas_ids[] = $databox->get_sbas_id();
            $n ++;
        }
        $this->object->give_access_to_sbas($sbas_ids);

        $sbas = $this->object->get_granted_sbas();

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
        foreach (self::$DI['app']->getDataboxes() as $databox) {
            $base_ids = [];
            foreach ($databox->get_collections() as $collection) {
                $base_id = $collection->get_base_id();
                $base_ids[] = $base_id;
            }
            $this->object->revoke_access_from_bases($base_ids);
            $this->object->revoke_unused_sbas_rights();
        }

        if ($this->object->is_admin())
            $this->assertTrue($this->object->has_access_to_module('admin'));
        else
            $this->assertFalse($this->object->has_access_to_module('admin'));
        $this->assertFalse($this->object->has_access_to_module('thesaurus'));
        $this->assertFalse($this->object->has_access_to_module('upload'));
        $this->assertFalse($this->object->has_access_to_module('report'));

        $found = false;
        foreach (self::$DI['app']->getDataboxes() as $databox) {
            foreach ($databox->get_collections() as $collection) {
                $base_id = $collection->get_base_id();
                $base_ids[] = $base_id;
                $this->object->update_rights_to_base(
                    $base_id,
                    [
                        \ACL::CANREPORT => true
                    ]
                );
                $found = true;
                break;
            }
            if ($found)
                break;
        }
        $this->assertTrue($this->object->has_access_to_module('report'));
        $this->assertFalse($this->object->has_access_to_module('thesaurus'));
        $this->assertFalse($this->object->has_access_to_module('upload'));

        /** @var Databox $databox */
        foreach (self::$DI['app']->getDataboxes() as $databox) {
            $this->object->update_rights_to_sbas(
                $databox->get_sbas_id(),
                [
                    \ACL::BAS_MODIF_TH => true
                ]
            );
            $found = true;
        }
        $this->assertTrue($this->object->has_access_to_module('report'));
        $this->assertTrue($this->object->has_access_to_module('thesaurus'));
        $this->assertFalse($this->object->has_access_to_module('upload'));

        $found = false;
        foreach (self::$DI['app']->getDataboxes() as $databox) {
            foreach ($databox->get_collections() as $collection) {
                $base_id = $collection->get_base_id();
                $base_ids[] = $base_id;
                $this->object->update_rights_to_base(
                    $base_id,
                    [
                        \ACL::CANADDRECORD => true
                    ]
                );
                $found = true;
                break;
            }
            if ($found)
                break;
        }
        $this->assertTrue($this->object->has_access_to_module('report'));
        $this->assertTrue($this->object->has_access_to_module('thesaurus'));
        $this->assertTrue($this->object->has_access_to_module('upload'));
    }

    public function testis_limited()
    {
        $found = false;

        /** @var Databox $databox */
        foreach (self::$DI['app']->getDataboxes() as $databox) {
            foreach ($databox->get_collections() as $collection) {
                $base_id = $collection->get_base_id();

                if ( ! $this->object->has_access_to_base($base_id))
                    continue;

                $this->assertFalse($this->object->is_limited($base_id));
                $this->object->set_limits($base_id, true, new DateTime('-1 day'), new DateTime('+1 day'));
                $this->assertFalse($this->object->is_limited($base_id));
                $this->object->set_limits($base_id, false, new DateTime('-1 day'), new DateTime('+1 day'));
                $this->assertFalse($this->object->is_limited($base_id));
                $this->object->set_limits($base_id, true, new DateTime('+1 day'), new DateTime('+2 day'));
                $this->assertTrue($this->object->is_limited($base_id));
                $this->object->set_limits($base_id, true, new DateTime('-2 day'), new DateTime('-1 day'));
                $this->assertTrue($this->object->is_limited($base_id));
                $this->object->set_limits($base_id, true, new DateTime('-2 day'), new DateTime('+2 day'));
                $this->assertFalse($this->object->is_limited($base_id));
                $this->object->set_limits($base_id, false, new DateTime('-2 day'), new DateTime('+2 day'));
                $this->assertFalse($this->object->is_limited($base_id));
                $found = true;
            }
        }

        if ( ! $found)
            $this->fail('Unable to test');
    }

    public function testget_limits()
    {
        $found = false;

        /** @var Databox $databox */
        foreach (self::$DI['app']->getDataboxes() as $databox) {
            foreach ($databox->get_collections() as $collection) {
                $base_id = $collection->get_base_id();

                if ( ! $this->object->has_access_to_base($base_id))
                    continue;

                $minusone = new DateTime('-1 day');
                $plusone = new DateTime('+1 day');
                $this->object->set_limits($base_id, true, $minusone, $plusone);
                $limits = $this->object->get_limits($base_id);
                $this->assertEquals($limits['dmin'], $minusone);
                $this->assertEquals($limits['dmax'], $plusone);
                $minustwo = new DateTime('-2 day');
                $plustwo = new DateTime('-2 day');
                $this->object->set_limits($base_id, true, $minustwo, $plustwo);
                $limits = $this->object->get_limits($base_id);
                $this->assertEquals($limits['dmin'], $minustwo);
                $this->assertEquals($limits['dmax'], $plustwo);
                $this->object->set_limits($base_id, false);
                $this->assertNull($this->object->get_limits($base_id));
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
