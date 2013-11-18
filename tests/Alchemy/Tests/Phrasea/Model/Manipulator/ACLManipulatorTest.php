<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Tests\Phrasea\Model\Manipulator;

class ACLManipulatorTest extends \PhraseanetPHPUnitAbstract
{
    public function testResetAdminRights()
    {
        $user = \User_Adapter::create(self::$DI['app'], uniqid('toto'), 'toto', null, true);
        $acl = self::$DI['app']['acl']->get($user);

        $databoxId = null;
        $baseId = null;

        foreach (self::$DI['app']['phraseanet.appbox']->get_databoxes() as $databox) {
            $databoxId = $databox->get_sbas_id();

            $acl->update_rights_to_sbas($databoxId, [
                'bas_manage'        => '0',
                'bas_modify_struct' => '0',
                'bas_modif_th'      => '0',
                'bas_chupub'        => '0'
            ]);

            foreach ($databox->get_collections() as $collection) {
                $baseId = $collection->get_base_id();
                $acl->set_limits($baseId, true);
                $acl->set_masks_on_base($baseId, '1', '1', '1', '1');

                $acl->update_rights_to_base($baseId, [
                    'canputinalbum'     => '0',
                    'candwnldhd'        => '0',
                    'candwnldsubdef'    => '0',
                    'nowatermark'       => '0',
                    'candwnldpreview'   => '0',
                    'cancmd'            => '0',
                    'canadmin'          => '0',
                    'canreport'         => '0',
                    'canpush'           => '0',
                    'creationdate'      => '0',
                    'canaddrecord'      => '0',
                    'canmodifrecord'    => '0',
                    'candeleterecord'   => '0',
                    'chgstatus'         => '0',
                    'imgtools'          => '0',
                    'manage'            => '0',
                    'modify_struct'     => '0',
                    'bas_modify_struct' => '0'
                ]);

                break 2;
            }
        }

        self::$DI['app']['manipulator.acl']->resetAdminRights($user);

        self::$DI['app']['acl']->purge();
        $acl = self::$DI['app']['acl']->get($user);

        if ($baseId === null) {
            $this->fail("Need at least one collection");
        }

        $this->assertTrue($acl->has_right_on_sbas($databoxId, 'bas_manage'));
        $this->assertTrue($acl->has_right_on_sbas($databoxId, 'bas_modify_struct'));
        $this->assertTrue($acl->has_right_on_sbas($databoxId, 'bas_modif_th'));
        $this->assertTrue($acl->has_right_on_sbas($databoxId, 'bas_chupub'));

        $this->assertTrue($acl->has_right_on_base($baseId, 'canputinalbum'));
        $this->assertTrue($acl->has_right_on_base($baseId, 'candwnldhd'));
        $this->assertTrue($acl->has_right_on_base($baseId, 'nowatermark'));
        $this->assertTrue($acl->has_right_on_base($baseId, 'candwnldpreview'));
        $this->assertTrue($acl->has_right_on_base($baseId, 'cancmd'));
        $this->assertTrue($acl->has_right_on_base($baseId, 'canadmin'));
        $this->assertTrue($acl->has_right_on_base($baseId, 'canreport'));
        $this->assertTrue($acl->has_right_on_base($baseId, 'canpush'));
        $this->assertTrue($acl->has_right_on_base($baseId, 'canaddrecord'));
        $this->assertTrue($acl->has_right_on_base($baseId, 'canmodifrecord'));
        $this->assertTrue($acl->has_right_on_base($baseId, 'candeleterecord'));
        $this->assertTrue($acl->has_right_on_base($baseId, 'chgstatus'));
        $this->assertTrue($acl->has_right_on_base($baseId, 'imgtools'));
        $this->assertTrue($acl->has_right_on_base($baseId, 'manage'));
        $this->assertTrue($acl->has_right_on_base($baseId, 'modify_struct'));

        $this->assertEquals(0, $acl->get_limits($baseId));
        $this->assertEquals(0, $acl->get_limits($acl->get_mask_xor($baseId)));
        $this->assertEquals(0, $acl->get_limits($acl->get_mask_and($baseId)));

        $user->delete();
    }
}
