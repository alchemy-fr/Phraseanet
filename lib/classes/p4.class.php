<?php

class p4
{

  public static function fullmkdir($path, $depth=0)
  {
    clearstatcache();
    if (!is_dir($path))
    {
      $p = dirname($path);
      if ($p != "\\" && $p != "/" && $p != "." && $depth < 40)
        self::fullmkdir($p, $depth + 1);
      if (!is_dir($path))
      {
        mkdir($path);
        if (is_dir($path) && defined('GV_filesGroup') && defined('GV_filesOwner'))
        {
          if (trim(GV_filesGroup) !== '' && function_exists('chgrp'))
            chgrp($path, GV_filesGroup);
          if (trim(GV_filesOwner) !== '' && function_exists('chown'))
            chown($path, GV_filesOwner);
          self::chmod($path);
        }
      }
    }
    return is_dir($path);
  }

  public static function chmod($path)
  {
    if (function_exists('chmod'))
    {
      if (is_dir($path))
        chmod($path, 0755);
      if (is_file($path))
        chmod($path, 0766);
    }
    return true;
  }

  public static function getHttpCodeFromUrl($url)
  {
    $result = false;
    if (function_exists('curl_init'))
    {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
      curl_setopt($ch, CURLOPT_NOBODY, true);
      curl_setopt($ch, CURLOPT_HEADER, true);

      curl_exec($ch);

      $result = curl_getinfo($ch, CURLINFO_HTTP_CODE);

      curl_close($ch);
    }
    else
    {
      $opts = array(
          'http' => array(
              'method' => "HEAD"
          )
      );

      $result = false;

      $context = stream_context_create($opts);
      $stream = fopen($url, 'r', false, $context);

      $datas = stream_get_meta_data($stream);

      if (isset($datas['wrapper_data']))
      {
        $datas = $datas['wrapper_data'];
        foreach ($datas as $value)
        {
          preg_match('/HTTP\/[0-9\.]+.*([0-9]{3}).*[a-zA-Z]+/', $value, $matches);
          if (is_array($matches) && isset($matches[1]) && strlen($matches[1]) == 3)
          {
            $result = $matches[1];
            break;
          }
        }
      }

      fclose($stream);
    }
    return $result;
  }

  public static function getUrl($url, $post_data=false)
  {
    $result = false;
    if (function_exists('curl_init'))
    {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_HEADER, false);
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);

      if ($post_data)
      {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
      }

      $result = (curl_exec($ch));
      curl_close($ch);
    }
    else
    {
      $result = file_get_contents($url);
    }
    return $result;
  }

  public static function checkUpdate()
  {
    $url = 'http://update.alchemyasp.com/';
    $ret = false;

    $infos = json_decode(self::getUrl($url));

    if (isset($infos->version) && isset($infos->sha256) && isset($infos->url))
    {
      if (version_compare(GV_version, $infos->version, '<'))
      {
        $archivefile = GV_RootPath . 'tmp/' . time() . '__update_' . $infos->version . '.zip';
        $archiveunzipped = GV_RootPath . 'tmp/' . 'update_' . $infos->version . '/';


        if (is_dir($archiveunzipped))
          return false;

        $zip = self::getUrl($infos->url);

        if ($zip !== false)
        {

          $dir = opendir(GV_RootPath . 'tmp/');
          while (($file = readdir($dir)) !== false)
          {
            if ($file != '.' && $file != '..' && is_dir(GV_RootPath . 'tmp/' . $file))
            {
              preg_match_all('/(update_[0-9]{1}\.[0-9]{1}\.[0-9]{1})/', $file, $matches);
              if (isset($matches[0]) && isset($matches[0][0]))
              {
                p4::rmdir(GV_RootPath . 'tmp/' . $file);
              }
            }
          }

          $archive = fopen('file://' . $archivefile, 'w');
          fwrite($archive, $zip);
          fclose($archive);

          self::chmod($archivefile);

          if (hash_file('sha256', $archivefile) === $infos->sha256)
          {
            if (self::unzip($archivefile, $archiveunzipped))
              $ret = $archiveunzipped;
          }
          unlink($archivefile);
        }
        elseif (GV_debug)
        {
          echo 'impossible de telecharger le zip\n\n';
        }
      }
      elseif (GV_debug)
      {
        echo "version compare donne une version plutot bonne\n\n";
      }
    }
    elseif (GV_debug)
    {
      echo 'Manque des elements sur le webservice\n\n';
      var_dump($infos);
    }
    return $ret;
  }

  public static function unzip($zipfile, $dest)
  {

    $fzip = zip_open($zipfile);
    $ret = true;

    if (!is_dir($dest))
    {
      if (!mkdir($dest, 0755, true))
        $ret = false;
    }
    while ($zip_read = zip_read($fzip))
    {
      $zip_content = zip_entry_name($zip_read);

      $c = substr($zip_content, -1, 1);
      $path_dest = $dest . $zip_content;

      if ($c != "/" && $c != "\\")
      {
        $path_dest_hand = fopen('file://' . $path_dest, 'w+');
        while (($entry = zip_entry_read($zip_read)) !== false && $entry !== '')
        {
          if (!fwrite($path_dest_hand, $entry))
            $ret = false;
        }
        fclose($path_dest_hand);
      }
      else
      {
        if (!mkdir($path_dest, 0755, true))
          $ret = false;
      }
    }
    return $ret;
  }

  public static function copyUpdate($source, $dest)
  {
    $result = false;

    if (is_file($source))
    {
      if (is_dir($dest))
        $__dest = p4string::addEndSlash($dest) . basename($source);
      else
        $__dest = $dest;

      $result = copy($source, $__dest);
      self::chmod($__dest);
      unlink($source);
    }
    elseif (is_dir($source))
    {
      if (!is_dir($dest))
      {
        @mkdir($dest, $folderPermission);
        self::chmod($dest);
      }

      $source = p4string::addEndSlash($source);
      $dest = p4string::addEndSlash($dest);

      $result = true;
      $dirHandle = opendir($source);
      while ($file = readdir($dirHandle))
      {
        if ($file != "." && $file != "..")
          $result = self::copyUpdate($source . $file, $dest . $file);
      }
      closedir($dirHandle);
      rmdir($source);
    }
    else
    {
      $result = false;
    }
    return $result;
  }

  public static function rmdir($source)
  {
    $result = false;

    if (is_file($source))
    {
      unlink($source);
      $result = true;
    }
    elseif (is_dir($source))
    {
      $result = true;
      $dirHandle = opendir($source);
      while ($file = readdir($dirHandle))
      {
        if ($file != "." && $file != "..")
          $result = self::rmdir($source);
      }
      closedir($dirHandle);
      rmdir($source);
    }
    else
    {
      $result = false;
    }
    return $result;
  }

  public static function doUpdate($folder)
  {

    set_time_limit(300);
    if (self::copyUpdate($folder, GV_RootPath) !== true)
      return false;

    $appb = new appbox();

    if ($appb->upgradeAvalaible())
    {
      $appb->upgradeDB();
    }

    $sbas = $appb->getSbas();
    foreach ($sbas as $s)
    {
      if ($s->upgradeAvalaible())
        $s->upgradeDB();
    }

    self::rmdir($folder);

    return true;
  }

  public static function checkBeforeUpgrade()
  {
    $conn = connection::getInstance();
    $sql = 'SELECT schedstatus FROM sitepreff';
    if ($rs = $conn->query($sql))
    {
      if ($row = $conn->fetch_assoc($rs))
      {
        if ($row['schedstatus'] != 'stopped')
        {
          return array(_('Veuillez arreter le planificateur avant la mise a jour'));
        }
      }
      $conn->free_result($rs);
    }
    return true;
  }

  public static function empty_directory($origine, $delete_origine = true)
  {
    $origine = p4string::addEndSlash($origine);
    
    $dirs = array();
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($origine), RecursiveIteratorIterator::LEAVES_ONLY) as $file)
    {
      $pathfile = $file->getRealPath();
      if (substr($file->getFilename(), 0, 1) == '.' || strpos($pathfile, '.svn') !== false)
      {
        continue;
      }
      $path = p4string::addEndSlash($file->getPath());
      if($delete_origine || $path != $origine)
        $dirs[$path] = $path;
      unlink($pathfile);
    }

    arsort($dirs);

    foreach ($dirs as $dir)
      rmdir($dir);
  }

  public static function forceUpgrade()
  {
    $ret = false;
    $appb = new appbox();

    skins::delete_skins_files();
    self::empty_directory(GV_RootPath . 'tmp/cache_minify/', false);
    self::empty_directory(GV_RootPath . 'tmp/cache_twig/', false);
    skins::merge();
    self::copy_custom_files();

    if ($appb->upgradeDB())
    {

      $sbas = $appb->getSbas();
      foreach ($sbas as $s)
      {
        $s->upgradeDB();
      }
      $ret = true;
    }

    $cache = cache::getInstance();

    if ($cache->is_ok())
    {
      $cache->flush();
    }

    return $ret;
  }

  private static function copy_custom_files()
  {

    $origine = GV_RootPath . 'config/custom_files/';
    $dest = GV_RootPath . 'www/custom/';

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($origine), RecursiveIteratorIterator::LEAVES_ONLY) as $file)
    {
      if (substr($file->getFilename(), 0, 1) == '.' || strpos($file->getRealPath(), '.svn') !== false)
        continue;

      $dest_file = str_replace($origine, $dest, $file->getRealPath());
      $dest_dir = dirname($dest_file);
      if (!is_dir($dest_dir))
        self::fullmkdir($dest_dir);
      copy($file->getRealPath(), $dest_file);
      self::chmod($dest_file);
    }
  }

  public static function signOnAPI($login, $password)
  {
    $session = session::getInstance();

    $usr_id = false;
    $error = 'bad';
    $conn = connection::getInstance();

    $sql = 'SELECT usr_id, usr_login FROM usr WHERE usr_login="' . $conn->escape_string($login) . '" AND usr.usr_password="' . $conn->escape_string(hash('sha256', $conn->escape_string($password))) . '" AND usr_login != "invite" AND usr_login != "autoregister" AND model_of="0" AND invite="0"';

    if ($rs = $conn->query($sql))
    {
      if ($row = $conn->fetch_assoc($rs))
      {
        $usr_id = $row['usr_id'];
        $login = $row['usr_login'];
      }
      $conn->free_result($rs);
    }

    if ($usr_id)
    {
      $error = false;
      if (!self::authenticate($usr_id))
        $error = 'session';
      else
      {
        $session->invite = false;
      }
    }

    return array('error' => $error, 'usr_id' => $usr_id);
  }

  public static function signOn($login, $password, $captcha)
  {

    $session = session::getInstance();

    $error = $usr_id = false;

    $conn = connection::getInstance();

    $theclient = browser::getInstance();
    $ip = $theclient->getIP();

    $sql = 'SELECT id FROM badlog WHERE (login="' . $conn->escape_string($login) . '" OR ip="' . $conn->escape_string($ip) . '") AND locked="1"';

    if ($rs = $conn->query($sql))
    {
      if (($conn->num_rows($rs)) > 0)
      {
        if ($captcha)
        {
          $sql = 'UPDATE badlog SET locked="0" WHERE (login="' . $conn->escape_string($login) . '" OR ip="' . $conn->escape_string($ip) . '")';
          $conn->query($sql);
        }
        elseif (($conn->num_rows($rs)) > 9)
        {
          $error = 'bad';
          if (GV_captchas && trim(GV_captcha_private_key) !== '' && trim(GV_captcha_public_key) !== '')
            $error = 'captcha';
        }
      }
      $conn->free_result($rs);
    }



    $sql = 'SELECT usr_id, canchgprofil FROM usr WHERE usr_login="' . $conn->escape_string($login) . '" AND usr.usr_password="' . $conn->escape_string(hash('sha256', $password)) . '" AND usr_login NOT IN ("invite","autoregister") AND model_of="0" AND invite="0"';

    if ($rs = $conn->query($sql))
    {
      if (($conn->num_rows($rs)) == 0)
      {

        $sql = 'DELETE FROM badlog WHERE  date < "' . date('Y-m-d H:i:s', mktime(0, 0, 0, date("m") - 1, date("d"), date("Y"))) . '"';
        $conn->query($sql);

        if (GV_captchas && trim(GV_captcha_private_key) !== '' && trim(GV_captcha_public_key) !== '')
        {
          $sql = 'INSERT INTO badlog (date,login,pwd,ip,locked) VALUES (NOW(),"' . $conn->escape_string($login) . '","' . $conn->escape_string($password) . '","' . $conn->escape_string($ip) . '","1")';
          $conn->query($sql);
        }

        $sql = 'SELECT login FROM badlog WHERE (login="' . $conn->escape_string($login) . '" OR ip="' . $conn->escape_string($ip) . '") AND date >= "' . date('Y-m-d H:i:s', mktime(date("H"), date("i") - 10, date("s"), date("m") - 1, date("d"), date("Y"))) . '" AND locked="1"';

        $error = 'bad';
        $mail = '';
        if (GV_captchas && trim(GV_captcha_private_key) !== '' && trim(GV_captcha_public_key) !== '' && ($rs = $conn->query($sql)))
        {
          if (($conn->num_rows($rs)) > 9)
          {
            $error = 'captcha';

            if ($rs2 = $conn->query('SELECT * FROM badlog WHERE login="' . $conn->escape_string($login) . '"'))
            {
              while ($row = $conn->fetch_assoc($rs2))
                $mail .= '<div>bad log FROM ' . $row['ip'] . ' --- tried login "' . $row['login'] . '" AND password "' . $row['pwd'] . '" on "' . $row['date'] . '"' . "</div>\n";
              $conn->free_result($rs2);
            }
          }
          $conn->free_result($rs);
        }

        if ($mail != '' && trim(GV_adminMail) != '')
        {
          mail::hack_alert(GV_adminMail, $mail);
        }
      }
      elseif ($row = $conn->fetch_assoc($rs))
      {
        $usr_id = $row['usr_id'];
      }
      $conn->free_result($rs);
    }

    $sql = 'SELECT mail_locked, usr_id FROM usr WHERE usr_login="' . $conn->escape_string($login) . '" AND usr.usr_password="' . $conn->escape_string($password) . '"';
    if ($rs = $conn->query($sql))
    {
      if ($row = $conn->fetch_assoc($rs))
      {
        if ($row['mail_locked'] == "1")
        {
          $error = 'mail_lock';
          $usr_id = $row['usr_id'];
        }
      }
      $conn->free_result($rs);
    }

    if (!$error)
    {
      $session->invite = false;

      $transferBasks = false;

      if (isset($session->postlog) && $session->postlog)
        $transferBasks = true;

      if ($transferBasks && $session->isset_cookie('invite-usr_id'))
      {
        $basks = array();
        $oldusr = $session->get_cookie('invite-usr_id');

        $sql = 'SELECT sselcont_id, s.ssel_id FROM sselcont c, ssel s WHERE s.usr_id="' . $conn->escape_string($oldusr) . '" and s.ssel_id = c.ssel_id';

        if ($rs = $conn->query($sql))
        {
          while ($row = $conn->fetch_assoc($rs))
            $basks[] = $row['ssel_id'];
          $conn->free_result($rs);
        }
        foreach ($basks as $ssel_id)
        {
          $sql = 'UPDATE ssel set usr_id = "' . $conn->escape_string($usr_id) . '" WHERE ssel_id="' . $conn->escape_string($ssel_id) . '" AND usr_id ="' . $conn->escape_string($oldusr) . '"';
          if ($conn->query($sql))
          {
            
          }
        }
        if ($usr_id != $oldusr)
        {
          $sql = 'DELETE FROM ssel WHERE usr_id = "' . $conn->escape_string($oldusr) . '"';
          $conn->query($sql);
        }

        $sql = 'UPDATE dsel SET usr_id="' . $conn->escape_string($usr_id) . '" WHERE usr_id="' . $conn->escape_string($oldusr) . '"';
        $conn->query($sql);

        $sql = 'DELETE FROM usr WHERE usr_id = "' . $conn->escape_string($oldusr) . '"';
        $conn->query($sql);
        $sql = 'DELETE FROM basusr WHERE usr_id = "' . $conn->escape_string($oldusr) . '"';
        $conn->query($sql);
        $sql = 'DELETE FROM sbasusr WHERE usr_id = "' . $conn->escape_string($oldusr) . '"';
        $conn->query($sql);

        $session->set_cookie('invite-usr_id', $usr_id, -400000, true);
        $session->set_cookie('invite-hash', hash('sha256', $password), -400000, true);
      }

      if (!$transferBasks)
        $session->set_cookie('last_act', '', -400000, true);

      if (!self::authenticate($usr_id))
        $error = 'session';
    }

    return array('error' => $error, 'usr_id' => $usr_id);
  }

  public static function signOnWithToken($token)
  {

    $session = session::getInstance();
    $error = $usr_id = false;

    $datas = random::helloToken($token);
    if (!$datas)
      $error = 'wrong-token';
    else
    {

      $usr_id = $datas['usr_id'];
      if ((int) $usr_id > 0)
      {
        $session->invite = true;

        $conn = connection::getInstance();

        $sql = 'SELECT usr_login FROM usr WHERE usr_id="' . $conn->escape_string($usr_id) . '" AND invite="0" AND usr_login!="invite" AND usr_login !="autoregister"';

        if ($rs = $conn->query($sql))
        {
          if ($row = $conn->fetch_assoc($rs))
          {
            $session->invite = false;
          }
        }

        if (!self::authenticate($usr_id))
          $error = 'session';
      }
    }


    return array('error' => $error, 'usr_id' => $usr_id);
  }

  public static function signOnasGuest()
  {
    $session = session::getInstance();

    $conn = connection::getInstance();

    $usr_id = $error = false;
    $invite_modtime = false;
    $inviteUsrid = false;

    $sql = 'SELECT usr_id, UNIX_TIMESTAMP(usr_modificationdate) as t_time FROM usr WHERE usr_login="' . $conn->escape_string('invite') . '"';
    if ($rs = $conn->query($sql))
    {
      if ($row = $conn->fetch_assoc($rs))
      {
        $inviteUsrid = $row['usr_id'];
        $invite_modtime = $row['t_time'];
      }
      $conn->free_result($rs);
    }

    if ($session->isset_cookie('invite-usr_id') && $session->isset_cookie('invite-hash'))
    {
      if ($session->isset_cookie('invite-time'))
      {
        $reload_privileges = false;

        $date = new DateTime("@" . (int) $session->get_cookie('invite-time'));

        if ($invite_modtime != phraseadate::format_mysql($date))
        {
          $reload_privileges = true;
        }
      }
      else
        $reload_privileges = true;


      $sql = 'SELECT usr_id, usr_password FROM usr WHERE invite="1" AND usr_login="' . $conn->escape_string('invite' . $session->get_cookie('invite-usr_id')) . '"';

      if ($rs = $conn->query($sql))
      {
        if (($conn->num_rows($rs)) == 1)
        {
          $row = $conn->fetch_assoc($rs);

          if (hash('sha256', $row['usr_password']) == $session->get_cookie('invite-hash'))
          {
            $login = 'invite' . $session->get_cookie('invite-usr_id');
            $usr_id = $row['usr_id'];
            $password = $row['usr_password'];

            if ($reload_privileges)
            {
              $conn->query('DELETE FROM basusr WHERE usr_id = "' . $usr_id . '"');
              $conn->query('DELETE FROM sbasusr WHERE usr_id = "' . $usr_id . '"');
              $conn->query("INSERT INTO basusr (SELECT null as id, base_id, '" . $conn->escape_string($usr_id) . "' as usr_id, canpreview, canhd, canputinalbum, candwnldhd, candwnldsubdef, candwnldpreview, cancmd, canadmin, actif, canreport, canpush, creationdate, basusr_infousr, mask_and, mask_xor, restrict_dwnld, month_dwnld_max, remain_dwnld, time_limited, limited_from, limited_to, canaddrecord, canmodifrecord, candeleterecord, chgstatus, lastconn, imgtools, manage, modify_struct, bas_manage, bas_modify_struct, needwatermark FROM basusr WHERE usr_id='" . $conn->escape_string($inviteUsrid) . "')");
              $conn->query("INSERT INTO sbasusr (SELECT null as sbasusr_id, sbas_id, '" . $conn->escape_string($usr_id) . "' as usr_id, bas_manage, bas_modify_struct, bas_modif_th, bas_chupub FROM sbasusr WHERE usr_id='" . $conn->escape_string($inviteUsrid) . "')");
            }
          }
        }
      }
    }
    if (!$usr_id)
    {
      $usr_id = $conn->getId("USR");
      $login = 'invite' . $usr_id;
      $password = random::generatePassword();

      $conn->query("INSERT INTO usr (usr_id, usr_login, usr_password,model_of,usr_creationdate,invite) values ('" . $conn->escape_string($usr_id) . "', '" . $conn->escape_string($login) . "', '" . $conn->escape_string($password) . "',0,now(),'1')");
      $conn->query("INSERT INTO basusr (SELECT null as id, base_id, '" . $conn->escape_string($usr_id) . "' as usr_id, canpreview, canhd, canputinalbum, candwnldhd, candwnldsubdef, candwnldpreview, cancmd, canadmin, actif, canreport, canpush, creationdate, basusr_infousr, mask_and, mask_xor, restrict_dwnld, month_dwnld_max, remain_dwnld, time_limited, limited_from, limited_to, canaddrecord, canmodifrecord, candeleterecord, chgstatus, lastconn, imgtools, manage, modify_struct, bas_manage, bas_modify_struct, needwatermark FROM basusr WHERE usr_id='" . $conn->escape_string($inviteUsrid) . "')");
      $conn->query("INSERT INTO sbasusr (SELECT null as sbasusr_id, sbas_id, '" . $conn->escape_string($usr_id) . "' as usr_id, bas_manage, bas_modify_struct, bas_modif_th, bas_chupub FROM sbasusr WHERE usr_id='" . $conn->escape_string($inviteUsrid) . "')");
    }

    if ($usr_id)
    {
      $session->invite = true;
      $expire = 30 * 24 * 3600;
      $session->set_cookie('invite-usr_id', $usr_id, $expire, true);
      $session->set_cookie('invite-hash', hash('sha256', $password), $expire, true);
      $session->set_cookie('invite-time', $invite_modtime, $expire, true);

      if (!self::authenticate($usr_id))
        $error = 'session';
      else
        $session->set_cookie('last_act', '', -400000, true);
    }
    else
      $error = 'Error';


    return array('error' => $error, 'usr_id' => $usr_id);
  }

  public static function logout($ses_id = false)
  {
    $conn = connection::getInstance();
    $session = session::getInstance();

    if(!$ses_id)
      $ses_id = $session->ses_id;

    phrasea_close_session($ses_id);

    $session->destroy();
    return true;
  }

  /*
   *
   * This function is activated everytime somebody logins and make several checks
   *
   */

  private function auto_batch()
  {
    $conn = connection::getInstance();

    $sql = "SELECT session_id FROM cache WHERE lastaccess < DATE_SUB(NOW(), INTERVAL 48 HOUR)";
    if ($rs = $conn->query($sql))
    {
      while ($row = $conn->fetch_assoc($rs))
      {
        phrasea_close_session($row['session_id']);
      }
      $conn->free_result($rs);
    }

    if (defined('GV_validation_reminder'))
    {
      $date_two_day = new DateTime('+' . (int) GV_validation_reminder . ' days');

      $events_mngr = eventsmanager::getInstance();
      //Je veux les validations en cours dont la date de fin est dans les 48heures et dont vers laquelle il n'est pas encore parti de mail
      $sql = 'SELECT v.id as validate_id, v.usr_id, v.ssel_id, s.usr_id as owner, t.value
					FROM (validate v, ssel s) LEFT JOIN tokens t ON (t.datas = s.ssel_id AND v.usr_id=t.usr_id AND t.type="validate")  
					WHERE expires_on < "' . $conn->escape_string(phraseadate::format_mysql($date_two_day)) . '"
					AND ISNULL(last_reminder) AND confirmed="0" AND s.ssel_id = v.ssel_id ';

      if ($rs = $conn->query($sql))
      {
        while ($row = $conn->fetch_assoc($rs))
        {
          $params = array(
              'to' => $row['usr_id'],
              'ssel_id' => $row['ssel_id'],
              'from' => $row['owner'],
              'validate_id' => $row['validate_id'],
              'url' => GV_ServerName . 'lightbox/?LOG=' . $row['value']
          );

          $events_mngr->trigger('__VALIDATION_REMINDER__', $params);
        }
        $conn->free_result($rs);
      }
    }
  }

  static function authenticate($usr_id)
  {
    if (GV_maintenance)
      return false;

    $session = session::getInstance();

    if (file_exists(GV_RootPath . "config/prelog.php"))
      include(GV_RootPath . "config/prelog.php");

    $session = session::getInstance();
    $conn = connection::getInstance();
    $theclient = browser::getInstance();
    $ip = $theclient->getIP();

    $admin = false;
    $upload = false;
    $thesaurus = false;
    $report = false;
    $userPrefs = false;
    $userRegis = null;
    $ses_id = $locale = false;
    $bases_logged = array();
    $sbases = array();

    $fonction = $societe = $activite = $pays = '';

    $session->account_editor = false;

    self::auto_batch();

    $sql = 'SELECT usr_id, create_db, desktop, locale, usr_login, usr_mail, canchgprofil, fonction, societe, activite, pays FROM usr WHERE usr_id="' . $conn->escape_string($usr_id) . '"';

    if ($rs = $conn->query($sql))
    {
      if ($row = $conn->fetch_assoc($rs))
      {
        if (($ses_id = phrasea_create_session((int) $row['usr_id'])) !== false)
        {
          if ($row['create_db'] === '1')
            $admin = true;
          $userPrefs = $row['desktop'];
          $locale = $row['locale'];
          $login = $session->login = $row['usr_login'];
          $session->email = $row['usr_mail'];
          if ($row['canchgprofil'] == '1' && $session->invite === false)
            $session->account_editor = true;

          $fonction = $row['fonction'];
          $societe = $row['societe'];
          $activite = $row['activite'];
          $pays = $row['pays'];
        }
      }
    }
    if (!$ses_id || (int) $ses_id <= 0)
      return false;

    $sql = 'SELECT bas.sbas_id, dbname, bas.server_coll_id, basusr.canaddrecord, bas.base_id,usr.usr_id, basusr.canadmin, basusr.canreport,
		 basusr.manage, sbasusr.bas_manage, basusr.modify_struct, sbasusr.bas_modify_struct, sbasusr.bas_modif_th, mask_and, mask_xor, restrict_dwnld, basusr.id AS basusrid
		 FROM (usr INNER JOIN basusr
		 ON usr.usr_id="' . $conn->escape_string($usr_id) . '"
		 AND usr.usr_id=basusr.usr_id AND model_of=0  AND actif=1)
		 INNER JOIN ( bas  INNER JOIN sbas ON sbas.sbas_id=bas.sbas_id )
		 ON (bas.active>0 AND bas.base_id=basusr.base_id)
		 AND (time_limited=0 OR ( limited_from<NOW() AND limited_to>NOW() ) )
		 INNER JOIN sbasusr ON (bas.sbas_id=sbasusr.sbas_id AND sbasusr.usr_id=usr.usr_id)
		 ORDER BY sbas.ord, sbas.sbas_id, bas.ord, bas.server_coll_id';

    if ($rs = $conn->query($sql))
    {
      $iord = 1;
      while ($row = $conn->fetch_assoc($rs))
      {

        if (!isset($sbases[$row['sbas_id']]))
        {
          $sbases[$row['sbas_id']] = array();
          $sbases[$row['sbas_id']]['colls'] = array();
        }
        $connbas = connection::getInstance($row['sbas_id']);
        if ($connbas)
        {
          $sql = sprintf("REPLACE INTO collusr (site, usr_id, coll_id, mask_and, mask_xor, ord) VALUES ('%s', %s, %s, '%s', '%s', %s)",
                          $connbas->escape_string(GV_sit),
                          $connbas->escape_string($usr_id),
                          $connbas->escape_string($row["server_coll_id"]),
                          $connbas->escape_string($row["mask_and"]),
                          $connbas->escape_string($row["mask_xor"]),
                          $connbas->escape_string($iord++)
          );
          $connbas->query($sql);

          $sql = 'REPLACE INTO clients (site_id) VALUES ("' . $connbas->escape_string(GV_ServerName) . '")';
          $connbas->query($sql);

          if (phrasea_register_base($ses_id, $row["base_id"], "", "") === true)
          {

            if (!isset($userRegis[$row['dbname']]))
              $userRegis[$row['dbname']] = null;
            $userRegis[$row['dbname']][$row['server_coll_id']] = false;

            $sbases[$row['sbas_id']]['colls'][] = $row['server_coll_id'];

            if ($row['canreport'] == '1')
            {
              if (!isset($report[$row['sbas_id']]))
                $report[$row['sbas_id']] = array();
              $report[$row['sbas_id']][$row['server_coll_id']] = $row['base_id'];
            }
            if ($row['canaddrecord'] == '1')
            {
              if (!isset($upload[$row['sbas_id']]))
                $upload[$row['sbas_id']] = array();
              $upload[$row['sbas_id']][$row['server_coll_id']] = $row['base_id'];
            }
            if ($row['canadmin'] == '1' || $row['manage'] == '1' || $row['bas_manage'] == '1' || $row['modify_struct'] == '1' || $row['bas_modify_struct'] == '1')
              $admin = true;
            if ($row['bas_modif_th'] == '1')
              $thesaurus = true;

            if ($row["restrict_dwnld"] && ($row["moiscourant"] != $row["moislastconn"]))
            {
              $conn->query("UPDATE basusr SET remain_dwnld='" . $conn->escape_string($row["month_dwnld_max"]) . "' WHERE id='" . $conn->escape_string($row["basusrid"]) . "'");
            }

            $conn->query("UPDATE basusr SET lastconn=now() WHERE id='" . $conn->escape_string($row["basusrid"]) . "'");

            $bases_logged[] = $row["base_id"];
          }
        }
      }
    }
    $logs = array();

    foreach ($sbases as $sbas => $props)
    {
      $connbas = connection::getInstance($sbas);
      if ($connbas)
      {
        $newid = $connbas->getId("LOG");

        $screen = $session->isset_cookie('screen') ? $session->get_cookie('screen') : 'unknown';

        $browser_version = $theclient->getVersion() . ($theclient->isChromeFrame() ? ' ChromeFrame' : '');

        $sql = "INSERT INTO log
				(id, date,sit_session, user, site, usrid,coll_list, nav, version, os, res, ip, user_agent,appli, fonction, societe, activite, pays) VALUES ";
        $sql .= "('" . $connbas->escape_string($newid) . "',now() ,'" . $connbas->escape_string($ses_id) . "','" . $connbas->escape_string($login) . "', '" . $connbas->escape_string(GV_sit) . "', '" . $connbas->escape_string($usr_id) . "','" . $connbas->escape_string(implode(',', $props['colls'])) . "', '" . $connbas->escape_string($theclient->getBrowser()) . "', '" . $connbas->escape_string($browser_version) . "', '" . $connbas->escape_string($theclient->getPlatform()) . "', '" . $connbas->escape_string($screen) . "','" . $connbas->escape_string($theclient->getIP()) . "','" . $connbas->escape_string($theclient->getUserAgent()) . "','" . $connbas->escape_string(serialize(array())) . "'
				,'" . $connbas->escape_string($fonction) . "','" . $connbas->escape_string($societe) . "','" . $connbas->escape_string($activite) . "','" . $connbas->escape_string($pays) . "')";

        if ($connbas->query($sql))
          $logs[$sbas] = $newid;
      }
    }

    $session->locale = isset($session->locale) ? $session->locale : $locale;

    $conn->query("UPDATE cache SET dist_logid='" . $conn->escape_string(serialize($logs)) . "' WHERE session_id='" . $conn->escape_string($ses_id) . "'");
    $conn->query('UPDATE usr SET last_conn=now(), locale="' . $conn->escape_string($session->locale) . '" WHERE usr_id = "' . $conn->escape_string($usr_id) . '"');


    if (isset($session->postlog))
      unset($session->postlog);


    $session->logs = $logs;
    $session->admin = $admin;
    $session->upload = $upload;
    $session->thesaurus = $thesaurus;
    $session->report = $report;
    $session->userPrefs = $userPrefs;
    $session->userRegis = $userRegis;
    $session->b_log = $bases_logged;
    $session->usr_id = $usr_id;
    $session->ses_id = $ses_id;
    $session->prod = array('push' => array(), 'query' => array('nba' => 0));
    $session->client = array();
    $session->lightbox = false;
    $session->prefs = array();
    $valNews = self::lightboxNews($usr_id);

    if ($valNews[0])
    {
      $session->lightbox = array('enabled' => true, 'new' => $valNews[1]);
    }

    return true;
  }

  public static function lightboxNews($usrid)
  {

    $conn = connection::getInstance();

    $sql = 'SELECT id, confirmed FROM validate WHERE usr_id ="' . $conn->escape_string($usrid) . '"';

    $validator_enabled = false;
    $new_thg_toval = 0;

    if ($rs = $conn->query($sql))
    {
      while ($row = $conn->fetch_assoc($rs))
      {
        $validator_enabled = true;
        if ($row['confirmed'] == "0")
        {
          $new_thg_toval++;
          break;
        }
      }
      $conn->free_result($rs);
    }

    return array($validator_enabled, $new_thg_toval);
  }

  public static function dispatch($repository_path, $date=false)
  {
    if (!$date)
      $date = date('Y-m-d H:i:s');

    $repository_path = p4string::addEndSlash($repository_path);

    $year = date('Y', strtotime($date));
    $month = date('m', strtotime($date));
    $day = date('d', strtotime($date));

    $n = 0;
    $comp = $year . '/' . $month . '/' . $day . '/';

    $condition = true;

    $pathout = $repository_path . $comp;

    while (($pathout = $repository_path . $comp . self::addZeros($n)) && is_dir($pathout) && self::more_than_limit_in_dir($pathout))
    {
      $n++;
    }
    if (!is_dir($pathout))
      self::fullmkdir($pathout);


    return p4string::addEndSlash($pathout);
  }

  private function more_than_limit_in_dir($path)
  {
    $limit = 1000;
    $n = 0;
    if (is_dir($path))
    {
      if ($hdir = opendir($path))
      {
        while ($file = readdir($hdir))
        {
          if ($file != '.' && $file != '..')
          {
            $n++;
          }
        }
      }
    }
    if ($n > $limit)
      return true;

    return false;
  }

  private function addZeros($n, $length = 4)
  {
    while (strlen($n) < $length)
      $n = '0' . $n;
    return $n;
  }

}