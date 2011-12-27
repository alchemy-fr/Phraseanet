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
class basket_adapter implements cache_cacheableInterface
{

  /**
   * The name of the basket
   *
   * @var string
   */
  protected $name = false;
  /**
   *
   * @var string
   */
  protected $desc = false;
  /**
   *
   * @var DateTime
   */
  protected $created_on;
  /**
   *
   * @var DateTime
   */
  protected $updated_on;
  /**
   *
   * @var User_Adapter
   */
  protected $pusher;
  /**
   *
   * @var boolean
   */
  protected $noview = false;
  /**
   *
   * @var string
   */
  protected $instance_key;
  /**
   *
   * @var mixed
   */
  protected $valid = false;
  /**
   *
   * @var boolean
   */
  protected $is_grouping = false;
  /**
   *
   * @var int
   */
  protected $record_id;
  /**
   *
   * @var boolean
   */
  protected $is_mine = false;
  /**
   *
   * @var int
   */
  protected $usr_id;
  /**
   *
   * @var array
   */
  protected $elements;
  /**
   *
   * @var int
   */
  protected $ssel_id;
  /**
   *
   * @var array
   */
  protected $validating_users = array();
  /**
   *
   * @var boolean
   */
  protected $validation_see_others = false;
  /**
   *
   * @var boolean
   */
  protected $validation_end_date = false;
  /**
   *
   * @var boolean
   */
  protected $validation_is_confirmed = false;
  /**
   *
   * @var int
   */
  protected $sbas_id;
  /**
   *
   * @var int
   */
  protected $coll_id;
  /**
   *
   * @var int
   */
  protected $base_id;
  /**
   *
   * @var boolean
   */
  protected $owner_changed = false;
  /**
   *
   * @var array
   */
  static $_regfields = null;
  /**
   *
   * @var appbox
   */
  protected $appbox;
  /**
   *
   * @var boolean
   */
  protected static $_instance = array();

  const CACHE_ELEMENTS = 'elements';

  const CACHE_VALIDATING_USERS ='validatin';

  /**
   *
   * @return int
   */
  public function get_base_id()
  {
    return $this->base_id;
  }

  /**
   *
   * @return User_Adapter
   */
  public function get_pusher()
  {
    return $this->pusher;
  }

  /**
   *
   * @return DateTime
   */
  public function get_create_date()
  {
    return $this->created_on;
  }

  /**
   *
   * @return DateTime
   */
  public function get_update_date()
  {
    return $this->updated_on;
  }

  /**
   *
   * @return int
   */
  public function get_record_id()
  {
    return $this->record_id;
  }

  /**
   *
   * @return boolean
   */
  public function is_mine()
  {
    return $this->is_mine;
  }

  /**
   *
   * @return boolean
   */
  public function is_grouping()
  {
    return $this->is_grouping;
  }

  /**
   *
   * @return string
   */
  public function get_name()
  {
    return $this->name;
  }

  /**
   *
   * @return string
   */
  public function get_description()
  {
    return $this->desc;
  }

  /**
   *
   * @return int
   */
  public function get_ssel_id()
  {
    return $this->ssel_id;
  }

  /**
   *
   * @return array
   */
  public function get_validating_users()
  {
    return $this->validating_users;
  }

  /**
   *
   * @return basket_element_adapter
   */
  public function get_elements()
  {
    if (!$this->elements)
      $this->load_elements();

    return $this->elements;
  }

  /**
   *
   * @return int
   */
  public function get_sbas_id()
  {
    return $this->sbas_id;
  }

  /**
   *
   * @return boolean
   */
  public function is_valid()
  {
    return in_array($this->valid, array('valid', 'myvalid'));
  }

  /**
   *
   * @return boolean
   */
  public function is_my_valid()
  {
    return $this->valid == 'myvalid';
  }

  /**
   *
   * @return boolean
   */
  public function is_unread()
  {
    return $this->noview;
  }

  /**
   *
   * @return basket_element_adapter
   */
  public function get_first_element()
  {
    foreach ($this->get_elements() as $basket_element)

      return $basket_element;
    return null;
  }

  /**
   *
   * @return DateTime
   */
  public function get_validation_end_date()
  {
    if (!$this->valid || !$this->validation_end_date)

      return null;
    return $this->validation_end_date;
  }

  /**
   *
   * @return boolean
   */
  public function is_validation_finished()
  {
    if (!$this->valid || !$this->validation_end_date)

      return null;
    $now = new DateTime();

    return ($now > $this->validation_end_date);
  }

  /**
   *
   * @return boolean
   */
  public function is_confirmed()
  {
    if (!$this->valid)

      return null;

    return $this->validation_is_confirmed;
  }

  public function is_releasable()
  {
    if (!$this->valid)

      return false;

    if ($this->is_confirmed())

      return false;

    foreach($this->get_elements() as $element)
    {
      if($element->get_my_agreement() == '0')

        return false;
    }

    return true;
  }

  /**
   *
   * @param const $option
   * @return string
   */
  public function get_cache_key($option = null)
  {
    return 'basket_' . $this->usr_id . '_' . $this->ssel_id . ($option ? '_' . $option : '');
  }

  /**
   *
   * @param <type> $option
   * @return <type>
   */
  public function get_data_from_cache($option = null)
  {
    return $this->appbox->get_data_from_cache($this->get_cache_key($option));
  }

  /**
   *
   * @param <type> $value
   * @param <type> $option
   * @param <type> $duration
   * @return <type>
   */
  public function set_data_to_cache($value, $option = null, $duration = 0)
  {
    return $this->appbox->set_data_to_cache($value, $this->get_cache_key($option), $duration);
  }

  /**
   *
   * @param <type> $option
   * @return <type>
   */
  public function delete_data_from_cache($option = null)
  {
    if ($option === self::CACHE_ELEMENTS)
      $this->elements = null;

    return $this->appbox->delete_data_from_cache($this->get_cache_key($option));
  }

  /**
   *
   * @param appbox $appbox
   * @param int $ssel_id
   * @param int $usr_id
   * @return basket_adapter
   */
  protected function __construct(appbox &$appbox, $ssel_id, $usr_id)
  {
    $this->instance_key = 'basket_' . $usr_id . '_' . $ssel_id;
    $this->appbox = $appbox;
    $this->ssel_id = (int) $ssel_id;
    $this->usr_id = $usr_id;

    $this->load();

    if ($this->valid)
      $this->load_validation_users();

    return $this;
  }

  protected function load()
  {

    try
    {
      $datas = $this->get_data_from_cache();

      $this->sbas_id = $datas['sbas_id'];
      $this->record_id = $datas['record_id'];
      $this->is_grouping = $datas['is_grouping'];
      $this->pusher = $datas['pusher_id'] ? User_Adapter::getInstance($datas['pusher_id'], $this->appbox) : null;
      $this->validation_is_confirmed = $datas['validation_is_confirmed'];
      $this->validation_end_date = $datas['validation_end_date'];
      $this->validation_see_others = $datas['validation_see_others'];
      $this->valid = $datas['valid'];
      $this->is_mine = $datas['is_mine'];
      $this->noview = $datas['noview'];
      $this->updated_on = $datas['updated_on'];
      $this->created_on = $datas['created_on'];
      $this->name = $datas['name'];
      $this->desc = $datas['desc'];

      return $this;
    }
    catch (Exception $e)
    {

    }

    $sql = 'SELECT s.pushFrom, n.id as noview, s.usr_id as owner, s.rid
              , s.sbas_id, s.temporaryType, s.name, s.descript, s.pushFrom
              , s.date, s.updater
              , v.id as validate_id, v.can_see_others, v.expires_on, v.confirmed
          FROM ssel s
            LEFT JOIN validate v
              ON (s.ssel_id = v.ssel_id AND v.usr_id = :validate_usr_id)
            LEFT JOIN sselnew n
              ON (n.usr_id = :receive_usr_id AND n.ssel_id = s.ssel_id)
          WHERE s.ssel_id = :ssel_id
            AND (s.usr_id = :usr_id OR v.id IS NOT NULL)';

    $params = array(
        ':usr_id' => $this->usr_id,
        ':receive_usr_id' => $this->usr_id,
        ':validate_usr_id' => $this->usr_id,
        ':ssel_id' => $this->ssel_id
    );

    $stmt = $this->appbox->get_connection()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (!$row)
      throw new Exception_Basket_NotFound();

    $this->name = $row['name'];
    $this->desc = $row['descript'];
    $this->created_on = new DateTime($row['date']);
    $this->updated_on = new DateTime($row['updater']);
    $this->usr_id = (int) $row['owner'];
    $this->noview = !!$row['noview'];

    $this->is_mine = ($row['owner'] == $this->usr_id);

    if ($row['validate_id'] != null)
    {
      $this->valid = 'valid';
      if ($this->is_mine)
      {
        $this->valid = 'myvalid';
        $this->validation_see_others = true;
      }
      elseif ($row['can_see_others'] == '1')
      {
        $this->validation_see_others = true;
      }
      $this->validation_end_date = $row['expires_on'] ? new DateTime($row['expires_on']) : null;
      $this->validation_is_confirmed = !!$row['confirmed'];

      $this->load_validation_users();
    }


    if ((int) $row['pushFrom'] > 0)
    {
      try
      {
        $this->pusher = User_Adapter::getInstance($row['pushFrom'], $this->appbox);
      }
      catch (Exception $e)
      {

      }
    }

    $this->is_grouping = ($row['temporaryType'] == 1);
    if ($this->is_grouping)
    {
      $this->record_id = $row['rid'];
      $this->sbas_id = $row['sbas_id'];
    }

    $pusher_id = $this->pusher instanceof User_Adapter ? $this->pusher->get_id() : null;

    $datas = array(
        'sbas_id' => $this->sbas_id
        , 'record_id' => $this->record_id
        , 'is_grouping' => $this->is_grouping
        , 'pusher_id' => $pusher_id
        , 'validation_is_confirmed' => $this->validation_is_confirmed
        , 'validation_end_date' => $this->validation_end_date
        , 'validation_see_others' => $this->validation_see_others
        , 'valid' => $this->valid
        , 'is_mine' => $this->is_mine
        , 'noview' => $this->noview
        , 'updated_on' => $this->updated_on
        , 'created_on' => $this->created_on
        , 'name' => $this->name
        , 'desc' => $this->desc
    );

    $this->set_data_to_cache($datas);

    return $this;
  }

  /**
   *
   * @param <type> $order
   * @return basket_adapter
   */
  public function sort($order)
  {
    if (!$this->valid || !in_array($order, array('asc', 'desc')))

      return;

    $this->load_elements();

    if ($order == 'asc')
      uasort($this->elements, array('basket_adapter', 'order_validation_asc'));
    else
      uasort($this->elements, array('basket_adapter', 'order_validation_desc'));

    return $this;
  }

  /**
   *
   * @todo change this shit
   * @param mixed $lst
   * @param boolean $fixing
   * @return array
   */
  public function push_list($lst, $fixing)
  {
    $ret = array('error' => false, 'datas' => array());

    if (!is_array($lst))
      $lst = explode(';', $lst);

    foreach ($lst as $basrec)
    {
      try
      {
        if (!is_array($basrec))
          $basrec = explode('_', $basrec);
        if (count($basrec) != 2)
          continue;
        $sbas_id = $basrec[0];
        $record = new record_adapter($sbas_id, $basrec[1]);
        $push = $this->push_element($record, $this->record_id, $fixing);
        unset($record);
        if ($push['error'])
          $ret['error'] = $push['error'];
        else
          $ret['datas'] = array_merge($ret['datas'], $push['datas']);
      }
      catch (Exception_Record_AdapterNotFound $e)
      {

      }
      catch (Exception $e)
      {
        $ret['error'] = "an error occured";
      }
    }

    return $ret;
  }

  /**
   *
   * @param record_Interface $record
   * @param int $parent_record_id
   * @param boolean $fixing
   * @return array
   */
  public function push_element(record_Interface $record, $parent_record_id, $fixing)
  {
    $base_id = $record->get_base_id();
    $record_id = $record->get_record_id();
    if ($parent_record_id === true && phrasea::sbasFromBas($base_id) != $this->sbas_id)
    {
      return array(
          'error' => _('panier:: Un reportage ne peux recevoir que des elements provenants de la base ou il est enregistre'),
          'datas' => array()
      );
    }

    if ($this->valid && !$this->is_mine)
    {
      return array('error' => _('Ce panier est en lecture seule'), 'datas' => array());
    }

    try
    {
      $sselcont_id = basket_element_adapter::create($this, $base_id, $record_id, $parent_record_id, $this->valid, $fixing);

      $this->add_element($sselcont_id);

      $this->delete_data_from_cache(self::CACHE_ELEMENTS);

      $ret['error'] = false;
      $ret['datas'] = array($sselcont_id->get_sselcont_id());
    }
    catch (Exception $e)
    {
      $ret['error'] = $e->getMessage();
      $ret['datas'] = array();
    }

    return $ret;
  }

  /**
   *
   * @return string
   */
  public function get_excerpt()
  {
    $ret = '';

    $i = 0;

    foreach ($this->get_elements() as $basket_element)
    {
      $i++;
      if ($i > 9)
        break;

      $thumbnail = $basket_element->get_record()->get_thumbnail();

      $ratio = $thumbnail->get_width() / $thumbnail->get_height();
      $top = $left = 0;
      if ($thumbnail->get_width() > $thumbnail->get_height())//paysage
      {
        $h = 80;
        $w = $h * $ratio;
        $left = round((80 - $w) / 2);
      }
      else
      {
        $w = 80;
        $h = $w / $ratio;
        $top = round((80 - $h) / 2);
      }
      $ret .= '<div style="margin:5px;position:relative;float:left;width:80px;height:80px;overflow:hidden;">
                        <img style="position:relative;top:' . $top . 'px;left:' . $left . 'px;width:' . $w . 'px;
                                height:' . $h . 'px;" src="' . $thumbnail->get_url() . '" />
                    </div>';
    }

    return $ret;
  }

  /**
   *
   * Return the total HD size of documents inside the basket
   * @return <type>
   */
  public function get_size()
  {
    $totSize = 0;
    $session = $this->appbox->get_session();

    foreach ($this->get_elements() as $basket_element)
    {
      try
      {
        $sd = $basket_element->get_record()->get_subdef('document');
        $totSize += $sd->get_size();
      }
      catch (Exception $e)
      {

      }
    }

    $totSize = round($totSize / (1024 * 1024), 2);

    return $totSize;
  }

  /**
   *
   * @return <type>
   */
  public function getOrderDatas()
  {
    $out = '';
    $n = 0;

    foreach ($this->get_elements() as $basket_element)
    {
      $thumbnail = $basket_element->get_record()->get_thumbnail();
      if ($thumbnail->get_width() > $thumbnail->get_height())
      {
        $h = (int) (82 * $thumbnail->get_height() / $thumbnail->get_width());
        $w = 82;
      }
      else
      {
        $w = (int) (82 * $thumbnail->get_width() / $thumbnail->get_height());
        $h = 82;
      }

      $title = $basket_element->get_record()->get_title();
      $record = $basket_element->get_record();

      $out .= '<div id="ORDER_' . $basket_element->get_sselcont_id() . '" class="CHIM diapo" style="height:130px;overflow:hidden;">' .
              '<div class="title" title="' . $title . '"
                        style="position:relative;z-index:1200;height:30px;overflow:visible;text-align:center;">
                        <span>' . $title . '</span></div>' .
              '<img ondragstart="return false;" class="CHIM_' . $record->get_sbas_id() . '_' . $record->get_record_id() . '"
                        src="' . $thumbnail->get_url() . '"
                        style="position:relative;width:' . $w . 'px;height:' . $h . 'px;
                                padding:' . (floor((82 - $h) / 2) + 9) . 'px ' . (floor((82 - $w) / 2) + 9) . 'px;z-index:1000;"/>';
      $out .= '<form style="display:none;">
                <input type="hidden" name="id" value="' . $basket_element->get_sselcont_id() . '"/>';

      $out .= '<input type="hidden" name="record_id" value="' . $record->get_record_id() . '"/>';

      $out .= '<input type="hidden" name="base_id" value="' . $record->get_base_id() . '"/>
                <input type="hidden" name="title" value="' .
              trim(str_replace(array("\r\n", "\r", "\n"), array(" ", " ", " "), strip_tags($title))) . '"/>
                <input type="hidden" name="default" value="' . $n . '"/>
            </form>';
      $out .= '</div>';

      $n++;
    }

    return $out . '<form style="display:none;" name="save">
                        <input type="hidden" name="ssel_id" value="' . $this->ssel_id . '"/>
                    </form>';
  }

  /**
   * Save re-ordered basket
   * @param Json serialized array
   * @return Json serialized array
   */
  public function saveOrderDatas($value)
  {
    $conn = connection::getPDOConnection();

    $conn->beginTransaction();

    $error = false;

    $value = json_decode($value);

    $rid_parent = (int) $this->record_id;
    $ssel_id = (int) $this->ssel_id;
    $sbas_id = (int) $this->sbas_id;

    $cacheusers = array();
    $sselcont_equiv = array();


    if ($this->is_grouping)
    {
      $sql = 'SELECT c1.sselcont_id, s.usr_id, s.ssel_id, c2.sselcont_id as equiv FROM sselcont c1, sselcont c2, ssel s
                    WHERE temporaryType="1" AND s.rid = :record_id AND s.sbas_id = :sbas_id
                    AND s.ssel_id = c1.ssel_id AND s.ssel_id != :ssel_id_dif AND c1.base_id = c2.base_id
                    AND c1.record_id = c2.record_id AND c2.ssel_id = :ssel_id';

      $params = array(
          ':record_id' => $this->get_record_id()
          , ':sbas_id' => $this->get_sbas_id()
          , ':ssel_id_dif' => $this->get_ssel_id()
          , ':ssel_id' => $ssel_id
      );

      $stmt = $conn->prepare($sql);
      $stmt->execute($params);
      $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $stmt->closeCursor();

      foreach ($rs as $row)
      {
        if (!isset($cacheusers[$row['usr_id']]))
          $cacheusers[$row['usr_id']] = array();

        $cacheusers[$row['usr_id']][$row['ssel_id']] = $row['ssel_id'];

        $sselcont_equiv[$row['equiv']][] = $row['sselcont_id'];
      }
    }

    foreach ($value as $id => $infos)
    {
      $infos->order = trim($infos->order);
      $infos->record_id = (int) $infos->record_id;
      $id = trim($id);

      if ($this->is_grouping)
      {
        try
        {
          $connbas = connection::getPDOConnection($sbas_id);

          $sql = 'UPDATE regroup SET ord = :ordre
              WHERE rid_parent = :record_id_parent
              AND rid_child = :record_id';

          $params = array(
              ':ordre' => $infos->order
              , ':record_id_parent' => $rid_parent
              , ':record_id' => $infos->record_id
          );

          $stmt = $connbas->prepare($sql);
          $stmt->execute($params);
          $stmt->closeCursor();


          if (isset($sselcont_equiv[trim($id)]))
          {
            try
            {
              $sql = "UPDATE sselcont SET ord = :ordre''
                  WHERE sselcont_id IN (" . implode(', ', $sselcont_equiv[$id]) . ")";
              $stmt = $conn->prepare($sql);
              $stmt->execute(array(':ordre' => $infos->order));
              $stmt->closeCursor();
            }
            catch (Exception $e)
            {

            }
          }
        }
        catch (Exception $e)
        {
          $error = true;
        }
      }

      try
      {
        $sql = "UPDATE sselcont SET ord = :ordre
                    WHERE sselcont_id = :sselcont_id AND ssel_id = :ssel_id";

        $params = array(
            ':ordre' => $infos->order
            , ':sselcont_id' => $id
            , ':ssel_id' => $ssel_id
        );
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();
      }
      catch (Exception $e)
      {
        $error = true;
      }
    }

    foreach ($cacheusers as $usr_id => $ssel_ids)
    {
      foreach ($ssel_ids as $ssel_id)
      {
        $basket_usr = self::getInstance($this->appbox, $ssel_id, $usr_id);
        $basket_usr->set_unread();
      }
    }

    if (!$error)
    {
      $conn->commit();
    }
    else
    {
      $conn->rollBack();
    }
    $this->delete_cache();

    $ret = array('error' => $error);

    return p4string::jsonencode($ret);
  }

  /**
   * Delete the basket
   * @return boolean
   */
  public function delete()
  {
    $sql = 'DELETE FROM ssel WHERE ssel_id = :ssel_id';
    $stmt = $this->appbox->get_connection()->prepare($sql);
    $stmt->execute(array(':ssel_id' => $this->ssel_id));
    $stmt->closeCursor();

    $sql = 'DELETE FROM sselcont WHERE ssel_id = :ssel_id';
    $stmt = $this->appbox->get_connection()->prepare($sql);
    $stmt->execute(array(':ssel_id' => $this->ssel_id));
    $stmt->closeCursor();

    $sql = 'DELETE FROM sselnew WHERE ssel_id = :ssel_id';
    $stmt = $this->appbox->get_connection()->prepare($sql);
    $stmt->execute(array(':ssel_id' => $this->ssel_id));
    $stmt->closeCursor();

    $this->delete_cache();
    unset(self::$_instance[$this->instance_key]);

    return false;
  }

  /**
   * Set the basket unread for the user
   * @return boolean
   */
  public function set_unread($usr_id = null)
  {
    if (is_null($usr_id))
      $usr_id = $this->usr_id;

    try
    {
      $sql = 'INSERT INTO sselnew (id, ssel_id, usr_id)
                VALUES (null, :ssel_id, :usr_id)';
      $stmt = $this->appbox->get_connection()->prepare($sql);
      $stmt->execute(array(':ssel_id' => $this->ssel_id, ':usr_id' => $usr_id));
      $stmt->closeCursor();

      $this->noview = true;
    }
    catch (Exception $e)
    {
      return false;
    }
    $this->delete_cache();

    return true;
  }

  /**
   * Set the basket read for the user
   * @return boolean
   */
  public function set_read()
  {
    if (!$this->noview)

      return true;
    $session = $this->appbox->get_session();

    try
    {
      $sql = 'DELETE FROM sselnew WHERE ssel_id = :ssel_id AND usr_id = :usr_id';
      $stmt = $this->appbox->get_connection()->prepare($sql);
      $stmt->execute(array(':ssel_id' => $this->ssel_id, ':usr_id' => $session->get_usr_id()));
      $stmt->closeCursor();

      $this->noview = false;
    }
    catch (Exception $e)
    {
      return false;
    }
    $this->delete_cache();

    return true;
  }

  /**
   * Add users to the validation process
   *
   * @param Integer $usr_id
   * @param boolean $can_agree
   * @param boolean $can_see_others
   * @param boolean $can_hd
   * @param DateTime $expire
   */
  public function validation_to_users(User_Adapter $user, $can_agree, $can_see_others, $can_hd, DateTime $expire = null)
  {
    try
    {
      $sql = 'REPLACE INTO validate (id, ssel_id, created_on, updated_on, expires_on,
                last_reminder, usr_id, confirmed, can_agree, can_see_others)
              VALUES
                (null, :ssel_id, NOW(), NOW(), :expire,
                null, :usr_id, 0, :can_agree, :can_see_others)';

      $stmt = $this->appbox->get_connection()->prepare($sql);

      $params = array(
          ':ssel_id' => $this->ssel_id
          , ':expire' => (is_null($expire) ? null : $expire->format(DATE_ISO8601))
          , ':usr_id' => $user->get_id()
          , ':can_agree' => ($can_agree ? '1' : '0')
          , ':can_see_others' => ($can_see_others ? '1' : '0')
      );

      $stmt->execute($params);
      $insert_id = $this->appbox->get_connection()->lastInsertId();
      $stmt->closeCursor();

      $me = User_Adapter::getInstance($this->appbox->get_session()->get_usr_id(), $this->appbox);

      foreach ($this->get_elements() as $basket_element)
      {
        $basket_element->validate($me, $user, $insert_id, $can_hd);
      }

      $this->valid = 'myvalid';

      $this->set_unread($user->get_id());
      $this->delete_data_from_cache(self::CACHE_VALIDATING_USERS);
    }
    catch (Exception $e)
    {
      return false;
    }

    $this->delete_cache();

    return true;
  }

  /**
   *
   * @return string
   */
  public function get_validation_infos()
  {
    if ($this->is_mine)
    {
      if ($this->is_validation_finished())

        return sprintf(_('Vous aviez envoye cette demande a %d utilisateurs'), (count($this->validating_users) - 1));
      else

        return sprintf(_('Vous avez envoye cette demande a %d utilisateurs'), (count($this->validating_users) - 1));
    }
    else
    {
      if ($this->validation_see_others)

        return sprintf(_('Processus de validation recu de %s et concernant %d utilisateurs'), User_Adapter::getInstance($this->usr_id, $this->appbox)->get_display_name(), (count($this->validating_users) - 1));
      else

        return sprintf(_('Processus de validation recu de %s'), User_Adapter::getInstance($this->usr_id, $this->appbox)->get_display_name());
    }
  }

  /**
   *
   * @return basket_adapter
   */
  public function set_released()
  {
    if(!$this->is_valid())
      throw new Exception('Not a validation basket');

    $Core = bootstrap::getCore();
    $session = $this->appbox->get_session();

    $sql = 'UPDATE validate SET confirmed="1"
        WHERE ssel_id = :ssel_id AND usr_id = :usr_id';

    $params = array(
        ':ssel_id' => $this->get_ssel_id()
        , ':usr_id' => $session->get_usr_id()
    );
    $stmt = $this->appbox->get_connection()->prepare($sql);
    $stmt->execute($params);
    $stmt->closeCursor();

    $evt_mngr = eventsmanager_broker::getInstance($this->appbox, $Core);

    $sql = 'SELECT s.usr_id FROM validate v, ssel s
                    WHERE s.ssel_id = v.ssel_id
            AND v.usr_id = :usr_id AND v.ssel_id = :ssel_id';

    $stmt = $this->appbox->get_connection()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if ($row)
    {
      $to = $row['usr_id'];
      $params = array(
          'ssel_id' => $this->ssel_id,
          'from' => $session->get_usr_id(),
          'to' => $to
      );
      $evt_mngr->trigger('__VALIDATION_DONE__', $params);
    }

    return $this;
  }

  /**
   *
   * @param appbox $appbox
   * @param <type> $ssel_id
   * @param <type> $usr_id
   * @return basket_adapter
   */
  public static function getInstance(appbox &$appbox, $ssel_id, $usr_id)
  {
    $instance_key = 'basket_' . $usr_id . '_' . $ssel_id;
    if (!isset(self::$_instance[$instance_key]))
    {
      self::$_instance[$instance_key] = new self($appbox, $ssel_id, $usr_id);
    }

    return array_key_exists($instance_key, self::$_instance) ? self::$_instance[$instance_key] : false;
  }

  /**
   * @todo ameliorer les tests connbas
   * @return basket_adapter
   */
  protected function load_elements()
  {
    if (!is_null($this->elements))

      return;

    $this->elements = array();

    $user = User_Adapter::getInstance($this->usr_id, $this->appbox);

    $rs = array();

    try
    {
      $rs = $this->get_data_from_cache(self::CACHE_ELEMENTS);
    }
    catch (Exception $e)
    {

      try
      {
        $sql = 'SELECT sselcont_id FROM sselcont WHERE ssel_id = :ssel_id ORDER BY ord ASC';
        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':ssel_id' => $this->ssel_id));
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->set_data_to_cache($rs, self::CACHE_ELEMENTS);
        $stmt->closeCursor();
      }
      catch (Exception $e)
      {

      }
    }

    foreach ($rs as $row)
    {
      try
      {
        $this->add_element(basket_element_adapter::getInstance($row['sselcont_id']));
      }
      catch (Exception $e)
      {
        /**
         * @todo
         * manage case where record has been deleted and not removed from basket
         */
      }
    }

    return $this;
  }

  public function set_name($name)
  {
    $sql = 'UPDATE ssel SET name = :name WHERE ssel_id = :ssel_id';

    $name = trim(strip_tags($name));
    if ($name === '')
      throw new Exception_InvalidArgument ();

    $stmt = $this->appbox->get_connection()->prepare($sql);
    $stmt->execute(array(':name' => $name, ':ssel_id' => $this->get_ssel_id()));
    $stmt->closeCursor();

    $this->name = $name;
    
    $this->delete_data_from_cache();

    return $this;
  }

  public function set_description($desc)
  {

    $sql = 'UPDATE ssel SET descript = :description WHERE ssel_id = :ssel_id';

    $desc = trim(strip_tags($desc));

    $stmt = $this->appbox->get_connection()->prepare($sql);
    $stmt->execute(array(':description' => $desc, ':ssel_id' => $this->get_ssel_id()));
    $stmt->closeCursor();

    $this->desc = $desc;

    $this->delete_data_from_cache();
    
    return $this;
  }

  /**
   * Add an element to the basket
   *
   * @param basket_element_adapter $basket_element
   * @return basket_adapter
   */
  protected function add_element(basket_element_adapter &$basket_element)
  {
    $this->elements[$basket_element->get_sselcont_id()] = $basket_element;
    $this->elements[$basket_element->get_sselcont_id()]->set_order(count($this->elements) + 1);

    return $this;
  }

  /**
   *
   * @param <type> $sselcont_id
   * @return <type>
   */
  protected function remove_basket_elements($sselcont_id)
  {
    try
    {
      $sql = 'DELETE FROM sselcont
        WHERE sselcont_id = :sselcont_id AND ssel_id = :ssel_id';

      $stmt = $this->appbox->get_connection()->prepare($sql);
      $stmt->execute(array(':sselcont_id' => $sselcont_id, ':ssel_id' => $this->ssel_id));

      $sql = 'DELETE FROM validate_datas WHERE sselcont_id = :sselcont_id';
      $stmt = $this->appbox->get_connection()->prepare($sql);
      $stmt->execute(array(':sselcont_id' => $sselcont_id));

      $this->delete_data_from_cache(self::CACHE_ELEMENTS);

      return array('error' => false, 'status' => 1);
    }
    catch (Exception $e)
    {

    }

    return array('error' => true, 'status' => 0);
  }

  /**
   *
   * @param <type> $sselcont_id
   * @return <type>
   */
  protected function remove_grouping_elements($sselcont_id)
  {
    $session = $this->appbox->get_session();

    $sbas_id = $parent_record_id = $collid = $base_id = $record_id = null;

    $ssel_id = $this->ssel_id;

    try
    {
      $sql = 'SELECT s.sbas_id, s.ssel_id, s.rid, c.record_id, c.base_id
              FROM ssel s, sselcont c
              WHERE c.sselcont_id = :sselcont_id
              AND c.ssel_id = s.ssel_id AND s.ssel_id = :ssel_id';
      $stmt = $this->appbox->get_connection()->prepare($sql);
      $stmt->execute(array(':sselcont_id' => $sselcont_id, ':ssel_id' => $this->ssel_id));
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      $stmt->closeCursor();

      if ($row)
      {
        $parent_record_id = $row["rid"];
        $base_id = $row['base_id'];
        $sbas_id = $row['sbas_id'];
        $record_id = $row['record_id'];
      }
    }
    catch (Exception $e)
    {

    }

    $ret = array('error' => false, 'status' => 0);

    try
    {
      $user = User_Adapter::getInstance($session->get_usr_id(), $this->appbox);

      if (!$user->ACL()->has_right_on_base($base_id, 'canmodifrecord'))
        throw new Exception('Not enough rights');
      $connbas = connection::getPDOConnection($sbas_id);

      $sql = "DELETE FROM regroup WHERE rid_parent = :parent_record_id
                    AND rid_child = :record_id";
      $stmt = $connbas->prepare($sql);
      $stmt->execute(array(':parent_record_id' => $parent_record_id, ':record_id' => $record_id));
      $stmt->closeCursor();

      $sql = 'SELECT sselcont_id, s.ssel_id, s.usr_id FROM ssel s, sselcont c
            WHERE s.rid = :parent_record_id AND s.sbas_id = :sbas_id
                        AND temporaryType="1" AND c.ssel_id = s.ssel_id
            AND c.base_id = :base_id AND c.record_id = :record_id';

      $stmt = $this->appbox->get_connection()->prepare($sql);
      $stmt->execute(
              array(
                  ':parent_record_id' => $parent_record_id
                  , ':sbas_id' => $sbas_id
                  , ':base_id' => $base_id
                  , ':record_id' => $record_id
              )
      );
      $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $stmt->closeCursor();

      $first = true;
      $good = false;

      foreach ($rs as $row)
      {
        $sql = 'DELETE FROM sselcont WHERE sselcont_id = :sselcont_id';
        $stmt = $this->appbox->get_connection()->prepare($sql);
        if ($first)
          $good = true;
        $first = false;
        try
        {
          $stmt->execute(array(':sselcont_id' => $row['sselcont_id']));

          $basket_usr = self::getInstance($this->appbox, $row['ssel_id'], $row['usr_id']);
          $basket_usr->set_unread();
          $stmt->closeCursor();
        }
        catch (Exception $e)
        {
          $good = false;
        }
      }

      if (!$good)
        $ret = array('error' => _('panier:: erreur lors de la suppression'), 'status' => 0);
      else
        $ret = array('error' => false, 'status' => 1);
      $this->delete_data_from_cache(self::CACHE_ELEMENTS);
    }
    catch (Exception $e)
    {

      $ret = array(
          'error' => _('phraseanet :: droits insuffisants, vous devez avoir les doits d\'edition sur le regroupement '),
          'status' => 0);
    }

    return $ret;
  }

  /**
   * Flattent a basket
   * Remove groupings from the basket and and their contents
   *
   * @return basket_adapter
   */
  public function flatten()
  {
    foreach ($this->get_elements() as $basket_element)
    {
      $record = $basket_element->get_record();
      if ($record->is_grouping())
      {
        $lst = array();
        foreach ($record->get_children() as $tmp_record)
        {
          $lst[] = sprintf("%s_%s", $tmp_record->get_base_id(), $tmp_record->get_record_id());
        }

        $this->push_list($lst, true);
        $this->remove_from_ssel($basket_element);
      }
      unset($record);
    }

    return $this;
  }

  /**
   *
   * @param <type> $sselcont_id
   * @return <type>
   */
  public function remove_from_ssel($sselcont_id)
  {
    if (!$this->is_mine)

      return array('error' => 'error', 'status' => 0);

    if ($this->is_grouping)

      return $this->remove_grouping_elements($sselcont_id);
    else

      return $this->remove_basket_elements($sselcont_id);
  }

  /**
   *
   * @return basket_adapter
   */
  public function delete_cache()
  {
    $keys = array();

    if ($this->is_valid())
    {
      foreach ($this->get_validating_users() as $user_data)
      {
        $keys[] = 'basket_' . $user_data['usr_id'] . '_' . $this->get_ssel_id();
        $keys[] = 'basket_' . $user_data['usr_id'] . '_' . $this->get_ssel_id().'_'.self::CACHE_ELEMENTS;
        $keys[] = 'basket_' . $user_data['usr_id'] . '_' . $this->get_ssel_id().'_'.self::CACHE_VALIDATING_USERS;
      }
    }

    $keys[] = 'basket_' . $this->usr_id . '_' . $this->get_ssel_id();
    $keys[] = 'basket_' . $this->usr_id . '_' . $this->get_ssel_id().'_'.self::CACHE_ELEMENTS;
    $keys[] = 'basket_' . $this->usr_id . '_' . $this->get_ssel_id().'_'.self::CACHE_VALIDATING_USERS;

    $this->appbox->delete_data_from_cache($keys);

    return $this;
  }

  /**
   *
   * @param <type> $lst
   * @return <type>
   */
  public static function fix_grouping($lst)
  {
    $appbox = appbox::get_instance();
    $session = $appbox->get_session();
    $usr_id = $session->get_usr_id();
    $registry = $appbox->get_registry();

    $retour = array();

    if (!is_array($lst))
      $lst = explode(";", $lst);

    foreach ($lst as $basrec)
    {
      $basrec = explode('_', $basrec);
      $record_id = (int) $basrec[1];
      $sbas_id = (int) $basrec[0];

      $record = new record_adapter($sbas_id, $record_id);
      $base_id = $record->get_base_id();

      $regfield = self::getRegFields($sbas_id, $record->get_caption());
      $connbas = connection::getPDOConnection($sbas_id);

      $sql = 'SELECT moddate FROM record WHERE record_id = :record_id';
      $stmt = $connbas->prepare($sql);
      $stmt->execute(array(':record_id' => $record_id));
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      $stmt->closeCursor();

      $moddate = $row ? $row['moddate'] : '';

      $sql = 'SELECT ssel_id FROM ssel
          WHERE usr_id = :usr_id
            AND temporaryType=1 AND rid = :record_id AND sbas_id = :base_id';

      $params = array(
          ':usr_id' => $usr_id
          , ':record_id' => $record_id
          , ':base_id' => $record->get_sbas_id()
      );

      $stmt = $appbox->get_connection()->prepare($sql);
      $stmt->execute($params);
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      $stmt->closeCursor();

      if (!$row)
      {
        $sql = 'INSERT INTO ssel (ssel_id, usr_id, date,temporaryType , rid , sbas_id, updater,name,descript )
                                VALUES (null, :usr_id, :date, "1" , :record_id ,:sbas_id,  :moddate,  :name, :desc )';

        $params = array(
            ':usr_id' => $usr_id
            , ':date' => $regfield['regdate']
            , ':record_id' => $record_id
            , ':sbas_id' => $sbas_id
            , ':moddate' => $moddate
            , ':name' => $regfield['regname']
            , ':desc' => $regfield['regdesc']
        );

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $id = $appbox->get_connection()->lastInsertId();
        $basket = self::getInstance($appbox, $id, $usr_id);

        $lst = $record->get_children();
        $lst = $lst->serialize_list();
        $basket->push_list($lst, true);
        $retour[] = $id;
      }
      else
      {
        $retour[] = $row['ssel_id'];
      }
    }

    return p4string::jsonencode($retour);
  }

  /**
   *
   * @param <type> $sselid
   * @return boolean
   */
  public static function unfix_grouping($sselid)
  {
    $appbox = appbox::get_instance();
    $session = $appbox->get_session();

    try
    {
      $appbox->get_connection()->beginTransaction();
      $sql = 'DELETE FROM ssel WHERE ssel_id = :ssel_id AND usr_id = :usr_id';
      $stmt = $appbox->get_connection()->prepare($sql);
      $stmt->execute(array(':ssel_id' => $sselid, ':usr_id' => $session->get_usr_id()));
      $stmt->closeCursor();

      $sql = 'DELETE FROM sselcont WHERE ssel_id = :ssel_id';
      $stmt = $appbox->get_connection()->prepare($sql);
      $stmt->execute(array(':ssel_id' => $sselid));
      $stmt->closeCursor();
      $ret = true;
      $appbox->get_connection()->commit();
    }
    catch (Exception $e)
    {
      $appbox->get_connection()->rollBack();
      $ret = false;
    }

    return $ret;
  }

  /**
   *
   * @param appbox $appbox
   * @param <type> $name
   * @param User_Interface $user
   * @param <type> $desc
   * @param User_Adapter $pusher
   * @param <type> $base_id
   * @return <type>
   */
  public static function create(appbox $appbox, $name, User_Interface $user, $desc = '', User_Adapter $pusher=null, $base_id = null)
  {
    $conn = $appbox->get_connection();

    $record = false;

    $desc = trim(strip_tags(str_replace('<br>', "\n", $desc)));
    $name = trim(strip_tags($name));


    if ($base_id)
    {
      $databox = $appbox->get_databox(phrasea::sbasFromBas($base_id));
      $meta_struct = $databox->get_meta_structure();

      try
      {
        if (!$user->ACL()->has_right_on_base($base_id, 'canaddrecord'))
          throw new Exception('No rights');

        $ret = FALSE;

        $registry = $appbox->get_registry();
        $collection = collection::get_from_base_id($base_id);

        $record = record_adapter::create(
                        $collection
                        , new system_file($registry->get('GV_RootPath') . 'www/skins/icons/substitution/regroup_doc.png')
                        , false
                        , true
        );


        $record_id = $record->get_record_id();

        $metadatas = array();

        foreach ($meta_struct as $meta)
        {
          if ($meta->is_regname())
            $value = $name;
          elseif ($meta->is_regdesc())
            $value = $desc;
          else
            continue;

          $metadatas[] = array(
              'meta_struct_id' => $meta->get_id()
              , 'meta_id' => null
              , 'value' => array($value)
          );
        }

        $record->set_metadatas($metadatas)
                ->rebuild_subdefs();

        $ret = true;
      }
      catch (Exception $e)
      {
        $ret = false;
      }
    }

    $sql = 'INSERT INTO ssel (ssel_id, name, descript, usr_id, pushFrom, date, updater, temporaryType, rid, sbas_id)
                        VALUES (null, :name, :description, :usr_id, :pushFrom, NOW(), NOW(), :temporaryType, :record_id, :sbas_id)';

    $stmt = $conn->prepare($sql);

    $params = array(
        ':name' => $name
        , ':usr_id' => $user->get_id()
        , ':description' => $desc
        , ':pushFrom' => ($pusher instanceof User_Interface ? $pusher->get_id() : '0')
        , ':temporaryType' => ($record instanceof record_adapter ? '1' : '0')
        , ':record_id' => ($record instanceof record_adapter ? $record->get_record_id() : '0')
        , ':sbas_id' => ($record instanceof record_adapter ? $record->get_sbas_id() : '0')
    );

    if (!$stmt->execute($params))
    {
      throw new Exception('Error while creating basket');
    }
    $ssel_id = $conn->lastInsertId();

    return self::getInstance($appbox, $ssel_id, $user->get_id());
  }

  /**
   * Revoke cache when user documents have their collection changed or status
   * - do not cache datas which are now forbidden
   *
   * @param $usr_id
   * @return boolean
   */
  public static function revoke_baskets_record(record_adapter &$record, appbox &$appbox)
  {
    $keys = array();

    $sql = 'SELECT s.ssel_id, s.usr_id FROM ssel s, sselcont c
        WHERE base_id = "' . $record->get_base_id() . '"
          AND record_id="' . $record->get_record_id() . '"
          AND c.ssel_id = s.ssel_id';

    $stmt = $appbox->get_connection()->prepare($sql);
    $stmt->execute();
    $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    foreach ($rs as $row)
    {
      $keys[] = 'basket_' . $row['usr_id'] . '_' . $row['ssel_id'];
    }

    return $appbox->delete_data_from_cache($keys);
  }

  /**
   *
   * @param User_Interface $user
   * @return <type>
   */
  public static function revoke_baskets_usr(User_Interface $user)
  {
    $ssel_ids = array();
    $appbox = appbox::get_instance();
    try
    {
      $sql = 'SELECT distinct s.ssel_id FROM ssel s, validate v
        WHERE s.usr_id=:usr_id
          OR (v.usr_id=:other_usr_id AND v.ssel_id = s.ssel_id)';
      $stmt = $appbox->get_connection()->prepare($sql);
      $stmt->execute(array(':usr_id' => $user->get_id(), ':other_usr_id' => $user->get_id()));
      $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $stmt->closeCursor();
      foreach ($rs as $row)
      {
        $ssel_ids[] = 'basket_' . $user->get_id() . '_' . $row['ssel_id'];
      }
    }
    catch (Exception $e)
    {

    }

    return $appbox->delete_data_from_cache($ssel_ids);
  }

  /**
   * Load users in current validation process
   * @return void
   */
  protected function load_validation_users()
  {
    try
    {
      $datas = $this->get_data_from_cache(self::CACHE_VALIDATING_USERS);
      $this->validating_users = $datas;

      foreach ($this->validating_users as $row)
      {
        $user = User_Adapter::getInstance($row['usr_id'], $this->appbox);
        $name = $user->get_display_name();
        $this->validating_users[$row['usr_id']]['usr_name'] = $name;
      }

      return $this;
    }
    catch (Exception $e)
    {

    }

    $sql = 'SELECT id, usr_id, confirmed, can_agree, can_see_others
                FROM validate WHERE ssel_id = :ssel_id';

    $stmt = $this->appbox->get_connection()->prepare($sql);
    $stmt->execute(array(':ssel_id' => $this->get_ssel_id()));
    $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    foreach ($rs as $row)
    {
      $this->validating_users[$row['usr_id']] = array(
          'usr_id' => $row['usr_id'],
          'usr_name' => User_Adapter::getInstance($row['usr_id'], $this->appbox)->get_display_name(),
          'confirmed' => $row['confirmed'],
          'can_agree' => $row['can_agree'],
          'can_see_others' => $row['can_see_others']
      );
    }

    $this->set_data_to_cache($this->validating_users, self::CACHE_VALIDATING_USERS);

    return $this;
  }

  /**
   *
   * @param <type> $a
   * @param <type> $b
   * @return <type>
   */
  protected function order_validation_asc($a, $b)
  {
    if (is_null($a->get_avrDisAgree()) || is_null($b->get_avrDisAgree()))
    {
      return 0;
    }
    $comp = $a->get_avrDisAgree() - $b->get_avrDisAgree();

    if ($comp == 0)
    {
      $comp = $b->get_avrAgree() - $a->get_avrAgree();
      if ($comp == 0)
      {
        return 0;
      }
    }

    return $comp > 0 ? -1 : 1;
  }

  /**
   *
   * @param <type> $a
   * @param <type> $b
   * @return <type>
   */
  protected function order_validation_desc($a, $b)
  {
    if (is_null($a->get_avrAgree()) || is_null($b->get_avrAgree()))
    {
      return 0;
    }
    $comp = $a->get_avrAgree() - $b->get_avrAgree();

    if ($comp == 0)
    {
      $comp = $b->get_avrDisAgree() - $a->get_avrDisAgree();
      if ($comp == 0)
      {
        return 0;
      }
    }

    return $comp > 0 ? -1 : 1;
  }

}
