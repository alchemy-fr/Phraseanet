<?php

class connection
{

  private $private_connect = false;
  private $private_lockedtables; // tableau assoc. des bases lockees
  private static $_instance = array();
  private static $_query_counter = array();
  private $_id;
  private $_name = false;

  /**
   * @return connection
   */
  public static function getInstance($name='')
  {
    if (trim($name) == '')
    {
      $name = 'appbox';
    }
    elseif (is_int((int) $name))
    {
      $name = (int) $name;
    }
    else
      return false;

    if (!isset(self::$_instance[$name]))
    {
      $tmp = new connection($name);
      if ($tmp->isok())
      {
        self::$_instance[$name] = $tmp;
      }
    }


//		if(!self::$_instance[$name]->isok())
//		{
//			header("HTTP/1.0 500 Internal Server Error");
//			die('<h2>HTTP/1.0 500 Internal Server Error</h2><h2>Can\'t establish database connection<h2>');
//		}

    return array_key_exists($name, self::$_instance) ? self::$_instance[$name] : false;
  }

  function __construct($name)
  {

    $this->_name = $name;
    $hostname = $port = $user = $password = $dbname = false;

    $connection_params = array();

    if ($name == 'appbox')
    {
      require (dirname(__FILE__) . '/../../config/connexion.inc');
    }
    else
    {
      $connection_params = phrasea::sbas_params();
    }

    if (isset($connection_params[$name]))
    {
      $hostname = $connection_params[$name]['host'];
      $port = $connection_params[$name]['port'];
      $user = $connection_params[$name]['user'];
      $password = $connection_params[$name]['pwd'];
      $dbname = $connection_params[$name]['dbname'];
    }

    $this->private_lockedtables = null;
    if (($this->private_connect = @mysql_connect($hostname . ":" . $port, $user, $password, true)) !== false)
    {
      $this->_id = $name;

      if (defined('GV_debug') && GV_debug)
      {
        self::$_query_counter[$name] = 0;
      }
      $this->useBase($dbname);
    }
    else
    {
      //detruire l'entite creee
    }
  }

  function __destruct()
  {
    if (defined('GV_debug') && GV_debug && isset(self::$_query_counter[$this->_name]))
    {
      $error = ' fermeture connection  `' . $this->_name . '` -- ' . self::$_query_counter[$this->_name] . " queries\n";
      file_put_contents(GV_RootPath . 'logs/sql_log.log', $error, FILE_APPEND);
      logs::rotate(GV_RootPath . 'logs/sql_log.log');
    }
    return;
  }

  function useBase($dbname)
  {
    if ($dbname)
    {
      if (!@mysql_select_db($dbname, $this->private_connect))
      {
        @mysql_close($this->private_connect);
        $this->private_connect = false;
      }
      else
      {
        mysql_set_charset('utf8', $this->private_connect);
        mysql_query("SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'", $this->private_connect);
      }
    }
  }

  function server_info()
  {
    if ($this->private_connect)
    {
      return mysql_get_server_info($this->private_connect);
    }
    return false;
  }

  function insert_id()
  {
    if ($this->private_connect)
    {
      return mysql_insert_id($this->private_connect);
    }
    return(false);
  }

  function ping()
  {
    if ($this->private_connect)
    {
      return(mysql_ping($this->private_connect));
    }
    return(false);
  }

  function start_transaction()
  {
    if ($this->private_connect)
    {
      if (mysql_query('SET AUTOCOMMIT=0;', $this->private_connect))
        return(mysql_query('START TRANSACTION;', $this->private_connect));
      else
        return false;
    }
    return(false);
  }

  function commit()
  {
    if ($this->private_connect)
    {
      mysql_query('COMMIT;', $this->private_connect);
      mysql_query('SET AUTOCOMMIT=1;', $this->private_connect);
      return;
    }
    return(false);
  }

  function rollback()
  {
    if ($this->private_connect)
    {
      mysql_query('ROLLBACK;', $this->private_connect);
      mysql_query('SET AUTOCOMMIT=1;', $this->private_connect);
      return;
    }
    return(false);
  }

  function getBases()
  {
    $r = array();
    $requiredtables = array("COLL", "IDX", "KWORD", "PROP", "RECORD", "UIDS", "XPATH");
    if ($rs = $this->query("SHOW DATABASES;"))
    {
      while ($row = $this->fetch_assoc($rs))
      {
        if ($rst = $this->query("SHOW TABLE STATUS FROM " . $row["Database"] . ";"))
        {
          $ntablesok = 0;
          while ($rowt = $this->fetch_assoc($rst))
          {
            if (in_array(strtoupper($rowt["Name"]), $requiredtables))
              $ntablesok++;
          }
          $this->free_result($rst);
          if ($ntablesok == count($requiredtables))
            $r[] = $row["Database"];
        }
      }
      $this->free_result($rs);
    }
    return $r;
  }

  function lockTables($tables)
  {
    if ($this->private_lockedtables !== null)
      return(false); // il faut unlocker avant de pouvoir re-locker
 $this->private_lockedtables = array();
    if (!is_array($tables))
      $tables = array($tables);
    $sql = "";
    foreach ($tables as $t)
    {
      $sql .= ( $sql == "" ? "" : ", ") . $t . " WRITE";
      $this->private_lockedtables[mb_strtolower($t)] = true;
    }
    if ($this->query("LOCK TABLES $sql"))
      return(true);
    // si on arrive ici, c'est que qq chose a rate...
    $this->private_lockedtables = null;
    return(false);
  }

  function unlockTables()
  {
    if ($this->private_lockedtables === null)
      return(false); // rien a unlocker
 if ($this->query("UNLOCK TABLES"))
    {
      $this->private_lockedtables = null;
      return(true);
    }
    return(false);
  }

  function getId($typeId, $askfor_n=1)
  {
    $x = null;
    $lockedbyme = false;
    if ((is_array($this->private_lockedtables) && isset($this->private_lockedtables["uids"]) && $this->private_lockedtables["uids"]) || ($lockedbyme = $this->lockTables("uids")))
    {
      if ($this->query("UPDATE uids SET uid=uid+$askfor_n WHERE name='$typeId'")) //incremente
      {
        if ($result = $this->query("SELECT uid FROM uids WHERE name='$typeId'"))
        {
          if ($row = $this->fetch_assoc($result))
            $x = ($row["uid"] - $askfor_n) + 1;
          $this->free_result($result);
        }
      }
      if ($lockedbyme)
        $this->unlockTables();
    }
    return $x;
  }

  function isok()
  {
    return($this->private_connect);
  }

  function affected_rows()
  {
    if ($this->private_connect)
    {
      return(mysql_affected_rows($this->private_connect));
    }
    return(false);
  }

  function close()
  {
    if ($this->private_connect)
    {
      if (mysql_close($this->private_connect))
      {
        $this->private_connect = false;
      }
    }
    unset(self::$_instance[$this->_name]);
    return(false);
  }

  function query($sql)
  {
    if ($this->private_connect)
    {
      if (defined('GV_debug') && GV_debug)
      {
        self::$_query_counter[$this->_id]++;
        $res = mysql_query($sql, $this->private_connect);

        $date_obj = new DateTime();
        $error = $date_obj->format(DATE_ATOM) . ' :: `' . $this->_id . '` ' . $sql . ' -- ' . mysql_error($this->private_connect) . "\n";
        file_put_contents(GV_RootPath . 'logs/sql_log.log', $error, FILE_APPEND);
        logs::rotate(GV_RootPath . 'logs/sql_log.log');
        if (!$res)
        {
          $date_obj = new DateTime();
          $error = $date_obj->format(DATE_ATOM) . ' :: `' . $this->_id . '` ' . $sql . ' -- ' . mysql_error($this->private_connect) . "\n";
          file_put_contents(GV_RootPath . 'logs/sql_error.log', $error, FILE_APPEND);
          logs::rotate(GV_RootPath . 'logs/sql_error.log');
        }
        return $res;
      }
      else
      {
        return(mysql_query($sql, $this->private_connect));
      }
    }
    return(false);
  }

  function last_error()
  {
    if ($this->private_connect)
    {
      return(mysql_error($this->private_connect));
    }
    return("");
  }

  function num_rows($rs)
  {
    if ($this->private_connect && $rs)
    {
      return(mysql_num_rows($rs));
    }
    return(false);
  }

  function fetch_assoc($rs)
  {
    if ($this->private_connect && $rs)
    {
      return(@mysql_fetch_assoc($rs));
    }
    return(false);
  }

  function num_fields($rs)
  {
    if ($this->private_connect && $rs)
    {
      return(mysql_num_fields($rs));
    }
    return(false);
  }

  function result($rs, $i)
  {
    if ($this->private_connect && $rs)
    {
      return(mysql_result($rs, $i));
    }
    return(false);
  }

  function field_name($rs, $index)
  {
    if ($this->private_connect && $rs)
    {
      return(mysql_field_name($rs, $index));
    }
    return(false);
  }

  function free_result($rs)
  {
    if ($this->private_connect && $rs)
    {
      return(mysql_free_result($rs));
    }
    return(false);
  }

  function escape_string($str)
  {
    $strout = false;
    if ($this->private_connect)
    {
      $strout = @mysql_real_escape_string($str, $this->private_connect);
    }
    return($strout);
  }

}

?>