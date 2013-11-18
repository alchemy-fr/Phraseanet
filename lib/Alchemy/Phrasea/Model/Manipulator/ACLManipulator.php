<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model\Manipulator;

use Alchemy\Phrasea\Authentication\ACLProvider;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Exception\LogicException;

class ACLManipulator implements ManipulatorInterface
{
    /** @var ACLProvider */
    private $ACLProvider;
    /** @var \appbox */
    private $appbox;

    public function __construct(ACLProvider $ACLProvider, \appbox $appbox)
    {
        $this->ACLProvider = $ACLProvider;
        $this->appbox = $appbox;
    }

    /**
     * @throws LogicException
     */
    public function getRepository()
    {
        throw new LogicException('ACL class is not a doctrine entity and therefore it does not have repository.');
    }

    /**
     * Resets rights for users.
     *
     * @param User_Adapter $user
     *
     * @throws InvalidArgumentException
     */
    public function resetAdminRights($users)
    {
        foreach ($this->makeTraversable($users) as $user) {
            $this->doResetAdminRights($user);
        }
    }

    /**
     * Resets rights for a user.
     *
     * @param \User_adapter $user
     */
    private function doResetAdminRights(\User_adapter $user)
    {
        $acl = $this->ACLProvider->get($user);
        $databoxes = $this->appbox->get_databoxes();

        $acl->give_access_to_sbas(array_map(function (\databox $databox) {
            return $databox->get_sbas_id();
        }, $databoxes));

        foreach ($databoxes as $databox) {
            $this->doResetAdminRightsOnDatabox($acl, $databox);
        }
    }

    /**
     * Resets admin rights on a databox.
     *
     * @param \ACL     $acl
     * @param \databox $databox
     */
    private function doResetAdminRightsOnDatabox(\ACL $acl, \databox $databox)
    {
        $collections = $databox->get_collections();

        $acl->update_rights_to_sbas($databox->get_sbas_id(), [
            'bas_manage'        => '1',
            'bas_modify_struct' => '1',
            'bas_modif_th'      => '1',
            'bas_chupub'        => '1'
        ]);

        $acl->give_access_to_base(array_map(function (\collection $collection) {
            return $collection->get_base_id();
        }, $collections));

        foreach ($collections as $collection) {
            $this->doResetRightsOnCollection($acl, $collection);
        }
    }

    /**
     * Resets admin rights on a collection.
     *
     * @param \ACL        $acl
     * @param \collection $collection
     */
    private function doResetRightsOnCollection(\ACL $acl, \collection $collection)
    {
        $baseId = $collection->get_base_id();

        $acl->set_limits($baseId, false);
        $acl->remove_quotas_on_base($baseId);
        $acl->set_masks_on_base($baseId, '0', '0', '0', '0');
        $acl->update_rights_to_base($baseId, [
            'canputinalbum'     => '1',
            'candwnldhd'        => '1',
            'candwnldsubdef'    => '1',
            'nowatermark'       => '1',
            'candwnldpreview'   => '1',
            'cancmd'            => '1',
            'canadmin'          => '1',
            'canreport'         => '1',
            'canpush'           => '1',
            'creationdate'      => '1',
            'canaddrecord'      => '1',
            'canmodifrecord'    => '1',
            'candeleterecord'   => '1',
            'chgstatus'         => '1',
            'imgtools'          => '1',
            'manage'            => '1',
            'modify_struct'     => '1',
            'bas_modify_struct' => '1'
        ]);
    }

    /**
     * Makes given variable traversable.
     *
     * @param mixed $var
     *
     * @return array
     */
    private function makeTraversable($var)
    {
        if (!is_array($var) && !$var instanceof \Traversable) {
            return [$var];
        }

        return $var;
    }
}
