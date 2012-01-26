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
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class databox_status
{

  /**
   *
   * @var Array
   */
  private static $_status = array();

  /**
   *
   * @var Array
   */
  protected static $_statuses;

  /**
   *
   * @var Array
   */
  private $status = array();

  /**
   *
   * @var string
   */
  private $path = '';

  /**
   *
   * @var string
   */
  private $url = '';

  /**
   *
   * @param int $sbas_id
   * @return status
   */
  private function __construct($sbas_id)
  {

    $this->status = array();

    $path = $url = false;

    $sbas_params = phrasea::sbas_params();
    $registry = registry::get_instance();

    if (!isset($sbas_params[$sbas_id]))

      return;

    $path = $this->path = $registry->get('GV_RootPath') . "config/status/" . urlencode($sbas_params[$sbas_id]["host"]) . "-" . urlencode($sbas_params[$sbas_id]["port"]) . "-" . urlencode($sbas_params[$sbas_id]["dbname"]);
    $url = $this->url = "/custom/status/" . urlencode($sbas_params[$sbas_id]["host"]) . "-" . urlencode($sbas_params[$sbas_id]["port"]) . "-" . urlencode($sbas_params[$sbas_id]["dbname"]);

    $databox = databox::get_instance((int) $sbas_id);
    $xmlpref = $databox->get_structure();
    $sxe = simplexml_load_string($xmlpref);

    if ($sxe)
    {

      foreach ($sxe->statbits->bit as $sb)
      {
        $bit = (int) ($sb["n"]);
        if ($bit < 4 && $bit > 63)
          continue;

        $this->status[$bit]["name"] = (string) ($sb);
        $this->status[$bit]["labeloff"] = (string) $sb['labelOff'];
        $this->status[$bit]["labelon"] = (string) $sb['labelOn'];

        $this->status[$bit]["img_off"] = false;
        $this->status[$bit]["img_on"] = false;

        if (is_file($path . "-stat_" . $bit . "_0.gif"))
        {
          $this->status[$bit]["img_off"] = $url . "-stat_" . $bit . "_0.gif";
          $this->status[$bit]["path_off"] = $path . "-stat_" . $bit . "_0.gif";
        }
        if (is_file($path . "-stat_" . $bit . "_1.gif"))
        {
          $this->status[$bit]["img_on"] = $url . "-stat_" . $bit . "_1.gif";
          $this->status[$bit]["path_on"] = $path . "-stat_" . $bit . "_1.gif";
        }

        $this->status[$bit]["searchable"] = isset($sb['searchable']) ? (int) $sb['searchable'] : 0;
        $this->status[$bit]["printable"] = isset($sb['printable']) ? (int) $sb['printable'] : 0;
      }
    }
    ksort($this->status);

    return $this;
  }

  public static function getStatus($sbas_id)
  {

    if (!isset(self::$_status[$sbas_id]))
      self::$_status[$sbas_id] = new databox_status($sbas_id);

    return self::$_status[$sbas_id]->status;
  }

  public static function getDisplayStatus()
  {
    if (self::$_statuses)

      return self::$_statuses;

    $appbox = appbox::get_instance();
    $session = $appbox->get_session();
    $user = User_Adapter::getInstance($session->get_usr_id(), $appbox);

    $sbas_ids = $user->ACL()->get_granted_sbas();

    $statuses = array();

    foreach ($sbas_ids as $databox)
    {
      try
      {
        $statuses[$databox->get_sbas_id()] = $databox->get_statusbits();
      }
      catch (Exception $e)
      {

      }
    }

    self::$_statuses = $statuses;

    return self::$_statuses;
  }

  public static function getSearchStatus()
  {
    $appbox = appbox::get_instance();
    $session = $appbox->get_session();
    $user = User_Adapter::getInstance($session->get_usr_id(), $appbox);

    $statuses = array();

    $sbas_ids = $user->ACL()->get_granted_sbas();

    foreach ($sbas_ids as $databox)
    {
      try
      {
        $statuses[$databox->get_sbas_id()] = $databox->get_statusbits();
      }
      catch (Exception $e)
      {

      }
    }

    $stats = array();

    foreach ($statuses as $sbas_id => $status)
    {

      $see_all = false;

      if ($user->ACL()->has_right_on_sbas($sbas_id, 'bas_modify_struct'))
        $see_all = true;

      foreach ($status as $bit => $props)
      {

        if ($props['searchable'] == 0 && !$see_all)
          continue;

        $set = false;
        if (isset($stats[$bit]))
        {
          foreach ($stats[$bit] as $k => $s_desc)
          {
            if (mb_strtolower($s_desc['labelon']) == mb_strtolower($props['labelon'])
                    && mb_strtolower($s_desc['labeloff']) == mb_strtolower($props['labeloff']))
            {
              $stats[$bit][$k]['sbas'][] = $sbas_id;
              $set = true;
            }
          }
          if (!$set)
          {
            $stats[$bit][] = array(
                'sbas' => array($sbas_id),
                'labeloff' => $props['labeloff'],
                'labelon' => $props['labelon'],
                'imgoff' => $props['img_off'],
                'imgon' => $props['img_on']
            );
            $set = true;
          }
        }

        if (!$set)
        {
          $stats[$bit] = array(
              array(
                  'sbas' => array($sbas_id),
                  'labeloff' => $props['labeloff'],
                  'labelon' => $props['labelon'],
                  'imgoff' => $props['img_off'],
                  'imgon' => $props['img_on']
              )
          );
        }
      }
    }

    return $stats;
  }

  public static function getPath($sbas_id)
  {

    if (!isset(self::$_status[$sbas_id]))
      self::$_status[$sbas_id] = new databox_status($sbas_id);

    return self::$_status[$sbas_id]->path;
  }

  public static function getUrl($sbas_id)
  {

    if (!isset(self::$_status[$sbas_id]))
      self::$_status[$sbas_id] = new databox_status($sbas_id);

    return self::$_status[$sbas_id]->url;
  }

  public static function deleteStatus($sbas_id, $bit)
  {
    $appbox = appbox::get_instance();
    $session = $appbox->get_session();
    $user = User_Adapter::getInstance($session->get_usr_id(), $appbox);

    if (!$user->ACL()->has_right_on_sbas($sbas_id, 'bas_modify_struct'))

      return false;

    $status = self::getStatus($sbas_id);

    if (isset($status[$bit]))
    {
      $connbas = connection::getPDOConnection($sbas_id);

      $databox = databox::get_instance((int) $sbas_id);

      $doc = $databox->get_dom_structure();
      if ($doc)
      {
        $xpath = $databox->get_xpath_structure();
        $entries = $xpath->query($q = "/record/statbits/bit[@n=" . $bit . "]");

        foreach ($entries as $sbit)
        {
          if ($p = $sbit->previousSibling)
          {
            if ($p->nodeType == XML_TEXT_NODE && $p->nodeValue == "\n\t\t")
              $p->parentNode->removeChild($p);
          }
          if ($sbit->parentNode->removeChild($sbit))
          {
            $sql = 'UPDATE record SET status = status&(~(1<<' . $bit . '))';
            $stmt = $connbas->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();
          }
        }

        $databox->saveStructure($doc);

        if (self::$_status[$sbas_id]->status[$bit]['img_off'])
        {
          unlink(self::$_status[$sbas_id]->status[$bit]['path_off']);
        }
        if (self::$_status[$sbas_id]->status[$bit]['img_on'])
        {
          unlink(self::$_status[$sbas_id]->status[$bit]['path_on']);
        }

        unset(self::$_status[$sbas_id]->status[$bit]);

        return true;
      }
    }

    return false;
  }

  public static function updateStatus($sbas_id, $bit, $properties)
  {
    $appbox = appbox::get_instance();
    $session = $appbox->get_session();
    $user = User_Adapter::getInstance($session->get_usr_id(), $appbox);

    if (!$user->ACL()->has_right_on_sbas($sbas_id, 'bas_modify_struct'))

      return false;

    $status = self::getStatus($sbas_id);

    $databox = $appbox->get_databox((int) $sbas_id);

    $doc = $databox->get_dom_structure($sbas_id);
    if ($doc)
    {
      $xpath = $databox->get_xpath_structure($sbas_id);
      $entries = $xpath->query("/record/statbits");
      if ($entries->length == 0)
      {
        $statbits = $doc->documentElement->appendChild($doc->createElement("statbits"));
      }
      else
      {
        $statbits = $entries->item(0);
      }

      if ($statbits)
      {
        $entries = $xpath->query("/record/statbits/bit[@n=" . $bit . "]");

        if ($entries->length >= 1)
        {
          foreach ($entries as $k => $sbit)
          {
            if ($p = $sbit->previousSibling)
            {
              if ($p->nodeType == XML_TEXT_NODE && $p->nodeValue == "\n\t\t")
                $p->parentNode->removeChild($p);
            }
            $sbit->parentNode->removeChild($sbit);
          }
        }

        $sbit = $statbits->appendChild($doc->createElement("bit"));

        if ($n = $sbit->appendChild($doc->createAttribute("n")))
        {
          $n->value = $bit;
          $sbit->appendChild($doc->createTextNode($properties['name']));
        }

        if ($labOn = $sbit->appendChild($doc->createAttribute("labelOn")))
        {
          $labOn->value = $properties['labelon'];
        }

        if ($searchable = $sbit->appendChild($doc->createAttribute("searchable")))
        {
          $searchable->value = $properties['searchable'];
        }

        if ($printable = $sbit->appendChild($doc->createAttribute("printable")))
        {
          $printable->value = $properties['printable'];
        }

        if ($labOff = $sbit->appendChild($doc->createAttribute("labelOff")))
        {
          $labOff->value = $properties['labeloff'];
        }
      }

      $databox->saveStructure($doc);

      self::$_status[$sbas_id]->status[$bit]["name"] = $properties['name'];
      self::$_status[$sbas_id]->status[$bit]["labelon"] = $properties['labelon'];
      self::$_status[$sbas_id]->status[$bit]["labeloff"] = $properties['labeloff'];
      self::$_status[$sbas_id]->status[$bit]["searchable"] = (int) $properties['searchable'];
      self::$_status[$sbas_id]->status[$bit]["printable"] = (int) $properties['printable'];

      if (!isset(self::$_status[$sbas_id]->status[$bit]['img_on']))
        self::$_status[$sbas_id]->status[$bit]['img_on'] = false;
      if (!isset(self::$_status[$sbas_id]->status[$bit]['img_off']))
        self::$_status[$sbas_id]->status[$bit]['img_off'] = false;
    }

    return false;
  }

  public static function deleteIcon($sbas_id, $bit, $switch)
  {
    $appbox = appbox::get_instance();
    $session = $appbox->get_session();
    $user = User_Adapter::getInstance($session->get_usr_id(), $appbox);

    if (!$user->ACL()->has_right_on_sbas($sbas_id, 'bas_modify_struct'))

      return false;

    $status = self::getStatus($sbas_id);

    $switch = in_array($switch, array('on', 'off')) ? $switch : false;

    if (!$switch)

      return false;

    if ($status[$bit]['img_' . $switch])
    {
      if (isset($status[$bit]['path_' . $switch]))
        unlink($status[$bit]['path_' . $switch]);

      $status[$bit]['img_' . $switch] = false;
      unset($status[$bit]['path_' . $switch]);
    }

    return true;
  }

  public static function updateIcon($sbas_id, $bit, $switch, $file)
  {
    $appbox = appbox::get_instance();
    $session = $appbox->get_session();

    $user = User_Adapter::getInstance($session->get_usr_id(), $appbox);

    $registry = registry::get_instance();

    if (!$user->ACL()->has_right_on_sbas($sbas_id, 'bas_modify_struct'))
      throw new Exception_Forbidden();

    $switch = in_array($switch, array('on', 'off')) ? $switch : false;

    if (!$switch)
      throw new Exception_InvalidArgument();

    $status = self::getStatus($sbas_id);
    $url = self::getUrl($sbas_id);
    $path = self::getPath($sbas_id);
    if ($file['size'] >= 65535)
      throw new Exception_Upload_FileTooBig();

    if ($file['error'] !== UPLOAD_ERR_OK)
      throw new Exception_Upload_Error();

    self::deleteIcon($sbas_id, $bit, $switch);
    $name = "-stat_" . $bit . "_" . ($switch == 'on' ? '1' : '0') . ".gif";

    if (!move_uploaded_file($file["tmp_name"], $path . $name))
      throw new Exception_Upload_CannotWriteFile();

    $custom_path = $registry->get('GV_RootPath') . 'www/custom/status/';

    if (!is_dir($custom_path))
      system_file::mkdir($custom_path);

    copy($path . $name, $custom_path . basename($path . $name));
    self::$_status[$sbas_id]->status[$bit]['img_' . $switch] = $url . $name;
    self::$_status[$sbas_id]->status[$bit]['path_' . $switch] = $path . $name;

    return true;
  }

  public static function operation_and($stat1, $stat2)
  {
    $conn = connection::getPDOConnection();

    $status = '0';

    if(substr($stat1, 0, 2) === '0x')
    {
      $stat1 = self::hex2bin(substr($stat1, 2));
    }
    if(substr($stat2, 0, 2) === '0x')
    {
      $stat2 = self::hex2bin(substr($stat2, 2));
    }

    $sql = 'select bin(0b' . trim($stat1) . ' & 0b' . trim($stat2) . ') as result';

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if ($row)
    {
      $status = $row['result'];
    }

    return $status;
  }

  public static function operation_and_not($stat1, $stat2)
  {
    $conn = connection::getPDOConnection();

    $status = '0';

    if(substr($stat1, 0, 2) === '0x')
    {
      $stat1 = self::hex2bin(substr($stat1, 2));
    }
    if(substr($stat2, 0, 2) === '0x')
    {
      $stat2 = self::hex2bin(substr($stat2, 2));
    }

    $sql = 'select bin(0b' . trim($stat1) . ' & ~0b' . trim($stat2) . ') as result';

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if ($row)
    {
      $status = $row['result'];
    }

    return $status;
  }

  public static function operation_or($stat1, $stat2)
  {
    $conn = connection::getPDOConnection();

    $status = '0';

    if(substr($stat1, 0, 2) === '0x')
    {
      $stat1 = self::hex2bin(substr($stat1, 2));
    }
    if(substr($stat2, 0, 2) === '0x')
    {
      $stat2 = self::hex2bin(substr($stat2, 2));
    }

    $sql = 'select bin(0b' . trim($stat1) . ' | 0b' . trim($stat2) . ') as result';

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if ($row)
    {
      $status = $row['result'];
    }

    return $status;
  }

  public static function dec2bin($status)
  {
    $status = (string) $status;

    if(!ctype_digit($status))
    {
      throw new \Exception('Non-decimal value');
    }

    $conn = connection::getPDOConnection();

    $sql = 'select bin(' .  $status . ') as result';

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $status = '0';

    if ($row)
    {
      $status = $row['result'];
    }

    return $status;
  }

  public static function hex2bin($status)
  {
    $status = (string) $status;
    if(substr($status, 0, 2) === '0x')
    {
      $status = substr($status, 2);
    }

    if(!ctype_xdigit($status))
    {
      throw new \Exception('Non-hexadecimal value');
    }

    $conn = connection::getPDOConnection();

    $sql = 'select BIN( CAST( 0x'.trim($status).' AS UNSIGNED ) ) as result';

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $status = '0';

    if ($row)
    {
      $status = $row['result'];
    }

    return $status;
  }

}
