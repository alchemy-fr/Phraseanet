<?php

namespace Alchemy\Tests\Phrasea\Model\Manipulator;

/**
 * @group functional
 * @group legacy
 */
class ACLManipulatorTest extends \PhraseanetTestCase
{
    public function testResetAdminRights()
    {
        $user = self::$DI['app']['manipulator.user']->createUser(uniqid('toto'), 'toto', null, true);
        $acl = self::$DI['app']->getAclForUser($user);

        $databoxId = null;
        $baseId = null;

        foreach (self::$DI['app']->getDataboxes() as $databox) {
            $databoxId = $databox->get_sbas_id();

            $acl->update_rights_to_sbas($databoxId, [
                \ACL::BAS_MANAGE        => '0',
                \ACL::BAS_MODIFY_STRUCT => '0',
                \ACL::BAS_MODIF_TH      => '0',
                \ACL::BAS_CHUPUB        => '0',
            ]);

            foreach ($databox->get_collections() as $collection) {
                $baseId = $collection->get_base_id();
                $acl->set_limits($baseId, true);
                $acl->set_masks_on_base($baseId, '1', '1', '1', '1');

                $acl->update_rights_to_base($baseId, [
                    \ACL::CANPUTINALBUM      => '0',
                    \ACL::CANDWNLDHD         => '0',
                    'candwnldsubdef'    => '0',
                    \ACL::NOWATERMARK        => '0',
                    \ACL::CANDWNLDPREVIEW    => '0',
                    \ACL::CANCMD             => '0',
                    \ACL::CANADMIN           => '0',
                    \ACL::CANREPORT          => '0',
                    \ACL::CANPUSH            => '0',
                    'creationdate'      => '0',
                    \ACL::CANADDRECORD       => '0',
                    \ACL::CANMODIFRECORD     => '0',
                    \ACL::CANDELETERECORD    => '0',
                    \ACL::CHGSTATUS          => '0',
                    \ACL::IMGTOOLS           => '0',
                    \ACL::COLL_MANAGE        => '0',
                    \ACL::COLL_MODIFY_STRUCT => '0',
                    \ACL::BAS_MODIFY_STRUCT  => '0'
                ]);

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
