<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class databox extends base
{

    /**
     *
     * @var int
     */
    protected $id;

    /**
     *
     * @var string
     */
    protected $structure;

    /**
     *
     * @var Array
     */
    protected static $_xpath_thesaurus = array();

    /**
     *
     * @var Array
     */
    protected static $_dom_thesaurus = array();

    /**
     *
     * @var Array
     */
    protected static $_thesaurus = array();

    /**
     *
     * @var Array
     */
    protected $_xpath_structure;

    /**
     *
     * @var DOMDocument
     */
    protected $_dom_structure;

    /**
     *
     * @var DOMDocument
     */
    protected $_dom_cterms;

    /**
     *
     * @var SimpleXMLElement
     */
    protected $_sxml_structure;

    /**
     *
     * @var databox_descriptionStructure
     */
    protected $meta_struct;

    /**
     *
     * @var databox_subdefsStructure
     */
    protected $subdef_struct;

    /**
     *
     * @var SimpleXMLElement
     */
    protected static $_sxml_thesaurus = array();

    /**
     *
     * @var Array
     */
    protected static $_instances = array();

    const BASE_TYPE = self::DATA_BOX;
    const CACHE_META_STRUCT = 'meta_struct';
    const CACHE_THESAURUS   = 'thesaurus';
    const CACHE_COLLECTIONS = 'collections';
    const CACHE_STRUCTURE   = 'structure';
    const PIC_PDF = 'logopdf';

    protected $cache;
    protected $connection;
    protected $registry;

    /**
     *
     * @param int $sbas_id
     * @return databox
     */
    protected function __construct($sbas_id)
    {
        $this->registry = registry::get_instance();
        $this->connection = connection::getPDOConnection($sbas_id);
        $this->Core = \bootstrap::getCore();
        $this->id = $sbas_id;

        $connection_params = phrasea::sbas_params();

        if ( ! isset($connection_params[$sbas_id]))
            throw new Exception_DataboxNotFound ();

        $this->host = $connection_params[$sbas_id]['host'];
        $this->port = $connection_params[$sbas_id]['port'];
        $this->user = $connection_params[$sbas_id]['user'];
        $this->passwd = $connection_params[$sbas_id]['pwd'];
        $this->dbname = $connection_params[$sbas_id]['dbname'];

        return $this;
    }

    public function get_collections()
    {
        $ret = array();

        foreach ($this->get_available_collections() as $coll_id)
        {
            try
            {
                $ret[] = collection::get_from_coll_id($this, $coll_id);
            }
            catch (Exception $e)
            {

            }
        }

        return $ret;
    }

    protected function get_available_collections()
    {
        try
        {
            return $this->get_data_from_cache(self::CACHE_COLLECTIONS);
        }
        catch (Exception $e)
        {

        }

        $conn = connection::getPDOConnection();

        $sql  = "SELECT b.server_coll_id FROM sbas s, bas b
            WHERE s.sbas_id = b.sbas_id AND b.sbas_id = :sbas_id
              AND b.active = '1'
            ORDER BY s.ord ASC, b.ord,b.base_id ASC";
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':sbas_id' => $this->get_sbas_id()));
        $rs        = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $ret = array();

        foreach ($rs as $row)
        {
            $ret[] = (int) $row['server_coll_id'];
        }
        $this->set_data_to_cache($ret, self::CACHE_COLLECTIONS);

        return $ret;
    }

    /**
     *
     * @param int $record_id
     * @param int $number
     * @return record_adapter
     */
    public function get_record($record_id, $number = null)
    {
        return new record_adapter($this->get_sbas_id(), $record_id, $number);
    }

    public function get_viewname()
    {
        return phrasea::sbas_names($this->get_sbas_id());
    }

    /**
     *
     * @param int $sbas_id
     * @return databox
     */
    public static function get_instance($sbas_id)
    {
        assert(is_int($sbas_id));
        assert($sbas_id > 0);
        if ( ! array_key_exists($sbas_id, self::$_instances))
        {
            self::$_instances[$sbas_id] = new self($sbas_id);
        }

        return self::$_instances[$sbas_id];
    }

    /**
     *
     * @return databox_status
     */
    public function get_statusbits()
    {
        return databox_status::getStatus($this->get_sbas_id());
    }

    /**
     * Returns current sbas_id
     *
     * @return int
     */
    public function get_sbas_id()
    {
        return $this->id;
    }

    public function get_unique_keywords()
    {
        $sql = "SELECT COUNT(kword_id) AS n FROM kword";

        $stmt   = $this->get_connection()->prepare($sql);
        $stmt->execute();
        $rowbas = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        return ($rowbas ? $rowbas['n'] : null);
    }

    public function get_index_amount()
    {
        $sql = "SELECT COUNT(idx_id) AS n FROM idx";

        $stmt   = $this->get_connection()->prepare($sql);
        $stmt->execute();
        $rowbas = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        return ($rowbas ? $rowbas['n'] : null);
    }

    public function get_thesaurus_hits()
    {
        $sql = "SELECT COUNT(thit_id) AS n FROM thit";

        $stmt   = $this->get_connection()->prepare($sql);
        $stmt->execute();
        $rowbas = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        return ($rowbas ? $rowbas['n'] : null);
    }

    public function get_record_details($sort)
    {
        $sql = "SELECT record.coll_id, ISNULL(coll.coll_id) AS lostcoll,
                        COALESCE(asciiname, CONCAT('_',record.coll_id)) AS asciiname, name,
                        SUM(1) AS n, SUM(size) AS siz FROM (record, subdef)
                    LEFT JOIN coll ON record.coll_id=coll.coll_id
                    WHERE record.record_id = subdef.record_id
                    GROUP BY record.coll_id, name
          UNION
          SELECT coll.coll_id, 0, asciiname, '_' AS name, 0 AS n, 0 AS siz
            FROM coll LEFT JOIN record ON record.coll_id=coll.coll_id
            WHERE ISNULL(record.coll_id)
                    GROUP BY record.coll_id, name";

        if ($sort == "obj")
        {
            $sortk1 = "name";
            $sortk2 = "asciiname";
        }
        else
        {
            $sortk1 = "asciiname";
            $sortk2 = "name";
        }

        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute();
        $rs   = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $rowbas)
        {
            if ( ! isset($trows[$rowbas[$sortk1]]))
                $trows[$rowbas[$sortk1]] = array();
            $trows[$rowbas[$sortk1]][$rowbas[$sortk2]] = array(
              "coll_id"   => $rowbas["coll_id"],
              "asciiname" => $rowbas["asciiname"],
              "lostcoll"  => $rowbas["lostcoll"],
              "name"      => $rowbas["name"],
              "n"         => $rowbas["n"],
              "siz"       => $rowbas["siz"]
            );
        }


        ksort($trows);
        foreach ($trows as $kgrp => $vgrp)
            ksort($trows[$kgrp]);

        return $trows;
    }

    public function get_record_amount()
    {
        $sql    = "SELECT COUNT(record_id) AS n FROM record";
        $stmt   = $this->get_connection()->prepare($sql);
        $stmt->execute();
        $rowbas = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $amount = $rowbas ? (int) $rowbas["n"] : null;

        return $amount;
    }

    public function get_indexed_record_amount()
    {

        $sql  = "SELECT status & 3 AS status, SUM(1) AS n FROM record GROUP BY(status & 3)";
        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute();
        $rs   = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $ret = array('xml_indexed'       => 0, 'thesaurus_indexed' => 0);
        foreach ($rs as $row)
        {
            $status = $row['status'];
            if ($status & 1)
                $ret['xml_indexed'] += $row['n'];
            if ($status & 2)
                $ret['thesaurus_indexed'] += $row['n'];
        }

        return $ret;
    }

    public function unmount_databox(appbox $appbox)
    {
        foreach ($this->get_collections() as $collection)
        {
            $collection->unmount_collection($appbox);
        }

        $query = new User_Query($appbox);
        $total = $query->on_sbas_ids(array($this->get_sbas_id()))
          ->include_phantoms(false)
          ->include_special_users(true)
          ->include_invite(true)
          ->include_templates(true)
          ->get_total();
        $n = 0;
        while ($n < $total)
        {
            $results = $query->limit($n, 50)->execute()->get_results();
            foreach ($results as $user)
            {
                $user->ACL()->delete_data_from_cache(ACL::CACHE_RIGHTS_SBAS);
                $user->ACL()->delete_data_from_cache(ACL::CACHE_RIGHTS_BAS);
                $user->ACL()->delete_injected_rights_sbas($this);
            }
            $n+=50;
        }

        $params = array(':site_id' => $this->get_registry()->get('GV_sit'));

        $sql  = 'DELETE FROM clients WHERE site_id = :site_id';
        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $sql  = 'DELETE FROM memcached WHERE site_id = :site_id';
        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $sql  = "DELETE FROM sbas WHERE sbas_id = :sbas_id";
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':sbas_id' => $this->get_sbas_id()));
        $stmt->closeCursor();

        $sql  = "DELETE FROM sbasusr WHERE sbas_id = :sbas_id";
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':sbas_id' => $this->get_sbas_id()));
        $stmt->closeCursor();

        phrasea::reset_sbasDatas();

        return;
    }

    /**
     *
     * @param string $host
     * @param int $port
     * @param string $user
     * @param string $password
     * @param string $dbname
     * @param system_file $data_template
     * @param registryInterface $registry
     * @return databox
     */
    public static function create(appbox &$appbox, connection_pdo &$connection, system_file $data_template, registryInterface $registry)
    {
        $credentials = $connection->get_credentials();

        $sql = 'SELECT sbas_id
            FROM sbas
            WHERE host = :host AND port = :port AND dbname = :dbname
              AND user = :user AND pwd = :password';

        $host     = $credentials['hostname'];
        $port     = $credentials['port'];
        $dbname   = $credentials['dbname'];
        $user     = $credentials['user'];
        $password = $credentials['password'];

        $params = array(
          ':host'     => $host
          , ':port'     => $port
          , ':dbname'   => $dbname
          , ':user'     => $user
          , ':password' => $password
        );

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $row  = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($row)
            return self::get_instance((int) $row['sbas_id']);

        try
        {
            $sql  = 'CREATE DATABASE `' . $dbname . '`
              CHARACTER SET utf8 COLLATE utf8_unicode_ci';
            $stmt = $connection->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();
        }
        catch (Exception $e)
        {

        }

        $sql  = 'USE `' . $dbname . '`';
        $stmt = $connection->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $sql  = 'SELECT MAX(ord) as ord FROM sbas';
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $row  = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        if ($row)
            $ord  = $row['ord'] + 1;

        $sql  = 'INSERT INTO sbas (sbas_id, ord, host, port, dbname, sqlengine, user, pwd)
              VALUES (null, :ord, :host, :port, :dbname, "MYSQL", :user, :password)';
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute(array(
          ':ord'      => $ord
          , ':host'     => $host
          , ':port'     => $port
          , ':dbname'   => $dbname
          , ':user'     => $user
          , ':password' => $password
        ));
        $stmt->closeCursor();
        $sbas_id    = (int) $appbox->get_connection()->lastInsertId();

        $appbox->delete_data_from_cache(appbox::CACHE_LIST_BASES);

        $databox = self::get_instance($sbas_id);
        $databox->insert_datas();
        $databox->setNewStructure(
          $data_template, $registry->get('GV_base_datapath_web'), $registry->get('GV_base_datapath_noweb'), $registry->get('GV_base_dataurl')
        );

        return $databox;
    }

    /**
     *
     * @param string $host
     * @param int $port
     * @param string $user
     * @param string $password
     * @param string $dbname
     * @param registry $registry
     * @return databox
     */
    public static function mount(appbox $appbox, $host, $port, $user, $password, $dbname, registry $registry)
    {
        $name       = 'test';
        $connection = new connection_pdo($name, $host, $port, $user, $password, $dbname, array(), $registry);

        $conn = $appbox->get_connection();
        $sql  = 'SELECT MAX(ord) as ord FROM sbas';
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $row  = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        if ($row)
            $ord  = $row['ord'] + 1;

        $sql  = 'INSERT INTO sbas (sbas_id, ord, host, port, dbname, sqlengine, user, pwd)
              VALUES (null, :ord, :host, :port, :dbname, "MYSQL", :user, :password)';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(
          ':ord'      => $ord
          , ':host'     => $host
          , ':port'     => $port
          , ':dbname'   => $dbname
          , ':user'     => $user
          , ':password' => $password
        ));
        $stmt->closeCursor();
        $sbas_id    = (int) $conn->lastInsertId();
        phrasea::clear_sbas_params();
        phrasea::reset_baseDatas();
        phrasea::reset_sbasDatas();

        $databox = self::get_instance($sbas_id);

        $databox->delete_data_from_cache(databox::CACHE_COLLECTIONS);
        $appbox->delete_data_from_cache(appbox::CACHE_LIST_BASES);
        $appbox->delete_data_from_cache(appbox::CACHE_SBAS_IDS);

        phrasea::reset_sbasDatas();

        cache_databox::update($databox->get_sbas_id(), 'structure');

        return $databox;
    }

    public function get_base_type()
    {
        return self::BASE_TYPE;
    }

    public function get_cache_key($option = null)
    {
        return 'databox_' . $this->get_sbas_id() . '_' . ($option ? $option . '_' : '');
    }

    /**
     *
     * @return databox_descriptionStructure
     */
    public function get_meta_structure()
    {
        if ($this->meta_struct)
        {
            return $this->meta_struct;
        }

        try
        {
            $this->meta_struct = $this->get_data_from_cache(self::CACHE_META_STRUCT);

            return $this->meta_struct;
        }
        catch (Exception $e)
        {
            unset($e);
        }

        $meta_struct = new databox_descriptionStructure();

        $sql  = 'SELECT id, name FROM metadatas_structure ORDER BY sorter ASC';
        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute();
        $rs   = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row)
        {

            try
            {
                $meta = databox_field::get_instance($this, $row['id']);
            }
            catch (Exception $e)
            {
                $meta = databox_fieldUnknown::get_instance($this, $row['id']);
                $meta->set_name($row['name']);
                unset($e);
            }
            $meta_struct->add_element($meta);
        }
        $this->meta_struct = $meta_struct;
        $this->set_data_to_cache($this->meta_struct, self::CACHE_META_STRUCT);

        return $this->meta_struct;
    }

    /**
     *
     * @return databox_subdefsStructure
     */
    public function get_subdef_structure()
    {
        if ( ! $this->subdef_struct)
        {
            $this->subdef_struct = new databox_subdefsStructure($this);
        }

        return $this->subdef_struct;
    }

    /**
     *
     * @param <type> $repository_path
     * @param <type> $date
     * @return <type>
     */
    public static function dispatch($repository_path, $date = false)
    {
        if ( ! $date)
            $date = date('Y-m-d H:i:s');

        $repository_path = p4string::addEndSlash($repository_path);

        $year  = date('Y', strtotime($date));
        $month = date('m', strtotime($date));
        $day   = date('d', strtotime($date));

        $n    = 0;
        $comp = $year . DIRECTORY_SEPARATOR . $month . DIRECTORY_SEPARATOR . $day . DIRECTORY_SEPARATOR;

        $condition = true;

        $pathout = $repository_path . $comp;

        while (($pathout = $repository_path . $comp . self::addZeros($n)) && is_dir($pathout) && self::more_than_limit_in_dir($pathout))
        {
            $n ++;
        }

        if ( ! is_dir($pathout))
        {
            system_file::mkdir($pathout);
        }


        return p4string::addEndSlash($pathout);
    }

    public function delete()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());

        $sql  = 'DROP DATABASE `' . $this->get_dbname() . '`';
        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $appbox->delete_data_from_cache(appbox::CACHE_LIST_BASES);

        return;
    }

    private function more_than_limit_in_dir($path)
    {
        $limit = 100;
        $n     = 0;
        if (is_dir($path))
        {
            $hdir = opendir($path);
            if ($hdir)
            {
                while ($file = readdir($hdir))
                {
                    if ($file != '.' && $file != '..')
                    {
                        $n ++;
                    }
                }
            }
        }
        if ($n > $limit)
            return true;

        return false;
    }

    private function addZeros($n, $length = 5)
    {
        while (strlen($n) < $length)
            $n = '0' . $n;

        return $n;
    }

    public function get_serialized_server_info()
    {
        return sprintf("%s@%s:%s (MySQL %s)", $this->dbname, $this->host, $this->port, $this->get_connection()->server_info());
    }

    /**
     * Return an array of available metadata objects
     *
     * @return Array
     */
    public static function get_available_metadatas()
    {
        $available_fields = array();
        $dir      = __DIR__ . '/metadata/description/';
        $registry = registry::get_instance();
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir), RecursiveIteratorIterator::LEAVES_ONLY) as $file)
        {
            if ($file->isDir() || strpos($file->getPathname(), '/.svn/') !== false)
            {
                continue;
            }
            if ($file->isFile())
            {
                $classname = str_replace(array($registry->get('GV_RootPath') . 'lib/classes/', '.class.php', '/'), array('', '', '_'), $file->getPathname());
                $available_fields[$classname] = new $classname;
            }
        }
        ksort($available_fields);

        return $available_fields;
    }

    public function get_available_dcfields()
    {
        return array(
          databox_Field_DCESAbstract::Contributor => new databox_Field_DCES_Contributor()
          , databox_Field_DCESAbstract::Coverage    => new databox_Field_DCES_Coverage()
          , databox_Field_DCESAbstract::Creator     => new databox_Field_DCES_Creator()
          , databox_Field_DCESAbstract::Date        => new databox_Field_DCES_Date()
          , databox_Field_DCESAbstract::Description => new databox_Field_DCES_Description()
          , databox_Field_DCESAbstract::Format      => new databox_Field_DCES_Format()
          , databox_Field_DCESAbstract::Identifier  => new databox_Field_DCES_Identifier()
          , databox_Field_DCESAbstract::Language    => new databox_Field_DCES_Language()
          , databox_Field_DCESAbstract::Publisher   => new databox_Field_DCES_Publisher()
          , databox_Field_DCESAbstract::Relation    => new databox_Field_DCES_Relation
          , databox_Field_DCESAbstract::Rights      => new databox_Field_DCES_Rights
          , databox_Field_DCESAbstract::Source      => new databox_Field_DCES_Source
          , databox_Field_DCESAbstract::Subject     => new databox_Field_DCES_Subject()
          , databox_Field_DCESAbstract::Title       => new databox_Field_DCES_Title()
          , databox_Field_DCESAbstract::Type        => new databox_Field_DCES_Type()
        );
    }

    /**
     *
     * @return Array
     */
    public function get_mountable_colls()
    {
        $conn  = connection::getPDOConnection();
        $colls = array();

        $sql  = 'SELECT server_coll_id FROM bas WHERE sbas_id = :sbas_id';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':sbas_id' => $this->get_sbas_id()));
        $rs        = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row)
        {
            $colls[] = (int) $row['server_coll_id'];
        }

        $mountable_colls = array();

        $sql = 'SELECT coll_id, asciiname FROM coll';

        if (count($colls) > 0)
        {
            $sql .= ' WHERE coll_id NOT IN (' . implode(',', $colls) . ')';
        }

        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute();
        $rs   = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row)
        {
            $mountable_colls[$row['coll_id']] = $row['asciiname'];
        }

        return $mountable_colls;
    }

    public function get_activable_colls()
    {
        $conn     = connection::getPDOConnection();
        $base_ids = array();

        $sql  = 'SELECT base_id FROM bas WHERE sbas_id = :sbas_id AND active = "0"';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':sbas_id' => $this->get_sbas_id()));
        $rs        = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row)
        {
            $base_ids[] = (int) $row['base_id'];
        }

        return $base_ids;
    }

    /**
     *
     * @param DOMDocument $dom_struct
     * @return databox
     */
    public function saveStructure(DOMDocument $dom_struct)
    {

        $dom_struct->documentElement
          ->setAttribute("modification_date", $now = date("YmdHis"));

        $sql = "UPDATE pref SET value= :structure, updated_on= :now
        WHERE prop='structure'";

        $this->structure = $dom_struct->saveXML();

        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute(
          array(
            ':structure' => $this->structure,
            ':now'       => $now
          )
        );
        $stmt->closeCursor();

        $this->_sxml_structure = $this->_dom_structure = $this->_xpath_structure = null;

        $com_struct = $this->get_dom_structure();
        $xp_struct  = $this->get_xpath_structure();

        $this->meta_struct = null;

        $appbox = appbox::get_instance(\bootstrap::getCore());
        $appbox->delete_data_from_cache(appbox::CACHE_LIST_BASES);
        $this->delete_data_from_cache(self::CACHE_STRUCTURE);
        $this->delete_data_from_cache(self::CACHE_META_STRUCT);

        cache_databox::update($this->get_sbas_id(), 'structure');

        return $this;
    }

    public function saveCterms(DOMDocument $dom_cterms)
    {

        $dom_cterms->documentElement->setAttribute("modification_date", $now = date("YmdHis"));

        $sql = "UPDATE pref SET value = :xml, updated_on = :date
                WHERE prop='cterms'";

        $this->cterms = $dom_cterms->saveXML();
        $params = array(
          ':xml'  => $this->cterms
          , ':date' => $now
        );

        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();


        return $this;
    }

    protected $thesaurus;

    public function saveThesaurus(DOMDocument $dom_thesaurus)
    {

        $dom_thesaurus->documentElement->setAttribute("modification_date", $now = date("YmdHis"));
        $this->thesaurus = $dom_thesaurus->saveXML();

        $sql  = "UPDATE pref SET value = :xml, updated_on = :date WHERE prop='thesaurus'";
        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute(array(':xml'  => $this->thesaurus, ':date' => $now));
        $stmt->closeCursor();
        $this->delete_data_from_cache(databox::CACHE_THESAURUS);

        return $this;
    }

    /**
     *
     * @param system_file $data_template
     * @param string $path_web
     * @param string $path_doc
     * @param string $baseurl
     * @return databox
     */
    public function setNewStructure(system_file $data_template, $path_web, $path_doc, $baseurl)
    {
        $contents = file_get_contents($data_template->getPathname());

        $baseurl = $baseurl ? p4string::addEndSlash($baseurl) : '';

        $contents = str_replace(
          array("{{dataurl}}", "{{basename}}", "{{datapathweb}}", "{{datapathnoweb}}")
          , array($baseurl, $this->dbname, $path_web, $path_doc)
          , $contents
        );

        $dom_doc = new DOMDocument();
        $dom_doc->loadXML($contents);
        $this->saveStructure($dom_doc);

        $this->feed_meta_fields();
//    exit;
        return $this;
    }

    protected function feed_meta_fields()
    {
        $sxe = $this->get_sxml_structure();

        foreach ($sxe->description->children() as $fname => $field)
        {
            $dom_struct = $this->get_dom_structure();
            $xp_struct  = $this->get_xpath_structure();
            $fname      = (string) $fname;
            $src        = trim(isset($field['src']) ? $field['src'] : '');

            $meta_id = isset($field['meta_id']) ? $field['meta_id'] : null;
            if ( ! is_null($meta_id))
                continue;


            $nodes = $xp_struct->query('/record/description/' . $fname);
            if ($nodes->length > 0)
            {
                $nodes->item(0)->parentNode->removeChild($nodes->item(0));
            }
            $this->saveStructure($dom_struct);

            $type = isset($field['type']) ? $field['type'] : 'string';
            $type = in_array($type
                , array(
                databox_field::TYPE_DATE
                , databox_field::TYPE_NUMBER
                , databox_field::TYPE_STRING
                , databox_field::TYPE_TEXT
                )
              ) ? $type : databox_field::TYPE_STRING;

            $meta_struct_field = databox_field::create($this, $fname);
            $meta_struct_field
              ->set_readonly(isset($field['readonly']) ? $field['readonly'] : 0)
              ->set_indexable(isset($field['index']) ? $field['index'] : '1')
              ->set_separator(isset($field['separator']) ? $field['separator'] : '')
              ->set_required((isset($field['required']) && $field['required'] == 1))
              ->set_type($type)
              ->set_tbranch(isset($field['tbranch']) ? $field['tbranch'] : '')
              ->set_thumbtitle(isset($field['thumbtitle']) ? $field['thumbtitle'] : (isset($field['thumbTitle']) ? $field['thumbTitle'] : '0'))
              ->set_multi(isset($field['multi']) ? $field['multi'] : 0)
              ->set_report(isset($field['report']) ? $field['report'] : '1')
              ->save();

            try
            {
                $meta_struct_field->set_source($src)->save();
            }
            catch (Exception $e)
            {

            }
        }

        return $this;
    }

    /**
     *
     * @param User_Interface $user
     * @return databox
     */
    public function registerAdmin(User_Interface $user)
    {
        $conn     = connection::getPDOConnection();
        $registry = registry::get_instance();

        $user->ACL()
          ->give_access_to_sbas(array($this->get_sbas_id()))
          ->update_rights_to_sbas(
            $this->get_sbas_id(), array(
            'bas_manage'        => 1, 'bas_modify_struct' => 1,
            'bas_modif_th'      => 1, 'bas_chupub'        => 1
            )
        );


        $sql  = "SELECT * FROM coll";
        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute();
        $rs   = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $sql  = "INSERT INTO bas
                            (base_id, active, server_coll_id, sbas_id) VALUES
                            (null,'1', :coll_id, :sbas_id)";
        $stmt = $conn->prepare($sql);

        $base_ids = array();
        foreach ($rs as $row)
        {
            try
            {
                $stmt->execute(array(':coll_id'  => $row['coll_id'], ':sbas_id'  => $this->get_sbas_id()));
                $base_ids[] = $base_id    = $conn->lastInsertId();

                if ( ! empty($row['logo']))
                {
                    file_put_contents($registry->get('GV_RootPath') . 'config/minilogos/' . $base_id, $row['logo']);
                }
            }
            catch (Exception $e)
            {
                unset($e);
            }
        }

        $user->ACL()->give_access_to_base($base_ids);
        foreach ($base_ids as $base_id)
        {
            $user->ACL()->update_rights_to_base($base_id, array(
              'canpush'         => 1, 'cancmd'          => 1
              , 'canputinalbum'   => 1, 'candwnldhd'      => 1, 'candwnldpreview' => 1, 'canadmin'        => 1
              , 'actif'           => 1, 'canreport'       => 1, 'canaddrecord'    => 1, 'canmodifrecord'  => 1
              , 'candeleterecord' => 1, 'chgstatus'       => 1, 'imgtools'        => 1, 'manage'          => 1
              , 'modify_struct'   => 1, 'nowatermark'     => 1
              )
            );
        }

        $stmt->closeCursor();

        return $this;
    }

    /**
     *
     * @param <type> $sbas_id
     * @return <type>
     */
    public static function getPrintLogo($sbas_id)
    {
        $registry = registry::get_instance();

        $out      = '';
        if (is_file(($filename = $registry->get('GV_RootPath') . 'config/minilogos/logopdf_' . $sbas_id . '.jpg')))
            $out      = file_get_contents($filename);

        return $out;
    }

    public function clear_logs()
    {
        foreach (array('log', 'exports', 'quest') as $table)
        {
            $sql  = 'TRUNCATE ' . $table;
            $stmt = $this->get_connection()->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();
        }

        return $this;
    }

    public function reindex()
    {
        $sql  = 'UPDATE pref SET updated_on="0000-00-00 00:00:00" WHERE prop="indexes"';
        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        return $this;
    }

    /**
     *
     * @param <type> $sbas_id
     * @return DOMDocument
     */
    public function get_dom_thesaurus()
    {
        $sbas_id = $this->get_sbas_id();
        if (isset(self::$_dom_thesaurus[$sbas_id]))
        {
            return self::$_dom_thesaurus[$sbas_id];
        }

        $thesaurus = $this->get_thesaurus();

        if ($thesaurus && ($tmp = DomDocument::loadXML($thesaurus)) !== false)
            self::$_dom_thesaurus[$sbas_id] = $tmp;
        else
            self::$_dom_thesaurus[$sbas_id] = false;

        return self::$_dom_thesaurus[$sbas_id];
    }

    /**
     *
     * @param <type> $sbas_id
     * @return DOMXpath
     */
    public function get_xpath_thesaurus()
    {
        $sbas_id = $this->get_sbas_id();
        if (isset(self::$_xpath_thesaurus[$sbas_id]))
        {
            return self::$_xpath_thesaurus[$sbas_id];
        }

        $DOM_thesaurus = $this->get_dom_thesaurus();

        if ($DOM_thesaurus && ($tmp = new thesaurus_xpath($DOM_thesaurus)) !== false)
            self::$_xpath_thesaurus[$sbas_id] = $tmp;
        else
            self::$_xpath_thesaurus[$sbas_id] = false;

        return self::$_xpath_thesaurus[$sbas_id];
    }

    /**
     *
     * @param int $sbas_id
     * @return SimpleXMLElement
     */
    public function get_sxml_thesaurus()
    {
        $sbas_id = $this->get_sbas_id();
        if (isset(self::$_sxml_thesaurus[$sbas_id]))
        {
            return self::$_sxml_thesaurus[$sbas_id];
        }

        $thesaurus = $this->get_thesaurus();

        if ($thesaurus && ($tmp = simplexml_load_string($thesaurus)) !== false)
            self::$_sxml_thesaurus[$sbas_id] = $tmp;
        else
            self::$_sxml_thesaurus[$sbas_id] = false;

        return self::$_sxml_thesaurus[$sbas_id];
    }

    /**
     *
     * @param int $sbas_id
     * @return string
     */
    public function get_thesaurus()
    {
        try
        {
            $this->thesaurus = $this->get_data_from_cache(self::CACHE_THESAURUS);

            return $this->thesaurus;
        }
        catch (Exception $e)
        {
            unset($e);
        }

        $thesaurus = false;
        try
        {
            $sql  = 'SELECT value AS thesaurus FROM pref WHERE prop="thesaurus" LIMIT 1;';
            $stmt = $this->get_connection()->prepare($sql);
            $stmt->execute();
            $row  = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            $this->thesaurus = $row['thesaurus'];
            $this->set_data_to_cache($this->thesaurus, self::CACHE_THESAURUS);
        }
        catch (Exception $e)
        {
            unset($e);
        }

        return $this->thesaurus;
    }

    /**
     *
     * @return string
     */
    public function get_structure()
    {
        if ($this->structure)
            return $this->structure;
        $this->structure = $this->retrieve_structure();

        return $this->structure;
    }

    protected function retrieve_structure()
    {
        try
        {
            return $this->get_data_from_cache(self::CACHE_STRUCTURE);
        }
        catch (Exception $e)
        {

        }

        $structure = null;
        $sql       = "SELECT value FROM pref WHERE prop='structure'";
        $stmt      = $this->get_connection()->prepare($sql);
        $stmt->execute();
        $row       = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($row)
            $structure = $row['value'];
        $this->set_data_to_cache($structure, self::CACHE_STRUCTURE);

        return $structure;
    }

    protected $cterms;

    /**
     *
     * @return string
     */
    public function get_cterms()
    {
        if ($this->cterms)
            return $this->cterms;

        $sql  = "SELECT value FROM pref WHERE prop='cterms'";
        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute();
        $row  = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($row)
            $this->cterms = $row['value'];

        return $this->cterms;
    }

    /**
     *
     * @param <type> $sbas_id
     * @return DOMDocument
     */
    public function get_dom_structure()
    {
        if ($this->_dom_structure)
        {
            return $this->_dom_structure;
        }

        $structure = $this->get_structure();

        $dom = new DOMDocument();

        $dom->standalone = true;
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        if ($structure && $dom->loadXML($structure) !== false)
            $this->_dom_structure = $dom;
        else
            $this->_dom_structure = false;

        return $this->_dom_structure;
    }

    /**
     *
     * @param <type> $sbas_id
     * @return DOMDocument
     */
    public function get_dom_cterms()
    {
        if ($this->_dom_cterms)
        {
            return $this->_dom_cterms;
        }

        $cterms = $this->get_cterms();

        $dom = new DOMDocument();

        $dom->standalone = true;
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        if ($cterms && $dom->loadXML($cterms) !== false)
            $this->_dom_cterms = $dom;
        else
            $this->_dom_cterms = false;

        return $this->_dom_cterms;
    }

    /**
     *
     * @return SimpleXMLElement
     */
    public function get_sxml_structure()
    {
        if ($this->_sxml_structure)
        {
            return $this->_sxml_structure;
        }

        $structure = $this->get_structure();

        if ($structure && ($tmp = simplexml_load_string($structure)) !== false)
            $this->_sxml_structure = $tmp;
        else
            $this->_sxml_structure = false;

        return $this->_sxml_structure;
    }

    /**
     *
     * @param <type> $sbas_id
     * @return DOMXpath
     */
    public function get_xpath_structure()
    {
        if ($this->_xpath_structure)
        {
            return $this->_xpath_structure;
        }

        $dom_doc = $this->get_dom_structure();

        if ($dom_doc && ($tmp = new DOMXpath($dom_doc)) !== false)
            $this->_xpath_structure = $tmp;
        else
            $this->_xpath_structure = false;

        return $this->_xpath_structure;
    }

    /**
     *
     * @param string $structure
     * @return Array
     */
    public static function get_structure_errors($structure)
    {
        $sx_structure = simplexml_load_string($structure);

        $subdefgroup = $sx_structure->subdefs[0];
        $AvSubdefs   = array();

        $errors = array();

        foreach ($subdefgroup as $k => $subdefs)
        {
            $subdefgroup_name = trim((string) $subdefs->attributes()->name);

            if ($subdefgroup_name == '')
            {
                $errors[] = _('ERREUR : TOUTES LES BALISES subdefgroup necessitent un attribut name');
                continue;
            }

            if ( ! isset($AvSubdefs[$subdefgroup_name]))
                $AvSubdefs[$subdefgroup_name] = array();

            foreach ($subdefs as $sd)
            {
                $sd_name  = trim(mb_strtolower((string) $sd->attributes()->name));
                $sd_class = trim(mb_strtolower((string) $sd->attributes()->class));
                if ($sd_name == '' || isset($AvSubdefs[$subdefgroup_name][$sd_name]))
                {
                    $errors[] = _('ERREUR : Les name de subdef sont uniques par groupe de subdefs et necessaire');
                    continue;
                }
                if ( ! in_array($sd_class, array('thumbnail', 'preview', 'document')))
                {
                    $errors[]                               = _('ERREUR : La classe de subdef est necessaire et egal a "thumbnail","preview" ou "document"');
                    continue;
                }
                $AvSubdefs[$subdefgroup_name][$sd_name] = $sd;
            }
        }

        return $errors;
    }

    protected $cgus;

    public function get_cgus()
    {
        if ($this->cgus)
            return $this->cgus;

        $this->load_cgus();

        return $this->cgus;
    }

    protected function load_cgus()
    {
        try
        {
            $this->cgus = $this->get_data_from_cache(self::CACHE_CGUS);

            return $this;
        }
        catch (Exception $e)
        {

        }

        $sql  = 'SELECT value, locale, updated_on FROM pref WHERE prop ="ToU"';
        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute();
        $rs   = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row)
        {
            $TOU[$row['locale']] = array('updated_on' => $row['updated_on'], 'value'      => $row['value']);
        }

        $missing_locale = array();

        $avLanguages      = User_Adapter::avLanguages();
        foreach ($avLanguages as $lang)
            foreach ($lang as $k => $v)
                if ( ! isset($TOU[$k]))
                    $missing_locale[] = $k;

        $date_obj = new DateTime();
        $date     = phraseadate::format_mysql($date_obj);
        $sql      = "INSERT INTO pref (id, prop, value, locale, updated_on, created_on)
              VALUES (null, 'ToU', '', :locale, :date, NOW())";
        $stmt     = $this->get_connection()->prepare($sql);
        foreach ($missing_locale as $v)
        {
            $stmt->execute(array(':locale' => $v, ':date'   => $date));
            $TOU[$v]  = array('updated_on' => $date, 'value'      => '');
        }
        $stmt->closeCursor();
        $this->cgus = $TOU;

        $this->set_data_to_cache($TOU, self::CACHE_CGUS);

        return $this;
    }

    const CACHE_CGUS = 'cgus';

    public function update_cgus($locale, $terms, $reset_date)
    {
        $terms = str_replace(array("\r\n", "\n", "\r"), array('', '', ''), strip_tags($terms, '<p><strong><a><ul><ol><li><h1><h2><h3><h4><h5><h6>'));
        $sql = 'UPDATE pref SET value = :terms ';

        if ($reset_date)
            $sql .= ', updated_on=NOW() ';

        $sql .= ' WHERE prop="ToU" AND locale = :locale';

        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute(array(':terms'  => $terms, ':locale' => $locale));
        $stmt->closeCursor();
        $update   = true;
        $this->cgus = null;
        $this->delete_data_from_cache(self::CACHE_CGUS);

        return $this;
    }

    public function delete_data_from_cache($option = null)
    {
        switch ($option)
        {
            case self::CACHE_CGUS:
                $this->cgus = null;
                break;
            case self::CACHE_META_STRUCT:
                $this->meta_struct = null;
                break;
            case self::CACHE_STRUCTURE:
                $this->_dom_structure = $this->_xpath_structure = $this->structure = $this->_sxml_structure = null;
                break;
            case self::CACHE_THESAURUS:
                $this->thesaurus = null;
                break;
            default:
                break;
        }
        parent::delete_data_from_cache($option);
    }

}
