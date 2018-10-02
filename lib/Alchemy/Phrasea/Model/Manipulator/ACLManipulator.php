<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model\Manipulator;

use Alchemy\Phrasea\Authentication\ACLProvider;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Model\Entities\User;

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
     * Resets rights for users.
     *
     * @param User[] $users
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
     * @param User $user
     */
    private function doResetAdminRights(User $user)
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

        $acl->update_rights_to_sbas(
            $databox->get_sbas_id(),
            [
                \ACL::BAS_MANAGE        => true,
                \ACL::BAS_MODIFY_STRUCT => true,
                \ACL::BAS_MODIF_TH      => true,
                \ACL::BAS_CHUPUB        => true
            ]
        );

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
        $acl->update_rights_to_base(
            $baseId,
            [
                'creationdate'      => '1',         // todo : wtf
                \ACL::CANPUTINALBUM      => true,
                \ACL::CANDWNLDHD         => true,
                \ACL::NOWATERMARK        => true,
                \ACL::CANDWNLDPREVIEW    => true,
                \ACL::CANCMD             => true,
                \ACL::CANADMIN           => true,
                \ACL::CANREPORT          => true,
                \ACL::CANPUSH            => true,
                \ACL::CANADDRECORD       => true,
                \ACL::CANMODIFRECORD     => true,
                \ACL::CANDELETERECORD    => true,
                \ACL::CHGSTATUS          => true,
                \ACL::IMGTOOLS           => true,
                \ACL::COLL_MANAGE        => true,
                \ACL::COLL_MODIFY_STRUCT => true,
                \ACL::BAS_MODIFY_STRUCT  => true
            ]
        );
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
