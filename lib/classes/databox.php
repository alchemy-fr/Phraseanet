<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Symfony\Component\Filesystem\Filesystem;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Translation\TranslatorInterface;

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
    protected static $_xpath_thesaurus = [];

    /**
     *
     * @var Array
     */
    protected static $_dom_thesaurus = [];

    /**
     *
     * @var Array
     */
    protected static $_thesaurus = [];

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
    protected static $_sxml_thesaurus = [];

    const BASE_TYPE = self::DATA_BOX;
    const CACHE_BASE_DATABOX = 'base_infos';
    const CACHE_META_STRUCT = 'meta_struct';
    const CACHE_THESAURUS = 'thesaurus';
    const CACHE_COLLECTIONS = 'collections';
    const CACHE_STRUCTURE = 'structure';
    const PIC_PDF = 'logopdf';

    protected $cache;
    protected $connection;
    protected $app;
    private $labels = [];
    private $ord;
    private $viewname;
    private $loaded = false;

    public function __construct(Application $app, $sbas_id)
    {
        assert(is_int($sbas_id));
        assert($sbas_id > 0);

        $this->app = $app;
        $this->id = $sbas_id;

        $connection_params = phrasea::sbas_params($this->app);

        if (! isset($connection_params[$sbas_id])) {
            throw new NotFoundHttpException(sprintf('databox %d not found', $sbas_id));
        }

        $this->connection = connection::getPDOConnection($app, $sbas_id);

        $this->host = $connection_params[$sbas_id]['host'];
        $this->port = $connection_params[$sbas_id]['port'];
        $this->user = $connection_params[$sbas_id]['user'];
        $this->passwd = $connection_params[$sbas_id]['pwd'];
        $this->dbname = $connection_params[$sbas_id]['dbname'];

        return $this;
    }

    private function load()
    {
        if ($this->loaded) {
            return;
        }

        try {
            $row = $this->get_data_from_cache(static::CACHE_BASE_DATABOX);
        } catch (\Exception $e) {
            $sql = 'SELECT ord, viewname, label_en, label_fr, label_de, label_nl
                FROM sbas WHERE sbas_id = :sbas_id';
            $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
            $stmt->execute(['sbas_id' => $this->id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            $this->set_data_to_cache($row, static::CACHE_BASE_DATABOX);
        }

        if (!$row) {
            throw new NotFoundHttpException(sprintf('databox %d not found', $this->id));
        }

        $this->ord = $row['ord'];
        $this->viewname = $row['viewname'];
        $this->labels['fr'] = $row['label_fr'];
        $this->labels['en'] = $row['label_en'];
        $this->labels['de'] = $row['label_de'];
        $this->labels['nl'] = $row['label_nl'];

        $this->loaded = true;
    }

    public function get_viewname()
    {
        $this->load();

        return $this->viewname ? : $this->dbname;
    }

    public function set_viewname($viewname)
    {
        $this->load();

        $sql = 'UPDATE sbas SET viewname = :viewname WHERE sbas_id = :sbas_id';

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute([':viewname' => $viewname, ':sbas_id' => $this->id]);
        $stmt->closeCursor();

        $this->delete_data_from_cache(static::CACHE_BASE_DATABOX);
        $this->app['phraseanet.appbox']->delete_data_from_cache(appbox::CACHE_LIST_BASES);
        cache_databox::update($this->app, $this->id, 'structure');

        $this->viewname = $viewname;

        return $this;
    }

    public function get_ord()
    {
        $this->load();

        return $this->ord;
    }

    public function get_appbox()
    {
        return $this->app['phraseanet.appbox'];
    }

    public function get_collections()
    {
        $ret = [];

        foreach ($this->get_available_collections() as $coll_id) {
            try {
                $ret[] = collection::get_from_coll_id($this->app, $this, $coll_id);
            } catch (Exception $e) {

            }
        }

        return $ret;
    }

    protected function get_available_collections()
    {
        try {
            return $this->get_data_from_cache(self::CACHE_COLLECTIONS);
        } catch (Exception $e) {

        }

        $conn = connection::getPDOConnection($this->app);

        $sql = "SELECT b.server_coll_id FROM sbas s, bas b
            WHERE s.sbas_id = b.sbas_id AND b.sbas_id = :sbas_id
              AND b.active = '1'
            ORDER BY s.ord ASC, b.ord,b.base_id ASC";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':sbas_id' => $this->id]);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $ret = [];

        foreach ($rs as $row) {
            $ret[] = (int) $row['server_coll_id'];
        }
        $this->set_data_to_cache($ret, self::CACHE_COLLECTIONS);

        return $ret;
    }

    /**
     *
     * @param  int            $record_id
     * @param  int            $number
     * @return record_adapter
     */
    public function get_record($record_id, $number = null)
    {
        return new record_adapter($this->app, $this->id, $record_id, $number);
    }

    public function get_label($code, $substitute = true)
    {
        $this->load();

        if (!array_key_exists($code, $this->labels)) {
            throw new InvalidArgumentException(sprintf('Code %s is not defined', $code));
        }

        if ($substitute) {
            return isset($this->labels[$code]) ? $this->labels[$code] : $this->get_viewname();
        } else {
            return $this->labels[$code];
        }
    }

    public function set_label($code, $label)
    {
        $this->load();

        if (!array_key_exists($code, $this->labels)) {
            throw new InvalidArgumentException(sprintf('Code %s is not defined', $code));
        }

        $sql = "UPDATE sbas SET label_$code = :label
            WHERE sbas_id = :sbas_id";
        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute([':label' => $label, ':sbas_id'   => $this->id]);
        $stmt->closeCursor();

        $this->labels[$code] = $label;

        $this->delete_data_from_cache(static::CACHE_BASE_DATABOX);

        phrasea::reset_sbasDatas($this->app['phraseanet.appbox']);

        return $this;
    }

    /**
     *
     * @return databox_status
     */
    public function get_statusbits()
    {
        return databox_status::getStatus($this->app, $this->id);
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

        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute();
        $rowbas = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        return ($rowbas ? $rowbas['n'] : null);
    }

    public function get_index_amount()
    {
        $sql = "SELECT COUNT(idx_id) AS n FROM idx";

        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute();
        $rowbas = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        return ($rowbas ? $rowbas['n'] : null);
    }

    public function get_thesaurus_hits()
    {
        $sql = "SELECT COUNT(thit_id) AS n FROM thit";

        $stmt = $this->get_connection()->prepare($sql);
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

        if ($sort == "obj") {
            $sortk1 = "name";
            $sortk2 = "asciiname";
        } else {
            $sortk1 = "asciiname";
            $sortk2 = "name";
        }

        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $rowbas) {
            if ( ! isset($trows[$rowbas[$sortk1]]))
                $trows[$rowbas[$sortk1]] = [];
            $trows[$rowbas[$sortk1]][$rowbas[$sortk2]] = [
                "coll_id"   => $rowbas["coll_id"],
                "asciiname" => $rowbas["asciiname"],
                "lostcoll"  => $rowbas["lostcoll"],
                "name"      => $rowbas["name"],
                "n"         => $rowbas["n"],
                "siz"       => $rowbas["siz"]
            ];
        }

        ksort($trows);
        foreach ($trows as $kgrp => $vgrp)
            ksort($trows[$kgrp]);

        return $trows;
    }

    public function get_record_amount()
    {
        $sql = "SELECT COUNT(record_id) AS n FROM record";
        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute();
        $rowbas = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $amount = $rowbas ? (int) $rowbas["n"] : null;

        return $amount;
    }

    public function get_indexed_record_amount()
    {

        $sql = "SELECT status & 3 AS status, SUM(1) AS n FROM record GROUP BY(status & 3)";
        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $ret = ['xml_indexed'       => 0, 'thesaurus_indexed' => 0];
        foreach ($rs as $row) {
            $status = $row['status'];
            if ($status & 1)
                $ret['xml_indexed'] += $row['n'];
            if ($status & 2)
                $ret['thesaurus_indexed'] += $row['n'];
        }

        return $ret;
    }

    public function unmount_databox()
    {
        foreach ($this->get_collections() as $collection) {
            $collection->unmount_collection($this->app);
        }

        $query = new User_Query($this->app);
        $total = $query->on_sbas_ids([$this->id])
            ->include_phantoms(false)
            ->include_special_users(true)
            ->include_invite(true)
            ->include_templates(true)
            ->get_total();
        $n = 0;
        while ($n < $total) {
            $results = $query->limit($n, 50)->execute()->get_results();
            foreach ($results as $user) {
                $this->app['acl']->get($user)->delete_data_from_cache(ACL::CACHE_RIGHTS_SBAS);
                $this->app['acl']->get($user)->delete_data_from_cache(ACL::CACHE_RIGHTS_BAS);
                $this->app['acl']->get($user)->delete_injected_rights_sbas($this);
            }
            $n+=50;
        }

        foreach ($this->app['EM']->getRepository('Phraseanet:StoryWZ')->findByDatabox($this->app, $this) as $story) {
            $this->app['EM']->remove($story);
        }

        foreach ($this->app['EM']->getRepository('Phraseanet:BasketElement')->findElementsByDatabox($this) as $element) {
            $this->app['EM']->remove($element);
        }

        $this->app['EM']->flush();

        $params = [':site_id' => $this->app['conf']->get(['main', 'key'])];

        $sql = 'DELETE FROM clients WHERE site_id = :site_id';
        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $sql = 'DELETE FROM memcached WHERE site_id = :site_id';
        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $sql = "DELETE FROM sbas WHERE sbas_id = :sbas_id";
        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute([':sbas_id' => $this->id]);
        $stmt->closeCursor();

        $sql = "DELETE FROM sbasusr WHERE sbas_id = :sbas_id";
        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute([':sbas_id' => $this->id]);
        $stmt->closeCursor();

        $this->app['phraseanet.appbox']->delete_data_from_cache(appbox::CACHE_LIST_BASES);

        return;
    }

    public static function create(Application $app, connection_pdo $connection, \SplFileInfo $data_template)
    {
        if ( ! file_exists($data_template->getRealPath())) {
            throw new \InvalidArgumentException($data_template->getRealPath() . " does not exist");
        }

        $credentials = $connection->get_credentials();

        $sql = 'SELECT sbas_id
            FROM sbas
            WHERE host = :host AND port = :port AND dbname = :dbname
              AND user = :user AND pwd = :password';

        $host = $credentials['hostname'];
        $port = $credentials['port'];
        $dbname = $credentials['dbname'];
        $user = $credentials['user'];
        $password = $credentials['password'];

        $params = [
            ':host'     => $host
            , ':port'     => $port
            , ':dbname'   => $dbname
            , ':user'     => $user
            , ':password' => $password
        ];

        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($row) {
            return $app['phraseanet.appbox']->get_databox((int) $row['sbas_id']);
        }

        try {
            $sql = 'CREATE DATABASE `' . $dbname . '`
              CHARACTER SET utf8 COLLATE utf8_unicode_ci';
            $stmt = $connection->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();
        } catch (Exception $e) {

        }

        $sql = 'USE `' . $dbname . '`';
        $stmt = $connection->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $sql = 'SELECT MAX(ord) as ord FROM sbas';
        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        if ($row)
            $ord = $row['ord'] + 1;

        $sql = 'INSERT INTO sbas (sbas_id, ord, host, port, dbname, sqlengine, user, pwd)
              VALUES (null, :ord, :host, :port, :dbname, "MYSQL", :user, :password)';
        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute([
            ':ord'      => $ord
            , ':host'     => $host
            , ':port'     => $port
            , ':dbname'   => $dbname
            , ':user'     => $user
            , ':password' => $password
        ]);
        $stmt->closeCursor();
        $sbas_id = (int) $app['phraseanet.appbox']->get_connection()->lastInsertId();

        $app['phraseanet.appbox']->delete_data_from_cache(appbox::CACHE_LIST_BASES);

        $databox = $app['phraseanet.appbox']->get_databox($sbas_id);
        $databox->insert_datas();
        $databox->setNewStructure(
            $data_template, $app['conf']->get(['main', 'storage', 'subdefs', 'default-dir'])
        );

        return $databox;
    }

    /**
     *
     * @param  Application $app
     * @param  string      $host
     * @param  int         $port
     * @param  string      $user
     * @param  string      $password
     * @param  string      $dbname
     * @param  registry    $registry
     * @return databox
     */
    public static function mount(Application $app, $host, $port, $user, $password, $dbname)
    {
        new connection_pdo('test', $host, $port, $user, $password, $dbname, [], $app['debug']);

        $conn = $app['phraseanet.appbox']->get_connection();
        $sql = 'SELECT MAX(ord) as ord FROM sbas';
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        if ($row)
            $ord = $row['ord'] + 1;

        $sql = 'INSERT INTO sbas (sbas_id, ord, host, port, dbname, sqlengine, user, pwd)
              VALUES (null, :ord, :host, :port, :dbname, "MYSQL", :user, :password)';
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':ord'      => $ord
            , ':host'     => $host
            , ':port'     => $port
            , ':dbname'   => $dbname
            , ':user'     => $user
            , ':password' => $password
        ]);

        $stmt->closeCursor();
        $sbas_id = (int) $conn->lastInsertId();

        $app['phraseanet.appbox']->delete_data_from_cache(appbox::CACHE_LIST_BASES);

        $databox = $app['phraseanet.appbox']->get_databox($sbas_id);

        $databox->delete_data_from_cache(databox::CACHE_COLLECTIONS);
        $app['phraseanet.appbox']->delete_data_from_cache(appbox::CACHE_SBAS_IDS);

        phrasea::reset_sbasDatas($app['phraseanet.appbox']);

        cache_databox::update($app, $databox->get_sbas_id(), 'structure');

        return $databox;
    }

    public function get_base_type()
    {
        return self::BASE_TYPE;
    }

    public function get_cache_key($option = null)
    {
        return 'databox_' . $this->id . '_' . ($option ? $option . '_' : '');
    }

    /**
     *
     * @return databox_descriptionStructure
     */
    public function get_meta_structure()
    {
        if ($this->meta_struct) {
            return $this->meta_struct;
        }

        try {
            $metaStructData = $this->get_data_from_cache(self::CACHE_META_STRUCT);
        } catch (Exception $e) {
            $sql = 'SELECT id, name FROM metadatas_structure ORDER BY sorter ASC';
            $stmt = $this->get_connection()->prepare($sql);
            $stmt->execute();
            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            $metaStructData = $rs;
            $this->set_data_to_cache($metaStructData, self::CACHE_META_STRUCT);
        }

        $this->meta_struct = new databox_descriptionStructure();

        foreach ($metaStructData as $row) {
            $this->meta_struct->add_element(databox_field::get_instance($this->app, $this, $row['id']));
        }

        return $this->meta_struct;
    }

    /**
     *
     * @return databox_subdefsStructure
     */
    public function get_subdef_structure()
    {
        if (! $this->subdef_struct) {
            $this->subdef_struct = new databox_subdefsStructure($this, $this->app['translator']);
        }

        return $this->subdef_struct;
    }

    public static function dispatch(Filesystem $filesystem, $repository_path, $date = false)
    {
        if (! $date) {
            $date = date('Y-m-d H:i:s');
        }

        $repository_path = p4string::addEndSlash($repository_path);

        $year = date('Y', strtotime($date));
        $month = date('m', strtotime($date));
        $day = date('d', strtotime($date));

        $n = 0;
        $comp = $year . DIRECTORY_SEPARATOR . $month . DIRECTORY_SEPARATOR . $day . DIRECTORY_SEPARATOR;

        $pathout = $repository_path . $comp;

        while (($pathout = $repository_path . $comp . self::addZeros($n)) && is_dir($pathout) && iterator_count(new \DirectoryIterator($pathout)) > 100) {
            $n ++;
        }

        $filesystem->mkdir($pathout, 0750);

        return $pathout . DIRECTORY_SEPARATOR;
    }

    public function delete()
    {
        $sql = 'DROP DATABASE `' . $this->get_dbname() . '`';
        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $this->app['phraseanet.appbox']->delete_data_from_cache(appbox::CACHE_LIST_BASES);

        return;
    }

    private static function addZeros($n, $length = 5)
    {
        while (strlen($n) < $length) {
            $n = '0' . $n;
        }

        return $n;
    }

    public function get_serialized_server_info()
    {
        return sprintf("%s@%s:%s (MySQL %s)", $this->dbname, $this->host, $this->port, $this->get_connection()->server_info());
    }

    public static function get_available_dcfields()
    {
        return [
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
        ];
    }

    /**
     *
     * @return Array
     */
    public function get_mountable_colls()
    {
        $conn = connection::getPDOConnection($this->app);
        $colls = [];

        $sql = 'SELECT server_coll_id FROM bas WHERE sbas_id = :sbas_id';
        $stmt = $conn->prepare($sql);
        $stmt->execute([':sbas_id' => $this->id]);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            $colls[] = (int) $row['server_coll_id'];
        }

        $mountable_colls = [];

        $sql = 'SELECT coll_id, asciiname FROM coll';

        if (count($colls) > 0) {
            $sql .= ' WHERE coll_id NOT IN (' . implode(',', $colls) . ')';
        }

        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            $mountable_colls[$row['coll_id']] = $row['asciiname'];
        }

        return $mountable_colls;
    }

    public function get_activable_colls()
    {
        $conn = connection::getPDOConnection($this->app);
        $base_ids = [];

        $sql = 'SELECT base_id FROM bas WHERE sbas_id = :sbas_id AND active = "0"';
        $stmt = $conn->prepare($sql);
        $stmt->execute([':sbas_id' => $this->id]);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            $base_ids[] = (int) $row['base_id'];
        }

        return $base_ids;
    }

    /**
     *
     * @param  DOMDocument $dom_struct
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
            [
                ':structure' => $this->structure,
                ':now'       => $now
            ]
        );
        $stmt->closeCursor();

        $this->_sxml_structure = $this->_dom_structure = $this->_xpath_structure = null;

        $this->meta_struct = null;

        $this->app['phraseanet.appbox']->delete_data_from_cache(appbox::CACHE_LIST_BASES);
        $this->delete_data_from_cache(self::CACHE_STRUCTURE);
        $this->delete_data_from_cache(self::CACHE_META_STRUCT);

        cache_databox::update($this->app, $this->id, 'structure');

        return $this;
    }

    public function saveCterms(DOMDocument $dom_cterms)
    {

        $dom_cterms->documentElement->setAttribute("modification_date", $now = date("YmdHis"));

        $sql = "UPDATE pref SET value = :xml, updated_on = :date
                WHERE prop='cterms'";

        $this->cterms = $dom_cterms->saveXML();
        $params = [
            ':xml'  => $this->cterms
            , ':date' => $now
        ];

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

        $sql = "UPDATE pref SET value = :xml, updated_on = :date WHERE prop='thesaurus'";
        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute([':xml'  => $this->thesaurus, ':date' => $now]);
        $stmt->closeCursor();
        $this->delete_data_from_cache(databox::CACHE_THESAURUS);

        return $this;
    }

    public function setNewStructure(\SplFileInfo $data_template, $path_doc)
    {
        if ( ! file_exists($data_template->getPathname())) {
            throw new \InvalidArgumentException(sprintf('File %s does not exists'));
        }

        $contents = file_get_contents($data_template->getPathname());

        $contents = str_replace(
            ["{{basename}}", "{{datapathnoweb}}"]
            , [$this->dbname, $path_doc]
            , $contents
        );

        $dom_doc = new DOMDocument();
        $dom_doc->loadXML($contents);
        $this->saveStructure($dom_doc);

        $this->feed_meta_fields();

        return $this;
    }

    public function feed_meta_fields()
    {
        $sxe = $this->get_sxml_structure();

        foreach ($sxe->description->children() as $fname => $field) {
            $dom_struct = $this->get_dom_structure();
            $xp_struct = $this->get_xpath_structure();
            $fname = (string) $fname;
            $src = trim(isset($field['src']) ? str_replace('/rdf:RDF/rdf:Description/', '', $field['src']) : '');

            $meta_id = isset($field['meta_id']) ? $field['meta_id'] : null;
            if ( ! is_null($meta_id))
                continue;

            $nodes = $xp_struct->query('/record/description/' . $fname);
            if ($nodes->length > 0) {
                $nodes->item(0)->parentNode->removeChild($nodes->item(0));
            }
            $this->saveStructure($dom_struct);

            $type = isset($field['type']) ? $field['type'] : 'string';
            $type = in_array($type
                    , [
                    databox_field::TYPE_DATE
                    , databox_field::TYPE_NUMBER
                    , databox_field::TYPE_STRING
                    , databox_field::TYPE_TEXT
                    ]
                ) ? $type : databox_field::TYPE_STRING;

            $multi = isset($field['multi']) ? (Boolean) (string) $field['multi'] : false;

            $meta_struct_field = databox_field::create($this->app, $this, $fname, $multi);
            $meta_struct_field
                ->set_readonly(isset($field['readonly']) ? (string) $field['readonly'] : 0)
                ->set_indexable(isset($field['index']) ? (string) $field['index'] : '1')
                ->set_separator(isset($field['separator']) ? (string) $field['separator'] : '')
                ->set_required((isset($field['required']) && (string) $field['required'] == 1))
                ->set_business((isset($field['business']) && (string) $field['business'] == 1))
                ->set_type($type)
                ->set_tbranch(isset($field['tbranch']) ? (string) $field['tbranch'] : '')
                ->set_thumbtitle(isset($field['thumbtitle']) ? (string) $field['thumbtitle'] : (isset($field['thumbTitle']) ? $field['thumbTitle'] : '0'))
                ->set_report(isset($field['report']) ? (string) $field['report'] : '1')
                ->save();

            try {
                $meta_struct_field->set_tag(\databox_field::loadClassFromTagName($src))->save();
            } catch (Exception $e) {
            }
        }

        return $this;
    }

    /**
     *
     * @param  User    $user
     * @return databox
     */
    public function registerAdmin(User $user)
    {
        $conn = connection::getPDOConnection($this->app);

        $this->app['acl']->get($user)
            ->give_access_to_sbas([$this->id])
            ->update_rights_to_sbas(
                $this->id, [
                'bas_manage'        => 1, 'bas_modify_struct' => 1,
                'bas_modif_th'      => 1, 'bas_chupub'        => 1
                ]
        );

        $sql = "SELECT * FROM coll";
        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $sql = "INSERT INTO bas
                            (base_id, active, server_coll_id, sbas_id) VALUES
                            (null,'1', :coll_id, :sbas_id)";
        $stmt = $conn->prepare($sql);

        $base_ids = [];
        foreach ($rs as $row) {
            try {
                $stmt->execute([':coll_id'  => $row['coll_id'], ':sbas_id'  => $this->id]);
                $base_ids[] = $base_id = $conn->lastInsertId();

                if ( ! empty($row['logo'])) {
                    file_put_contents($this->app['root.path'] . '/config/minilogos/' . $base_id, $row['logo']);
                }
            } catch (Exception $e) {
                unset($e);
            }
        }

        $this->app['acl']->get($user)->give_access_to_base($base_ids);
        foreach ($base_ids as $base_id) {
            $this->app['acl']->get($user)->update_rights_to_base($base_id, [
                'canpush'         => 1, 'cancmd'          => 1
                , 'canputinalbum'   => 1, 'candwnldhd'      => 1, 'candwnldpreview' => 1, 'canadmin'        => 1
                , 'actif'           => 1, 'canreport'       => 1, 'canaddrecord'    => 1, 'canmodifrecord'  => 1
                , 'candeleterecord' => 1, 'chgstatus'       => 1, 'imgtools'        => 1, 'manage'          => 1
                , 'modify_struct'   => 1, 'nowatermark'     => 1
                ]
            );
        }

        $stmt->closeCursor();

        return $this;
    }

    /**
     *
     * @param  <type> $sbas_id
     * @return <type>
     */
    public static function getPrintLogo($sbas_id)
    {
        $out = '';
        if (is_file(($filename = __DIR__ . '/../../config/minilogos/'.\databox::PIC_PDF.'_' . $sbas_id . '.jpg')))
            $out = file_get_contents($filename);

        return $out;
    }

    public function clear_logs()
    {
        foreach (['log', 'log_colls', 'log_docs', 'log_search', 'log_view', 'log_thumb'] as $table) {
            $sql = 'DELETE FROM ' . $table;
            $stmt = $this->get_connection()->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();
        }

        return $this;
    }

    public function reindex()
    {
        $sql = 'UPDATE pref SET updated_on="0000-00-00 00:00:00" WHERE prop="indexes"';
        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        return $this;
    }

    /**
     * @return DOMDocument
     */
    public function get_dom_thesaurus()
    {
        $sbas_id = $this->id;
        if (isset(self::$_dom_thesaurus[$sbas_id])) {
            return self::$_dom_thesaurus[$sbas_id];
        }

        $thesaurus = $this->get_thesaurus();

        $dom = new DOMDocument();

        if ($thesaurus && false !== $dom->loadXML($thesaurus)) {
            self::$_dom_thesaurus[$sbas_id] = $dom;
        } else {
            self::$_dom_thesaurus[$sbas_id] = false;
            unset($dom);
        }

        return self::$_dom_thesaurus[$sbas_id];
    }

    /**
     * @return DOMXpath
     */
    public function get_xpath_thesaurus()
    {
        $sbas_id = $this->id;
        if (isset(self::$_xpath_thesaurus[$sbas_id])) {
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
     * @return SimpleXMLElement
     */
    public function get_sxml_thesaurus()
    {
        $sbas_id = $this->id;
        if (isset(self::$_sxml_thesaurus[$sbas_id])) {
            return self::$_sxml_thesaurus[$sbas_id];
        }

        $thesaurus = $this->get_thesaurus();

        if ($thesaurus && false !== $tmp = simplexml_load_string($thesaurus))
            self::$_sxml_thesaurus[$sbas_id] = $tmp;
        else
            self::$_sxml_thesaurus[$sbas_id] = false;

        return self::$_sxml_thesaurus[$sbas_id];
    }

    /**
     * @return string
     */
    public function get_thesaurus()
    {
        try {
            $this->thesaurus = $this->get_data_from_cache(self::CACHE_THESAURUS);

            return $this->thesaurus;
        } catch (Exception $e) {
            unset($e);
        }

        try {
            $sql = 'SELECT value AS thesaurus FROM pref WHERE prop="thesaurus" LIMIT 1;';
            $stmt = $this->get_connection()->prepare($sql);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            $this->thesaurus = $row['thesaurus'];
            $this->set_data_to_cache($this->thesaurus, self::CACHE_THESAURUS);
        } catch (Exception $e) {
            unset($e);
        }

        return $this->thesaurus;
    }

    /**
     * @return string
     */
    public function get_structure()
    {
        if ($this->structure) {
            return $this->structure;
        }

        $this->structure = $this->retrieve_structure();

        return $this->structure;
    }

    protected function retrieve_structure()
    {
        try {
            return $this->get_data_from_cache(self::CACHE_STRUCTURE);
        } catch (Exception $e) {

        }

        $structure = null;
        $sql = "SELECT value FROM pref WHERE prop='structure'";
        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
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
        if ($this->cterms) {
            return $this->cterms;
        }

        $sql = "SELECT value FROM pref WHERE prop='cterms'";
        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($row)
            $this->cterms = $row['value'];

        return $this->cterms;
    }

    /**
     * @return DOMDocument
     */
    public function get_dom_structure()
    {
        if ($this->_dom_structure) {
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
     * @return DOMDocument
     */
    public function get_dom_cterms()
    {
        if ($this->_dom_cterms) {
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
        if ($this->_sxml_structure) {
            return $this->_sxml_structure;
        }

        $structure = $this->get_structure();

        if ($structure && false !== $tmp = simplexml_load_string($structure))
            $this->_sxml_structure = $tmp;
        else
            $this->_sxml_structure = false;

        return $this->_sxml_structure;
    }

    /**
     * @return DOMXpath
     */
    public function get_xpath_structure()
    {
        if ($this->_xpath_structure) {
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
     * @param  string $structure
     * @return Array
     */
    public static function get_structure_errors(TranslatorInterface $translator, $structure)
    {
        $sx_structure = simplexml_load_string($structure);

        $subdefgroup = $sx_structure->subdefs[0];
        $AvSubdefs = [];

        $errors = [];

        foreach ($subdefgroup as $k => $subdefs) {
            $subdefgroup_name = trim((string) $subdefs->attributes()->name);

            if ($subdefgroup_name == '') {
                $errors[] = $translator->trans('ERREUR : TOUTES LES BALISES subdefgroup necessitent un attribut name');
                continue;
            }

            if ( ! isset($AvSubdefs[$subdefgroup_name]))
                $AvSubdefs[$subdefgroup_name] = [];

            foreach ($subdefs as $sd) {
                $sd_name = trim(mb_strtolower((string) $sd->attributes()->name));
                $sd_class = trim(mb_strtolower((string) $sd->attributes()->class));
                if ($sd_name == '' || isset($AvSubdefs[$subdefgroup_name][$sd_name])) {
                    $errors[] = $translator->trans('ERREUR : Les name de subdef sont uniques par groupe de subdefs et necessaire');
                    continue;
                }
                if ( ! in_array($sd_class, ['thumbnail', 'preview', 'document'])) {
                    $errors[] = $translator->trans('ERREUR : La classe de subdef est necessaire et egal a "thumbnail","preview" ou "document"');
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
        if ($this->cgus) {
            return $this->cgus;
        }

        $this->load_cgus();

        return $this->cgus;
    }

    protected function load_cgus()
    {
        try {
            $this->cgus = $this->get_data_from_cache(self::CACHE_CGUS);

            return $this;
        } catch (Exception $e) {

        }

        $sql = 'SELECT value, locale, updated_on FROM pref WHERE prop ="ToU"';
        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            $TOU[$row['locale']] = ['updated_on' => $row['updated_on'], 'value'      => $row['value']];
        }

        $missing_locale = [];

        $avLanguages = $this->app['locales.available'];
        foreach ($avLanguages as $code => $language) {
            if (!isset($TOU[$code])) {
                $missing_locale[] = $code;
            }
        }

        $date_obj = new DateTime();
        $date = $this->app['date-formatter']->format_mysql($date_obj);
        $sql = "INSERT INTO pref (id, prop, value, locale, updated_on, created_on)
              VALUES (null, 'ToU', '', :locale, :date, NOW())";
        $stmt = $this->get_connection()->prepare($sql);
        foreach ($missing_locale as $v) {
            $stmt->execute([':locale' => $v, ':date'   => $date]);
            $TOU[$v] = ['updated_on' => $date, 'value'      => ''];
        }
        $stmt->closeCursor();
        $this->cgus = $TOU;

        $this->set_data_to_cache($TOU, self::CACHE_CGUS);

        return $this;
    }
    const CACHE_CGUS = 'cgus';

    public function update_cgus($locale, $terms, $reset_date)
    {
        $terms = str_replace(["\r\n", "\n", "\r"], ['', '', ''], strip_tags($terms, '<p><strong><a><ul><ol><li><h1><h2><h3><h4><h5><h6>'));
        $sql = 'UPDATE pref SET value = :terms ';

        if ($reset_date)
            $sql .= ', updated_on=NOW() ';

        $sql .= ' WHERE prop="ToU" AND locale = :locale';

        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute([':terms'    => $terms, ':locale'   => $locale]);
        $stmt->closeCursor();
        $this->cgus = null;
        $this->delete_data_from_cache(self::CACHE_CGUS);

        return $this;
    }

    public function __sleep()
    {
        $this->_sxml_structure = $this->_dom_structure = $this->_xpath_structure = null;

        $vars = [];

        foreach ($this as $key => $value) {
            if (in_array($key, ['app', 'meta_struct'])) {
                continue;
            }

            $vars[] = $key;
        }

        return $vars;
    }

    public function hydrate(Application $app)
    {
        $this->app = $app;
    }

    public function delete_data_from_cache($option = null)
    {
        switch ($option) {
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

    public static function purge()
    {
        self::$_xpath_thesaurus = self::$_dom_thesaurus = self::$_thesaurus = self::$_sxml_thesaurus = [];
    }
}
