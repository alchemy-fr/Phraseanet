<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Collection\Reference\CollectionReferenceCollection;
use Alchemy\Phrasea\Core\Event\Acl\AccessPeriodChangedEvent;
use Alchemy\Phrasea\Core\Event\Acl\AccessToBaseGrantedEvent;
use Alchemy\Phrasea\Core\Event\Acl\AccessToBaseRevokedEvent;
use Alchemy\Phrasea\Core\Event\Acl\AccessToSbasGrantedEvent;
use Alchemy\Phrasea\Core\Event\Acl\AclEvents;
use Alchemy\Phrasea\Core\Event\Acl\DownloadQuotasOnBaseChangedEvent;
use Alchemy\Phrasea\Core\Event\Acl\DownloadQuotasOnBaseRemovedEvent;
use Alchemy\Phrasea\Core\Event\Acl\DownloadQuotasResetEvent;
use Alchemy\Phrasea\Core\Event\Acl\MasksOnBaseChangedEvent;
use Alchemy\Phrasea\Core\Event\Acl\RightsToBaseChangedEvent;
use Alchemy\Phrasea\Core\Event\Acl\RightsToSbasChangedEvent;
use Alchemy\Phrasea\Core\Event\Acl\SysadminChangedEvent;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\RecordInterface;
use Alchemy\Phrasea\Model\RecordReferenceInterface;
use Alchemy\Phrasea\Utilities\NullableDateTime;
use Doctrine\DBAL\DBALException;


class ACL implements cache_cacheableInterface
{
    const BAS_MODIF_TH = 'bas_modif_th';
    const BAS_MODIFY_STRUCT = 'bas_modify_struct';
    const BAS_MANAGE = 'bas_manage';
    const BAS_CHUPUB = 'bas_chupub';

    const ACCESS = 'access';
    const ACTIF = 'actif';
    const CANADDRECORD = 'canaddrecord';
    const CANADMIN = 'canadmin';
    const CANCMD = 'cancmd';
    const CANDELETERECORD = 'candeleterecord';
    const CANDWNLDHD = 'candwnldhd';
    const CANDWNLDPREVIEW = 'candwnldpreview';
    const CANMODIFRECORD = 'canmodifrecord';
    const CANPUSH = 'canpush';
    const CANPUTINALBUM = 'canputinalbum';
    const CANREPORT = 'canreport';
    const CHGSTATUS = 'chgstatus';
    const IMGTOOLS = 'imgtools';
    const COLL_MANAGE = 'manage';
    const COLL_MODIFY_STRUCT = 'modify_struct';
    const NOWATERMARK = 'nowatermark';
    const ORDER_MASTER = 'order_master';
    const RESTRICT_DWNLD = 'restrict_dwnld';

    const TASKMANAGER = 'taskmanager';

    protected static $bas_rights = [
        self::ACTIF,
        self::CANADDRECORD,
        self::CANADMIN,
        self::CANCMD,
        self::CANDELETERECORD,
        self::CANDWNLDHD,
        self::CANDWNLDPREVIEW,
        self::CANMODIFRECORD,
        self::CANPUSH,
        self::CANPUTINALBUM,
        self::CANREPORT,
        self::CHGSTATUS,
        self::IMGTOOLS,
        self::COLL_MANAGE,
        self::COLL_MODIFY_STRUCT,
        self::NOWATERMARK,
        self::ORDER_MASTER,
    ];

    /**
     * @var User
     */
    protected $user;

    /**
     * @var array
     */
    protected $_rights_sbas;

    /**
     * @var array
     */
    protected $_rights_bas;

    /**
     * @var array
     */
    protected $_rights_records_document;

    /**
     * @var array
     */
    protected $_rights_records_preview;

    /**
     * @var array
     */
    protected $_limited;

    /**
     * @var bool
     */
    protected $is_admin;

    protected $_global_rights = [
        self::CANADDRECORD       => false,
        self::CANPUTINALBUM      => false,
        self::CANDWNLDHD         => true,
        self::CANDWNLDPREVIEW    => true,
        self::CHGSTATUS          => false,
        self::COLL_MANAGE        => false,
        self::COLL_MODIFY_STRUCT => false,
        self::CANDELETERECORD    => false,
        self::IMGTOOLS           => false,
        self::CANADMIN           => false,
        self::CANMODIFRECORD     => false,
        self::CANCMD             => false,
        self::ORDER_MASTER       => false,
        self::CANPUSH            => false,
        self::CANREPORT          => false,

        self::BAS_CHUPUB         => false,
        self::BAS_MANAGE         => false,
        self::BAS_MODIF_TH       => false,
        self::BAS_MODIFY_STRUCT  => false,

        self::TASKMANAGER        => false,
    ];

    /**
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
    const GRANT_ACTION_ORDER = 'order';

    /**
     * Constructor
     *
     * @param User        $user
     * @param Application $app
     */
    public function __construct(User $user, Application $app)
    {
        $this->user = $user;
        $this->app = $app;
    }

    /**
     * Returns the list of available rights for collections
     *
     * @return string[]
     */
    public function get_bas_rights()
    {
        return self::$bas_rights;
    }

    /**
     * Returns the list of available rights by databox for the current user
     *
     * @return array
     */
    public function get_sbas_rights()
    {
        $this->load_rights_sbas();

        return $this->_rights_sbas;
    }

    /**
     * Check if a hd grant has been received for a record
     *
     * @param RecordReferenceInterface $record
     * @return bool
     */
    public function has_hd_grant(RecordReferenceInterface $record)
    {

        $this->load_hd_grant();

        if (array_key_exists($record->getId(), $this->_rights_records_document)) {
            return true;
        }

        return false;
    }

    public function grant_hd_on(RecordReferenceInterface $record, User $pusher, $action)
    {
        $sql = "REPLACE INTO records_rights\n"
            . "(id, usr_id, sbas_id, record_id, document, `case`, pusher_usr_id)\n"
            . "VALUES (null, :usr_id, :sbas_id, :record_id, 1, :case, :pusher)";

        $params = [
            ':usr_id'    => $this->user->getId(),
            ':sbas_id'   => $record->getDataboxId(),
            ':record_id' => $record->getRecordId(),
            ':case'      => $action,
            ':pusher'    => $pusher->getId()
        ];

        $stmt = $this->app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $this->delete_data_from_cache(self::CACHE_RIGHTS_RECORDS);

        return $this;
    }

    public function grant_preview_on(RecordReferenceInterface $record, User $pusher, $action)
    {
        $sql = "REPLACE INTO records_rights\n"
            . " (id, usr_id, sbas_id, record_id, preview, `case`, pusher_usr_id)\n"
            . " VALUES\n"
            . " (null, :usr_id, :sbas_id, :record_id, 1, :case, :pusher)";

        $params = [
            ':usr_id'    => $this->user->getId()
            , ':sbas_id'   => $record->getDataboxId()
            , ':record_id' => $record->getRecordId()
            , ':case'      => $action
            , ':pusher'    => $pusher->getId()
        ];

        $stmt = $this->app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $this->delete_data_from_cache(self::CACHE_RIGHTS_RECORDS);

        return $this;
    }

    /**
     * Check if a hd grant has been received for a record
     *
     * @param RecordReferenceInterface $record
     * @return bool
     */
    public function has_preview_grant(RecordReferenceInterface $record)
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
        return 0 === (($record->getStatusBitField() ^ $this->get_mask_xor($record->getBaseId())) & $this->get_mask_and($record->getBaseId()));
    }

    public function has_access_to_subdef(RecordInterface $record, $subdef_name)
    {
        if ($subdef_name == 'thumbnail') {
            return true;
        }

        if ($record->isStory()) {
            return true;
        }

        $databox = $this->app->findDataboxById($record->getDataboxId());
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
        } elseif ($subdef_class == databox_subdef::CLASS_PREVIEW && $this->has_right_on_base($record->getBaseId(), self::CANDWNLDPREVIEW)) {
            $granted = true;
        } elseif ($subdef_class == databox_subdef::CLASS_PREVIEW && $this->has_preview_grant($record)) {
            $granted = true;
        } elseif ($subdef_class == databox_subdef::CLASS_DOCUMENT && $this->has_right_on_base($record->getBaseId(), self::CANDWNLDHD)) {
            $granted = true;
        } elseif ($subdef_class == databox_subdef::CLASS_DOCUMENT && $this->has_hd_grant($record)) {
            $granted = true;
        }

        if (false === $granted && $this->app['repo.feed-items']->isRecordInPublicFeed($record->getDataboxId(), $record->getRecordId())) {
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

        $sbas_rights = [
            self::BAS_MANAGE,
            self::BAS_MODIFY_STRUCT,
            self::BAS_MODIF_TH,
            self::BAS_CHUPUB
        ];

        $sbas_to_acces = [];
        $rights_to_give = [];

        foreach ($this->app->getAclForUser($template_user)->get_granted_sbas() as $databox) {
            $sbas_id = $databox->get_sbas_id();

            if (!in_array($sbas_id, $sbas_ids))
                continue;

            if (!$this->has_access_to_sbas($sbas_id)) {
                $sbas_to_acces[] = $sbas_id;
            }

            foreach ($sbas_rights as $right) {
                if ($this->app->getAclForUser($template_user)->has_right_on_sbas($sbas_id, $right)) {
                    $rights_to_give[$sbas_id][$right] = '1';
                }
            }
        }

        $this->give_access_to_sbas($sbas_to_acces);

        foreach ($rights_to_give as $sbas_id => $rights) {
            $this->update_rights_to_sbas($sbas_id, $rights);
        }

        $bas_rights = $this->get_bas_rights();

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

        foreach ($this->app->getAclForUser($template_user)->get_granted_base() as $collection) {
            $base_id = $collection->get_base_id();

            if (!in_array($base_id, $base_ids))
                continue;

            if (!$this->has_access_to_base($base_id)) {
                $bas_to_acces[] = $base_id;
            }

            foreach ($bas_rights as $right) {
                if ($this->app->getAclForUser($template_user)->has_right_on_base($base_id, $right)) {
                    $rights_to_give[$base_id][$right] = '1';
                }
            }

            $mask_and = $this->app->getAclForUser($template_user)->get_mask_and($base_id);
            $mask_xor = $this->app->getAclForUser($template_user)->get_mask_xor($base_id);

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
            $limited = $this->app->getAclForUser($template_user)->get_limits($base_id);
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
     * @param $base_id
     * @param $right
     * @return bool
     * @throws Exception
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
     * @param string|null $option
     * @return string
     */
    public function get_cache_key($option = null)
    {
        return '_ACL_' . $this->user->getId() . ($option ? '_' . $option : '');
    }

    /**
     * @param string|null $option
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

        $this->app->getApplicationBox()->delete_data_from_cache($this->get_cache_key($option));
    }

    /**
     * @param  string|null $option
     * @return array
     */
    public function get_data_from_cache($option = null)
    {
        return $this->app->getApplicationBox()->get_data_from_cache($this->get_cache_key($option));
    }

    /**
     * @param $value
     * @param string|null $option
     * @param int $duration
     * @return bool
     */
    public function set_data_to_cache($value, $option = null, $duration = 0)
    {
        return $this->app->getApplicationBox()->set_data_to_cache($value, $this->get_cache_key($option), $duration);
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

        return $this->_rights_bas[$base_id][self::RESTRICT_DWNLD];
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
     * Check if the user has the right, on at least one collection
     *
     * @param  string $right
     * @return bool
     * @throws Exception
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
     * @param  int $sbas_id
     * @param  string $right
     * @return bool
     * @throws Exception
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
     * @param  int $base_id
     * @return int
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
     * @param  int $base_id
     * @return int
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
            $this->_rights_bas[$base_id][self::ACTIF] === true);
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
     * @return collection[] An array of collection
     */
    public function get_granted_base(Array $rights = [], array $sbas_ids = null)
    {
        $this->load_rights_bas();
        $ret = [];

        foreach ($this->app->getDataboxes() as $databox) {
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

                $ret[$base_id] = $collection;
            }
        }

        return $ret;
    }

    /**
     * Return an array of databox (key=sbas_id) which are granted, with
     * optionnal filter by rights
     *
     * @param  Array $rights
     * @return \databox[]
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
                $ret[$sbas_id] = $this->app->findDataboxById((int) $sbas_id);
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

        $this->app['dispatcher']->dispatch(
            AclEvents::SYSADMIN_CHANGED,
            new SysadminChangedEvent(
                $this,
                array(
                    'is_sysadmin'=>$boolean
                )
            )
        );

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
        $sql = "SELECT sbas_id, record_id, preview, document FROM records_rights WHERE usr_id = :usr_id";

        $stmt = $this->app->getApplicationBox()->get_connection()->prepare($sql);
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
            'preview'  => $this->_rights_records_preview,
            'document' => $this->_rights_records_document
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
            $global_rights = $this->get_data_from_cache(self::CACHE_GLOBAL_RIGHTS);
            if (!is_array($global_rights)) {
                throw new Exception('global rights were not properly retrieved');
            }
            $sbas_rights = $this->get_data_from_cache(self::CACHE_RIGHTS_SBAS);
            if (!is_array($sbas_rights)) {
                throw new Exception('sbas rights were not properly retrieved');
            }

            $this->_global_rights = $global_rights;
            $this->_rights_sbas = $sbas_rights;

            return $this;
        } catch (\Exception $e) {

        }

        $sql = "SELECT sbasusr.* FROM sbasusr INNER JOIN sbas USING(sbas_id) WHERE usr_id= :usr_id";

        $stmt = $this->app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute([':usr_id' => $this->user->getId()]);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $this->_rights_sbas = [];

        $this->_global_rights[self::BAS_MODIF_TH]      = false;
        $this->_global_rights[self::BAS_MODIFY_STRUCT] = false;
        $this->_global_rights[self::BAS_MANAGE]        = false;
        $this->_global_rights[self::BAS_CHUPUB]        = false;

        foreach ($rs as $row) {
            $sbid = $row['sbas_id'];
            $this->_rights_sbas[$sbid] = [];
            $this->_global_rights[self::BAS_MODIF_TH]      |= ($this->_rights_sbas[$sbid][self::BAS_MODIF_TH]      = ($row['bas_modif_th'] == '1'));
            $this->_global_rights[self::BAS_MODIFY_STRUCT] |= ($this->_rights_sbas[$sbid][self::BAS_MODIFY_STRUCT] = ($row['bas_modify_struct'] == '1'));
            $this->_global_rights[self::BAS_MANAGE]        |= ($this->_rights_sbas[$sbid][self::BAS_MANAGE]        = ($row['bas_manage'] == '1'));
            $this->_global_rights[self::BAS_CHUPUB]        |= ($this->_rights_sbas[$sbid][self::BAS_CHUPUB]        = ($row['bas_chupub'] == '1'));
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
            $data = $this->get_data_from_cache(self::CACHE_GLOBAL_RIGHTS);
            if (!is_array($data)) {
                throw new Exception('Unable to retrieve global rights');
            }
            $this->_global_rights = $data;
            $data = $this->get_data_from_cache(self::CACHE_RIGHTS_BAS);
            if (!is_array($data)) {
                throw new Exception('Unable to retrieve base rights');
            }
            $this->_rights_bas = $data;
            $data = $this->get_data_from_cache(self::CACHE_LIMITS_BAS);
            if (!is_array($data)) {
                throw new Exception('Unable to retrieve limits rights');
            }
            $this->_limited = $data;

            return $this;
        }
        catch (\Exception $e) {
            // no-op
        }

        $sql = "SELECT  u.* FROM basusr u, bas b, sbas s\n"
            . " WHERE usr_id= :usr_id\n"
            . " AND b.base_id = u.base_id\n"
            . " AND s.sbas_id = b.sbas_id";

        $stmt = $this->app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute([':usr_id' => $this->user->getId()]);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $this->_rights_bas = $this->_limited = [];

        $this->_global_rights[self::CANADMIN] = false;
        $this->_global_rights[self::COLL_MANAGE] = false;
        $this->_global_rights[self::COLL_MODIFY_STRUCT] = false;
        $this->_global_rights[self::CANCMD] = false;
        $this->_global_rights[self::CANPUSH] = false;
        $this->_global_rights[self::CANADDRECORD] = false;
        $this->_global_rights[self::CANMODIFRECORD] = false;
        $this->_global_rights[self::CHGSTATUS] = false;
        $this->_global_rights[self::IMGTOOLS] = false;
        $this->_global_rights[self::CANDELETERECORD] = false;
        $this->_global_rights[self::CANPUTINALBUM] = false;
        $this->_global_rights[self::CANREPORT] = false;
        $this->_global_rights[self::CANDWNLDPREVIEW] = false;
        $this->_global_rights[self::CANDWNLDHD] = false;
        $this->_global_rights[self::ORDER_MASTER] = false;

        foreach ($rs as $row) {
            $bid = $row['base_id'];
            $this->_rights_bas[$bid]['actif'] = ($row['actif'] == '1');

            $row['limited_from'] = $row['limited_from'] == '0000-00-00 00:00:00' ? '' : trim($row['limited_from']);
            $row['limited_to'] = $row['limited_to'] == '0000-00-00 00:00:00' ? '' : trim($row['limited_to']);

            if ($row['time_limited'] == '1'
                && ($row['limited_from'] !== '' || $row['limited_to'] !== '')) {
                $this->_limited[$row['base_id']] = [
                    'dmin' => $row['limited_from'] ? new DateTime($row['limited_from']) : null,
                    'dmax' => $row['limited_to'] ? new DateTime($row['limited_to']) : null
                ];
            }

            $this->_global_rights[self::IMGTOOLS]           |= ($this->_rights_bas[$bid][self::IMGTOOLS]           = ($row['imgtools'] == '1'));
            $this->_global_rights[self::CHGSTATUS]          |= ($this->_rights_bas[$bid][self::CHGSTATUS]          = ($row['chgstatus'] == '1'));
            $this->_global_rights[self::CANCMD]             |= ($this->_rights_bas[$bid][self::CANCMD]             = ($row['cancmd'] == '1'));
            $this->_global_rights[self::CANADDRECORD]       |= ($this->_rights_bas[$bid][self::CANADDRECORD]       = ($row['canaddrecord'] == '1'));
            $this->_global_rights[self::CANPUSH]            |= ($this->_rights_bas[$bid][self::CANPUSH]            = ($row['canpush'] == '1'));
            $this->_global_rights[self::CANDELETERECORD]    |= ($this->_rights_bas[$bid][self::CANDELETERECORD]    = ($row['candeleterecord'] == '1'));
            $this->_global_rights[self::CANADMIN]           |= ($this->_rights_bas[$bid][self::CANADMIN]           = ($row['canadmin'] == '1'));
            $this->_global_rights[self::CANDWNLDPREVIEW]    |= ($this->_rights_bas[$bid][self::CANDWNLDPREVIEW]    = ($row['candwnldpreview'] == '1'));
            $this->_global_rights[self::CANDWNLDHD]         |= ($this->_rights_bas[$bid][self::CANDWNLDHD]         = ($row['candwnldhd'] == '1'));
            $this->_global_rights[self::CANMODIFRECORD]     |= ($this->_rights_bas[$bid][self::CANMODIFRECORD]     = ($row['canmodifrecord'] == '1'));
            $this->_global_rights[self::CANPUTINALBUM]      |= ($this->_rights_bas[$bid][self::CANPUTINALBUM]      = ($row['canputinalbum'] == '1'));
            $this->_global_rights[self::CANREPORT]          |= ($this->_rights_bas[$bid][self::CANREPORT]          = ($row['canreport'] == '1'));
            $this->_global_rights[self::COLL_MODIFY_STRUCT] |= ($this->_rights_bas[$bid][self::COLL_MODIFY_STRUCT] = ($row['modify_struct'] == '1'));
            $this->_global_rights[self::COLL_MANAGE]        |= ($this->_rights_bas[$bid][self::COLL_MANAGE]        = ($row['manage'] == '1'));
            $this->_global_rights[self::ORDER_MASTER]       |= ($this->_rights_bas[$bid][self::ORDER_MASTER]       = ($row['order_master'] == '1'));

            $this->_rights_bas[$bid][self::NOWATERMARK]    = ($row['nowatermark'] == '1');
            $this->_rights_bas[$bid][self::RESTRICT_DWNLD] = ($row['restrict_dwnld'] == '1');
            $this->_rights_bas[$bid]['remain_dwnld']   = (int) $row['remain_dwnld'];
            $this->_rights_bas[$bid]['mask_and']       = (int) $row['mask_and'];
            $this->_rights_bas[$bid]['mask_xor']       = (int) $row['mask_xor'];

            $row['limited_from'] = $row['limited_from'] == '0000-00-00 00:00:00' ? '' : trim($row['limited_from']);
            $row['limited_to']   = $row['limited_to']   == '0000-00-00 00:00:00' ? '' : trim($row['limited_to']);

            if ($row['time_limited'] == '1' && ($row['limited_from'] !== '' || $row['limited_to'] !== '')) {
                $this->_limited[$bid] = [
                    'dmin' => $row['limited_from'] ? new DateTime($row['limited_from']) : null,
                    'dmax' => $row['limited_to'] ? new DateTime($row['limited_to']) : null
                ];
            }
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
        $this->_global_rights[self::TASKMANAGER] = $this->is_admin();

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
                    ($this->has_right(self::BAS_MODIFY_STRUCT) ||
                    $this->has_right(self::COLL_MODIFY_STRUCT) ||
                    $this->has_right(self::BAS_MANAGE) ||
                    $this->has_right(self::COLL_MANAGE) ||
                    $this->has_right(self::CANADMIN) ||
                    $this->is_admin()) );
                break;
            case 'thesaurus':
                return ($this->has_right(self::BAS_MODIF_TH) === true );
                break;
            case 'upload':
                return ($this->has_right(self::CANADDRECORD) === true);
                break;
            case 'report':
                return ($this->has_right(self::CANREPORT) === true);
                break;
            default:
                break;
        }

        return true;
    }

    /**
     * @param array $base_ids
     * @return $this
     * @throws DBALException
     * @throws Exception
     */
    public function revoke_access_from_bases(Array $base_ids)
    {
        $sql_del = 'DELETE FROM basusr WHERE base_id = :base_id AND usr_id = :usr_id';
        $stmt_del = $this->app->getApplicationBox()->get_connection()->prepare($sql_del);

        $usr_id = $this->user->getId();

        foreach ($base_ids as $base_id) {
            if (!$stmt_del->execute([':base_id' => $base_id, ':usr_id'  => $usr_id])) {
                throw new Exception('Error while deleteing some rights');
            }

            $this->app['dispatcher']->dispatch(
                AclEvents::ACCESS_TO_BASE_REVOKED,
                new AccessToBaseRevokedEvent(
                    $this,
                    array(
                        'base_id'=>$base_id
                    )
                )
            );
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
        $this->load_rights_bas();

        $sql_i = "INSERT INTO basusr (base_id, usr_id, actif) VALUES (:base_id, :usr_id, '1')";
        $sql_u = "UPDATE basusr SET UPDATE actif='1' WHERE base_id = :base_id AND usr_id = :usr_id";
        $stmt_i = $this->app->getApplicationBox()->get_connection()->prepare($sql_i);
        $stmt_u = $this->app->getApplicationBox()->get_connection()->prepare($sql_u);

        $usr_id = $this->user->getId();
        foreach ($base_ids as $base_id) {
            if (!isset($this->_rights_bas[$base_id]) || $this->_rights_bas[$base_id][self::ACTIF] === false) {
                try {
                    $stmt_i->execute([':base_id' => $base_id, ':usr_id' => $usr_id]);
                    if($stmt_i->rowCount() > 0) {
                        $this->app['dispatcher']->dispatch(
                            AclEvents::ACCESS_TO_BASE_GRANTED,
                            new AccessToBaseGrantedEvent(
                                $this,
                                array(
                                    'base_id'=>$base_id
                                )
                            )
                        );
                    }
                    else {
                        $stmt_u->execute([':base_id' => $base_id, ':usr_id' => $usr_id]);
                    }
                }
                catch(\Exception $e) {
                    // no-opp
                }
            }
        }
        $stmt_u->closeCursor();
        $stmt_i->closeCursor();

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
        $stmt_ins = $this->app->getApplicationBox()->get_connection()->prepare($sql_ins);

        $usr_id = $this->user->getId();

        foreach ($sbas_ids as $sbas_id) {
            if (!$this->has_access_to_sbas($sbas_id)) {
                try {
                    $stmt_ins->execute([':sbas_id' => $sbas_id, ':usr_id'  => $usr_id]);

                    $this->app['dispatcher']->dispatch(
                        AclEvents::ACCESS_TO_SBAS_GRANTED,
                        new AccessToSbasGrantedEvent(
                            $this,
                            array(
                                'sbas_id'=>$sbas_id
                            )
                        )
                    );
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

        $stmt_up = $this->app->getApplicationBox()->get_connection()->prepare($sql_up);
        $stmt_up->execute($params);
        $stmt_up->closeCursor();

        $this->delete_data_from_cache(self::CACHE_RIGHTS_BAS);

        $this->app['dispatcher']->dispatch(
            AclEvents::RIGHTS_TO_BASE_CHANGED,
            new RightsToBaseChangedEvent(
                $this,
                array(
                    'base_id'=>$base_id,
                    'rights'=>$rights
                )
            )
        );

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

        $stmt = $this->app->getApplicationBox()->get_connection()->prepare($sql);
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

        foreach ($rights as $right => $v) {
            $sql_args[] = "`" . $right . "`=" . ($v ? '1' : '0');
        }

        if (count($sql_args) == 0) {
            return $this;
        }

        $sql_up .= implode(', ', $sql_args) . "\n"
            . " WHERE sbas_id = :sbas_id AND usr_id = :usr_id";

        $stmt_up = $this->app->getApplicationBox()->get_connection()->prepare($sql_up);

        if (!$stmt_up->execute([':sbas_id' => $sbas_id, ':usr_id'  => $usr_id])) {
            throw new Exception('Error while updating some rights');
        }
        $stmt_up->closeCursor();
        $this->delete_data_from_cache(self::CACHE_RIGHTS_SBAS);

        $this->app['dispatcher']->dispatch(
            AclEvents::RIGHTS_TO_SBAS_CHANGED,
            new RightsToSbasChangedEvent(
                $this,
                array(
                    'sbas_id'=>$sbas_id,
                    'rights'=>$rights
                )
            )
        );

        return $this;
    }

    /**
     *
     * @param  <type> $base_id
     * @return ACL
     */
    public function remove_quotas_on_base($base_id)
    {
        $sql = "UPDATE basusr SET remain_dwnld = 0, restrict_dwnld = 0, month_dwnld_max = 0\n"
            . " WHERE usr_id = :usr_id AND base_id = :base_id";

        $stmt = $this->app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute([':usr_id'  => $this->user->getId(), ':base_id' => $base_id]);
        $stmt->closeCursor();

        unset($stmt);
        $this->delete_data_from_cache(self::CACHE_RIGHTS_BAS);

        $this->app['dispatcher']->dispatch(
            AclEvents::DOWNLOAD_QUOTAS_ON_BASE_REMOVED,
            new DownloadQuotasOnBaseRemovedEvent(
                $this,
                array(
                    'base_id'=>$base_id
                )
            )
        );

        return $this;
    }

    public function update_download_restrictions()
    {
        $sql = "UPDATE basusr SET remain_dwnld = month_dwnld_max\n"
            . " WHERE actif = 1"
            . " AND usr_id = :usr_id"
            . " AND MONTH(lastconn) != MONTH(NOW()) AND restrict_dwnld = 1";
        $stmt = $this->app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute([':usr_id' => $this->user->getId()]);
        $stmt->closeCursor();

        $sql = "UPDATE basusr SET lastconn=NOW() WHERE usr_id = :usr_id AND actif = 1";
        $stmt = $this->app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute([':usr_id' => $this->user->getId()]);
        $stmt->closeCursor();

        unset($stmt);
        $this->delete_data_from_cache(self::CACHE_RIGHTS_BAS);

        $this->app['dispatcher']->dispatch(
            AclEvents::DOWNLOAD_QUOTAS_RESET,
            new DownloadQuotasResetEvent(
                $this
            )
        );

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
        $sql = "UPDATE basusr SET remain_dwnld = :restes, restrict_dwnld = 1, month_dwnld_max = :droits\n"
            . " WHERE usr_id = :usr_id AND base_id = :base_id";

        $params = [
            ':usr_id'  => $this->user->getId(),
            ':base_id' => $base_id,
            ':restes'  => $restes,
            ':droits'  => $droits
        ];

        $stmt = $this->app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        unset($stmt);
        $this->delete_data_from_cache(self::CACHE_RIGHTS_BAS);

        $this->app['dispatcher']->dispatch(
            AclEvents::DOWNLOAD_QUOTAS_ON_BASE_CHANGED,
            new DownloadQuotasOnBaseChangedEvent(
                $this,
                array(
                    'base_id'=>$base_id,
                    'remain_dwnld'=>$restes,
                    'month_dwnld_max'=>$droits
                )
            )
        );

        return $this;
    }

    public function duplicate_right_from_bas($base_id_from, $base_id_dest)
    {
        $sql = "SELECT * FROM basusr WHERE base_id = :base_from AND usr_id = :usr_id";

        $params = [
            ':base_from' => $base_id_from,
            ':usr_id'    => $this->user->getId()
        ];

        $stmt = $this->app->getApplicationBox()->get_connection()->prepare($sql);
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

        $rights[self::CANPUTINALBUM]      = ($row['canputinalbum'] == '1');
        $rights[self::CANDWNLDHD]         = ($row['candwnldhd'] == '1');
        $rights[self::CANDWNLDPREVIEW]    = ($row['candwnldpreview'] == '1');
        $rights[self::CANCMD]             = ($row['cancmd'] == '1');
        $rights[self::CANADMIN]           = ($row['canadmin'] == '1');
        $rights[self::CANREPORT]          = ($row['canreport'] == '1');
        $rights[self::CANPUSH]            = ($row['canpush'] == '1');
        $rights[self::NOWATERMARK]        = ($row['nowatermark'] == '1');
        $rights[self::CANADDRECORD]       = ($row['canaddrecord'] == '1');
        $rights[self::CANMODIFRECORD]     = ($row['canmodifrecord' == '1']);
        $rights[self::CANDELETERECORD]    = ($row['candeleterecord'] == '1');
        $rights[self::CHGSTATUS]          = ($row['chgstatus'] == '1');
        $rights[self::IMGTOOLS]           = ($row['imgtools'] == '1');
        $rights[self::COLL_MANAGE]        = ($row['manage'] == '1');
        $rights[self::COLL_MODIFY_STRUCT] = ($row['modify_struct'] == '1');

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

        $stmt = $this->app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute([':base_id' => $base_id, ':usr_id'  => $this->user->getId()]);
        $stmt->closeCursor();

        unset($stmt);

        $this->delete_data_from_cache(self::CACHE_RIGHTS_BAS);

        $this->app['dispatcher']->dispatch(
            AclEvents::MASKS_ON_BASE_CHANGED,
            new MasksOnBaseChangedEvent(
                $this,
                array(
                    'base_id'=>$base_id
                )
            )
        );

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

    /**
     * returns date limits ['dmin'=>x, 'dmax'=>y] with x,y : NullableDateTime
     *
     *
     * @param $base_id
     * @return array|null
     */
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
            ':usr_id' => $this->user->getId(),
            ':base_id' => $base_id,
            'limited_from' => NullableDateTime::format($limit_from, DATE_ISO8601),
            'limited_to' => NullableDateTime::format($limit_to, DATE_ISO8601),
        ];

        $stmt = $this->app->getApplicationBox()->get_connection()->prepare($sql);

        $stmt->execute($params);

        $stmt->closeCursor();

        $this->delete_data_from_cache(self::CACHE_LIMITS_BAS);

        $this->app['dispatcher']->dispatch(
            AclEvents::ACCESS_PERIOD_CHANGED,
            new AccessPeriodChangedEvent(
                $this,
                array(
                    'base_id'=>$base_id
                )
            )
        );

        return $this;
    }

    public function can_see_business_fields(\databox $databox)
    {
        // a user can see the business fields if he has at least the right on one collection to edit a record
        foreach($databox->get_collections() as $collection) {
            if ($this->has_access_to_base($collection->get_base_id()) && $this->has_right_on_base($collection->get_base_id(), self::CANMODIFRECORD)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns base ids on which user is 'order master'
     *
     * @return array
     */
    public function getOrderMasterCollectionsBaseIds()
    {
        $sql = "SELECT base_id FROM basusr WHERE order_master='1' AND usr_id= :usr_id";
        $result = $this->app->getApplicationBox()
            ->get_connection()
            ->executeQuery($sql, [':usr_id' => $this->user->getId()])
            ->fetchAll(\PDO::FETCH_ASSOC);


        $baseIds = [];

        foreach ($result as $item) {
            $baseIds[] = $item['base_id'];
        }

        return $baseIds;
    }

    /**
     * Returns an array of collections on which the user is 'order master'
     *
     * @return collection[]
     */
    public function get_order_master_collections()
    {
        $baseIds = $this->getOrderMasterCollectionsBaseIds();

        $collectionReferences = $this->app['repo.collection-references']->findHavingOrderMaster($baseIds);
        $groups = new CollectionReferenceCollection($collectionReferences);

        $collections = [];

        foreach ($groups->groupByDataboxIdAndCollectionId() as $databoxId => $group) {
            foreach ($group as $collectionId => $index) {
                $collections[$index] = \collection::getByCollectionId($this->app, $databoxId, $collectionId);
            }
        }

        ksort($collections);

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
        $sql = "UPDATE basusr SET order_master = :master WHERE usr_id = :usr_id AND base_id = :base_id";

        $stmt = $this->app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute([
            ':master'    => $bool ? 1 : 0,
            ':usr_id'    => $this->user->getId(),
            ':base_id'   => $collection->get_base_id()
        ]);
        $stmt->closeCursor();

        return $this;
    }
}
