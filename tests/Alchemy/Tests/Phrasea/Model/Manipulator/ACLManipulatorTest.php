<?php

namespace Alchemy\Tests\Phrasea\Model\Manipulator;

use \ACL;
use \Databox;

/**
 * @group functional
 * @group legacy
 */
class ACLManipulatorTest extends \PhraseanetTestCase
{
    public function testResetAdminRights()
    {
        $user = self::$DI['app']['manipulator.user']->createUser(uniqid('toto'), 'toto', null, true);
        /** @var ACL $acl */
        $acl = self::$DI['app']->getAclForUser($user);

        $databoxId = null;
        $baseId = null;

        /** @var Databox $databox */
        foreach (self::$DI['app']->getDataboxes() as $databox) {
            $databoxId = $databox->get_sbas_id();

            $acl->update_rights_to_sbas(
                $databoxId,
                [
                    \ACL::BAS_MANAGE        => false,
                    \ACL::BAS_MODIFY_STRUCT => false,
                    \ACL::BAS_MODIF_TH      => false,
                    \ACL::BAS_CHUPUB        => false
                ]
            );

            foreach ($databox->get_collections() as $collection) {
                $baseId = $collection->get_base_id();
                $acl->set_limits($baseId, true);
                $acl->set_masks_on_base($baseId, '1', '1', '1', '1');

                $acl->update_rights_to_base(
                    $baseId,
                    [
                        'creationdate'      => '0',         // todo: wtf
                        \ACL::CANPUTINALBUM      => false,
                        \ACL::CANDWNLDHD         => false,
                        \ACL::NOWATERMARK        => false,
                        \ACL::CANDWNLDPREVIEW    => false,
                        \ACL::CANCMD             => false,
                        \ACL::CANADMIN           => false,
                        \ACL::CANREPORT          => false,
                        \ACL::CANPUSH            => false,
                        \ACL::CANADDRECORD       => false,
                        \ACL::CANMODIFRECORD     => false,
                        \ACL::CANDELETERECORD    => false,
                        \ACL::CHGSTATUS          => false,
                        \ACL::IMGTOOLS           => false,
                        \ACL::COLL_MANAGE        => false,
                        \ACL::COLL_MODIFY_STRUCT => false,
                        \ACL::BAS_MODIFY_STRUCT  => false
                    ]
                );

                break 2;
            }
        }

        self::$DI['app']['manipulator.acl']->resetAdminRights($user);

        self::$DI['app']['acl']->purge();
        $acl = self::$DI['app']->getAclForUser($user);

        if ($baseId === null) {
            $this->fail("Need at least one collection");
        }

        $this->assertTrue($acl->has_right_on_sbas($databoxId, \ACL::BAS_MANAGE));
        $this->assertTrue($acl->has_right_on_sbas($databoxId, \ACL::BAS_MODIFY_STRUCT));
        $this->assertTrue($acl->has_right_on_sbas($databoxId, \ACL::BAS_MODIF_TH));
        $this->assertTrue($acl->has_right_on_sbas($databoxId, \ACL::BAS_CHUPUB));

        $this->assertTrue($acl->has_right_on_base($baseId, \ACL::CANPUTINALBUM));
        $this->assertTrue($acl->has_right_on_base($baseId, \ACL::CANDWNLDHD));
        $this->assertTrue($acl->has_right_on_base($baseId, \ACL::NOWATERMARK));
        $this->assertTrue($acl->has_right_on_base($baseId, \ACL::CANDWNLDPREVIEW));
        $this->assertTrue($acl->has_right_on_base($baseId, \ACL::CANCMD));
        $this->assertTrue($acl->has_right_on_base($baseId, \ACL::CANADMIN));
        $this->assertTrue($acl->has_right_on_base($baseId, \ACL::CANREPORT));
        $this->assertTrue($acl->has_right_on_base($baseId, \ACL::CANPUSH));
        $this->assertTrue($acl->has_right_on_base($baseId, \ACL::CANADDRECORD));
        $this->assertTrue($acl->has_right_on_base($baseId, \ACL::CANMODIFRECORD));
        $this->assertTrue($acl->has_right_on_base($baseId, \ACL::CANDELETERECORD));
        $this->assertTrue($acl->has_right_on_base($baseId, \ACL::CHGSTATUS));
        $this->assertTrue($acl->has_right_on_base($baseId, \ACL::IMGTOOLS));
        $this->assertTrue($acl->has_right_on_base($baseId, \ACL::COLL_MANAGE));
        $this->assertTrue($acl->has_right_on_base($baseId, \ACL::COLL_MODIFY_STRUCT));

        $this->assertEquals(0, $acl->get_limits($baseId));
        $this->assertEquals(0, $acl->get_limits($acl->get_mask_xor($baseId)));
        $this->assertEquals(0, $acl->get_limits($acl->get_mask_and($baseId)));

        $this->removeUser(self::$DI['app'], $user);
    }
}
