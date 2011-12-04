<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class phrasea
{

  private static $_bas2sbas = false;
  private static $_sbas_names = false;
  private static $_coll2bas = false;
  private static $_bas2coll = false;
  private static $_bas_names = false;
  private static $_sbas_params = false;

  const CACHE_BAS_2_SBAS = 'bas_2_sbas';
  const CACHE_COLL_2_BAS = 'coll_2_bas';
  const CACHE_BAS_2_COLL = 'bas_2_coll';
  const CACHE_BAS_NAMES = 'bas_names';
  const CACHE_SBAS_NAMES = 'sbas_names';
  const CACHE_SBAS_FROM_BAS = 'sbas_from_bas';
  const CACHE_SBAS_PARAMS = 'sbas_params';

  public static function copy_custom_files()
  {
    $registry = registry::get_instance();

    $origine = $registry->get('GV_RootPath') . 'config/custom_files/';
    $dest = $registry->get('GV_RootPath') . 'www/custom/';

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($origine), RecursiveIteratorIterator::LEAVES_ONLY) as $file)
    {
      if (substr($file->getFilename(), 0, 1) == '.' || strpos($file->getRealPath(), '.svn') !== false)
        continue;

      $dest_file = str_replace($origine, $dest, $file->getRealPath());
      $dest_dir = dirname($dest_file);
      if (!is_dir($dest_dir))
        system_file::mkdir($dest_dir);
      copy($file->getRealPath(), $dest_file);
      $system_file = new system_file($dest_file);
      $system_file->chmod();
      unset($system_file);
    }
    $origine = $registry->get('GV_RootPath') . 'config/minilogos/';
    $dest = $registry->get('GV_RootPath') . 'www/custom/minilogos/';

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($origine), RecursiveIteratorIterator::LEAVES_ONLY) as $file)
    {
      if (substr($file->getFilename(), 0, 1) == '.' || strpos($file->getRealPath(), '.svn') !== false)
        continue;

      $dest_file = str_replace($origine, $dest, $file->getRealPath());
      $dest_dir = dirname($dest_file);
      if (!is_dir($dest_dir))
        system_file::mkdir($dest_dir);
      copy($file->getRealPath(), $dest_file);
      $system_file = new system_file($dest_file);
      $system_file->chmod();
      unset($system_file);
    }
    $origine = $registry->get('GV_RootPath') . 'config/status/';
    $dest = $registry->get('GV_RootPath') . 'www/custom/status/';

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($origine), RecursiveIteratorIterator::LEAVES_ONLY) as $file)
    {
      if (substr($file->getFilename(), 0, 1) == '.' || strpos($file->getRealPath(), '.svn') !== false)
        continue;

      $dest_file = str_replace($origine, $dest, $file->getRealPath());
      $dest_dir = dirname($dest_file);
      if (!is_dir($dest_dir))
        system_file::mkdir($dest_dir);
      copy($file->getRealPath(), $dest_file);
      $system_file = new system_file($dest_file);
      $system_file->chmod();
      unset($system_file);
    }
  }

  public static function is_scheduler_started()
  {
    $retval = false;
    $conn = connection::getPDOConnection();
    $sql = 'SELECT schedstatus FROM sitepreff';

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if ($row && $row['schedstatus'] != 'stopped')
    {
      $retval = true;
    }

    return $retval;
  }

  public static function start()
  {
    require (dirname(__FILE__) . '/../../config/connexion.inc');

    if (!extension_loaded('phrasea2'))
      printf("Missing Extension php-phrasea");

    if (function_exists('phrasea_conn'))
      if (phrasea_conn($hostname, $port, $user, $password, $dbname) !== true)
        self::headers(500);
  }

  function getHome($type='PUBLI', $context='prod')
  {
    $appbox = appbox::get_instance();
    $session = $appbox->get_session();
    $registry = $appbox->get_registry();
    $user = User_Adapter::getInstance($session->get_usr_id(), $appbox);
    if ($type == 'HELP')
    {
      if (file_exists($registry->get('GV_RootPath') . "config/help_" . $session->get_I18n() . ".php"))
      {
        require($registry->get('GV_RootPath') . "config/help_" . $session->get_I18n() . ".php");
      }
      elseif (file_exists($registry->get('GV_RootPath') . 'config/help.php'))// on verifie si il y a une home personnalisee sans langage
      {
        require($registry->get('GV_RootPath') . 'config/help.php');
      }
      else
      {
        require($registry->get('GV_RootPath') . 'www/client/help.php');
      }
    }

    if ($type == 'PUBLI')
    {
      if ($context == 'prod')
        require($registry->get('GV_RootPath') . "www/prod/homeinterpubbask.php");
      else
        require($registry->get('GV_RootPath') . "www/client/homeinterpubbask.php");
    }

    if (in_array($type, array('QUERY', 'LAST_QUERY')))
    {
      $context = in_array($context, array('client', 'prod')) ? $context : 'prod';
      $parm = array();

      $bas = array();

      $searchSet = json_decode($user->getPrefs('search'));

      if ($searchSet && isset($searchSet->bases))
      {
        foreach ($searchSet->bases as $bases)
          $bas = array_merge($bas, $bases);
      }
      else
      {
        $user = User_Adapter::getInstance($session->get_usr_id(), $appbox);
        $bas = array_keys($user->ACL()->get_granted_base());
      }

      $start_page_query = $user->getPrefs('start_page_query');

      if ($context == "prod")
      {
        $parm["bas"] = $bas;
        $parm["qry"] = $start_page_query;
        $parm["pag"] = 0;
        $parm["sel"] = '';
        $parm["ord"] = null;
        $parm["search_type"] = 0;
        $parm["recordtype"] = '';
        $parm["status"] = array();
        $parm["fields"] = array();
        $parm["datemin"] = '';
        $parm["datemax"] = '';
        $parm["datefield"] = '';
      }
      if ($context == "client")
      {
        $parm["mod"] = $user->getPrefs('client_view');
        $parm["bas"] = $bas;
        $parm["qry"] = $start_page_query;
        $parm["pag"] = '';
        $parm["search_type"] = 0;
        $parm["qryAdv"] = '';
        $parm["opAdv"] = array();
        $parm["status"] = '';
        $parm["nba"] = '';
        $parm["datemin"] = '';
        $parm["datemax"] = '';
        $parm["recordtype"] = '';
        $parm["datefield"] = '';
        $parm["sort"] = '';
        $parm["stemme"] = '';
        $parm["dateminfield"] = array();
        $parm["datemaxfield"] = array();
        $parm["infield"] = '';
        $parm["regroup"] = null;
        $parm["ord"] = 0;
      }

      require($registry->get('GV_RootPath') . 'www/' . $context . "/answer.php");
    }

    return;
  }

  public static function clear_sbas_params()
  {
    self::$_sbas_params = null;
    $appbox = appbox::get_instance();
    $appbox->delete_data_from_cache(self::CACHE_SBAS_PARAMS);

    return true;
  }

  public static function sbas_params()
  {
    if (self::$_sbas_params)

      return self::$_sbas_params;

    $appbox = appbox::get_instance();
    try
    {
      self::$_sbas_params = $appbox->get_data_from_cache(self::CACHE_SBAS_PARAMS);

      return self::$_sbas_params;
    }
    catch (Exception $e)
    {

    }

    self::$_sbas_params = array();

    $sql = 'SELECT sbas_id, host, port, user, pwd, dbname FROM sbas';
    $stmt = $appbox->get_connection()->prepare($sql);
    $stmt->execute();
    $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    foreach ($rs as $row)
    {
      self::$_sbas_params[$row['sbas_id']] = $row;
    }

    $appbox->set_data_to_cache(self::$_sbas_params, self::CACHE_SBAS_PARAMS);

    return self::$_sbas_params;
  }

  public static function guest_allowed()
  {
    $usr_id = User_Adapter::get_usr_id_from_login('invite');
    if (!$usr_id)

      return false;
    $appbox = appbox::get_instance();
    $user = User_Adapter::getInstance($usr_id, $appbox);

    return $user->ACL()->get_granted_base() > 0;
  }

  public static function load_events()
  {
    $events = eventsmanager_broker::getInstance(appbox::get_instance());
    $events->start();
  }

  public static function use_i18n($locale, $textdomain = 'phraseanet')
  {
    $codeset = "UTF-8";

    putenv('LANG=' . $locale . '.' . $codeset);
    putenv('LANGUAGE=' . $locale . '.' . $codeset);
    bind_textdomain_codeset($textdomain, 'UTF-8');

    bindtextdomain($textdomain, dirname(__FILE__) . '/../../locale/');
    setlocale(LC_ALL
            , $locale . '.UTF-8'
            , $locale . '.UTF8'
            , $locale . '.utf-8'
            , $locale . '.utf8');
    textdomain($textdomain);
  }

  public static function modulesName($array_modules)
  {
    $array = array();

    $modules = array(
        1 => _('admin::monitor: module production'),
        2 => _('admin::monitor: module client'),
        3 => _('admin::monitor: module admin'),
        4 => _('admin::monitor: module report'),
        5 => _('admin::monitor: module thesaurus'),
        6 => _('admin::monitor: module comparateur'),
        7 => _('admin::monitor: module validation'),
        8 => _('admin::monitor: module upload')
    );

    foreach ($array_modules as $a)
    {
      if (isset($modules[$a]))
        $array[] = $modules[$a];
    }


    return $array;
  }

  public static function sbasFromBas($base_id)
  {
    if (!self::$_bas2sbas)
    {
      $appbox = appbox::get_instance();
      try
      {
        self::$_bas2sbas = $appbox->get_data_from_cache(self::CACHE_SBAS_FROM_BAS);
      }
      catch (Exception $e)
      {
        $sql = 'SELECT base_id, sbas_id FROM bas';
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row)
        {
          self::$_bas2sbas[$row['base_id']] = (int) $row['sbas_id'];
        }

        $appbox->set_data_to_cache(self::$_bas2sbas, self::CACHE_SBAS_FROM_BAS);
      }
    }

    return isset(self::$_bas2sbas[$base_id]) ? self::$_bas2sbas[$base_id] : false;
  }

  public static function baseFromColl($sbas_id, $coll_id)
  {
    if (!self::$_coll2bas)
    {
      $conn = connection::getPDOConnection();
      $sql = 'SELECT base_id, server_coll_id, sbas_id FROM bas';
      $stmt = $conn->prepare($sql);
      $stmt->execute();
      $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $stmt->closeCursor();

      foreach ($rs as $row)
      {
        if (!isset(self::$_coll2bas[$row['sbas_id']]))
          self::$_coll2bas[$row['sbas_id']] = array();
        self::$_coll2bas[$row['sbas_id']][$row['server_coll_id']] = (int) $row['base_id'];
      }
    }

    return isset(self::$_coll2bas[$sbas_id][$coll_id]) ? self::$_coll2bas[$sbas_id][$coll_id] : false;
  }

  public static function reset_baseDatas()
  {
    self::$_coll2bas = self::$_bas2coll = self::$_bas_names = self::$_bas2sbas = null;
    $appbox = appbox::get_instance();
    $appbox->delete_data_from_cache(
            array(
                self::CACHE_BAS_2_COLL
                , self::CACHE_BAS_2_COLL
                , self::CACHE_BAS_NAMES
                , self::CACHE_SBAS_FROM_BAS
            )
    );

    return;
  }

  public static function reset_sbasDatas()
  {
    self::$_sbas_names = self::$_sbas_params = self::$_bas2sbas = null;
    $appbox = appbox::get_instance();
    $appbox->delete_data_from_cache(
            array(
                self::CACHE_SBAS_NAMES
                , self::CACHE_SBAS_FROM_BAS
                , self::CACHE_SBAS_PARAMS
            )
    );

    return;
  }

  public static function collFromBas($base_id)
  {
    if (!self::$_bas2coll)
    {
      $conn = connection::getPDOConnection();
      $sql = 'SELECT base_id, server_coll_id FROM bas';
      $stmt = $conn->prepare($sql);
      $stmt->execute();
      $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $stmt->closeCursor();

      foreach ($rs as $row)
      {
        self::$_bas2coll[$row['base_id']] = (int) $row['server_coll_id'];
      }
    }

    return isset(self::$_bas2coll[$base_id]) ? self::$_bas2coll[$base_id] : false;
  }

  public static function sbas_names($sbas_id)
  {
    if (!self::$_sbas_names)
    {
      $appbox = appbox::get_instance();
      try
      {
        self::$_sbas_names = $appbox->get_data_from_cache(self::CACHE_SBAS_NAMES);
      }
      catch (Exception $e)
      {
        $sql = 'SELECT sbas_id, viewname, dbname FROM sbas';
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row)
        {
          $row['viewname'] = trim($row['viewname']);
          self::$_sbas_names[$row['sbas_id']] = $row['viewname'] ? $row['viewname'] : $row['dbname'];
        }
        $appbox->set_data_to_cache(self::$_sbas_names, self::CACHE_SBAS_NAMES);
      }
    }

    return isset(self::$_sbas_names[$sbas_id]) ? self::$_sbas_names[$sbas_id] : 'Unknown base';
  }

  public static function bas_names($base_id)
  {
    if (!self::$_bas_names)
    {
      $appbox = appbox::get_instance();
      try
      {
        self::$_bas_names = $appbox->get_data_from_cache(self::CACHE_BAS_NAMES);
      }
      catch (Exception $e)
      {
        foreach ($appbox->get_databoxes() as $databox)
        {
          foreach ($databox->get_collections() as $collection)
          {
            self::$_bas_names[$collection->get_base_id()] = $collection->get_name();
          }
        }

        $appbox->set_data_to_cache(self::$_bas_names, self::CACHE_BAS_NAMES);
      }
    }

    return isset(self::$_bas_names[$base_id]) ? self::$_bas_names[$base_id] : 'Unknown collection';
  }

  public static function redirect($url)
  {
    header("Location: $url");
    exit;
  }

  public static function headers($code = 200, $nocache=false, $content='text/html', $charset='UTF-8', $doctype=true)
  {
    switch ((int) $code)
    {
      case 204:
      case 403:
      case 404:
      case 400:
      case 500:
        $request = http_request::getInstance();
        if ($request->is_ajax())
        {
          exit(sprintf('error %d : Content unavailable', (int) $code));
        }
        else
        {
          $request->set_code($code);
          include(dirname(__FILE__) . '/../../www/include/error.php');
        }
        die();
        break;

      case 200:
        header("Content-Type: " . $content . "; charset=" . $charset);
        if ($nocache)
        {
          header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
          header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // always modified
          header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
          header("Cache-Control: post-check=0, pre-check=0", false);
          header("Pragma: no-cache");                          // HTTP/1.0
        }
        if ($doctype)
          echo '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">';
        break;
    }

    return;
  }

  public static function scheduler_key($renew = false)
  {
    $conn = connection::getPDOConnection();

    $schedulerkey = false;

    $sql = 'SELECT schedulerkey FROM sitepreff';

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if ($row)
    {
      $schedulerkey = trim($row['schedulerkey']);
    }

    if ($renew === true || $schedulerkey == '')
    {
      $schedulerkey = random::generatePassword(20);
      $sql = 'UPDATE sitepreff SET schedulerkey = :scheduler_key';
      $stmt = $conn->prepare($sql);
      $stmt->execute(array(':scheduler_key' => $schedulerkey));
      $stmt->closeCursor();
    }

    return $schedulerkey;
  }

}
