<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Helper\User;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Notification\Mail\MailSuccessEmailUpdate;
use Alchemy\Phrasea\Notification\Receiver;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Edit extends \Alchemy\Phrasea\Helper\Helper
{
    /**
     *
     * @var array
     */
    protected $users = array();

    /**
     *
     * @var array
     */
    protected $users_datas;

    /**
     *
     * @var int
     */
    protected $base_id;

    public function __construct(Application $app, Request $Request)
    {
        parent::__construct($app, $Request);

        $this->users = explode(';', $Request->get('users'));

        $users = array();
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
            $user = \User_Adapter::getInstance($usr_id, $this->app);
            $this->delete_user($user);
        }

        return $this;
    }

    protected function delete_user(\User_Adapter $user)
    {
        $list = array_keys($this->app['phraseanet.user']->ACL()->get_granted_base(array('canadmin')));

        $user->ACL()->revoke_access_from_bases($list);

        if ($user->ACL()->is_phantom()) {
            $user->delete();
        }

        return $this;
    }

    public function get_users_rights()
    {
        $list = array_keys($this->app['phraseanet.user']->ACL()->get_granted_base(array('canadmin')));

        $sql = "SELECT
            b.sbas_id,
            b.base_id,
            sum(actif) as actif,
            sum(canputinalbum) as canputinalbum,
            sum(candwnldpreview) as candwnldpreview,
            sum(candwnldhd) as candwnldhd,
            sum(cancmd) as cancmd,
            sum(nowatermark) as nowatermark,

            sum(canaddrecord) as canaddrecord,
            sum(canmodifrecord) as canmodifrecord,
            sum(chgstatus) as chgstatus,
            sum(candeleterecord) as candeleterecord,
            sum(imgtools) as imgtools,

            sum(canadmin) as canadmin,
            sum(canreport) as canreport,
            sum(canpush) as canpush,
            sum(manage) as manage,
            sum(modify_struct) as modify_struct,

            sum(sbu.bas_modif_th) as bas_modif_th,
            sum(sbu.bas_manage) as bas_manage,
            sum(sbu.bas_modify_struct) as bas_modify_struct,
            sum(sbu.bas_chupub) as bas_chupub,

            sum(time_limited) as time_limited,
            DATE_FORMAT(limited_from,'%Y%m%d') as limited_from,
            DATE_FORMAT(limited_to,'%Y%m%d') as limited_to,

            sum(restrict_dwnld) as restrict_dwnld,
            sum(remain_dwnld) as remain_dwnld,
            sum(month_dwnld_max) as month_dwnld_max,

            sum(mask_and + mask_xor) as masks

            FROM (usr u, bas b, sbas s)
              LEFT JOIN (basusr bu)
                ON (bu.base_id = b.base_id AND u.usr_id = bu.usr_id)
              LEFT join  sbasusr sbu
                ON (sbu.sbas_id = b.sbas_id AND u.usr_id = sbu.usr_id)
            WHERE ( (u.usr_id = " . implode(' OR u.usr_id = ', $this->users) . " )
                    AND b.sbas_id = s.sbas_id
                    AND (b.base_id = '" . implode("' OR b.base_id = '", $list) . "'))
            GROUP BY b.base_id
            ORDER BY s.ord, s.sbas_id, b.ord, b.base_id ";

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $sql = 'SELECT base_id, sum(1) as access FROM basusr
            WHERE (usr_id = ' . implode(' OR usr_id = ', $this->users) . ')
              AND  (base_id = ' . implode(' OR base_id = ', $list) . ')
            GROUP BY base_id';
        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute();
        $access = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $base_ids = array();
        foreach ($access as $acc) {
            $base_ids[$acc['base_id']] = $acc;
        }
        unset($access);

        foreach ($rs as $k => $row) {
            $rs[$k]['access'] = array_key_exists($row['base_id'], $base_ids) ? $base_ids[$row['base_id']]['access'] : '0';
            foreach ($row as $dk => $data) {
                if (is_null($data))
                    $rs[$k][$dk] = '0';
            }
        }

        $query = new \User_Query($this->app);
        $templates = $query
                ->only_templates(true)
                ->execute()->get_results();

        $this->users_datas = $rs;
        $out = array(
            'datas'        => $this->users_datas,
            'users'        => $this->users,
            'users_serial' => implode(';', $this->users),
            'base_id'      => $this->base_id,
            'main_user'    => null,
            'templates'    => $templates
        );

        if (count($this->users) == 1) {
            $usr_id = array_pop($this->users);
            $out['main_user'] = \User_Adapter::getInstance($usr_id, $this->app);
        }

        return $out;
    }

    public function get_quotas()
    {
        $this->base_id = (int) $this->request->get('base_id');

        $sql = "SELECT u.usr_id, restrict_dwnld, remain_dwnld, month_dwnld_max
      FROM (usr u INNER JOIN basusr bu ON u.usr_id = bu.usr_id)
      WHERE u.usr_id = " . implode(' OR u.usr_id = ', $this->users) . "
      AND bu.base_id = :base_id";

        $conn = \connection::getPDOConnection($this->app);
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':base_id' => $this->base_id));
        $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $this->users_datas = $rs;

        return array(
            'datas'        => $this->users_datas,
            'users'        => $this->users,
            'users_serial' => implode(';', $this->users),
            'base_id'      => $this->base_id,
            'collection'   => \collection::get_from_base_id($this->app, $this->base_id),
        );
    }

    public function get_masks()
    {
        $this->base_id = (int) $this->request->get('base_id');

        $sql = "SELECT BIN(mask_and) AS mask_and, BIN(mask_xor) AS mask_xor
            FROM basusr
            WHERE usr_id IN (" . implode(',', $this->users) . ")
              AND base_id = :base_id";

        $conn = $this->app['phraseanet.appbox']->get_connection();
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':base_id' => $this->base_id));
        $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $msk_and = null;
        $msk_xor = null;
        $tbits_and = array();
        $tbits_xor = array();

        $nrows = 0;

        for ($bit = 0; $bit < 32; $bit++)
            $tbits_and[$bit] = $tbits_xor[$bit] = array("nset" => 0);

        foreach ($rs as $row) {
            $sta_xor = strrev($row["mask_xor"]);
            for ($bit = 0; $bit < strlen($sta_xor); $bit++)
                $tbits_xor[$bit]["nset"] += substr($sta_xor, $bit, 1) != "0" ? 1 : 0;

            $sta_and = strrev($row["mask_and"]);
            for ($bit = 0; $bit < strlen($sta_and); $bit++)
                $tbits_and[$bit]["nset"] += substr($sta_and, $bit, 1) != "0" ? 1 : 0;

            $nrows++;
        }

        $tbits_left = array();
        $tbits_right = array();

        $sbas_id = \phrasea::sbasFromBas($this->app, $this->base_id);
        $databox = $this->app['phraseanet.appbox']->get_databox($sbas_id);
        $status = $databox->get_statusbits();

        foreach ($status as $bit => $datas) {
            $tbits_left[$bit]["nset"] = 0;
            $tbits_left[$bit]["name"] = $datas["labeloff"];
            $tbits_left[$bit]["icon"] = $datas["img_off"];

            $tbits_right[$bit]["nset"] = 0;
            $tbits_right[$bit]["name"] = $datas["labelon"];
            $tbits_right[$bit]["icon"] = $datas["img_on"];
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

        $this->users_datas = array(
            'tbits_left'  => $tbits_left,
            'tbits_right' => $tbits_right,
            'vand_and'    => $vand_and,
            'vand_or'     => $vand_or,
            'vxor_and'    => $vxor_and,
            'vxor_or'     => $vxor_or
        );

        return array(
            'datas'        => $this->users_datas,
            'users'        => $this->users,
            'users_serial' => implode(';', $this->users),
            'base_id'      => $this->base_id,
            'collection'   => \collection::get_from_base_id($this->app, $this->base_id),
        );
    }

    public function get_time()
    {
        $this->base_id = (int) $this->request->get('base_id');

        $sql = "SELECT u.usr_id, time_limited, limited_from, limited_to
      FROM (usr u INNER JOIN basusr bu ON u.usr_id = bu.usr_id)
      WHERE u.usr_id = " . implode(' OR u.usr_id = ', $this->users) . "
      AND bu.base_id = :base_id";

        $conn = \connection::getPDOConnection($this->app);
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':base_id' => $this->base_id));
        $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

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

        $datas = array('time_limited' => $time_limited, 'limited_from' => $limited_from, 'limited_to'   => $limited_to);

        $this->users_datas = $datas;

        return array(
            'datas'        => $this->users_datas,
            'users'        => $this->users,
            'users_serial' => implode(';', $this->users),
            'base_id'      => $this->base_id,
            'collection'   => \collection::get_from_base_id($this->app, $this->base_id),
        );
    }

    public function apply_rights()
    {
        $request = \http_request::getInstance();
        $ACL = $this->app['phraseanet.user']->ACL();
        $base_ids = array_keys($ACL->get_granted_base(array('canadmin')));

        $update = $create = $delete = $create_sbas = $update_sbas = array();

        foreach ($base_ids as $base_id) {
            $rights = array(
                'access',
                'actif',
                'canputinalbum',
                'nowatermark',
                'candwnldpreview',
                'candwnldhd',
                'cancmd',
                'canaddrecord',
                'canmodifrecord',
                'chgstatus',
                'candeleterecord',
                'imgtools',
                'canadmin',
                'canreport',
                'canpush',
                'manage',
                'modify_struct'
            );
            foreach ($rights as $k => $right) {
                if (($right == 'access' && !$ACL->has_access_to_base($base_id))
                    || ($right != 'access' && !$ACL->has_right_on_base($base_id, $right))) {
                    unset($rights[$k]);
                    continue;
                }
                $rights[$k] = $right . '_' . $base_id;
            }
            $parm = $request->get_parms_from_serialized_datas($rights, 'values');

            foreach ($parm as $p => $v) {
                if (trim($v) == '')
                    continue;

                $serial = explode('_', $p);
                $base_id = array_pop($serial);

                $p = implode('_', $serial);

                if ($p == 'access') {
                    if ($v === '1') {
                        $create_sbas[\phrasea::sbasFromBas($this->app, $base_id)] = \phrasea::sbasFromBas($this->app, $base_id);
                        $create[] = $base_id;
                    } else
                        $delete[] = $base_id;
                } else {
                    $create_sbas[\phrasea::sbasFromBas($this->app, $base_id)] = \phrasea::sbasFromBas($this->app, $base_id);
                    $update[$base_id][$p] = $v;
                }
            }
        }

        $sbas_ids = $ACL->get_granted_sbas();

        foreach ($sbas_ids as $databox) {
            $rights = array(
                'bas_modif_th',
                'bas_manage',
                'bas_modify_struct',
                'bas_chupub'
            );
            foreach ($rights as $k => $right) {
                if (!$ACL->has_right_on_sbas($databox->get_sbas_id(), $right)) {
                    unset($rights[$k]);
                    continue;
                }
                $rights[$k] = $right . '_' . $databox->get_sbas_id();
            }

            $parm = $request->get_parms_from_serialized_datas($rights, 'values');

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
                $this->app['phraseanet.appbox']->get_connection()->beginTransaction();

                $user = \User_Adapter::getInstance($usr_id, $this->app);
                $user->ACL()->revoke_access_from_bases($delete)
                    ->give_access_to_base($create)
                    ->give_access_to_sbas($create_sbas);

                foreach ($update as $base_id => $rights) {
                    $user->ACL()->update_rights_to_base($base_id, $rights);
                }

                foreach ($update_sbas as $sbas_id => $rights) {
                    $user->ACL()->update_rights_to_sbas($sbas_id, $rights);
                }

                $this->app['phraseanet.appbox']->get_connection()->commit();

                $user->ACL()->revoke_unused_sbas_rights();

                unset($user);
            } catch (\Exception $e) {
                $this->app['phraseanet.appbox']->get_connection()->rollBack();
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

        $user = \User_adapter::getInstance(array_pop($users), $this->app);

        if ($user->is_template() || $user->is_special()) {
            return $this;
        }

        $request = \http_request::getInstance();

        $infos = array(
            'gender'
            , 'first_name'
            , 'last_name'
            , 'email'
            , 'address'
            , 'zip'
            , 'geonameid'
            , 'function'
            , 'company'
            , 'activite'
            , 'telephone'
            , 'fax'
        );

        $parm = $request->get_parms_from_serialized_datas($infos, 'user_infos');

        if ($parm['email'] && !\Swift_Validate::email($parm['email'])) {
            throw new \Exception_InvalidArgument(_('Email addess is not valid'));
        }

        $old_email = $user->get_email();

        $user->set_firstname($parm['first_name'])
            ->set_lastname($parm['last_name'])
            ->set_gender($parm['gender'])
            ->set_email($parm['email'])
            ->set_address($parm['address'])
            ->set_zip($parm['zip'])
            ->set_geonameid($parm['geonameid'])
            ->set_position($parm['function'])
            ->set_job($parm['activite'])
            ->set_company($parm['company'])
            ->set_tel($parm['telephone'])
            ->set_fax($parm['fax']);

        $new_email = $user->get_email();

        if ($old_email != $new_email) {
            try {
                $oldReceiver = new Receiver(null, $old_email);
            } catch (InvalidArgumentException $e) {

            }

            if ($oldReceiver) {
                $mailOldAddress = MailSuccessEmailUpdate::create($this->app, $oldReceiver, null, sprintf(_('You will now receive notifications at %s'), $new_email));
                $this->app['notification.deliverer']->deliver($mailOldAddress);
            }

            try {
                $newReceiver = new Receiver(null, $new_email);
            } catch (InvalidArgumentException $e) {

            }

            if ($newReceiver) {
                $mailNewAddress = MailSuccessEmailUpdate::create($this->app, $newReceiver, null, sprintf(_('You will no longer receive notifications at %s'), $old_email));
                $this->app['notification.deliverer']->deliver($mailNewAddress);
            }
        }

        return $this;
    }

    public function apply_template()
    {
        $template = \User_adapter::getInstance($this->request->get('template'), $this->app);

        if ($template->get_template_owner()->get_id() != $this->app['phraseanet.user']->get_id()) {
            throw new \Exception_Forbidden('You are not the owner of the template');
        }

        $base_ids = array_keys($this->app['phraseanet.user']->ACL()->get_granted_base(array('canadmin')));

        foreach ($this->users as $usr_id) {
            $user = \User_adapter::getInstance($usr_id, $this->app);

            if ($user->is_template()) {
                continue;
            }

            $user->ACL()->apply_model($template, $base_ids);
        }

        return $this;
    }

    public function apply_quotas()
    {
        $this->base_id = (int) $this->request->get('base_id');

        foreach ($this->users as $usr_id) {
            $user = \User_Adapter::getInstance($usr_id, $this->app);
            if ($this->request->get('quota'))
                $user->ACL()->set_quotas_on_base($this->base_id, $this->request->get('droits'), $this->request->get('restes'));
            else
                $user->ACL()->remove_quotas_on_base($this->base_id);
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
                $user = \User_Adapter::getInstance($usr_id, $this->app);

                $user->ACL()->set_masks_on_base($this->base_id, $vand_and, $vand_or, $vxor_and, $vxor_or);
            }
        }

        return $this;
    }

    public function apply_time()
    {
        $this->base_id = (int) $this->request->get('base_id');

        $dmin = $this->request->get('dmin') ? new \DateTime($this->request->get('dmin')) : null;
        $dmax = $this->request->get('dmax') ? new \DateTime($this->request->get('dmax')) : null;

        $activate = !!$this->request->get('limit');

        foreach ($this->users as $usr_id) {
            $user = \User_Adapter::getInstance($usr_id, $this->app);

            $user->ACL()->set_limits($this->base_id, $activate, $dmin, $dmax);
        }
    }

    public function resetRights()
    {
        $base_ids = array_keys($this->app['phraseanet.user']->ACL()->get_granted_base(array('canadmin')));

        foreach ($this->users as $usr_id) {
            $user = \User_Adapter::getInstance($usr_id, $this->app);
            $ACL = $user->ACL();

            if ($user->is_template()) {
                $template = $user;

                if ($template->get_template_owner()->get_id() !== $this->app['phraseanet.user']->get_id()) {
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
}
