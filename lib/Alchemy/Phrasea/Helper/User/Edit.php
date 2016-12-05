<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Helper\User;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Application\Helper\NotifierAware;
use Alchemy\Phrasea\Controller\LazyLocator;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Notification\Mail\MailSuccessEmailUpdate;
use Alchemy\Phrasea\Notification\Receiver;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Edit extends \Alchemy\Phrasea\Helper\Helper
{
    use NotifierAware;

    /** @var array */
    protected $users = [];

    /** @var array */
    protected $users_datas;

    /** @var int */
    protected $base_id;

    public function __construct(Application $app, Request $Request)
    {
        parent::__construct($app, $Request);
        $this->setDelivererLocator(new LazyLocator($app, 'notification.deliverer'));

        $this->users = explode(';', $Request->get('users'));

        $users = [];
        foreach ($this->users as $usr_id) {
            $usr_id = (int) $usr_id;

            if ($usr_id > 0)
                $users[$usr_id] = $usr_id;
        }

        $this->users = $users;

        return $this;
    }

    public function delete_users()
    {
        foreach ($this->users as $usr_id) {
            if ($this->app->getAuthenticatedUser()->getId() === (int) $usr_id) {
                continue;
            }
            $user = $this->app['repo.users']->find($usr_id);
            $this->delete_user($user);
        }

        return $this;
    }

    protected function delete_user(User $user)
    {
        $list = array_keys($this->app->getAclForUser($this->app->getAuthenticatedUser())->get_granted_base([\ACL::CANADMIN]));

        $this->app->getAclForUser($user)->revoke_access_from_bases($list);

        if ($this->app->getAclForUser($user)->is_phantom()) {
            $this->app['manipulator.user']->delete($user);
        }

        return $this;
    }

    public function get_users_rights()
    {
        $list = array_keys($this->app->getAclForUser($this->app->getAuthenticatedUser())->get_granted_base([\ACL::CANADMIN]));

        $sql = "SELECT b.sbas_id, b.base_id,\n"

            . " SUM(actif) AS actif,\n"
            . " SUM(canputinalbum) AS canputinalbum,\n"
            . " SUM(candwnldpreview) AS candwnldpreview,\n"
            . " SUM(candwnldhd) AS candwnldhd,\n"
            . " SUM(cancmd) AS cancmd,\n"
            . " SUM(nowatermark) AS nowatermark,\n"

            . " SUM(canaddrecord) AS canaddrecord,\n"
            . " SUM(canmodifrecord) AS canmodifrecord,\n"
            . " SUM(chgstatus) AS chgstatus,\n"
            . " SUM(candeleterecord) AS candeleterecord,\n"
            . " SUM(imgtools) AS imgtools,\n"

            . " SUM(canadmin) AS canadmin,\n"
            . " SUM(canreport) AS canreport,\n"
            . " SUM(canpush) AS canpush,\n"
            . " SUM(manage) AS manage,\n"
            . " SUM(modify_struct) AS modify_struct,\n"

            . " SUM(sbu.bas_modif_th) AS bas_modif_th,\n"
            . " SUM(sbu.bas_manage) AS bas_manage,\n"
            . " SUM(sbu.bas_modify_struct) AS bas_modify_struct,\n"
            . " SUM(sbu.bas_chupub) AS bas_chupub,\n"

            . " SUM(time_limited) AS time_limited,\n"
            . " SUM(restrict_dwnld) AS restrict_dwnld,\n"

            // --- todo : wtf doing sum on non booleans ?
            . " SUM(remain_dwnld) AS remain_dwnld,\n"
            . " SUM(month_dwnld_max) AS month_dwnld_max,\n"
            . " SUM(mask_and + mask_xor) AS masks,\n"
            // ---

            // -- todo : wtf no aggregate fct ?
            . " DATE_FORMAT(limited_from,'%Y%m%d') AS limited_from,\n"
            . " DATE_FORMAT(limited_to,'%Y%m%d') AS limited_to\n"
            // ---

            . " FROM (Users u, bas b, sbas s)\n"
            . " LEFT JOIN (basusr bu) ON (bu.base_id = b.base_id AND u.id = bu.usr_id)\n"
            . " LEFT join  sbasusr sbu ON (sbu.sbas_id = b.sbas_id AND u.id = sbu.usr_id)\n"
            . " WHERE ( (u.id IN (:users) ) AND b.sbas_id = s.sbas_id AND (b.base_id IN (:bases)))\n"
            . " GROUP BY b.base_id\n"
            . " ORDER BY s.ord, s.sbas_id, b.ord, b.base_id ";

        $rs = $this->app->getApplicationBox()->get_connection()->fetchAll(
            $sql,
            [
                'users' => $this->users,
                'bases' => $list,
            ],
            [
                'users' => Connection::PARAM_INT_ARRAY,
                'bases' => Connection::PARAM_INT_ARRAY,
            ]
        );

        $sql = "SELECT base_id, SUM(1) AS access FROM basusr\n"
            . " WHERE (usr_id IN (:users)) AND (base_id IN (:bases))\n"
            . " GROUP BY base_id";

        $access = $this->app->getApplicationBox()->get_connection()->fetchAll(
            $sql,
            [
                'users' => $this->users,
                'bases' => $list,
            ],
            [
                'users' => Connection::PARAM_INT_ARRAY,
                'bases' => Connection::PARAM_INT_ARRAY,
            ]
        );

        $base_ids = [];
        foreach ($access as $acc) {
            $base_ids[$acc['base_id']] = $acc['access'];
        }
        unset($access);

        // add a 'access' column
        foreach ($rs as $k => $row) {
            $rs[$k]['access'] = array_key_exists($row['base_id'], $base_ids) ? $base_ids[$row['base_id']] : '0';
            foreach ($row as $dk => $data) {
                if (is_null($data))
                    $rs[$k][$dk] = '0';
            }
        }

        $query = $this->app['phraseanet.user-query'];
        $templates = $query
                ->only_templates(true)
                ->execute()->get_results();

        $this->users_datas = $rs;
        $out = [
            'datas'        => $this->users_datas,
            'users'        => $this->users,
            'users_serial' => implode(';', $this->users),
            'base_id'      => $this->base_id,
            'main_user'    => null,
            'templates'    => $templates
        ];

        if (count($this->users) == 1) {
            $usr_id = array_pop($this->users);
            $out['main_user'] = $this->app['repo.users']->find($usr_id);
        }

        return $out;
    }

    public function get_quotas()
    {
        $this->base_id = (int) $this->request->get('base_id');

        $sql = "SELECT u.id, restrict_dwnld, remain_dwnld, month_dwnld_max
      FROM (Users u INNER JOIN basusr bu ON u.id = bu.usr_id)
      WHERE (u.id IN (:users)) AND bu.base_id = :base_id";

        /** @var Connection $conn */
        $conn = $this->app->getApplicationBox()->get_connection();
        $rs = $conn->fetchAll($sql,
            [
                'base_id' => $this->base_id,
                'users' => $this->users,
            ],
            [
                'base_id' => \PDO::PARAM_INT,
                'users' => Connection::PARAM_INT_ARRAY,
            ]
        );

        $this->users_datas = $rs;

        return [
            'datas'        => $this->users_datas,
            'users'        => $this->users,
            'users_serial' => implode(';', $this->users),
            'base_id'      => $this->base_id,
            'collection'   => \collection::getByBaseId($this->app, $this->base_id),
        ];
    }

    public function get_masks()
    {
        $this->base_id = (int) $this->request->get('base_id');

        $sql = "SELECT BIN(mask_and) AS mask_and, BIN(mask_xor) AS mask_xor
            FROM basusr
            WHERE usr_id IN (:users)
              AND base_id = :base_id";

        /** @var Connection $conn */
        $conn = $this->app->getApplicationBox()->get_connection();
        $rs = $conn->fetchAll($sql,
            [
                'base_id' => $this->base_id,
                'users' => $this->users,
            ],
            [
                'base_id' => \PDO::PARAM_INT,
                'users' => Connection::PARAM_INT_ARRAY,
            ]
        );

        $tbits_and = [];
        $tbits_xor = [];

        $nrows = 0;

        for ($bit = 0; $bit < 32; $bit++)
            $tbits_and[$bit] = $tbits_xor[$bit] = ["nset" => 0];

        foreach ($rs as $row) {
            $sta_xor = strrev($row["mask_xor"]);
            $length = strlen($sta_xor);
            for ($bit = 0; $bit < $length; $bit++) {
                $tbits_xor[$bit]["nset"] += substr($sta_xor, $bit, 1) != "0" ? 1 : 0;
            }

            $sta_and = strrev($row["mask_and"]);
            $length = strlen($sta_and);
            for ($bit = 0; $bit < $length; $bit++) {
                $tbits_and[$bit]["nset"] += substr($sta_and, $bit, 1) != "0" ? 1 : 0;
            }
            $nrows++;
        }

        $tbits_left = [];
        $tbits_right = [];

        $sbas_id = \phrasea::sbasFromBas($this->app, $this->base_id);
        $databox = $this->app->findDataboxById($sbas_id);
        $statusStructure = $databox->getStatusStructure();

        foreach ($statusStructure as $bit => $status) {
            $tbits_left[$bit]["nset"] = 0;
            $tbits_left[$bit]["name"] = $status['labels_off_i18n'][$this->app['locale']];
            $tbits_left[$bit]["icon"] = $status["img_off"];

            $tbits_right[$bit]["nset"] = 0;
            $tbits_right[$bit]["name"] = $status['labels_on_i18n'][$this->app['locale']];
            $tbits_right[$bit]["icon"] = $status["img_on"];
        }

        $vand_and = $vand_or = $vxor_and = $vxor_or = "0000";

        for ($bit = 4; $bit < 32; $bit++) {
            if (($tbits_and[$bit]["nset"] != 0 && $tbits_and[$bit]["nset"] != $nrows) || ($tbits_xor[$bit]["nset"] != 0 && $tbits_xor[$bit]["nset"] != $nrows)) {
                if (isset($tbits_left[$bit]) && isset($tbits_right[$bit])) {
                    $tbits_left[$bit]["nset"] = 2;
                    $tbits_right[$bit]["nset"] = 2;
                }
                $vand_and = "1" . $vand_and;
                $vand_or = "0" . $vand_or;
                $vxor_and = "1" . $vxor_and;
                $vxor_or = "0" . $vxor_or;
            } else {
                if (isset($tbits_left[$bit]) && isset($tbits_right[$bit])) {
                    $tbits_left[$bit]["nset"] = (($tbits_and[$bit]["nset"] == $nrows && $tbits_xor[$bit]["nset"] == 0) || $tbits_and[$bit]["nset"] == 0 ) ? 1 : 0;
                    $tbits_right[$bit]["nset"] = (($tbits_and[$bit]["nset"] == $nrows && $tbits_xor[$bit]["nset"] == $nrows) || $tbits_and[$bit]["nset"] == 0 ) ? 1 : 0;
                }
                $vand_and = ($tbits_and[$bit]["nset"] == 0 ? "0" : "1") . $vand_and;
                $vand_or = ($tbits_and[$bit]["nset"] == $nrows ? "1" : "0") . $vand_or;
                $vxor_and = ($tbits_xor[$bit]["nset"] == 0 ? "0" : "1") . $vxor_and;
                $vxor_or = ($tbits_xor[$bit]["nset"] == $nrows ? "1" : "0") . $vxor_or;
            }
        }

        $this->users_datas = [
            'tbits_left'  => $tbits_left,
            'tbits_right' => $tbits_right,
            'vand_and'    => $vand_and,
            'vand_or'     => $vand_or,
            'vxor_and'    => $vxor_and,
            'vxor_or'     => $vxor_or
        ];

        return [
            'datas'        => $this->users_datas,
            'users'        => $this->users,
            'users_serial' => implode(';', $this->users),
            'base_id'      => $this->base_id,
            'collection'   => \collection::getByBaseId($this->app, $this->base_id),
        ];
    }

    public function get_time()
    {
        $this->base_id = (int) $this->request->get('base_id');

        $sql = "SELECT u.id, time_limited, limited_from, limited_to
      FROM (Users u INNER JOIN basusr bu ON u.id = bu.usr_id)
      WHERE (u.id IN (:users)) AND bu.base_id = :base_id";

        /** @var Connection $conn */
        $conn = $this->app->getApplicationBox()->get_connection();
        $rs = $conn->fetchAll($sql,
            [
                'base_id' => $this->base_id,
                'users' => $this->users,
            ],
            [
                'base_id' => \PDO::PARAM_INT,
                'users' => Connection::PARAM_INT_ARRAY,
            ]
        );

        $time_limited = -1;
        $limited_from = $limited_to = false;

        foreach ($rs as $row) {
            if ($time_limited < 0)
                $time_limited = $row['time_limited'];
            if ($time_limited < 2 && $row['time_limited'] != $row['time_limited'])
                $time_limited = 2;

            if ($limited_from !== '' && trim($row['limited_from']) != '0000-00-00 00:00:00') {
                $limited_from = $limited_from === false ? $row['limited_from'] : (($limited_from == $row['limited_from']) ? $limited_from : '');
            }
            if ($limited_to !== '' && trim($row['limited_to']) != '0000-00-00 00:00:00') {
                $limited_to = $limited_to === false ? $row['limited_to'] : (($limited_to == $row['limited_to']) ? $limited_to : '');
            }
        }

        if ($limited_from) {
            $date_obj_from = new \DateTime($limited_from);
            $limited_from = $date_obj_from->format('Y-m-d');
        }
        if ($limited_to) {
            $date_obj_to = new \DateTime($limited_to);
            $limited_to = $date_obj_to->format('Y-m-d');
        }

        $datas = ['time_limited' => $time_limited, 'limited_from' => $limited_from, 'limited_to'   => $limited_to];

        $this->users_datas = $datas;

        return [
            'datas'        => $this->users_datas,
            'users'        => $this->users,
            'users_serial' => implode(';', $this->users),
            'base_id'      => $this->base_id,
            'collection'   => \collection::getByBaseId($this->app, $this->base_id),
        ];
    }

    public function get_time_sbas()
    {
        $sbas_id = (int) $this->request->get('sbas_id');

        $sql = "SELECT u.id, time_limited, limited_from, limited_to
            FROM (Users u
              INNER JOIN basusr bu ON u.id = bu.usr_id
              INNER JOIN bas b ON b.base_id = bu.base_id)
            WHERE (u.id IN (:users)) AND b.sbas_id = :sbas_id";

        /** @var Connection $conn */
        $conn = $this->app->getApplicationBox()->get_connection();
        $rs = $conn->fetchAll($sql,
            [
                'sbas_id' => $sbas_id,
                'users' => $this->users,
            ],
            [
                'sbas_id' => \PDO::PARAM_INT,
                'users' => Connection::PARAM_INT_ARRAY,
            ]
        );

        $time_limited = $limited_from = $limited_to = [];

        foreach ($rs as $row) {
            $time_limited[] = $row['time_limited'];
            $limited_from[] = $row['limited_from'];
            $limited_to[] = $row['limited_to'];
        }

        $time_limited = array_unique($time_limited);
        $limited_from = array_unique($limited_from);
        $limited_to = array_unique($limited_to);

        if (1 === count($time_limited)
            && 1 === count($limited_from)
            && 1 === count($limited_to)) {
            $limited_from = array_pop($limited_from);
            $limited_to = array_pop($limited_to);

            if ($limited_from !== '' && trim($limited_from) != '0000-00-00 00:00:00') {
                $date_obj_from = new \DateTime($limited_from);
                $limited_from = $date_obj_from->format('Y-m-d');
            } else {
                $limited_from = false;
            }

            if ($limited_to !== '' && trim($limited_to) != '0000-00-00 00:00:00') {
                $date_obj_to = new \DateTime($limited_to);
                $limited_to = $date_obj_to->format('Y-m-d');
            } else {
                $limited_to = false;
            }

            $datas = [
                'time_limited' => array_pop($time_limited),
                'limited_from' => $limited_from,
                'limited_to'   => $limited_to
            ];
        } else {
            $datas = [
                'time_limited' => 2,
                'limited_from' => '',
                'limited_to'   => ''
            ];
        }

        $this->users_datas = $datas;

        return [
            'sbas_id'      => $sbas_id,
            'datas'        => $this->users_datas,
            'users'        => $this->users,
            'users_serial' => implode(';', $this->users),
            'databox'      => $this->app->findDataboxById($sbas_id),
        ];
    }

    public function apply_rights()
    {
        $ACL = $this->app->getAclForUser($this->app->getAuthenticatedUser());
        $base_ids = array_keys($ACL->get_granted_base([\ACL::CANADMIN]));

        $update = $create = $delete = $create_sbas = $update_sbas = [];

        foreach ($base_ids as $base_id) {
            $rights = [
                \ACL::ACCESS,
                \ACL::ACTIF,
                \ACL::CANPUTINALBUM,
                \ACL::NOWATERMARK,
                \ACL::CANDWNLDPREVIEW,
                \ACL::CANDWNLDHD,
                \ACL::CANCMD,
                \ACL::CANADDRECORD,
                \ACL::CANMODIFRECORD,
                \ACL::CHGSTATUS,
                \ACL::CANDELETERECORD,
                \ACL::IMGTOOLS,
                \ACL::CANADMIN,
                \ACL::CANREPORT,
                \ACL::CANPUSH,
                \ACL::COLL_MANAGE,
                \ACL::COLL_MODIFY_STRUCT
            ];
            foreach ($rights as $k => $right) {
                if (($right == \ACL::ACCESS && !$ACL->has_access_to_base($base_id))
                    || ($right != \ACL::ACCESS && !$ACL->has_right_on_base($base_id, $right))) {
                    unset($rights[$k]);
                    continue;
                }
                $rights[$k] = $right . '_' . $base_id;
            }

            // todo : wtf check if parm contains good types (a checkbox should be a bool, not a "0" or "1"
            //        as required by ACL::update_rights_to_bas(...)
            $parm = $this->unserializedRequestData($this->app['request'], $rights, 'values');

            foreach ($parm as $p => $v) {
                // p is like {bid}_{right} => right-value
                if (trim($v) == '')
                    continue;

                $serial = explode('_', $p);
                $base_id = array_pop($serial);

                $p = implode('_', $serial);

                if ($p == \ACL::ACCESS) {
                    if ($v === '1') {
                        $create_sbas[\phrasea::sbasFromBas($this->app, $base_id)] = \phrasea::sbasFromBas($this->app, $base_id);
                        $create[] = $base_id;
                    }
                    else {
                        $delete[] = $base_id;
                    }
                }
                else {
                    $create_sbas[\phrasea::sbasFromBas($this->app, $base_id)] = \phrasea::sbasFromBas($this->app, $base_id);
                    // todo : wtf $update is arg. for ACL::update_rights_to_base(...) but $v is always a string. how to convert to bool ?
                    $update[$base_id][$p] = $v;
                }
            }
        }

        $sbas_ids = $ACL->get_granted_sbas();

        foreach ($sbas_ids as $databox) {
            $rights = [
                \ACL::BAS_MODIF_TH,
                \ACL::BAS_MANAGE,
                \ACL::BAS_MODIFY_STRUCT,
                \ACL::BAS_CHUPUB
            ];
            foreach ($rights as $k => $right) {
                if (!$ACL->has_right_on_sbas($databox->get_sbas_id(), $right)) {
                    unset($rights[$k]);
                    continue;
                }
                $rights[$k] = $right . '_' . $databox->get_sbas_id();
            }

            // todo : wtf check if parm contains good types (a checkbox should be a bool, not a "0" or "1"
            //        as required by ACL::update_rights_to_sbas(...)
            $parm = $this->unserializedRequestData($this->app['request'], $rights, 'values');

            foreach ($parm as $p => $v) {
                if (trim($v) == '')
                    continue;

                $serial = explode('_', $p);
                $sbas_id = array_pop($serial);

                $p = implode('_', $serial);

                $update_sbas[$sbas_id][$p] = $v;
            }
        }

        foreach ($this->users as $usr_id) {
            try {
                $this->app->getApplicationBox()->get_connection()->beginTransaction();

                /** @var User $user */
                $user = $this->app['repo.users']->find($usr_id);

                $this->app->getAclForUser($user)->revoke_access_from_bases($delete)
                    ->give_access_to_base($create)
                    ->give_access_to_sbas($create_sbas);

                foreach ($update as $base_id => $rights) {
                    $this->app->getAclForUser($user)
                        ->update_rights_to_base(
                            $base_id,
                            $rights
                        );
                }

                foreach ($update_sbas as $sbas_id => $rights) {
                    $this->app->getAclForUser($user)->update_rights_to_sbas(
                        $sbas_id,
                        $rights
                    );
                }

                $this->app->getApplicationBox()->get_connection()->commit();

                $this->app->getAclForUser($user)->revoke_unused_sbas_rights();

                unset($user);
            } catch (\Exception $e) {
                $this->app->getApplicationBox()->get_connection()->rollBack();
            }
        }

        return $this;
    }

    public function apply_infos()
    {
        if (count($this->users) != 1) {
            return $this;
        }

        $users = $this->users;

        $user = $this->app['repo.users']->find(array_pop($users));

        if ($user->isTemplate() || $user->isSpecial()) {
            return $this;
        }

        $infos = [
            'gender',
            'first_name',
            'last_name',
            'email',
            'address',
            'zip',
            'geonameid',
            'function',
            'company',
            'activite',
            'telephone',
            'fax'
        ];

        $parm = $this->unserializedRequestData($this->request, $infos, 'user_infos');

        if ($parm['email'] && !\Swift_Validate::email($parm['email'])) {
            throw new \Exception_InvalidArgument('Email addess is not valid');
        }

        $old_email = $user->getEmail();

        $user->setFirstName($parm['first_name'])
            ->setLastName($parm['last_name'])
            ->setGender((int) $parm['gender'])
            ->setEmail($parm['email'])
            ->setAddress($parm['address'])
            ->setZipCode($parm['zip'])
            ->setActivity($parm['function'])
            ->setJob($parm['activite'])
            ->setCompany($parm['company'])
            ->setPhone($parm['telephone'])
            ->setFax($parm['fax']);

        $this->app['manipulator.user']->setGeonameId($user, $parm['geonameid']);

        $new_email = $user->getEmail();

        if ($old_email != $new_email) {
            $oldReceiver = $newReceiver = null;
            try {
                $oldReceiver = new Receiver(null, $old_email);
            } catch (InvalidArgumentException $e) {

            }

            if ($oldReceiver) {
                $mailOldAddress = MailSuccessEmailUpdate::create($this->app, $oldReceiver, null, $this->app->trans('You will now receive notifications at %new_email%', ['%new_email%' => $new_email]));
                $this->deliver($mailOldAddress);
            }

            try {
                $newReceiver = new Receiver(null, $new_email);
            } catch (InvalidArgumentException $e) {

            }

            if ($newReceiver) {
                $mailNewAddress = MailSuccessEmailUpdate::create($this->app, $newReceiver, null, $this->app->trans('You will no longer receive notifications at %old_email%', ['%old_email%' => $old_email]));
                $this->deliver($mailNewAddress);
            }
        }

        return $this;
    }

    public function apply_template()
    {
        $template = $this->app['repo.users']->find($this->request->get('template'));

        if (null === $template) {
            throw new NotFoundHttpException(sprintf('Given template "%s" could not be found', $this->request->get('template')));
        }
        if (null === $template->getTemplateOwner() || $template->getTemplateOwner()->getId() !== $this->app->getAuthenticatedUser()->getId()) {
            throw new AccessDeniedHttpException('You are not the owner of the template');
        }

        $base_ids = array_keys($this->app->getAclForUser($this->app->getAuthenticatedUser())->get_granted_base([\ACL::CANADMIN]));

        foreach ($this->users as $usr_id) {
            $user = $this->app['repo.users']->find($usr_id);
            
            $this->app->getAclForUser($user)->apply_model($template, $base_ids);
        }

        return $this;
    }

    public function apply_quotas()
    {
        $this->base_id = (int) $this->request->get('base_id');

        foreach ($this->users as $usr_id) {
            $user = $this->app['repo.users']->find($usr_id);
            if ($this->request->get('quota'))
                $this->app->getAclForUser($user)->set_quotas_on_base($this->base_id, $this->request->get('droits'), $this->request->get('restes'));
            else
                $this->app->getAclForUser($user)->remove_quotas_on_base($this->base_id);
        }

        return $this;
    }

    public function apply_masks()
    {
        $this->base_id = (int) $this->request->get('base_id');

        $vand_and = $this->request->get('vand_and');
        $vand_or = $this->request->get('vand_or');
        $vxor_and = $this->request->get('vxor_and');
        $vxor_or = $this->request->get('vxor_or');

        if ($vand_and && $vand_or && $vxor_and && $vxor_or) {
            foreach ($this->users as $usr_id) {
                $user = $this->app['repo.users']->find($usr_id);

                $this->app->getAclForUser($user)->set_masks_on_base($this->base_id, $vand_and, $vand_or, $vxor_and, $vxor_or);
            }
        }

        return $this;
    }

    public function apply_time()
    {
        $this->base_id = (int) $this->request->get('base_id');
        $sbas_id = (int) $this->request->get('sbas_id');

        $dmin = $this->request->get('dmin') ? new \DateTime($this->request->get('dmin')) : null;
        $dmax = $this->request->get('dmax') ? new \DateTime($this->request->get('dmax')) : null;

        $activate = !!$this->request->get('limit');

        $base_ids = array_keys($this->app->getAclForUser($this->app->getAuthenticatedUser())->get_granted_base([\ACL::CANADMIN]));

        foreach ($this->users as $usr_id) {
            $user = $this->app['repo.users']->find($usr_id);

            if ($this->base_id > 0) {
                $this->app->getAclForUser($user)->set_limits($this->base_id, $activate, $dmin, $dmax);
            } elseif ($sbas_id > 0) {
                foreach ($base_ids as $base_id) {
                    $this->app->getAclForUser($user)->set_limits($base_id, $activate, $dmin, $dmax);
                }
            } else {
                $this->app->abort(400, 'No collection or databox id available');
            }
        }
    }

    public function resetRights()
    {
        $base_ids = array_keys($this->app->getAclForUser($this->app->getAuthenticatedUser())->get_granted_base([\ACL::CANADMIN]));

        foreach ($this->users as $usr_id) {
            $user = $this->app['repo.users']->find($usr_id);
            $ACL = $this->app->getAclForUser($user);

            if ($user->isTemplate()) {
                $template = $user;

                if ($template->getTemplateOwner()->getId() !== $this->app->getAuthenticatedUser()->getId()) {
                    continue;
                }
            }

            foreach ($base_ids as $base_id) {
                if (!$ACL->has_access_to_base($base_id)) {
                    continue;
                }

                $ACL->set_limits($base_id, false);
                $ACL->set_masks_on_base($base_id, 0, 0, 0, 0);
                $ACL->remove_quotas_on_base($base_id);
            }
            $ACL->revoke_access_from_bases($base_ids);
            $ACL->revoke_unused_sbas_rights();
        }
    }

    private function unserializedRequestData(Request $request, array $indexes, $requestIndex)
    {
        $parameters = $data = [];
        $requestValue = $request->get($requestIndex);

        if (is_array($requestValue)) {
            $data = $requestValue;
        } else {
            parse_str($requestValue, $data);
        }

        if (count($data) > 0) {
            foreach ($indexes as $index) {
                $parameters[$index] = isset($data[$index]) ? $data[$index] : null;
            }
        }

        return $parameters;
    }
}
