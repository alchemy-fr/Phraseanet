<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package     Basket
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class basket_element_adapter implements cache_cacheableInterface
{

  /**
   *
   * @var Array
   */
  protected $choices;

  /**
   *
   * @var boolean
   */
  protected $is_validation_item = false;

  /**
   *
   * @var int
   */
  protected $validate_id;

  /**
   *
   * @var int
   */
  protected $my_agreement;

  /**
   *
   * @var string
   */
  protected $my_note;

  /**
   *
   * @var int
   */
  protected $avrAgree;

  /**
   *
   * @var int
   */
  protected $avrDisAgree;

  /**
   *
   * @var int
   */
  protected $sselcont_id;

  /**
   *
   * @var int
   */
  protected $parent_record_id;

  /**
   *
   * @var int
   */
  protected $ssel_id;

  /**
   *
   * @var record_adapter
   */
  protected $record;
  protected $usr_id;

  /**
   *
   * @var array
   */
  protected static $_instance = array();

  public function __construct($sselcont_id)
  {
    $appbox = appbox::get_instance();
    $session = $appbox->get_session();
    $this->usr_id = $session->get_usr_id();
    $this->sselcont_id = (int) $sselcont_id;

    $this->load();

    return $this;
  }

  protected function load()
  {

    try
    {
      $datas = $this->get_data_from_cache();

      $this->ssel_id = $datas['ssel_id'];
      $this->order = $datas['order'];
      $this->record = new record_adapter($datas['sbas_id'], $datas['record_id'], $this->order);
      $this->avrDisAgree = $datas['avrDisAgree'];
      $this->avrAgree = $datas['avrAgree'];
      $this->is_validation_item = $datas['is_validation_item'];
      $this->my_agreement = $datas['my_agreement'];
      $this->my_note = $datas['my_note'];
      $this->validate_id = $datas['validate_id'];
      $this->choices = $datas['choices'];
      $this->avrAgree = $datas['avrAgree'];
      $this->avrDisAgree = $datas['avrDisAgree'];

      return $this;
    }
    catch (Exception $e)
    {

    }

    $sql = 'SELECT s.usr_id as owner, v.id as validate_id, v.can_see_others,
                  c.base_id, c.record_id, c.ord, c.ssel_id, v.usr_id,
                  d.agreement, d.note, d.updated_on
              FROM (sselcont c, ssel s)
              LEFT JOIN (validate v, validate_datas d)
                  ON (d.sselcont_id = c.sselcont_id AND d.validate_id = v.id )
              WHERE s.ssel_id = c.ssel_id
              AND c.sselcont_id = :sselcont_id';

    try
    {
      $conn = connection::getPDOConnection();
      $stmt = $conn->prepare($sql);
      $stmt->execute(array(':sselcont_id' => $this->sselcont_id));
      $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $stmt->closeCursor();
    }
    catch (Exception $e)
    {

    }

    $first = true;


    foreach ($rs as $row)
    {
      if ($row['validate_id'])
      {
        $this->is_validation_item = true;

        if ($row['owner'] == $this->usr_id)
          $see_others = true;
        else
          $see_others = ($row['can_see_others'] == '1');

        if (!$see_others)
        {
          if ($row['usr_id'] != $this->usr_id)
            continue;
        }
      }

      if ($first)
      {
        $sbas_id = (int) phrasea::sbasFromBas($row['base_id']);
        $record_id = (int) $row['record_id'];
        $this->ssel_id = (int) $row['ssel_id'];
        $this->order = $number = (int) $row['ord'];

        $this->record = new record_adapter($sbas_id, $record_id, $number);

        if ($this->is_validation_item)
        {
          $this->choices = array();
          $this->avrAgree = 0;
          $this->avrDisAgree = 0;
        }

        $first = false;
      }

      if ($this->is_validation_item)
      {
        if ($row['usr_id'] == $this->usr_id)
        {
          $this->my_agreement = (int) $row['agreement'];
          $this->my_note = $row['note'];
          $this->validate_id = (int) $row['validate_id'];
        }
        $this->choices[$row['usr_id']] = array(
            'usr_id' => $row['usr_id'],
            'usr_name' => User_Adapter::getInstance($row['usr_id'], appbox::get_instance())->get_display_name(),
            'is_mine' => ($row['usr_id'] == $this->usr_id),
            'agreement' => $row['agreement'],
            'updated_on' => $row['updated_on'],
            'note' => $row['note']
        );
        $this->avrAgree += $row["agreement"] > 0 ? 1 : 0;
        $this->avrDisAgree += $row["agreement"] < 0 ? 1 : 0;
      }
    }

    $datas = array(
        'ssel_id' => $this->ssel_id
        , 'sbas_id' => (int) $sbas_id
        , 'record_id' => $record_id
        , 'order' => $this->order
        , 'is_validation_item' => $this->is_validation_item
        , 'my_agreement' => $this->my_agreement
        , 'my_note' => $this->my_note
        , 'validate_id' => $this->validate_id
        , 'choices' => $this->choices
        , 'avrAgree' => $this->avrAgree
        , 'avrDisAgree' => $this->avrDisAgree
    );

    $this->set_data_to_cache($datas);

    return $this;
  }

  public function get_record()
  {
    return $this->record;
  }

  /**
   *
   * @return int
   */
  public function get_order()
  {
    return $this->order;
  }

  /**
   *
   * @param int $number
   * @return basket_element_adapter
   */
  public function set_order($order)
  {
    $this->order = (int) $order;

    return $this;
  }

  /**
   * @return basket_element_adapter
   */
  public static function getInstance($sselcont_id)
  {
    if (!isset(self::$_instance[$sselcont_id]))
    {
      self::$_instance[$sselcont_id] = new self($sselcont_id);
    }

    return array_key_exists($sselcont_id, self::$_instance) ? self::$_instance[$sselcont_id] : false;
  }

  /**
   *
   * @param basket_adapter $basket
   * @param int $base_id
   * @param int $record_id
   * @param int $parent_record_id
   * @param string $adjust_validation_datas
   * @param boolean $fixing
   * @return basket_element_adapter
   */
  public static function create(basket_adapter $basket, $base_id, $record_id, $parent_record_id, $adjust_validation_datas, $fixing)
  {
    $appbox = appbox::get_instance();
    $session = $appbox->get_session();
    $user = User_Adapter::getInstance($session->get_usr_id(), $appbox);
    $sbas_id = phrasea::sbasFromBas($base_id);
    $record = new record_adapter($sbas_id, $record_id);

    $ssel_id = $basket->get_ssel_id();

    if (!$user->ACL()->has_right_on_base($base_id, 'canputinalbum'))
      throw new Exception('You do not have rights' .
              ' to use this document in basket.');

    $exists = false;

    $sql = 'SELECT sselcont_id FROM sselcont
            WHERE ssel_id = :ssel_id AND base_id = :base_id AND record_id = :record_id ';
    $stmt = $appbox->get_connection()->prepare($sql);
    $params = array(
        ':ssel_id' => $basket->get_ssel_id()
        , ':base_id' => $base_id
        , ':record_id' => $record_id
    );
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if ($row)
    {
      return new self($row['sselcont_id']);
    }

    $connbas = connection::getPDOConnection($sbas_id);

    if (!$sbas_id)
      throw new Exception('Unknown database');

    if ($parent_record_id && $fixing === false)
    {
      if (!$user->ACL()->has_right_on_base($base_id, 'canaddrecord'))
        throw new Exception('You do not have the right');

      if ($record->is_grouping())
        throw new Exception('Can\'t add grouping to grouping');

      $ord = 0;
      $sql = "SELECT (max(ord)+1) as ord
              FROM regroup WHERE rid_parent = :parent_record_id";

      $stmt = $connbas->prepare($sql);
      $stmt->execute(array(':parent_record_id' => $parent_record_id));
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      $stmt->closeCursor();

      if ($row)
      {
        $ord = is_null($row["ord"]) ? 0 : $row["ord"];
      }
      else
      {
        $ord = 0;
      }

      $sql = 'INSERT INTO regroup (id, rid_parent, rid_child, dateadd, ord)
              VALUES (null, :parent_record_id, :record_id, NOW(), :ord)';

      $params = array(
          ':parent_record_id' => $parent_record_id
          , ':record_id' => $record_id
          , ':ord' => $ord
      );

      $stmt = $connbas->prepare($sql);
      $stmt->execute($params);
      $stmt->closeCursor();

      $sql = 'UPDATE record SET moddate = NOW() WHERE record_id = :parent_record_id';
      $stmt = $connbas->prepare($sql);
      $stmt->execute(array(':parent_record_id' => $parent_record_id));
      $stmt->closeCursor();
    }

    $sql = 'SELECT max(ord) as ord FROM sselcont WHERE ssel_id = :ssel_id';
    $stmt = $appbox->get_connection()->prepare($sql);
    $stmt->execute(array(':ssel_id' => $basket->get_ssel_id()));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if ($row)
    {
      $ord = (int) $row['ord'] + 1;
    }
    else
    {
      $ord = 0;
    }

    $sql = ' INSERT INTO sselcont
                (sselcont_id, ssel_id, base_id, record_id, ord)
                VALUES (null, :ssel_id, :base_id, :record_id, :ord) ';

    $stmt = $appbox->get_connection()->prepare($sql);
    $params = array(
        ':ssel_id' => $basket->get_ssel_id()
        , ':base_id' => $base_id
        , ':record_id' => $record_id
        , ':ord' => $ord
    );
    $stmt->execute($params);
    $stmt->closeCursor();

    $sselcont_id = $appbox->get_connection()->lastInsertId();

    $ret['error'] = false;
    $ret['datas'][] = $sselcont_id;

    $sql = 'UPDATE ssel SET updater=NOW() WHERE ssel_id = :ssel_id';
    $stmt = $appbox->get_connection()->prepare($sql);
    $stmt->execute(array(':ssel_id' => $basket->get_ssel_id()));
    $stmt->closeCursor();


    if ($adjust_validation_datas == 'myvalid')
    {
      $sql = 'INSERT INTO validate_datas
                (SELECT distinct null as id, id as validate_id
                  , :sselcont_id as sselcont_id
                  , null as updated_on, 0 as agreement, "" as note
                  FROM validate
                  WHERE ssel_id = :ssel_id)';

      $stmt = $appbox->get_connection()->prepare($sql);
      $stmt->execute(array(':sselcont_id' => $sselcont_id, ':ssel_id' => $basket->get_ssel_id()));
      $stmt->closeCursor();

      $sql = 'SELECT usr_id FROM validate WHERE ssel_id = :ssel_id';
      $stmt = $appbox->get_connection()->prepare($sql);
      $stmt->execute(array(':ssel_id' => $basket->get_ssel_id()));
      $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $stmt->closeCursor();

      foreach ($rs as $row)
      {
        if ($session->get_usr_id() != $row['usr_id'])
        {
          $basket->set_unread($row['usr_id']);
        }
      }
    }

    if ($parent_record_id)
    {
      $sql = 'SELECT null as id, ssel_id, usr_id
        FROM ssel
        WHERE usr_id != :usr_id AND rid = :parent_record_id
          AND sbas_id = :sbas_id  AND temporaryType="1"';

      $stmt = $appbox->get_connection()->prepare($sql);
      $params = array(
          ':usr_id' => $session->get_usr_id()
          , ':parent_record_id' => $parent_record_id
          , ':sbas_id' => $sbas_id
      );
      $stmt->execute($params);
      $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $stmt->closeCursor();

      foreach ($rs as $row)
      {
        $sql = 'SELECT max(ord) as ord FROM sselcont WHERE ssel_id = :ssel_id';

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':ssel_id' => $row['ssel_id']));
        $row2 = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($row)
        {
          $ord = (int) $row2['ord'] + 1;
        }
        else
        {
          $ord = 0;
        }

        $sqlUp = ' INSERT INTO sselcont
                      (sselcont_id, ssel_id, base_id, record_id,ord)
                      VALUES (null, :ssel_id, :base_id, :record_id, :ord) ';

        $stmt = $appbox->get_connection()->prepare($sql);
        $params = array(
            ':ssel_id' => $row['ssel_id']
            , ':base_id' => $base_id
            , ':record_id' => $record_id
            , ':ord' => $ord
        );
        $stmt->execute($params);
        $stmt->closeCursor();

        $sql = 'UPDATE ssel SET updater=NOW() WHERE ssel_id = :ssel_id';
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':ssel_id' => $row['ssel_id']));
        $stmt->closeCursor();

        try
        {
          $basket_to_clean = basket_adapter::getInstance($appbox, $row['ssel_id'], $user->get_id());
          $basket_to_clean->set_unread($row['usr_id']);
        }
        catch (Exception $e)
        {

        }
      }
    }

    $basket->delete_cache();

    return new self($sselcont_id);
  }

  /**
   *
   * @param string $note
   * @return boolean
   */
  function set_note($note)
  {
    if (!$this->is_validation_item)
    {
      throw new Exception('Element ' . $this->sselcont_id . ' is not a validation item');
    }

    $note = strip_tags($note);

    if (!$this->validate_id)

      return false;

    $appbox = appbox::get_instance();
    $usr_id = $appbox->get_session()->get_usr_id();

    $sql = 'UPDATE validate_datas SET note = :note
          WHERE sselcont_id = :sselcont_id AND validate_id = :validate_id ';
    $stmt = $appbox->get_connection()->prepare($sql);

    $params = array(
        ':note' => $note
        , ':sselcont_id' => $this->sselcont_id
        , ':validate_id' => $this->validate_id
    );

    $stmt->execute($params);
    $stmt->closeCursor();

    $this->my_note = $note;
    foreach ($this->choices as $key => $values)
    {
      if ($values['is_mine'])
      {
        $this->choices[$key]['note'] = $note;
        break;
      }
    }

    $this->delete_data_from_cache();

    $sql = 'SELECT distinct v.usr_id FROM ssel s, validate v
      WHERE v.ssel_id = s.ssel_id AND s.ssel_id = :ssel_id';

    $stmt = $appbox->get_connection()->prepare($sql);
    $stmt->execute(array(':ssel_id' => $this->get_ssel_id()));
    $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    foreach($rs as $row)
    {
      $appbox->delete_data_from_cache('basket_element_' . $row['usr_id'] . '_' . $this->sselcont_id);
    }

    try
    {
      $basket = basket_adapter::getInstance($appbox, $this->ssel_id, $usr_id);
      $basket->delete_cache();
    }
    catch (Exception $e)
    {

    }

    return $this;
  }

  /**
   *
   * @return void
   */
  function load_users_infos()
  {
    if (!$this->is_validation_item)
    {
      throw new Exception('Element is not a validation item');

      return false;
    }

    foreach ($this->choices as $key => $value)
    {
      $this->choices[$key]['usr_display'] = User_Adapter::getInstance($value['usr_id'], appbox::get_instance())->get_display_name();
    }
  }

  /**
   *
   * @return int
   */
  function get_note_count()
  {
    if (!$this->is_validation_item)
    {
      throw new Exception('Element is not a validation item');

      return false;
    }

    $n = 0;
    foreach ($this->choices as $key => $value)
    {
      if (trim($value['note']) != '')
        $n++;
    }

    return $n;
  }

  /**
   *
   * @param boolean $boolean
   * @return string
   */
  function set_agreement($boolean)
  {

    if (!$this->is_validation_item)
    {
      throw new Exception('not a validation item');
    }

    if (!$this->validate_id)
      throw new Exception('not a validation item');

    $appbox = appbox::get_instance();
    $usr_id = $appbox->get_session()->get_usr_id();

    $boolean = in_array($boolean, array('1', '-1')) ? $boolean : '0';

    $sql = 'UPDATE validate_datas
          SET agreement = :agreement
          WHERE sselcont_id = :sselcont_id
          AND validate_id = :validate_id';

    $params = array(
        ':agreement' => $boolean
        , ':sselcont_id' => $this->sselcont_id
        , ':validate_id' => $this->validate_id
    );

    $stmt = $appbox->get_connection()->prepare($sql);
    $stmt->execute($params);
    $stmt->closeCursor();

    $this->delete_data_from_cache();

    $sql = 'SELECT distinct v.usr_id FROM ssel s, validate v
      WHERE v.ssel_id = s.ssel_id AND s.ssel_id = :ssel_id';

    $stmt = $appbox->get_connection()->prepare($sql);
    $stmt->execute(array(':ssel_id' => $this->get_ssel_id()));
    $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    foreach($rs as $row)
    {
      $appbox->delete_data_from_cache('basket_element_' . $row['usr_id'] . '_' . $this->sselcont_id);
    }

    $basket = basket_adapter::getInstance($appbox, $this->ssel_id, $usr_id);
    $basket->delete_cache();

    return $this;
  }

  /**
   *
   * @return int
   */
  public function get_sselcont_id()
  {
    return $this->sselcont_id;
  }

  /**
   *
   * @return boolean
   */
  public function is_validation_item()
  {
    return $this->is_validation_item;
  }

  /**
   *
   * @return int
   */
  public function get_my_agreement()
  {
    return $this->my_agreement;
  }

  /**
   *
   * @return string
   */
  public function get_my_note()
  {
    return $this->my_note;
  }

  /**
   *
   * @return Array
   */
  public function get_choices()
  {
    return $this->choices;
  }

  /**
   *
   * @return int
   */
  public function get_ssel_id()
  {
    return $this->ssel_id;
  }

  public static function is_in_validation_session(record_Interface $record, User_Interface $user)
  {
    $conn = connection::getPDOConnection();
    $sql = 'SELECT v.id FROM sselcont c, validate v
                WHERE c.base_id = :base_id AND c.record_id = :record_id
                    AND v.usr_id = :usr_id AND c.ssel_id = v.ssel_id';

    $params = array(
        ':base_id' => $record->get_base_id()
        , ':record_id' => $record->get_record_id()
        , ':usr_id' => $user->get_id()
    );

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    return!!$row;
  }

  public static function has_been_received(record_Interface $record, User_Interface $user)
  {
    $conn = connection::getPDOConnection();
    $sql = 'SELECT sselcont_id FROM sselcont c, ssel s
            WHERE c.ssel_id=s.ssel_id AND c.record_id = :record_id
              AND c.base_id = :base_id AND s.pushFrom > 0
              AND s.usr_id = :usr_id';

    $params = array(
        ':base_id' => $record->get_base_id()
        , ':record_id' => $record->get_record_id()
        , ':usr_id' => $user->get_id()
    );

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    return!!$row;
  }

  public function get_avrAgree()
  {
    return $this->avrAgree;
  }

  public function get_avrDisAgree()
  {
    return $this->avrDisAgree;
  }

  public function validate(user_adapter $from_user, User_Adapter $to_user, $validate_id, $can_hd)
  {
    $appbox = appbox::get_instance();

    if ($can_hd)
      $to_user->ACL()->grant_hd_on($this->get_record(), $from_user, 'validate');
    else
      $to_user->ACL()->grant_preview_on($this->get_record(), $from_user, 'validate');

    $sql = 'REPLACE INTO validate_datas
            (id, validate_id, sselcont_id, updated_on, agreement)
            VALUES
            (null, :validate_id, :sselcont_id, null, 0)';
    $stmt = $appbox->get_connection()->prepare($sql);

    $params = array(
        ':validate_id' => $validate_id
        , ':sselcont_id' => $this->get_sselcont_id()
    );
    $stmt->execute($params);

    $stmt->closeCursor();

    if (!$this->is_validation_item)
    {
      $this->choices = array();
      $this->avrAgree = 0;
      $this->avrDisAgree = 0;
    }

    $this->is_validation_item = true;
    $this->choices[$to_user->get_id()] = array(
        'usr_id' => $to_user->get_id(),
        'usr_name' => $to_user->get_display_name(),
        'is_mine' => ($to_user->get_id() == $this->usr_id),
        'agreement' => 0,
        'updated_on' => new DateTime(),
        'note' => ''
    );
    if ($to_user->get_id() == $this->usr_id)
    {
      $this->validate_id = (int) $validate_id;
    }

    $this->delete_data_from_cache();

    return $this;
  }

  public function get_cache_key($option = null)
  {
    return 'basket_element_' . $this->usr_id . '_' . $this->sselcont_id . ($option ? '_' . $option : '');
  }

  public function get_data_from_cache($option = null)
  {
    $appbox = appbox::get_instance();

    return $appbox->get_data_from_cache($this->get_cache_key($option));
  }

  public function set_data_to_cache($value, $option = null, $duration = 0)
  {
    $appbox = appbox::get_instance();

    return $appbox->set_data_to_cache($value, $this->get_cache_key($option), $duration);
  }

  public function delete_data_from_cache($option = null)
  {
    $appbox = appbox::get_instance();

    return $appbox->delete_data_from_cache($this->get_cache_key($option));
  }

}
