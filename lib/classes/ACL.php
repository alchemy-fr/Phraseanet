<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Entities\User;
use Doctrine\DBAL\DBALException;
use Alchemy\Phrasea\Model\RecordInterface;

class ACL implements cache_cacheableInterface
{
    /**
     *
     * @var user
     */
    protected $user;

    /**
     *
     * @var Array
     */
    protected $_rights_sbas;

    /**
     *
     * @var Array
     */
    protected $_rights_bas;

    /**
     *
     * @var Array
     */
    protected $_rights_records_document;

    /**
     *
     * @var Array
     */
    protected $_rights_records_preview;

    /**
     *
     * @var Array
     */
    protected $_limited;

    /**
     *
     * @var boolean
     */
    protected $is_admin;

    /**
     *
     * @var Array
     */
    protected $_global_rights = [
        'taskmanager'        => false,
        'manageusers'        => false,
        'order'              => false,
        'report'             => false,
        'push'               => false,
        'addrecord'          => false,
        'modifyrecord'       => false,
        'changestatus'       => false,
        'doctools'           => false,
        'deleterecord'       => false,
        'addtoalbum'         => false,
        'coll_modify_struct' => false,
        'coll_manage'        => false,
        'order_master'       => false,
        'bas_modif_th'       => false,
        'bas_modify_struct'  => false,
        'bas_manage'         => false,
        'bas_chupub'         => false,
        'candwnldpreview'    => true,
        'candwnldhd'         => true
    ];

    /**
     *
     * @var Application
     */
    protected $app;

    const CACHE_IS_ADMIN = 'is_admin';
    const CACHE_RIGHTS_BAS = 'rights_bas';
    const CACHE_LIMITS_BAS = 'limits_bas';
    const CACHE_RIGHTS_SBAS = 'rights_sbas';
    const CACHE_RIGHTS_RECORDS = 'rights_records';
    const CACHE_GLOBAL_RIGHTS = 'global_rights';
    const GRANT_ACTION_PUSH = 'push';
    const GRANT_ACTION_VALIDATE = 'validate';

    /**
     * Constructor
     *
     * @param User        $user
     * @param Application $app
     *
     * @return \ACL
     */
    public function __construct(User $user, Application $app)
    {
        $this->user = $user;
        $this->app = $app;

        return $this;
    }

    /**
     * Check if a hd grant has been received for a record
     *
     * @param  \record_adapter $record
     * @return boolean
     */
    public function has_hd_grant(RecordInterface $record)
    {

        $this->load_hd_grant();

        if (array_key_exists($record->getId(), $this->_rights_records_document)) {
            return true;
        }

        return false;
    }

    public function grant_hd_on(RecordInterface $record, User $pusher, $action)
    {
        $sql = 'REPLACE INTO records_rights
            (id, usr_id, sbas_id, record_id, document, `case`, pusher_usr_id)
            VALUES
            (null, :usr_id, :sbas_id, :record_id, 1, :case, :pusher)';

        $params = [
            ':usr_id'    => $this->user->getId()
            , ':sbas_id'   => $record->getDataboxId()
            , ':record_id' => $record->getRecordId()
            , ':case'      => $action
            , ':pusher'    => $pusher->getId()
        ];

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $this->delete_data_from_cache(self::CACHE_RIGHTS_RECORDS);

        return $this;
    }

    public function grant_preview_on(RecordInterface $record, User $pusher, $action)
    {
        $sql = 'REPLACE INTO records_rights
            (id, usr_id, sbas_id, record_id, preview, `case`, pusher_usr_id)
            VALUES
            (null, :usr_id, :sbas_id, :record_id, 1, :case, :pusher)';

        $params = [
            ':usr_id'    => $this->user->getId()
            , ':sbas_id'   => $record->getDataboxId()
            , ':record_id' => $record->getRecordId()
            , ':case'      => $action
            , ':pusher'    => $pusher->getId()
        ];

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $this->delete_data_from_cache(self::CACHE_RIGHTS_RECORDS);

        return $this;
    }

    /**
     * Check if a hd grant has been received for a record
     *
     * @param  \record_adapter $record
     * @return boolean
     */
    public function has_preview_grant(RecordInterface $record)
    {
        $this->load_hd_grant();

        if (array_key_exists($record->getId(), $this->_rights_records_preview)) {
            return true;
        }

        return false;
    }

    public function has_access_to_record(RecordInterface $record)
    {
        if ($this->has_access_to_base($record->getBaseId()) && $this->has_status_access_to_record($record)) {
            return true;
        }

        return $this->has_preview_grant($record) || $this->has_hd_grant($record);
    }

    public function has_status_access_to_record(RecordInterface $record)
    {
        return 0 === ((bindec($record->getStatusBitField()) ^ $this->get_mask_xor($record->getBaseId())) & $this->get_mask_and($record->getBaseId()));
    }

    public function has_access_to_subdef(RecordInterface $record, $subdef_name)
    {
        if ($subdef_name == 'thumbnail') {
            return true;
        }

        if ($record->isStory()) {
            return true;
        }

        $databox = $this->app['phraseanet.appbox']->get_databox($record->getDataboxId());
        try {
            $subdef_class = $databox->get_subdef_structure()
                ->get_subdef($record->getType(), $subdef_name)
                ->get_class();
        } catch (\Exception $e) {
            return false;
        }

        $granted = false;

        if ($subdef_class == databox_subdef::CLASS_THUMBNAIL) {
            $granted = true;
        } elseif ($subdef_class == databox_subdef::CLASS_PREVIEW && $this->has_right_on_base($record->getBaseId(), 'candwnldpreview')) {
            $granted = true;
        } elseif ($subdef_class == databox_subdef::CLASS_PREVIEW && $this->has_preview_grant($record)) {
            $granted = true;
        } elseif ($subdef_class == databox_subdef::CLASS_DOCUMENT && $this->has_right_on_base($record->getBaseId(), 'candwnldhd')) {
            $granted = true;
        } elseif ($subdef_class == databox_subdef::CLASS_DOCUMENT && $this->has_hd_grant($record)) {
            $granted = true;
        }

        if (false === $granted && $this->app['repo.feed-items']->isRecordInPublicFeed($this->app, $record->getDataboxId(), $record->getRecordId())) {
            $granted = true;
        }

        return $granted;
    }

    /**
     * Apply a template on user
     *
     * @param  User  $template_user
     * @param  array $base_ids
     * @return ACL
     */
    public function apply_model(User $template_user, Array $base_ids)
    {
        if (count($base_ids) == 0) {
            return $this;
        }

        $sbas_ids = [];

        foreach ($base_ids as $base_id) {
            $sbas_ids[] = phrasea::sbasFromBas($this->app, $base_id);
        }

        $sbas_ids = array_unique($sbas_ids);

        $sbas_rights = ['bas_manage', 'bas_modify_struct', 'bas_modif_th', 'bas_chupub'];

        $sbas_to_acces = [];
        $rights_to_give = [];

        foreach ($this->app['acl']->get($template_user)->get_granted_sbas() as $databox) {
            $sbas_id = $databox->get_sbas_id();

            if (!in_array($sbas_id, $sbas_ids))
                continue;

            if (!$this->has_access_to_sbas($sbas_id)) {
                $sbas_to_acces[] = $sbas_id;
            }

            foreach ($sbas_rights as $right) {
                if ($this->app['acl']->get($template_user)->has_right_on_sbas($sbas_id, $right)) {
                    $rights_to_give[$sbas_id][$right] = '1';
                }
            }
        }

        $this->give_access_to_sbas($sbas_to_acces);

        foreach ($rights_to_give as $sbas_id => $rights) {
            $this->update_rights_to_sbas($sbas_id, $rights);
        }

        $bas_rights = ['canputinalbum', 'candwnldhd'
            , 'candwnldpreview', 'cancmd'
            , 'canadmin', 'actif', 'canreport', 'canpush'
            , 'canaddrecord', 'canmodifrecord', 'candeleterecord'
            , 'chgstatus', 'imgtools'
            , 'manage', 'modify_struct'
            , 'nowatermark', 'order_master'
        ];

        $bas_to_acces = $masks_to_give = $rights_to_give = [];

        /**
         * map masks (and+xor) of template to masks to apply to user on base
         * (and_and, and_or, xor_and, xor_or)
         */
        $sbmap = [
            '00' => ['aa' => '1', 'ao' => '0', 'xa' => '1', 'xo' => '0'],
            '01' => ['aa' => '1', 'ao' => '0', 'xa' => '1', 'xo' => '0'],
            '10' => ['aa' => '1', 'ao' => '1', 'xa' => '0', 'xo' => '0'],
            '11' => ['aa' => '1', 'ao' => '1', 'xa' => '1', 'xo' => '1']
        ];

        foreach ($this->app['acl']->get($template_user)->get_granted_base() as $collection) {
            $base_id = $collection->get_base_id();

            if (!in_array($base_id, $base_ids))
                continue;

            if (!$this->has_access_to_base($base_id)) {
                $bas_to_acces[] = $base_id;
            }

            foreach ($bas_rights as $right) {
                if ($this->app['acl']->get($template_user)->has_right_on_base($base_id, $right)) {
                    $rights_to_give[$base_id][$right] = '1';
                }
            }

            $mask_and = $this->app['acl']->get($template_user)->get_mask_and($base_id);
            $mask_xor = $this->app['acl']->get($template_user)->get_mask_xor($base_id);

            /**
             * apply sb is substractive
             */
            $mand = substr(
                str_repeat('0', 32)
                . decbin($mask_and)
                , -32
            );
            $mxor = substr(
                str_repeat('0', 32)
                . decbin($mask_xor)
                , -32
            );
            $m = ['aa' => '', 'ao' => '', 'xa' => '', 'xo' => ''];
            for ($i = 0; $i < 32; $i++) {
                $ax = $mand[$i] . $mxor[$i];

                foreach ($m as $k => $v) {
                    $m[$k] .= $sbmap[$ax][$k];
                }
            }

            $masks_to_give[$base_id] = [
                'aa' => $m['aa']
                , 'ao' => $m['ao']
                , 'xa' => $m['xa']
                , 'xo' => $m['xo']
            ];
        }

        $this->give_access_to_base($bas_to_acces);

        foreach ($masks_to_give as $base_id => $mask) {
            $this->set_masks_on_base($base_id, $mask['aa'], $mask['ao'], $mask['xa'], $mask['xo']);
        }

        foreach ($rights_to_give as $base_id => $rights) {
            $this->update_rights_to_base($base_id, $rights);
        }

        $this->apply_template_time_limits($template_user, $base_ids);

        $this->user->setLastAppliedTemplate($template_user);

        return $this;
    }

    private function apply_template_time_limits(User $template_user, Array $base_ids)
    {
        foreach ($base_ids as $base_id) {
            $limited = $this->app['acl']->get($template_user)->get_limits($base_id);
            if (null !== $limited) {
                $this->set_limits($base_id, '1', $limited['dmin'], $limited['dmax']);
            } else {
                $this->set_limits($base_id, '0', $limited['dmin'], $limited['dmax']);
            }
        }
    }

    /**
     *
     * @return boolean
     */
    public function is_phantom()
    {
        return count($this->get_granted_base()) === 0;
    }

    /**
     *
     * @param  int     $base_id
     * @param  string  $right
     * @return boolean
     */
    public function has_right_on_base($base_id, $right)
    {
        $this->load_rights_bas();

        if (!$this->has_access_to_base($base_id)) {
            return false;
        }

        if ($this->is_limited($base_id)) {
            return false;
        }

        if (!isset($this->_rights_bas[$base_id][$right]))
            throw new Exception('right ' . $right . ' does not exists');

        return ($this->_rights_bas[$base_id][$right] === true);
    }

    /**
     *
     * @param  <type> $option
     * @return <type>
     */
    public function get_cache_key($option = null)
    {
        return '_ACL_' . $this->user->getId() . ($option ? '_' . $option : '');
    }

    /**
     *
     * @param  <type> $option
     * @return <type>
     */
    public function delete_data_from_cache($option = null)
    {
        switch ($option) {
            case self::CACHE_GLOBAL_RIGHTS:
                $this->_global_rights = null;
                break;
            case self::CACHE_RIGHTS_BAS:
            case self::CACHE_LIMITS_BAS:
                $this->_rights_bas = null;
                $this->_limited = null;
                break;
            case self::CACHE_RIGHTS_RECORDS:
                $this->_rights_records_document = null;
                $this->_rights_records_preview = null;
                break;
            case self::CACHE_RIGHTS_SBAS:
                $this->_rights_sbas = null;
                break;
            default:
                break;
        }

        return $this->app['phraseanet.appbox']->delete_data_from_cache($this->get_cache_key($option));
    }

    /**
     *
     * @param  <type> $option
     * @return <type>
     */
    public function get_data_from_cache($option = null)
    {
        return $this->app['phraseanet.appbox']->get_data_from_cache($this->get_cache_key($option));
    }

    /**
     *
     * @param  <type> $value
     * @param  <type> $option
     * @param  <type> $duration
     * @return <type>
     */
    public function set_data_to_cache($value, $option = null, $duration = 0)
    {
        return $this->app['phraseanet.appbox']->set_data_to_cache($value, $this->get_cache_key($option), $duration);
    }

    /**
     * Return true if user is restricted in download on the collection
     *
     * @param  int     $base_id
     * @return boolean
     */
    public function is_restricted_download($base_id)
    {
        $this->load_rights_bas();

        if (!$this->has_access_to_base($base_id)) {
            return false;
        }

        return $this->_rights_bas[$base_id]['restrict_dwnld'];
    }

    /**
     * Return the number of remaining downloads on the collection
     *
     * @param  int $base_id
     * @return int
     */
    public function remaining_download($base_id)
    {
        $this->load_rights_bas();

        if (!$this->has_access_to_base($base_id)) {
            return false;
        }

        return (int) $this->_rights_bas[$base_id]['remain_dwnld'];
    }

    /**
     * Remove n download from the remainings
     *
     * @param  int $base_id
     * @param  int $n
     * @return ACL
     */
    public function remove_remaining($base_id, $n = 1)
    {
        $this->load_rights_bas();

        if (!$this->has_access_to_base($base_id)) {
            return false;
        }

        $this->_rights_bas[$base_id]['remain_dwnld'] =
            $this->_rights_bas[$base_id]['remain_dwnld'] - (int) $n;
        $v = $this->_rights_bas[$base_id]['remain_dwnld'];
        $this->_rights_bas[$base_id]['remain_dwnld'] =
            $this->_rights_bas[$base_id]['remain_dwnld'] < 0 ? 0 : $v;

        return $this;
    }

    /**
     * Check if the user has the right, at least on one collection
     *
     * @param  string  $right
     * @return boolean
     */
    public function has_right($right)
    {
        $this->load_global_rights();

        if (!isset($this->_global_rights[$right]))
            throw new Exception('This right does not exists');

        return $this->_global_rights[$right];
    }

    /**
     * Check if the user has the required right on a database
     *
     * @param  <type> $sbas_id
     * @param  <type> $right
     * @return <type>
     */
    public function has_right_on_sbas($sbas_id, $right)
    {
        $this->load_rights_sbas();

        if (!isset($this->_rights_sbas[$sbas_id])) {
            return false;
        }

        if (!isset($this->_rights_sbas[$sbas_id][$right]))
            throw new Exception('This right does not exists');

        if ($this->_rights_sbas[$sbas_id][$right] === true) {
            return true;
        }

        return false;
    }

    /**
     * Retrieve mask AND for user on specified base_id
     *
     * @param  int    $base_id
     * @return string
     */
    public function get_mask_and($base_id)
    {
        $this->load_rights_bas();
        if (!$this->has_access_to_base($base_id)) {
            return false;
        }

        return $this->_rights_bas[$base_id]['mask_and'];
    }

    /**
     * Retrieve mask XOR for user on specified base_id
     *
     * @param  int    $base_id
     * @return string
     */
    public function get_mask_xor($base_id)
    {
        $this->load_rights_bas();
        if (!$this->has_access_to_base($base_id)) {
            return false;
        }

        return $this->_rights_bas[$base_id]['mask_xor'];
    }

    /**
     * Return true if access to base_id is granted
     *
     * @param  int     $base_id
     * @return boolean
     */
    public function has_access_to_base($base_id)
    {
        $this->load_rights_bas();

        return (isset($this->_rights_bas[$base_id]) &&
            $this->_rights_bas[$base_id]['actif'] === true);
    }

    /**
     * Return true if access to sbas_id is granted
     *
     * @param  int     $sbas_id
     * @return boolean
     */
    public function has_access_to_sbas($sbas_id)
    {
        $this->load_rights_sbas();

        return (isset($this->_rights_sbas[$sbas_id]));
    }

    /**
     * Return an array of base_id which are granted, with
     * optionnal filter by rights
     *
     * @param  array      $rights
     * @param  array|null $sbas_ids Optionnal sbas_id to restrict the query on
     * @return array      An array of collection
     */
    public function get_granted_base(Array $rights = [], array $sbas_ids = null)
    {
        $this->load_rights_bas();
        $ret = [];

        foreach ($this->app['phraseanet.appbox']->get_databoxes() as $databox) {
            if ($sbas_ids && !in_array($databox->get_sbas_id(), $sbas_ids)) {
                continue;
            }

            foreach ($databox->get_collections() as $collection) {
                $continue = false;

                if (!array_key_exists($collection->get_base_id(), $this->_rights_bas)) {
                    continue;
                }

                $base_id = $collection->get_base_id();

                foreach ($rights as $right) {
                    if (!$this->has_right_on_base($base_id, $right)) {
                        $continue = true;
                        break;
                    }
                }
                if ($continue || $this->is_limited($base_id)) {
                    continue;
                }

                try {
                    $ret[$base_id] = collection::get_from_base_id($this->app, $base_id);
                } catch (\Exception $e) {

                }
            }
        }

        return $ret;
    }

    /**
     * Return an array of sbas_id which are granted, with
     * optionnal filter by rights
     *
     * @param  Array $rights
     * @return Array
     */
    public function get_granted_sbas($rights = [])
    {
        if (is_string($rights))
            $rights = [$rights];

        assert(is_array($rights));

        $this->load_rights_sbas();

        $ret = [];

        foreach ($this->_rights_sbas as $sbas_id => $datas) {
            $continue = false;

            foreach ($rights as $right) {
                if (!$this->has_right_on_sbas($sbas_id, $right)) {
                    $continue = true;
                    break;
                }
            }
            if ($continue)
                continue;

            try {
                $ret[$sbas_id] = $this->app['phraseanet.appbox']->get_databox((int) $sbas_id);
            } catch (\Exception $e) {

            }
        }

        return $ret;
    }

    public function is_admin()
    {
        return $this->user->isAdmin();
    }

    public function set_admin($boolean)
    {
        if ($boolean) {
            $this->app['manipulator.user']->promote($this->user);
        } else {
            $this->app['manipulator.user']->demote($this->user);
        }

        return $this;
    }

    /**
     * Load if needed the elements which have a HD grant
     *
     * @return Array
     */
    protected function load_hd_grant()
    {

        if ($this->_rights_records_preview) {
            return $this;
        }

        try {
            $tmp_rights = $this->get_data_from_cache(self::CACHE_RIGHTS_RECORDS);
            $this->_rights_records_preview = $tmp_rights['preview'];
            $this->_rights_records_document = $tmp_rights['document'];

            return $this;
        } catch (\Exception $e) {

        }
        $sql = 'SELECT sbas_id, record_id, preview, document
            FROM records_rights WHERE usr_id = :usr_id';

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute([':usr_id' => $this->user->getId()]);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);

        $this->_rights_records_preview = [];
        $this->_rights_records_document = [];

        foreach ($rs as $row) {
            $currentid = $row["sbas_id"] . "_" . $row["record_id"];
            if ($row['document'] == '1')
                $this->_rights_records_document[$currentid] = $currentid;
            $this->_rights_records_preview[$currentid] = $currentid;
        }

        $datas = [
            'preview'  => $this->_rights_records_preview
            , 'document' => $this->_rights_records_document
        ];

        $this->set_data_to_cache($datas, self::CACHE_RIGHTS_RECORDS);

        return $this;
    }

    /**
     * Loads rights of specified user for all sbas
     *
     * @return ACL
     */
    protected function load_rights_sbas()
    {

        if ($this->_rights_sbas && $this->_global_rights) {
            return $this;
        }

        try {
            $this->_global_rights = $this->get_data_from_cache(self::CACHE_GLOBAL_RIGHTS);
            $this->_rights_sbas = $this->get_data_from_cache(self::CACHE_RIGHTS_SBAS);

            return $this;
        } catch (\Exception $e) {

        }

        $sql = 'SELECT sbasusr.* FROM sbasusr, sbas
            WHERE usr_id= :usr_id
              AND sbas.sbas_id = sbasusr.sbas_id';

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute([':usr_id' => $this->user->getId()]);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $this->_rights_sbas = [];

        $this->_global_rights['bas_modif_th'] = false;
        $this->_global_rights['bas_modify_struct'] = false;
        $this->_global_rights['bas_manage'] = false;
        $this->_global_rights['bas_chupub'] = false;

        foreach ($rs as $row) {

            if ($row['bas_modif_th'] == '1')
                $this->_global_rights['bas_modif_th'] = true;
            if ($row['bas_modify_struct'] == '1')
                $this->_global_rights['bas_modify_struct'] = true;
            if ($row['bas_manage'] == '1')
                $this->_global_rights['bas_manage'] = true;
            if ($row['bas_chupub'] == '1')
                $this->_global_rights['bas_chupub'] = true;

            $this->_rights_sbas[$row['sbas_id']]['bas_modify_struct'] = ($row['bas_modify_struct'] == '1');
            $this->_rights_sbas[$row['sbas_id']]['bas_manage'] = ($row['bas_manage'] == '1');
            $this->_rights_sbas[$row['sbas_id']]['bas_chupub'] = ($row['bas_chupub'] == '1');
            $this->_rights_sbas[$row['sbas_id']]['bas_modif_th'] = ($row['bas_modif_th'] == '1');
        }
        $this->set_data_to_cache($this->_rights_sbas, self::CACHE_RIGHTS_SBAS);
        $this->set_data_to_cache($this->_global_rights, self::CACHE_GLOBAL_RIGHTS);

        return $this;
    }

    /**
     * Loads rights of specified user for all bas
     *
     * @return ACL
     */
    protected function load_rights_bas()
    {
        if ($this->_rights_bas && $this->_global_rights && is_array($this->_limited)) {
            return $this;
        }

        try {
            $this->_global_rights = $this->get_data_from_cache(self::CACHE_GLOBAL_RIGHTS);
            $this->_rights_bas = $this->get_data_from_cache(self::CACHE_RIGHTS_BAS);
            $this->_limited = $this->get_data_from_cache(self::CACHE_LIMITS_BAS);

            return $this;
        } catch (\Exception $e) {
        }

        $sql = 'SELECT  u.* FROM basusr u, bas b, sbas s
            WHERE usr_id= :usr_id
            AND b.base_id = u.base_id
            AND b.sbas_id = s.sbas_id
            AND s.sbas_id = b.sbas_id ';

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute([':usr_id' => $this->user->getId()]);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $this->_rights_bas = $this->_limited = [];

        $this->_global_rights['manageusers'] = false;
        $this->_global_rights['coll_manage'] = false;
        $this->_global_rights['coll_modify_struct'] = false;
        $this->_global_rights['order'] = false;
        $this->_global_rights['push'] = false;
        $this->_global_rights['addrecord'] = false;
        $this->_global_rights['modifyrecord'] = false;
        $this->_global_rights['changestatus'] = false;
        $this->_global_rights['doctools'] = false;
        $this->_global_rights['deleterecord'] = false;
        $this->_global_rights['addtoalbum'] = false;
        $this->_global_rights['report'] = false;
        $this->_global_rights['candwnldpreview'] = false;
        $this->_global_rights['candwnldhd'] = false;
        $this->_global_rights['order_master'] = false;

        foreach ($rs as $row) {
            $this->_rights_bas[$row['base_id']]['actif'] = ($row['actif'] == '1');

            if ($row['canadmin'] == '1')
                $this->_global_rights['manageusers'] = true;
            if ($row['manage'] == '1')
                $this->_global_rights['coll_manage'] = true;
            if ($row['modify_struct'] == '1')
                $this->_global_rights['coll_modify_struct'] = true;
            if ($row['cancmd'] == '1')
                $this->_global_rights['order'] = true;
            if ($row['canpush'] == '1')
                $this->_global_rights['push'] = true;
            if ($row['canaddrecord'] == '1')
                $this->_global_rights['addrecord'] = true;
            if ($row['canmodifrecord'] == '1')
                $this->_global_rights['modifyrecord'] = true;
            if ($row['chgstatus'] == '1')
                $this->_global_rights['changestatus'] = true;
            if ($row['imgtools'] == '1')
                $this->_global_rights['doctools'] = true;
            if ($row['candeleterecord'] == '1')
                $this->_global_rights['deleterecord'] = true;
            if ($row['canputinalbum'] == '1')
                $this->_global_rights['addtoalbum'] = true;
            if ($row['canreport'] == '1')
                $this->_global_rights['report'] = true;
            if ($row['candwnldpreview'] == '1')
                $this->_global_rights['candwnldpreview'] = true;
            if ($row['candwnldhd'] == '1')
                $this->_global_rights['candwnldhd'] = true;
            if ($row['order_master'] == '1')
                $this->_global_rights['order_master'] = true;

            $row['limited_from'] = $row['limited_from'] == '0000-00-00 00:00:00' ? '' : trim($row['limited_from']);
            $row['limited_to'] = $row['limited_to'] == '0000-00-00 00:00:00' ? '' : trim($row['limited_to']);

            if ($row['time_limited'] == '1'
                && ($row['limited_from'] !== '' || $row['limited_to'] !== '')) {
                $this->_limited[$row['base_id']] = [
                    'dmin' => $row['limited_from'] ? new DateTime($row['limited_from']) : null
                    , 'dmax' => $row['limited_to'] ? new DateTime($row['limited_to']) : null
                ];
            }

            $this->_rights_bas[$row['base_id']]['imgtools']
                = $row['imgtools'] == '1';

            $this->_rights_bas[$row['base_id']]['chgstatus']
                = $row['chgstatus'] == '1';
            $this->_rights_bas[$row['base_id']]['cancmd']
                = $row['cancmd'] == '1';
            $this->_rights_bas[$row['base_id']]['canaddrecord']
                = $row['canaddrecord'] == '1';
            $this->_rights_bas[$row['base_id']]['canpush']
                = $row['canpush'] == '1';
            $this->_rights_bas[$row['base_id']]['candeleterecord']
                = $row['candeleterecord'] == '1';
            $this->_rights_bas[$row['base_id']]['canadmin']
                = $row['canadmin'] == '1';
            $this->_rights_bas[$row['base_id']]['chgstatus']
                = $row['chgstatus'] == '1';
            $this->_rights_bas[$row['base_id']]['candwnldpreview']
                = $row['candwnldpreview'] == '1';
            $this->_rights_bas[$row['base_id']]['candwnldhd']
                = $row['candwnldhd'] == '1';
            $this->_rights_bas[$row['base_id']]['nowatermark']
                = $row['nowatermark'] == '1';
            $this->_rights_bas[$row['base_id']]['restrict_dwnld']
                = $row['restrict_dwnld'] == '1';
            $this->_rights_bas[$row['base_id']]['remain_dwnld']
                = (int) $row['remain_dwnld'];
            $this->_rights_bas[$row['base_id']]['canmodifrecord']
                = $row['canmodifrecord'] == '1';
            $this->_rights_bas[$row['base_id']]['canputinalbum']
                = $row['canputinalbum'] == '1';
            $this->_rights_bas[$row['base_id']]['canreport']
                = $row['canreport'] == '1';
            $this->_rights_bas[$row['base_id']]['mask_and']
                = (int) $row['mask_and'];
            $this->_rights_bas[$row['base_id']]['mask_xor']
                = (int) $row['mask_xor'];
            $this->_rights_bas[$row['base_id']]['modify_struct']
                = $row['modify_struct'] == '1';
            $this->_rights_bas[$row['base_id']]['manage']
                = $row['manage'] == '1';
            $this->_rights_bas[$row['base_id']]['order_master']
                = $row['order_master'] == '1';
        }

        $this->set_data_to_cache($this->_global_rights, self::CACHE_GLOBAL_RIGHTS);
        $this->set_data_to_cache($this->_rights_bas, self::CACHE_RIGHTS_BAS);
        $this->set_data_to_cache($this->_limited, self::CACHE_LIMITS_BAS);

        return $this;
    }

    /**
     * Loads global rights for user
     *
     * @return ACL
     */
    protected function load_global_rights()
    {
        $this->load_rights_bas();
        $this->load_rights_sbas();
        $this->_global_rights['taskmanager'] = $this->is_admin();

        return $this;
    }

    /**
     * Return whether or not the acces to the specified module is OK
     *
     * @param  String  $module_name
     * @return boolean
     */
    public function has_access_to_module($module_name)
    {
        switch ($module_name) {
            case 'admin':
                return (
                    ($this->has_right('bas_modify_struct') ||
                    $this->has_right('coll_modify_struct') ||
                    $this->has_right('bas_manage') ||
                    $this->has_right('coll_manage') ||
                    $this->has_right('manageusers') ||
                    $this->is_admin()) );
                break;
            case 'thesaurus':
                return ($this->has_right('bas_modif_th') === true );
                break;
            case 'upload':
                return ($this->has_right('addrecord') === true);
                break;
            case 'report':
                return ($this->has_right('report') === true);
                break;
            default:
                break;
        }

        return true;
    }

    /**
     *
     * @param  array $base_ids
     * @return ACL
     */
    public function revoke_access_from_bases(Array $base_ids)
    {
        $sql_del = 'DELETE FROM basusr WHERE base_id = :base_id AND usr_id = :usr_id';
        $stmt_del = $this->app['phraseanet.appbox']->get_connection()->prepare($sql_del);

        $usr_id = $this->user->getId();

        foreach ($base_ids as $base_id) {
            if (!$stmt_del->execute([':base_id' => $base_id, ':usr_id'  => $usr_id])) {
                throw new Exception('Error while deleteing some rights');
            }
        }
        $stmt_del->closeCursor();
        $this->delete_data_from_cache(self::CACHE_RIGHTS_BAS);

        return $this;
    }

    /**
     *
     * @param  array $base_ids
     * @return ACL
     */
    public function give_access_to_base(Array $base_ids)
    {
        $sql_ins = 'INSERT INTO basusr (id, base_id, usr_id, actif)
                VALUES (null, :base_id, :usr_id, "1")';
        $stmt_ins = $this->app['phraseanet.appbox']->get_connection()->prepare($sql_ins);
        $usr_id = $this->user->getId();
        $to_update = [];
        $this->load_rights_bas();

        foreach ($base_ids as $base_id) {
            if (!isset($this->_rights_bas[$base_id])) {
                try {
                    $stmt_ins->execute([':base_id' => $base_id, ':usr_id'  => $usr_id]);
                } catch (DBALException $e) {
//                    if (null !== $e) {
//                        var_dump(get_class($e->getPrevious()));
//                    }
                    if (($e->getCode() == 23000)) {
                        $to_update[] = $base_id;
                    }
                }
            } elseif ($this->_rights_bas[$base_id]['actif'] === false) {
                $to_update[] = $base_id;
            }
        }
        $stmt_ins->closeCursor();

        $sql_upd = 'UPDATE basusr SET actif="1"
                  WHERE usr_id = :usr_id AND base_id = :base_id';
        $stmt_upd = $this->app['phraseanet.appbox']->get_connection()->prepare($sql_upd);
        foreach ($to_update as $base_id) {
            $stmt_upd->execute([':usr_id'  => $usr_id, ':base_id' => $base_id]);
        }
        $stmt_upd->closeCursor();

        $this->delete_data_from_cache(self::CACHE_RIGHTS_BAS);
        $this->inject_rights();

        return $this;
    }

    /**
     *
     * @param  array $sbas_ids
     * @return ACL
     */
    public function give_access_to_sbas(Array $sbas_ids)
    {
        $sql_ins = 'INSERT INTO sbasusr (sbasusr_id, sbas_id, usr_id) VALUES (null, :sbas_id, :usr_id)';
        $stmt_ins = $this->app['phraseanet.appbox']->get_connection()->prepare($sql_ins);

        $usr_id = $this->user->getId();

        foreach ($sbas_ids as $sbas_id) {
            if (!$this->has_access_to_sbas($sbas_id)) {
                try {
                    $stmt_ins->execute([':sbas_id' => $sbas_id, ':usr_id'  => $usr_id]);
                } catch (DBALException $e) {

                }
            }
        }
        $stmt_ins->closeCursor();
        $this->delete_data_from_cache(self::CACHE_RIGHTS_SBAS);

        return $this;
    }

    /**
     * @todo  Create special toggle 'actif' / not a right like others
     *        => nested loops when updating right to actif on an inactif account
     *
     * @param  <type> $base_id
     * @param  <type> $rights
     * @return ACL
     */
    public function update_rights_to_base($base_id, $rights)
    {

        if (!$this->has_access_to_base($base_id) && (!isset($rights['actif']) || $rights['actif'] == '1')) {
            $this->give_access_to_base([$base_id]);
        }

        $sql_up = "UPDATE basusr SET ";

        $sql_args = $params = [];
        foreach ($rights as $right => $v) {
            $sql_args[] = " " . $right . " = :" . $right;
            switch ($right) {
                default:
                    $params[':' . $right] = $v ? '1' : '0';
                    break;
                case 'mask_and':
                case 'mask_xor':
                    $params[':' . $right] = $v;
                    break;
            }
        }

        if (count($sql_args) == 0) {
            return $this;
        }

        $usr_id = $this->user->getId();

        $sql_up .= implode(', ', $sql_args) . ' WHERE base_id = :base_id
               AND usr_id = :usr_id';

        $params = array_merge(
            $params
            , [':base_id' => $base_id, ':usr_id'  => $usr_id]
        );

        $stmt_up = $this->app['phraseanet.appbox']->get_connection()->prepare($sql_up);
        $stmt_up->execute($params);
        $stmt_up->closeCursor();

        $this->delete_data_from_cache(self::CACHE_RIGHTS_BAS);

        return $this;
    }

    /**
     *
     * @return ACL
     */
    public function revoke_unused_sbas_rights()
    {
        $sql = 'DELETE FROM sbasusr
            WHERE usr_id = :usr_id_1
            AND sbas_id NOT IN
          (SELECT distinct sbas_id FROM basusr bu, bas b
            WHERE usr_id = :usr_id_2 AND b.base_id = bu.base_id)';

        $usr_id = $this->user->getId();
        $params = [':usr_id_1' => $usr_id, ':usr_id_2' => $usr_id];

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $this->delete_data_from_cache(self::CACHE_RIGHTS_SBAS);

        return $this;
    }

    /**
     *
     * @param  <type> $sbas_id
     * @param  <type> $rights
     * @return ACL
     */
    public function update_rights_to_sbas($sbas_id, $rights)
    {
        if (!$this->has_access_to_sbas($sbas_id))
            $this->give_access_to_sbas([$sbas_id]);

        $sql_up = "UPDATE sbasusr SET ";

        $sql_args = [];
        $usr_id = $this->user->getId();
        $params = [':sbas_id' => $sbas_id, ':usr_id'  => $usr_id];

        foreach ($rights as $right => $v) {
            $sql_args[] = " " . $right . " = :" . $right;
            $params[':' . $right] = $v ? '1' : '0';
        }

        if (count($sql_args) == 0) {
            return $this;
        }

        $sql_up .= implode(', ', $sql_args) . '
                WHERE sbas_id = :sbas_id AND usr_id = :usr_id';

        $stmt_up = $this->app['phraseanet.appbox']->get_connection()->prepare($sql_up);

        if (!$stmt_up->execute($params)) {
            throw new Exception('Error while updating some rights');
        }
        $stmt_up->closeCursor();
        $this->delete_data_from_cache(self::CACHE_RIGHTS_SBAS);

        return $this;
    }

    /**
     *
     * @param  <type> $base_id
     * @return ACL
     */
    public function remove_quotas_on_base($base_id)
    {
        $sql = 'UPDATE basusr
      SET remain_dwnld = 0, restrict_dwnld = 0, month_dwnld_max = 0
      WHERE usr_id = :usr_id AND base_id = :base_id ';

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute([':usr_id'  => $this->user->getId(), ':base_id' => $base_id]);
        $stmt->closeCursor();

        unset($stmt);
        $this->delete_data_from_cache(self::CACHE_RIGHTS_BAS);

        return $this;
    }

    public function update_download_restrictions()
    {
        $sql = 'UPDATE basusr SET remain_dwnld = month_dwnld_max
            WHERE actif = 1
            AND usr_id = :usr_id
            AND MONTH(lastconn) != MONTH(NOW()) AND restrict_dwnld = 1';
        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute([':usr_id' => $this->user->getId()]);
        $stmt->closeCursor();

        $sql = "UPDATE basusr SET lastconn=now()
            WHERE usr_id = :usr_id AND actif = 1";
        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute([':usr_id' => $this->user->getId()]);
        $stmt->closeCursor();

        unset($stmt);
        $this->delete_data_from_cache(self::CACHE_RIGHTS_BAS);

        return $this;
    }

    /**
     *
     * @param  <type> $base_id
     * @param  <type> $droits
     * @param  <type> $restes
     * @return ACL
     */
    public function set_quotas_on_base($base_id, $droits, $restes)
    {
        $sql = 'UPDATE basusr
      SET remain_dwnld = :restes, restrict_dwnld = 1, month_dwnld_max = :droits
      WHERE usr_id = :usr_id AND base_id = :base_id ';

        $params = [
            ':usr_id'  => $this->user->getId(),
            ':base_id' => $base_id,
            ':restes'  => $restes,
            ':droits'  => $droits
        ];

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        unset($stmt);
        $this->delete_data_from_cache(self::CACHE_RIGHTS_BAS);

        return $this;
    }

    public function duplicate_right_from_bas($base_id_from, $base_id_dest)
    {
        $sql = 'SELECT * FROM basusr
              WHERE base_id = :base_from AND usr_id = :usr_id';

        $params = [
            ':base_from' => $base_id_from,
            ':usr_id'    => $this->user->getId()
        ];

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if (!$row) {
            return $this;
        }

        $this->give_access_to_base([$base_id_dest]);

        $rights = [
            'mask_and' => $row['mask_and'],
            'mask_xor' => $row['mask_xor'],
        ];

        if ($row['canputinalbum'])
            $rights['canputinalbum'] = true;
        if ($row['candwnldhd'])
            $rights['candwnldhd'] = true;
        if ($row['candwnldpreview'])
            $rights['candwnldpreview'] = true;
        if ($row['cancmd'])
            $rights['cancmd'] = true;
        if ($row['canadmin'])
            $rights['canadmin'] = true;
        if ($row['canreport'])
            $rights['canreport'] = true;
        if ($row['canpush'])
            $rights['canpush'] = true;
        if ($row['nowatermark'])
            $rights['nowatermark'] = true;
        if ($row['canaddrecord'])
            $rights['canaddrecord'] = true;
        if ($row['canmodifrecord'])
            $rights['canmodifrecord'] = true;
        if ($row['candeleterecord'])
            $rights['candeleterecord'] = true;
        if ($row['chgstatus'])
            $rights['chgstatus'] = true;
        if ($row['imgtools'])
            $rights['imgtools'] = true;
        if ($row['manage'])
            $rights['manage'] = true;
        if ($row['modify_struct'])
            $rights['modify_struct'] = true;

        $this->update_rights_to_base($base_id_dest, $rights);

        if ($row['time_limited']) {
            $this->set_limits($base_id_dest, $row['time_limited'], new \DateTime($row['limited_from']), new \DateTime($row['limited_to']));
        }

        if ($row['restrict_dwnld']) {
            $this->set_quotas_on_base($base_id_dest, $row['month_dwnld_max'], $row['remain_dwnld']);
        }

        return $this;
    }

    public function inject_rights()
    {
        $this->update_download_restrictions();

        foreach ($this->get_granted_sbas() as $databox) {
            $this->inject_rights_sbas($databox);
        }

        return $this;
    }

    protected function inject_rights_sbas(databox $databox)
    {
        $this->delete_injected_rights_sbas($databox);

        $sql = "INSERT INTO collusr
              (site, usr_id, coll_id, mask_and, mask_xor, ord)
              VALUES (:site_id, :usr_id, :coll_id, :mask_and, :mask_xor, :ord)";
        $stmt = $databox->get_connection()->prepare($sql);
        $iord = 0;

        foreach ($this->get_granted_base([], [$databox->get_sbas_id()]) as $collection) {
            try {
                $stmt->execute([
                    ':site_id'  => $this->app['conf']->get(['main', 'key']),
                    ':usr_id'   => $this->user->getId(),
                    ':coll_id'  => $collection->get_coll_id(),
                    ':mask_and' => $this->get_mask_and($collection->get_base_id()),
                    ':mask_xor' => $this->get_mask_xor($collection->get_base_id()),
                    ':ord'      => $iord++
                ]);
            } catch (DBALException $e) {

            }
        }

        $stmt->closeCursor();

        return $this;
    }

    public function delete_injected_rights()
    {
        foreach ($this->get_granted_sbas() as $databox) {
            $this->delete_injected_rights_sbas($databox);
        }

        return $this;
    }

    public function delete_injected_rights_sbas(databox $databox)
    {
        $sql = 'DELETE FROM collusr WHERE usr_id = :usr_id AND site = :site';
        $stmt = $databox->get_connection()->prepare($sql);
        $stmt->execute([
            ':usr_id' => $this->user->getId(), ':site'   => $this->app['conf']->get(['main', 'key'])
        ]);
        $stmt->closeCursor();

        return $this;
    }

    public function set_masks_on_base($base_id, $and_and, $and_or, $xor_and, $xor_or)
    {
        $vhex = [];
        $datas = [
            'and_and' => $and_and,
            'and_or'  => $and_or,
            'xor_and' => $xor_and,
            'xor_or'  => $xor_or
        ];

        foreach ($datas as $name => $f) {
            $vhex[$name] = "0x";
            while (strlen($datas[$name]) < 32) {
                $datas[$name] = "0" . $datas[$name];
            }
        }
        foreach ($datas as $name => $f) {
            while (strlen($datas[$name]) > 0) {
                $valtmp = substr($datas[$name], 0, 4);
                $datas[$name] = substr($datas[$name], 4);
                $vhex[$name] .= dechex(bindec($valtmp));
            }
        }

        $sql = "UPDATE basusr
        SET mask_and=((mask_and & " . $vhex['and_and'] . ") | " . $vhex['and_or'] . ")
          ,mask_xor=((mask_xor & " . $vhex['xor_and'] . ") | " . $vhex['xor_or'] . ")
        WHERE usr_id = :usr_id and base_id = :base_id";

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute([':base_id' => $base_id, ':usr_id'  => $this->user->getId()]);
        $stmt->closeCursor();

        unset($stmt);

        $this->delete_data_from_cache(self::CACHE_RIGHTS_BAS);

        return $this;
    }

    public function is_limited($base_id)
    {
        $this->load_rights_bas();

        $datetime = new DateTime();

        if (!isset($this->_limited[$base_id])) {
            return false;
        }

        $lim_min = $this->_limited[$base_id]['dmin'] && $this->_limited[$base_id]['dmin'] > $datetime;

        $lim_max = $this->_limited[$base_id]['dmax'] && $this->_limited[$base_id]['dmax'] < $datetime;

        return $lim_max || $lim_min;
    }

    public function get_limits($base_id)
    {
        $this->load_rights_bas();
        if (!isset($this->_limited[$base_id])) {
            return null;
        }

        return $this->_limited[$base_id];
    }

    public function set_limits($base_id, $limit, DateTime $limit_from = null, DateTime $limit_to = null)
    {
        if ($limit) {
            $sql = 'UPDATE basusr
              SET time_limited = 1
                  , limited_from = :limited_from
                  , limited_to = :limited_to
              WHERE base_id = :base_id AND usr_id = :usr_id';
        } else {
            $sql = 'UPDATE basusr
              SET time_limited = 0
                  , limited_from = :limited_from
                  , limited_to = :limited_to
              WHERE base_id = :base_id AND usr_id = :usr_id';
        }

        $params = [
            ':usr_id'      => $this->user->getId()
            , ':base_id'     => $base_id
            , 'limited_from' => ($limit_from ? $limit_from->format(DATE_ISO8601) : null)
            , 'limited_to'   => ($limit_to ? $limit_to->format(DATE_ISO8601) : null)
        ];

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);

        $stmt->execute($params);

        $stmt->closeCursor();

        $this->delete_data_from_cache(self::CACHE_LIMITS_BAS);

        return $this;
    }

    public function can_see_business_fields(\databox $databox)
    {
        // a user can see the business fields if he has at least the right on one collection to edit a record
        foreach($databox->get_collections() as $collection) {
            if ($this->has_access_to_base($collection->get_base_id()) && $this->has_right_on_base($collection->get_base_id(), 'canmodifrecord')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns an array of collections on which the user is 'order master'
     *
     * @return array
     */
    public function get_order_master_collections()
    {
        $sql = 'SELECT base_id FROM basusr WHERE order_master="1" AND usr_id= :usr_id';
        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute([':usr_id' => $this->user->getId()]);
        $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $collections = [];

        foreach ($rs as $row) {
            $collections[] = \collection::get_from_base_id($this->app, $row['base_id']);
        }

        return $collections;
    }

    /**
     * Sets the user as "order_master" on a collection
     *
     * @param \collection $collection The collection to apply
     * @param Boolean     $bool       Wheter the user is order master or not
     *
     * @return ACL
     */
    public function set_order_master(\collection $collection, $bool)
    {
        $sql = 'UPDATE basusr SET order_master = :master
                WHERE usr_id = :usr_id AND base_id = :base_id';

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute([
            ':master'    => $bool ? 1 : 0,
            ':usr_id'    => $this->user->getId(),
            ':base_id'   => $collection->get_base_id()
        ]);
        $stmt->closeCursor();

        return $this;
    }
}
