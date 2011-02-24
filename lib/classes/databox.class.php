<?php

class databox extends base
{

  var $id = false;
//	var $request_mails = array();
  var $structure = false;
  private static $_xpath_thesaurus = array();
  private static $_dom_thesaurus = array();
  private static $_thesaurus = array();
  private static $_xpath_structure = array();
  private static $_dom_structure = array();
  private static $_sxml_structure = array();
  private static $_sxml_thesaurus = array();


  function __construct($id=false, $host=false, $port=false, $user=false, $password=false)
  {
    $newServer = false;
    if ($host !== false && $port !== false && $user !== false && $password !== false)
      $newServer = array(
          'hostname' => $host,
          'port' => $port,
          'user' => $user,
          'password' => $password
      );
    elseif ($id !== false)
    {
      try
      {
        $conn = connection::getPDOConnection();
        $sql = 'SELECT host, port, user, pwd FROM sbas WHERE sbas_id= :sbas_id';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':sbas_id' => $id));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $newServer = array(
            'hostname' => $row['host'],
            'port' => $row['port'],
            'user' => $row['user'],
            'password' => $row['pwd']
        );
      }
      catch (Exception $e)
      {

      }
    }

    if ($this->init_data_conn($newServer) === false)
      return false;

    if ($this->load_schema('data_box') === false)
      return false;

    if ($id !== false)
      $this->load((int) $id);

    $this->type = 'data_box';

    return true;
  }

  function load($id)
  {

    $conn = connection::getInstance();
    $sql = 'SELECT dbname FROM sbas WHERE sbas_id="' . $id . '"';
    if ($rs = $conn->query($sql))
    {
      if ($row = $conn->fetch_assoc($rs))
      {
        $this->id = $id;
        if (mysql_select_db($row['dbname'], $this->conn))
          $this->dbname = $row['dbname'];
      }
      $conn->free_result($rs);
    }

    return false;
  }

  function __destruct()
  {

    return true;
  }

  public function get_mountable_colls()
  {
    $conn = connection::getInstance();
    $colls = array();

    $sql = 'SELECT server_coll_id FROM bas WHERE sbas_id="' . $conn->escape_string($this->id) . '"';
    if ($rs = $conn->query($sql))
    {
      while ($row = $conn->fetch_assoc($rs))
        $colls[] = $row['server_coll_id'];
      $conn->free_result($rs);
    }

    $connbas = connection::getInstance($this->id);

    $mountable_colls = array();

    $sql = 'SELECT coll_id, asciiname FROM coll WHERE coll_id NOT IN (' . implode(',', $colls) . ')';

    if ($rs = $connbas->query($sql))
    {
      while ($row = $connbas->fetch_assoc($rs))
        $mountable_colls[$row['coll_id']] = $row['asciiname'];
      $connbas->free_result($rs);
    }

    return $mountable_colls;
  }

  public function list_colls()
  {
    $lb = phrasea::bases();

    $colls = array();

    foreach ($lb['bases'] as $base)
    {
      if ($base['sbas_id'] != $this->id)
        continue;
      foreach ($base['collections'] as $coll)
      {
        $colls[$coll['base_id']] = $coll['name'];
      }
    }
    return $colls;
  }

  public function save($usr_id)
  {
    $conn = connection::getInstance();
    if ($this->id === false)
    {
      if (trim($this->dbname) == '')
        throw new Exception('invalid dbname');
      if (trim($this->user) == '')
        throw new Exception('invalid user');
      if (trim($this->host) == '')
        throw new Exception('invalid host');

      $ord = 0;
      $sql = '(SELECT MAX(ord) as ord FROM sbas)';
      if ($rs = $conn->query($sql))
      {
        if ($row = mysql_fetch_assoc($rs))
          $ord = $row['ord'] + 1;
      }

      $sql = 'INSERT INTO sbas (sbas_id, ord, host, port, dbname, sqlengine, user, pwd) VALUES (null, "' . $ord . '", "' . $conn->escape_string($this->host) . '", "' . $conn->escape_string($this->port) . '", "' . $conn->escape_string($this->dbname) . '", "MYSQL", "' . $conn->escape_string($this->user) . '", "' . mysql_real_escape_string($this->passwd) . '")';

      if ($conn->query($sql))
      {
        $this->id = $conn->insert_id();

        $sql = 'INSERT INTO sbasusr (sbasusr_id, sbas_id, usr_id, bas_manage, bas_modify_struct, bas_modif_th, bas_chupub) VALUES (null, "' . $this->id . '", "' . $usr_id . '", "0", "0", "0", "0")';
        $conn->query($sql);
      }
      else
        throw new Exception('unable to save databox in sbasusr : ' . $conn->last_error());
    }

    return $this->id;
  }

  function create($dbname)
  {
    $this->createDb($dbname);
    $cache_appbox = cache_appbox::getInstance();
    $cache_appbox->delete('list_bases');
    cache_databox::update($this->id, 'structure');
  }

  function mount($dbname, $usr_id)
  {
    $conn = connection::getInstance();
    if (mysql_select_db($dbname, $this->conn))
    {
      $this->dbname = $dbname;
      if ($this->save($usr_id) !== false)
      {
        $cache_appbox = cache_appbox::getInstance();


        $sql = "SELECT * FROM coll";
        if ($rs = mysql_query($sql, $this->conn))
        {
          $base_id = $this->getAppboxId('BAS', mysql_num_rows($rs));


          while ($row = mysql_fetch_assoc($rs))
          {
            if (!empty($row['logo']) && ($fp = fopen(GV_RootPath . 'config/minilogos/' . $base_id, 'w')) !== false)
            {
              fwrite($fp, $row["logo"]);
              fclose($fp);
            }

            $sql = "INSERT INTO bas
							(base_id, active, server_coll_id, sbas_id) VALUES 
							('" . $conn->escape_string($base_id) . "','1',
							'" . $conn->escape_string($row['coll_id']) . "','" . $conn->escape_string($this->id) . "')";
            if ($conn->query($sql))
            {

              $sql = "INSERT INTO basusr
							 (base_id, usr_id, canpreview, canpush, canhd, cancmd, canputinalbum, candwnldhd, candwnldpreview, canadmin, actif, canreport, canaddrecord, canmodifrecord, candeleterecord, chgstatus, imgtools, manage, modify_struct, mask_and, mask_xor, basusr_infousr, creationdate ) VALUES 
							 ('" . $base_id . "', '" . $usr_id . "', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '0', '0', '', NOW())";

              $conn->query($sql);
            }

            $base_id++;
          }
          $cache_appbox->delete('list_bases');
          cache_databox::update($this->id, 'structure');
        }
        return $this->id;
      }
    }
    return false;
  }

  public function saveStructure()
  {
    
  }

  private function getAppboxId($typeId, $askfor_n=1)
  {
    $x = null;

    $conn = connection::getInstance();

    $sql = 'LOCK TABLE uids WRITE';
    if ($conn->query($sql))
    {
      $sql = "UPDATE uids SET uid=uid+$askfor_n WHERE name='$typeId'";
      if ($conn->query($sql))
      {
        $sql = "SELECT uid FROM uids WHERE name='$typeId'";
        if ($result = $conn->query($sql))
        {
          if ($row = mysql_fetch_assoc($result))
            $x = ($row["uid"] - $askfor_n) + 1;
          mysql_free_result($result);
        }
      }
      $sql = 'UNLOCK TABLES';
      $conn->query($sql);
    }
    return $x;
  }

  private function init_data_conn($new_db=false)
  {
    // just in case
    if (is_resource($this->conn))
    {
      mysql_close($this->conn);
      $this->conn = false;
    }

    // connect to appbox
    require dirname(__FILE__) . '/../../config/connexion.inc';

    $conn = connection::getInstance();

    if ($conn)
    {

      if (!$new_db)
      {
        // same as appbox
        $this->host = $hostname;
        $this->port = $port;
        $this->user = $user;
        $this->passwd = $password;
      }
      else
      {
        $this->host = $new_db['hostname'];
        $this->port = $new_db['port'];
        $this->user = $new_db['user'];
        $this->passwd = $new_db['password'];
      }

      $this->conn = mysql_connect($this->host . ":" . $this->port, $this->user, $this->passwd, true);

      mysql_set_charset('utf8', $this->conn);
      mysql_query("SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'", $this->conn);

      if ($this->conn !== false)
        return true;
    }
    return false;
  }

  public function setNewStructure($data_template, $path_web, $path_doc, $baseurl)
  {
    if (is_file($data_template))
    {
      $contents = file_get_contents($data_template);

      $baseurl = $baseurl ? p4string::addEndSlash($baseurl) : '';
      
      $contents = str_replace(
                      array("{{dataurl}}", "{{basename}}", "{{datapathweb}}", "{{datapathnoweb}}"),
                      array($baseurl, $this->dbname, $path_web, $path_doc),
                      $contents
      );

      $this->structure = $contents;

      $sql = 'UPDATE pref SET value="' . mysql_real_escape_string($this->structure, $this->conn) . '", updated_on=NOW() WHERE prop="structure"';
      if (mysql_query($sql, $this->conn))
        return true;
    }

    return false;
  }

  public function registerAdmin($usr_id, $bool)
  {
    $conn = connection::getInstance();
    $sql = 'UPDATE sbasusr SET bas_manage="' . ($bool === true ? '1' : '0') . '" WHERE usr_id = "' . $usr_id . '" AND sbas_id="' . $this->id . '"';
    if ($conn->query($sql))
      return true;
    return false;
  }

  public function registerAdminStruct($usr_id, $bool)
  {
    $conn = connection::getInstance();
    $sql = 'UPDATE sbasusr SET bas_modify_struct="' . ($bool === true ? '1' : '0') . '" WHERE usr_id = "' . $usr_id . '" AND sbas_id="' . $this->id . '"';
    if ($conn->query($sql))
      return true;
    return false;
  }

  public function registerAdminThesaurus($usr_id, $bool)
  {
    $conn = connection::getInstance();
    $sql = 'UPDATE sbasusr SET bas_modif_th="' . ($bool === true ? '1' : '0') . '" WHERE usr_id = "' . $usr_id . '" AND sbas_id="' . $this->id . '"';
    if ($conn->query($sql))
      return true;
    return false;
  }

  public function registerPublication($usr_id, $bool)
  {
    $conn = connection::getInstance();
    $sql = 'UPDATE sbasusr SET bas_chupub="' . ($bool === true ? '1' : '0') . '" WHERE usr_id = "' . $usr_id . '" AND sbas_id="' . $this->id . '"';
    if ($conn->query($sql))
      return true;
    return false;
  }

  public static function printStatus($name)
  {

    $cache_data = cache_appbox::getInstance();

    if (($tmp = $cache_data->get('status' . $name)) !== false)
      return $tmp;

    $filename = GV_RootPath . 'config/status/' . $name;

    $out = '';

    if (is_file($filename))
    {
      $out = file_get_contents($filename);
    }

    $cache_data->set('status' . $name, $out);

    return $out;
  }

  public static function getPrintLogo($sbas_id)
  {

    $cache_data = cache_appbox::getInstance();

    $out = '';
    if (is_file(($filename = GV_RootPath . 'config/minilogos/logopdf_' . $sbas_id . '.jpg')))
      $out = file_get_contents($filename);

    return $out;
  }

  public static function getColls($sbas_id=false)
  {
    $tbas = array();
    $conn = connection::getInstance();

    if ($sbas_id !== false)
      $sql = "SELECT * FROM sbas s, bas b WHERE s.sbas_id='" . $conn->escape_string($sbas_id) . "' AND s.sbas_id = b.sbas_id AND b.active = '1' ORDER BY s.ord ASC, b.ord ASC";
    else
      $sql = "SELECT * FROM sbas s, bas b WHERE s.sbas_id = b.sbas_id AND b.active = '1' ORDER BY s.ord ASC, b.ord ASC";

    if ($rs = $conn->query($sql))
    {
      while ($row = $conn->fetch_assoc($rs))
      {
        if (!isset($tbas[$row["sbas_id"]]))
        {
          $tbas[$row["sbas_id"]] = array('viewname' => (trim($row['viewname']) != '' ? $row['viewname'] : $row['dbname']), 'colls' => array());

          $connbas = connection::getInstance($row['sbas_id']);
          if ($connbas)
          {
            $sql = "SELECT coll_id, asciiname FROM coll";
            if ($rsbas = $connbas->query($sql))
            {
              while ($rowbas = $connbas->fetch_assoc($rsbas))
              {
                $colls[$row['sbas_id']][$rowbas["coll_id"]] = $rowbas['asciiname'];
              }
              $conn->free_result($rsbas);
            }
          }
        }
        $tbas[$row["sbas_id"]]['colls'][$row['base_id']] = isset($colls[$row['sbas_id']][$row['server_coll_id']]) ? $colls[$row['sbas_id']][$row['server_coll_id']] : 'unknown name';
      }
      $conn->free_result($rs);
    }
    return($tbas);
  }

  public static function get_dom_thesaurus($sbas_id)
  {
    if (isset(self::$_dom_thesaurus[$sbas_id]))
    {
      return self::$_dom_thesaurus[$sbas_id];
    }

    $thesaurus = self::get_thesaurus($sbas_id);

    if ($thesaurus && ($tmp = DomDocument::loadXML($thesaurus)) !== false)
      self::$_dom_thesaurus[$sbas_id] = $tmp;
    else
      self::$_dom_thesaurus[$sbas_id] = false;

    return self::$_dom_thesaurus[$sbas_id];
  }

  public static function get_xpath_thesaurus($sbas_id)
  {
    if (isset(self::$_xpath_thesaurus[$sbas_id]))
    {
      return self::$_xpath_thesaurus[$sbas_id];
    }

    $DOM_thesaurus = self::get_dom_thesaurus($sbas_id);

    if ($DOM_thesaurus && ($tmp = new phrasea_DOMXPath($DOM_thesaurus)) !== false)
      self::$_xpath_thesaurus[$sbas_id] = $tmp;
    else
      self::$_xpath_thesaurus[$sbas_id] = false;

    return self::$_xpath_thesaurus[$sbas_id];
  }

  public static function get_sxml_thesaurus($sbas_id)
  {

    if (isset(self::$_sxml_thesaurus[$sbas_id]))
    {
      return self::$_sxml_thesaurus[$sbas_id];
    }

    $thesaurus = self::get_thesaurus($sbas_id);

    if ($thesaurus && ($tmp = simplexml_load_string($thesaurus)) !== false)
      self::$_sxml_thesaurus[$sbas_id] = $tmp;
    else
      self::$_sxml_thesaurus[$sbas_id] = false;

    return self::$_sxml_thesaurus[$sbas_id];
  }

  public static function get_thesaurus($sbas_id)
  {
    $cache_appbox = cache_appbox::getInstance();

    if (($tmp = $cache_appbox->get('thesaurus_' . $sbas_id)) !== false)
    {
      self::$_thesaurus[$sbas_id] = $tmp;
      return $tmp;
    }

    if (isset(self::$_thesaurus[$sbas_id]))
    {
      return self::$_thesaurus[$sbas_id];
    }

    $thesaurus = false;
    $connsbas = connection::getInstance($sbas_id);
    $sql = 'SELECT value AS thesaurus FROM pref WHERE prop="thesaurus" LIMIT 1;';

    if ($rs = $connsbas->query($sql))
    {
      if ($row = $connsbas->fetch_assoc($rs))
      {
        $thesaurus = trim($row['thesaurus']);
      }
      $connsbas->free_result($rs);
    }

    self::$_thesaurus[$sbas_id] = $thesaurus;

    if (self::$_thesaurus[$sbas_id])
      $cache_appbox->set('thesaurus_' . $sbas_id, self::$_thesaurus[$sbas_id]);

    return self::$_thesaurus[$sbas_id];
  }

  public static function get_structure($sbas_id)
  {
    $session = session::getInstance();
    $locale = isset($session->locale) ? $session->locale : GV_default_lng;
    $basesettings = phrasea::load_settings($locale);

    if (isset($basesettings["bases"][$sbas_id]))
      return $basesettings["bases"][$sbas_id]["structure"];

    return false;
  }

  public static function get_dom_structure($sbas_id)
  {
    if (isset(self::$_dom_structure[$sbas_id]))
    {
      return self::$_dom_structure[$sbas_id];
    }

    $structure = self::get_structure($sbas_id);

    $dom = new DOMDocument();

    $dom->standalone = true;
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;

    if ($structure && $dom->loadXML($structure) !== false)
      self::$_dom_structure[$sbas_id] = $dom;
    else
      self::$_dom_structure[$sbas_id] = false;

    return self::$_dom_structure[$sbas_id];
  }

  public static function get_sxml_structure($sbas_id)
  {
    if (isset(self::$_sxml_structure[$sbas_id]))
    {
      return self::$_sxml_structure[$sbas_id];
    }

    $structure = self::get_structure($sbas_id);

    if ($structure && ($tmp = simplexml_load_string($structure)) !== false)
      self::$_sxml_structure[$sbas_id] = $tmp;
    else
      self::$_sxml_structure[$sbas_id] = false;

    return self::$_sxml_structure[$sbas_id];
  }

  public static function get_xpath_structure($sbas_id)
  {
    if (isset(self::$_xpath_structure[$sbas_id]))
    {
      return self::$_xpath_structure[$sbas_id];
    }

    $dom_doc = self::get_dom_structure($sbas_id);

    if ($dom_doc && ($tmp = new DOMXpath($dom_doc)) !== false)
      self::$_xpath_structure[$sbas_id] = $tmp;
    else
      self::$_xpath_structure[$sbas_id] = false;

    return self::$_xpath_structure[$sbas_id];
  }

  public static function get_structure_errors($structure)
  {
    $sx_structure = simplexml_load_string($structure);

    $subdefgroup = $sx_structure->subdefs[0];
    $AvSubdefs = array();

    $errors = array();

    foreach ($subdefgroup as $k => $subdefs)
    {
      $subdefgroup_name = trim((string) $subdefs->attributes()->name);

      if ($subdefgroup_name == '')
      {
        $errors[] = _('ERREUR : TOUTES LES BALISES subdefgroup necessitent un attribut name');
        continue;
      }

      if (!isset($AvSubdefs[$subdefgroup_name]))
        $AvSubdefs[$subdefgroup_name] = array();

      foreach ($subdefs as $sd)
      {
        $sd_name = trim(mb_strtolower((string) $sd->attributes()->name));
        $sd_class = trim(mb_strtolower((string) $sd->attributes()->class));
        if ($sd_name == '' || isset($AvSubdefs[$subdefgroup_name][$sd_name]))
        {
          $errors[] = _('ERREUR : Les name de subdef sont uniques par groupe de subdefs et necessaire');
          continue;
        }
        if (!in_array($sd_class, array('thumbnail', 'preview', 'document')))
        {
          $errors[] = _('ERREUR : La classe de subdef est necessaire et egal a "thumbnail","preview" ou "document"');
          continue;
        }
        $AvSubdefs[$subdefgroup_name][$sd_name] = $sd;
      }
    }

    return $errors;
  }

  public static function get_subdefs($sbas_id)
  {
    $sx_struct = self::get_sxml_structure($sbas_id);

    if (!$sx_struct)
      return array();

    $subdefgroup = $sx_struct->subdefs[0];

    $AvSubdefs = array();

    foreach ($subdefgroup as $k => $subdefs)
    {
      $subdefgroup_name = (string) $subdefs->attributes()->name;

      if (!isset($AvSubdefs[$subdefgroup_name]))
        $AvSubdefs[$subdefgroup_name] = array();

      foreach ($subdefs as $sd)
      {
        $AvSubdefs[$subdefgroup_name][mb_strtolower((string) $sd->attributes()->name)] = $sd;
      }
    }

    if (!isset($AvSubdefs['flash']))
      $AvSubdefs['flash'] = $AvSubdefs['image'];
    if (!isset($AvSubdefs['document']))
      $AvSubdefs['document'] = $AvSubdefs['image'];


    return $AvSubdefs;
  }

}

class phrasea_DOMXPath extends DOMXPath
{
	static $r = array();
	function cache_query($xquery, $context_node=NULL, $context_path='')	
	{
		$context_path .= $xquery;
		if(!array_key_exists($context_path, self::$r))
			self::$r[$context_path] = $context_node ? parent::query($xquery, $context_node) : parent::query($xquery);
		return(self::$r[$context_path]);
	}
}

