<?php

abstract class base
{

  var $schema = false;
  var $conn = false;
  var $dbname = false;
  var $passwd = false;
  var $user = false;
  var $port = false;
  var $host = false;
  var $type = false;

  function init_conn()
  {
    require dirname(__FILE__) . '/../../config/connexion.inc';
    $this->conn = mysql_connect($hostname . ":" . $port, $user, $password, true);

    mysql_set_charset('utf8', $this->conn);
    mysql_query("SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'", $this->conn);

    if ($this->conn !== false)
      return true;

    return false;
  }

  function __destruct()
  {
    
  }

  function load_schema($schema_type)
  {
    $this->schema = false;
    $structure = simplexml_load_file(dirname(__FILE__) . "/../../lib/conf.d/bases_structure.xml");
    if ($structure !== false)
    {
      if ($schema_type === 'application_box')
        $this->schema = $structure->appbox;
      if ($schema_type === 'data_box')
        $this->schema = $structure->databox;
    }
    return $this->schema !== false ? true : false;
  }

  function createDb($dbname)
  {
    if ($this->schema)
    {
      $sql = 'CREATE DATABASE `' . mysql_real_escape_string($dbname, $this->conn) . '` CHARACTER SET utf8 COLLATE utf8_unicode_ci';
      if (mysql_query($sql, $this->conn) || mysql_select_db($dbname, $this->conn))
      {
        mysql_select_db($dbname, $this->conn);

        foreach ($this->schema->tables->table as $table)
        {
          $this->createTable($table);
        }

        $this->dbname = $dbname;

        if (defined('GV_version'))
          $this->setVersion(GV_version);

        return true;
      }
      else
        return false;
    }
    return false;
  }

  private function createTable($table)
  {
    $field_stmt = $defaults_stmt = array();

    $create_stmt = "CREATE TABLE `" . mysql_real_escape_string($table['name'], $this->conn) . "` (";

    foreach ($table->fields->field as $field)
    {

      $isnull = trim($field->null) == "" ? "NOT NULL" : "NULL";

      if (trim($field->default) != "" && trim($field->default) != "CURRENT_TIMESTAMP")
        $is_default = " default '" . $field->default . "'";
      elseif (trim($field->default) == "CURRENT_TIMESTAMP")
        $is_default = " default " . $field->default;
      else
        $is_default = '';

      $character_set = '';
      if (in_array(strtolower((string) $field->type), array('text', 'longtext', 'mediumtext', 'tinytext')) || substr(strtolower((string) $field->type), 0, 7) == 'varchar' || in_array(substr(strtolower((string) $field->type), 0, 4), array('char', 'enum')))
      {

        $collation = trim((string) $field->collation) != '' ? trim((string) $field->collation) : 'utf8_unicode_ci';

        $code = array_pop(array_reverse(explode('_', $collation)));

        $character_set = ' CHARACTER SET ' . $code . ' COLLATE ' . $collation;
      }

      $field_stmt[] = " `" . mysql_real_escape_string($field->name, $this->conn) . "` " . $field->type . " " . $field->extra . " " . $character_set . " " . $is_default . " " . $isnull . "";
    }


    if ($table->indexes)
    {
      foreach ($table->indexes->index as $index)
      {
        switch ($index->type)
        {

          case "PRIMARY":
            {
              $primary_fields = array();

              foreach ($index->fields->field as $field)
              {
                $primary_fields[] = "`" . mysql_real_escape_string($field, $this->conn) . "`";
              }

              $field_stmt[] = 'PRIMARY KEY (' . implode(',', $primary_fields) . ')';
            };
            break;

          case "UNIQUE":
            {
              $unique_fields = array();

              foreach ($index->fields->field as $field)
              {
                $unique_fields[] = "`" . mysql_real_escape_string($field, $this->conn) . "`";
              }

              $field_stmt[] = 'UNIQUE KEY `' . mysql_real_escape_string($index->name, $this->conn) . '` (' . implode(',', $unique_fields) . ')';
            };
            break;

          case "INDEX":
            {
              $index_fields = array();

              foreach ($index->fields->field as $field)
              {
                $index_fields[] = "`" . mysql_real_escape_string($field, $this->conn) . "`";
              }

              $field_stmt[] = 'KEY `' . mysql_real_escape_string($index->name, $this->conn) . '` (' . implode(',', $index_fields) . ')';
            };
            break;
        }
      }
    }
    if ($table->defaults)
    {
      foreach ($table->defaults->default as $default)
      {

        $k = $v = array();

        foreach ($default->data as $data)
        {
          $k[] = mysql_real_escape_string($data['key'], $this->conn);
          if ($k === 'usr_password')
            $data = hash('sha256', $data);
          $v[] = mysql_real_escape_string(trim(str_replace(array("\r\n", "\r", "\n", "\t"), '', $data)), $this->conn);
        }

        $k = implode(',', $k);
        $v = str_ireplace(array('"NOW()"', '"null"'), array('NOW()', 'null'), '"' . implode('","', $v) . '"');


        $defaults_stmt[] = 'INSERT INTO ' . mysql_real_escape_string($table['name'], $this->conn) . ' (' . $k . ') VALUES (' . $v . ') ';
      }
    }


    $engine = mb_strtolower(trim($table->engine));

    $engine = in_array($engine, array('innodb', 'myisam')) ? $engine : 'innodb';

    $create_stmt .= implode(',', $field_stmt);
    $create_stmt .= ") ENGINE=" . $engine . " CHARACTER SET utf8 COLLATE utf8_unicode_ci;";

    mysql_query($create_stmt, $this->conn);

    foreach ($defaults_stmt as $def)
    {
      mysql_query($def, $this->conn);
    }
  }

  private function upgradeTable($table)
  {
    $correct_table = array('fields' => array(), 'indexes' => array(), 'collation' => array());
    $alter = $alter_pre = array();

    if ($table)
    {
      foreach ($table->fields->field as $field)
      {
        $expr = trim((string) $field->type);


        $_extra = trim((string) $field->extra);
        if ($_extra)
          $expr .= ' ' . $_extra;

        $collation = trim((string) $field->collation) != '' ? trim((string) $field->collation) : 'utf8_unicode_ci';

        if (in_array(strtolower((string) $field->type), array('text', 'longtext', 'mediumtext', 'tinytext')) || substr(strtolower((string) $field->type), 0, 7) == 'varchar' || in_array(substr(strtolower((string) $field->type), 0, 4), array('char', 'enum')))
        {
          $code = array_pop(array_reverse(explode('_', $collation)));

          $collation = ' CHARACTER SET ' . $code . ' COLLATE ' . $collation;

          $correct_table['collation'][trim((string) $field->name)] = $collation;

          $expr .= $collation;
        }



        $_null = mb_strtolower(trim((string) $field->null));
        if (!$_null || $_null == 'no')
          $expr .= ' NOT NULL';

        $_default = (string) $field->default;
        if ($_default && $_default != 'CURRENT_TIMESTAMP')
          $expr .= ' DEFAULT \'' . $_default . '\'';
        elseif ($_default == 'CURRENT_TIMESTAMP')
          $expr .= ' DEFAULT ' . $_default . '';

        $correct_table['fields'][trim((string) $field->name)] = $expr;
      }
      if ($table->indexes)
      {
        foreach ($table->indexes->index as $index)
        {
          $i_name = (string) $index->name;
          $expr = array();
          foreach ($index->fields->field as $field)
            $expr[] = '`' . trim((string) $field) . '`';

          $expr = implode(', ', $expr);

          switch ((string) $index->type)
          {
            case "PRIMARY":
              $correct_table['indexes']['PRIMARY'] = 'PRIMARY KEY (' . $expr . ')';
              break;

            case "UNIQUE":
              $correct_table['indexes'][$i_name] = 'UNIQUE KEY `' . $i_name . '` (' . $expr . ')';
              break;

            case "INDEX":
              $correct_table['indexes'][$i_name] = 'KEY `' . $i_name . '` (' . $expr . ')';
              break;
          }
        }
      }


      $sql = "SHOW FULL FIELDS FROM `" . $table['name'] . "`";
      if ($rs2 = mysql_query($sql, $this->conn))
      {
        while ($row2 = mysql_fetch_assoc($rs2))
        {
          $f_name = $row2['Field'];
          $expr_found = trim($row2['Type']);

          $_extra = $row2['Extra'];

          if ($_extra)
            $expr_found .= ' ' . $_extra;

          $_collation = $row2['Collation'];

          $current_collation = '';

          if ($_collation)
          {
            $_collation = explode('_', $row2['Collation']);

            $expr_found .= $current_collation = ' CHARACTER SET ' . $_collation[0] . ' COLLATE ' . implode('_', $_collation);
          }

          $_null = mb_strtolower(trim($row2['Null']));

          if (!$_null || $_null == 'no')
            $expr_found .= ' NOT NULL';

          $_default = $row2['Default'];

          if ($_default)
          {
            if (trim($row2['Type']) == 'timestamp' && $_default == 'CURRENT_TIMESTAMP')
              $expr_found .= ' DEFAULT CURRENT_TIMESTAMP';
            else
              $expr_found .= ' DEFAULT \'' . $_default . '\'';
          }


          if (isset($correct_table['fields'][$f_name]))
          {
            if (isset($correct_table['collation'][$f_name]) && $correct_table['collation'][$f_name] != $current_collation)
            {


              $old_type = mb_strtolower(trim($row2['Type']));
              $new_type = false;

              switch ($old_type)
              {
                case 'text':
                  $new_type = 'blob';
                  break;
                case 'longtext':
                  $new_type = 'longblob';
                  break;
                case 'mediumtext':
                  $new_type = 'mediumblob';
                  break;
                case 'tinytext':
                  $new_type = 'tinyblob';
                  break;
                default:
                  if (substr($old_type, 0, 4) == 'char')
                    $new_type = 'varbinary(255)';
                  if (substr($old_type, 0, 7) == 'varchar')
                    $new_type = 'varbinary(767)';
                  break;
              }

              if ($new_type)
              {
                $alter_pre[] = "ALTER TABLE `" . $table['name'] . "` CHANGE `$f_name` `$f_name` " . $new_type . "";
              }
            }

            if (strtolower($expr_found) !== strtolower($correct_table['fields'][$f_name]))
            {
              $alter[] = "ALTER TABLE `" . $table['name'] . "` CHANGE `$f_name` `$f_name` " . $correct_table['fields'][$f_name];
            }
            unset($correct_table['fields'][$f_name]);
          }
//					else
//					{
//						$alter[] = "ALTER TABLE `".$table['name']."` DROP `$f_name`";
//					}
					}

        foreach ($correct_table['fields'] as $f_name => $expr)
        {
          $alter[] = "ALTER TABLE `" . $table['name'] . "` ADD `$f_name` " . $correct_table['fields'][$f_name];
        }

        mysql_free_result($rs2);
      }

      $tIndex = array();
      $sql = "SHOW INDEXES FROM `" . $table['name'] . "`";

      if ($rs2 = mysql_query($sql, $this->conn))
      {
        while ($row2 = mysql_fetch_assoc($rs2))
        {
          if (!isset($tIndex[$row2['Key_name']]))
            $tIndex[$row2['Key_name']] = array('unique' => ((int) ($row2['Non_unique']) == 0), 'columns' => array());
          $tIndex[$row2['Key_name']]['columns'][(int) ($row2['Seq_in_index'])] = $row2['Column_name'];
        }
        mysql_free_result($rs2);

        foreach ($tIndex as $kIndex => $vIndex)
        {
          $strColumns = array();

          foreach ($vIndex['columns'] as $column)
            $strColumns[] = '`' . $column . '`';

          $strColumns = '(' . implode(', ', $strColumns) . ')';

          if ($kIndex == 'PRIMARY')
            $expr_found = 'PRIMARY KEY ' . $strColumns;
          else
          {
            if ($vIndex['unique'])
              $expr_found = 'UNIQUE KEY `' . $kIndex . '` ' . $strColumns;
            else
              $expr_found = 'KEY `' . $kIndex . '` ' . $strColumns;
          }

          $full_name_index = ($kIndex == 'PRIMARY') ? 'PRIMARY KEY' : ('INDEX `' . $kIndex . '`');

          if (isset($correct_table['indexes'][$kIndex]))
          {

            if (mb_strtolower($expr_found) !== mb_strtolower($correct_table['indexes'][$kIndex]))
            {
              $alter[] = 'ALTER TABLE `' . $table['name'] . '` DROP ' . $full_name_index . ', ADD ' . $correct_table['indexes'][$kIndex];
            }

            unset($correct_table['indexes'][$kIndex]);
          }
//					else 
//						$alter[] = 'ALTER TABLE `'.$table['name'].'` DROP ' . $full_name_index;
        }
      }

      foreach ($correct_table['indexes'] as $kIndex => $expr)
        $alter[] = 'ALTER TABLE `' . $table['name'] . '` ADD ' . $expr;
    }

    $return = true;

    foreach ($alter_pre as $a)
    {
      if (!mysql_query($a, $this->conn))
        $return = false;
    }


    foreach ($alter as $a)
    {
      if (!mysql_query($a, $this->conn))
      {
        if (GV_debug)
        {
          echo $a, ' -- ', mysql_error($this->conn), '<br/>';
        }
        $return = false;
      }
    }

    return $return;
  }

  function upgradeDb()
  {
    require_once dirname(__FILE__) . '/../version.inc';
    if ($this->schema && $this->dbname)
    {
      $allTables = array();

      foreach ($this->schema->tables->table as $table)
        $allTables[(string) $table['name']] = $table;

      $sql = "SHOW TABLE STATUS";
      if ($rs = mysql_query($sql, $this->conn))
      {
        while ($row = mysql_fetch_assoc($rs))
        {
          $tname = $row["Name"];

          if (isset($allTables[$tname]))
          {
            $engine = strtolower(trim($allTables[$tname]->engine));
            $ref_engine = strtolower($row['Engine']);

            if ($engine != $ref_engine && in_array($engine, array('innodb', 'myisam')))
            {
              $sql = 'ALTER TABLE `' . $tname . '` ENGINE = ' . $engine;
              mysql_query($sql, $this->conn);
            }

            self::upgradeTable($allTables[$tname]);
            unset($allTables[$tname]);
          }
        }
        mysql_free_result($rs);
      }
      foreach ($allTables as $tname => $table)
      {
        $this->createTable($table);
      }
    }

    $current_version = self::getVersion();


    if (self::apply_patches($current_version, GV_version))
      self::setVersion(GV_version);

    return true;
  }

  function apply_patches($from, $to)
  {
    if (version_compare($from, $to, '='))
      return true;

    $list_patches = array();

    $iterator = new DirectoryIterator(GV_RootPath . 'lib/classes/patch/');

    foreach ($iterator as $fileinfo)
    {
      if (!$fileinfo->isDot())
      {
        if (substr($fileinfo->getFilename(), 0, 1) == '.')
          continue;

        $classname = 'patch_' . array_pop(array_reverse(explode('.', $fileinfo->getFilename())));

        $patch = new $classname();

        if (!in_array($this->type, $patch->concern()))
          continue;

        if (!version_compare($patch->get_release(), $from, '>') || !version_compare($patch->get_release(), $to, '<='))
        {
          continue;
        }

        $list_patches[$patch->get_release()][] = $patch;
      }
    }
    ksort($list_patches);

    $success = true;

    foreach ($list_patches as $v => $patches)
    {
      foreach ($patches as $patch)
      {
        if (!$patch->apply($this->id))
          $success = false;
      }
    }

    return $success;
  }

  function upgradeAvalaible()
  {
    if ($this->type == 'application_box')
      $sql = 'SELECT version FROM sitepreff';
    if ($this->type == 'data_box')
      $sql = 'SELECT value AS version FROM pref WHERE prop="version" LIMIT 1;';

    if ($sql !== '')
    {
      if ($rs = mysql_query($sql, $this->conn))
      {
        if ($row = mysql_fetch_assoc($rs))
          $version = $row['version'];
      }
    }
    if (isset($version))
      return version_compare(GV_version, $version, '>');
    else
      return true;
  }

  private function setVersion($version)
  {
    $sql = '';
    if ($this->type == 'application_box')
      $sql = 'UPDATE sitepreff SET version = "' . $version . '"';
    if ($this->type == 'data_box')
    {
      $sql = 'DELETE FROM pref WHERE prop="version" AND locale IS NULL';
      mysql_query($sql, $this->conn);
      $sql = 'REPLACE INTO pref (id, prop, value,locale, updated_on) VALUES (null, "version", "' . $version . '","", NOW())';
    }

    if ($sql !== '')
    {
      if (mysql_query($sql, $this->conn))
        return true;
    }

    return false;
  }

  public function getVersion()
  {
    $sql = '';
    if ($this->type == 'application_box')
      $sql = 'SELECT version FROM sitepreff';
    if ($this->type == 'data_box')
    {
      $sql = 'DELETE FROM pref WHERE prop="version" AND locale IS NULL';
      mysql_query($sql, $this->conn);
      $sql = 'SELECT value AS version FROM pref WHERE prop="version" LIMIT 1;';
    }

    if ($sql !== '')
    {
      if ($rs = mysql_query($sql, $this->conn))
      {
        if ($row = mysql_fetch_assoc($rs))
        {
          return $row['version'];
        }
      }
    }

    return '0.0.0';
  }

}
