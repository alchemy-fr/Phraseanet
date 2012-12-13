<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\HttpFoundation\File\File as SymfoFile;
use MediaAlchemyst\Specification\Image as ImageSpecification;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class appbox extends base
{
    /**
     *
     * @var int
     */
    protected $id;

    /**
     *
     * @var appbox
     */
    protected static $_instance;

    /**
     *
     * constant defining the app type
     */
    const BASE_TYPE = self::APPLICATION_BOX;

    /**
     *
     * @var <type>
     */
    protected $session;
    protected $cache;
    protected $connection;
    protected $registry;
    protected $Core;

    const CACHE_LIST_BASES = 'list_bases';
    const CACHE_SBAS_IDS = 'sbas_ids';

    /**
     * Singleton pattern
     *
     * @return appbox
     */
    public static function get_instance(\Alchemy\Phrasea\Core $Core, registryInterface &$registry = null)
    {
        if ( ! self::$_instance instanceof self) {
            self::$_instance = new self($Core, $registry);
        }

        return self::$_instance;
    }

    /**
     * Constructor
     *
     * @return appbox
     */
    protected function __construct(\Alchemy\Phrasea\Core $Core, registryInterface $registry = null)
    {
        $this->Core = $Core;
        if ( ! $registry)
            $registry = registry::get_instance($Core);
        $this->connection = connection::getPDOConnection(null, $registry);
        $this->registry = $registry;
        $this->session = Session_Handler::getInstance($this);

        $configuration = $Core->getConfiguration();

        $choosenConnexion = $configuration->getPhraseanet()->get('database');

        $connexion = $configuration->getConnexion($choosenConnexion);

        $this->host = $connexion->get('host');
        $this->port = $connexion->get('port');
        $this->user = $connexion->get('user');
        $this->passwd = $connexion->get('password');
        $this->dbname = $connexion->get('dbname');

        return $this;
    }

    public function write_collection_pic(collection $collection, SymfoFile $pathfile = null, $pic_type)
    {
        $core = \bootstrap::getCore();
        $filename = null;

        if ( ! is_null($pathfile)) {

            if ( ! in_array(mb_strtolower($pathfile->getMimeType()), array('image/gif', 'image/png', 'image/jpeg', 'image/jpg', 'image/pjpeg'))) {
                throw new \InvalidArgumentException('Invalid file format');
            }
            $filename = $pathfile->getPathname();
        }

        switch ($pic_type) {
            case collection::PIC_WM;
                $collection->reset_watermark();
                break;
            case collection::PIC_LOGO:
                if (null === $filename) {
                    break;
                }

                $imageSpec = new ImageSpecification();
                $imageSpec->setResizeMode(ImageSpecification::RESIZE_MODE_INBOUND_FIXEDRATIO);
                $imageSpec->setDimensions(120, 24);

                $tmp = tempnam(sys_get_temp_dir(), 'tmpdatabox') . '.jpg';

                try {
                    $core['media-alchemyst']
                        ->open($filename)
                        ->turninto($tmp, $imageSpec)
                        ->close();
                    $filename = $tmp;
                } catch (\MediaAlchemyst\Exception $e) {

                }
                break;
            case collection::PIC_PRESENTATION:
                break;
            case collection::PIC_STAMP:
                $collection->reset_stamp();
                break;
            default:
                throw new \InvalidArgumentException('unknown pic_type');
                break;
        }

        if ($pic_type == collection::PIC_LOGO) {
            $collection->update_logo($pathfile);
        }

        $registry = registry::get_instance();

        $file = $registry->get('GV_RootPath') . 'config/' . $pic_type . '/' . $collection->get_base_id();
        $custom_path = $registry->get('GV_RootPath') . 'www/custom/' . $pic_type . '/' . $collection->get_base_id();

        foreach (array($file, $custom_path) as $target) {
            if (is_file($target)) {
                $core['file-system']->remove($target);
            }

            if (null === $target || null === $filename) {
                continue;
            }

            $core['file-system']->mkdir(dirname($target), 0750);
            $core['file-system']->copy($filename, $target, true);
            $core['file-system']->chmod($target, 0760);
        }

        return $this;
    }

    public function write_databox_pic(databox $databox, SymfoFile $pathfile = null, $pic_type)
    {
        $core = \bootstrap::getCore();
        $filename = null;

        if ( ! is_null($pathfile)) {

            if ( ! in_array(mb_strtolower($pathfile->getMimeType()), array('image/jpeg', 'image/jpg', 'image/pjpeg', 'image/png', 'image/gif'))) {
                throw new \InvalidArgumentException('Invalid file format');
            }
        }

        if ( ! in_array($pic_type, array(databox::PIC_PDF))) {
            throw new \InvalidArgumentException('unknown pic_type');
        }

        if ($pathfile) {

            $filename = $pathfile->getPathname();

            $imageSpec = new ImageSpecification();
            $imageSpec->setResizeMode(ImageSpecification::RESIZE_MODE_INBOUND_FIXEDRATIO);
            $imageSpec->setDimensions(120, 35);

            $tmp = tempnam(sys_get_temp_dir(), 'tmpdatabox') . '.jpg';

            try {
                $core['media-alchemyst']
                    ->open($pathfile->getPathname())
                    ->turninto($tmp, $imageSpec)
                    ->close();
                $filename = $tmp;
            } catch (\MediaAlchemyst\Exception $e) {

            }
        }

        $registry = $databox->get_registry();
        $file = $registry->get('GV_RootPath') . 'config/minilogos/' . $pic_type . '_' . $databox->get_sbas_id();
        $custom_path = $registry->get('GV_RootPath') . 'www/custom/minilogos/' . $pic_type . '_' . $databox->get_sbas_id();

        foreach (array($file, $custom_path) as $target) {

            if (is_file($target)) {
                $core['file-system']->remove($target);
            }

            if (is_null($filename)) {
                continue;
            }

            $core['file-system']->mkdir(dirname($target));
            $core['file-system']->copy($filename, $target);
            $core['file-system']->chmod($target, 0760);
        }

        $databox->delete_data_from_cache('printLogo');

        return $this;
    }

    /**
     *
     * @param  collection $collection
     * @param  <type>     $ordre
     * @return appbox
     */
    public function set_collection_order(collection $collection, $ordre)
    {
        $sqlupd = "UPDATE bas SET ord = :ordre WHERE base_id = :base_id";
        $stmt = $this->get_connection()->prepare($sqlupd);
        $stmt->execute(array(':ordre'   => $ordre, ':base_id' => $collection->get_base_id()));
        $stmt->closeCursor();

        $collection->get_databox()->delete_data_from_cache(\databox::CACHE_COLLECTIONS);

        return $this;
    }

    /**
     *
     * @param  databox $databox
     * @param  <type>  $boolean
     * @return appbox
     */
    public function set_databox_indexable(databox $databox, $boolean)
    {
        $boolean = ! ! $boolean;
        $sql = 'UPDATE sbas SET indexable = :indexable WHERE sbas_id = :sbas_id';

        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute(array(
            ':indexable' => ($boolean ? '1' : '0'),
            ':sbas_id'   => $databox->get_sbas_id()
        ));
        $stmt->closeCursor();

        return $this;
    }

    /**
     *
     * @param  databox $databox
     * @return <type>
     */
    public function is_databox_indexable(databox $databox)
    {
        $sql = 'SELECT indexable FROM sbas WHERE sbas_id = :sbas_id';

        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute(array(':sbas_id' => $databox->get_sbas_id()));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $indexable = $row ? $row['indexable'] : null;

        return $indexable;
    }

    /**
     *
     * @param  databox $databox
     * @param  <type>  $viewname
     * @return appbox
     */
    public function set_databox_viewname(databox $databox, $viewname)
    {
        $viewname = strip_tags($viewname);
        $sql = 'UPDATE sbas SET viewname = :viewname WHERE sbas_id = :sbas_id';

        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute(array(':viewname' => $viewname, ':sbas_id'  => $databox->get_sbas_id()));
        $stmt->closeCursor();

        $appbox = appbox::get_instance(\bootstrap::getCore());
        $appbox->delete_data_from_cache(appbox::CACHE_LIST_BASES);
        cache_databox::update($databox->get_sbas_id(), 'structure');

        return $this;
    }

    /**
     *
     * @return const
     */
    public function get_base_type()
    {
        return self::BASE_TYPE;
    }

    public function forceUpgrade(Setup_Upgrade &$upgrader)
    {
        $from_version = $this->get_version();

        $upgrader->add_steps(7 + count($this->get_databoxes()));

        $registry = $this->get_registry();

        /**
         * Step 1
         */
        $upgrader->set_current_message(_('Flushing cache'));

        $this->Core['CacheService']->flushAll();

        $upgrader->add_steps_complete(1);

        $upgrader->set_current_message(_('Creating new tables'));
        $core = bootstrap::getCore();
        $em = $core->getEntityManager();
        //create schema

        if ($em->getConnection()->getDatabasePlatform()->supportsAlterTable()) {
            $tool = new \Doctrine\ORM\Tools\SchemaTool($em);
            $metas = $em->getMetadataFactory()->getAllMetadata();
            $tool->updateSchema($metas, true);
        }

        $upgrader->add_steps_complete(1);

        /**
         * Step 2
         */
        $upgrader->set_current_message(_('Purging directories'));

        $finder = new Symfony\Component\Finder\Finder();
        $finder->in(array(
            $registry->get('GV_RootPath') . 'tmp/cache_minify/',
            $registry->get('GV_RootPath') . 'tmp/cache_minify/',
        ))->ignoreVCS(true)->ignoreDotFiles(true);

        foreach ($finder as $file) {
            $core['file-system']->remove($file);
        }

        $upgrader->add_steps_complete(1);

        /**
         * Step 5
         */
        $upgrader->set_current_message(_('Copying files'));

        $filesystem = $core['file-system'];

        foreach (array(
        'config/custom_files/' => 'www/custom/',
        'config/minilogos/'    => 'www/custom/minilogos/',
        'config/stamp/'        => 'www/custom/stamp/',
        'config/status/'       => 'www/custom/status/',
        'config/wm/'           => 'www/custom/wm/',
        ) as $source => $target) {
            $filesystem->mirror($registry->get('GV_RootPath') . $source, $registry->get('GV_RootPath') . $target);
        }

        $upgrader->add_steps_complete(1);

        $advices = array();

        /**
         * Step 6
         */
        $upgrader->set_current_message(_('Upgrading appbox'));
        $advices = $this->upgradeDB(true, $upgrader);
        $upgrader->add_steps_complete(1);

        /**
         * Step 7
         */
        foreach ($this->get_databoxes() as $s) {
            $upgrader->set_current_message(sprintf(_('Upgrading %s'), $s->get_viewname()));
            $advices = array_merge($advices, $s->upgradeDB(true, $upgrader));
            $upgrader->add_steps_complete(1);
        }

        /**
         * Step 8
         */
        $upgrader->set_current_message(_('Post upgrade'));
        $this->post_upgrade($upgrader);
        $upgrader->add_steps_complete(1);

        /**
         * Step 9
         */
        $upgrader->set_current_message(_('Flushing cache'));

        $this->Core['CacheService']->flushAll();

        $upgrader->add_steps_complete(1);

        if (version_compare($from_version, '3.1') < 0) {
            $upgrader->addRecommendation(_('Your install requires data migration, please execute the following command'), 'bin/console system:upgrade-datas --from=3.1');
        } elseif (version_compare($from_version, '3.5') < 0) {
            $upgrader->addRecommendation(_('Your install requires data migration, please execute the following command'), 'bin/console system:upgrade-datas --from=3.5');
        }

        if (version_compare($from_version, '3.7') < 0) {
            $upgrader->addRecommendation(_('Your install might need to re-read technical datas'), 'bin/console records:rescan-technical-datas');
            $upgrader->addRecommendation(_('Your install might need to re-read technical datas'), 'bin/console records:build-missing-subdefs');
        }

        return $advices;
    }

    protected function post_upgrade(Setup_Upgrade &$upgrader)
    {
        $Core = bootstrap::getCore();

        $upgrader->add_steps(1 + count($this->get_databoxes()));
        $this->apply_patches($this->get_version(), $Core->getVersion()->getNumber(), true, $upgrader);
        $this->setVersion($Core->getVersion()->getNumber());
        $upgrader->add_steps_complete(1);

        foreach ($this->get_databoxes() as $databox) {
            $databox->apply_patches($databox->get_version(), $Core->getVersion()->getNumber(), true, $upgrader);
            $databox->setVersion($Core->getVersion()->getNumber());
            $upgrader->add_steps_complete(1);
        }

        return $this;
    }

    /**
     *
     * @param  registryInterface $registry
     * @param  type              $conn
     * @param  type              $dbname
     * @param  type              $write_file
     * @return type
     */
    public static function create(\Alchemy\Phrasea\Core $Core, registryInterface &$registry, connection_interface $conn, $dbname, $write_file = false)
    {
        $credentials = $conn->get_credentials();

        if ($conn->is_multi_db() && trim($dbname) === '') {
            throw new \Exception(_('Nom de base de donnee incorrect'));
        }

        if ($write_file) {
            if ($conn->is_multi_db() && ! isset($credentials['dbname'])) {
                $credentials['dbname'] = $dbname;
            }

            foreach ($credentials as $key => $value) {
                $key = $key == 'hostname' ? 'host' : $key;
                $connexionINI[$key] = (string) $value;
            }

            $Core->getConfiguration()->initialize();
            $connexionINI['driver'] = 'pdo_mysql';
            $connexionINI['charset'] = 'UTF8';

            $serverName = $registry->get('GV_ServerName');

            $root = __DIR__ . '/../../';

            $connexion = array(
                'main_connexion' => $connexionINI,
                'test_connexion' => array(
                    'driver'  => 'pdo_sqlite',
                    'path'    => ':memory:',
                    'charset' => 'UTF8'
                ));

            $cacheService = "array_cache";

            $Core->getConfiguration()->setConnexions($connexion);

            $services = $Core->getConfiguration()->getServices();

            foreach ($services as $serviceName => $service) {
                if ($serviceName === "doctrine_prod") {

                    $services["doctrine_prod"]["options"]["cache"] = array(
                        "query"    => $cacheService,
                        "result"   => $cacheService,
                        "metadata" => $cacheService
                    );
                }
            }
            $Core->getConfiguration()->setServices($services);

            $arrayConf = $Core->getConfiguration()->getConfigurations();

            $arrayConf['key'] = md5(time() . '--' . mt_rand(1000000, 9999999));

            foreach ($arrayConf as $key => $value) {
                if (is_array($value) && array_key_exists('phraseanet', $value)) {
                    $arrayConf[$key]["phraseanet"]["servername"] = $serverName;
                }

                if (is_array($value) && $key === 'prod') {
                    $arrayConf[$key]["cache"] = $cacheService;
                }
            }

            $Core->getConfiguration()->setConfigurations($arrayConf);

            $Core->getConfiguration()->setEnvironnement('prod');
        }
        try {
            if ($conn->is_multi_db()) {
                $conn->query('CREATE DATABASE `' . $dbname . '`
            CHARACTER SET utf8 COLLATE utf8_unicode_ci');
            }
        } catch (Exception $e) {

        }

        try {
            if ($conn->is_multi_db()) {
                $conn->query('USE `' . $dbname . '`');
            }
        } catch (Exception $e) {
            throw new Exception(_('setup::la base de donnees existe deja et vous n\'avez pas les droits ou vous n\'avez pas les droits de la creer') . $e->getMessage());
        }

        try {
            $appbox = self::get_instance($Core, $registry);
            $appbox->insert_datas();
        } catch (Exception $e) {
            throw new Exception('Error while installing ' . $e->getMessage());
        }

        return $appbox;
    }
    protected $databoxes;

    /**
     *
     * @return Array
     */
    public function get_databoxes()
    {
        if ($this->databoxes) {
            return $this->databoxes;
        }

        $ret = array();
        foreach ($this->retrieve_sbas_ids() as $sbas_id) {
            try {
                $ret[$sbas_id] = databox::get_instance($sbas_id);
            } catch (Exception $e) {

            }
        }

        $this->databoxes = $ret;

        return $this->databoxes;
    }

    protected function retrieve_sbas_ids()
    {
        try {
            return $this->get_data_from_cache(self::CACHE_SBAS_IDS);
        } catch (Exception $e) {

        }
        $sql = 'SELECT sbas_id FROM sbas';

        $ret = array();

        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            $ret[] = (int) $row['sbas_id'];
        }

        $this->set_data_to_cache($ret, self::CACHE_SBAS_IDS);

        return $ret;
    }

    public function get_databox($sbas_id)
    {
        $databoxes = $this->get_databoxes();
        if ( ! array_key_exists($sbas_id, $databoxes))
            throw new Exception_DataboxNotFound('Databox `' . $sbas_id . '` not found');

        return $databoxes[$sbas_id];
    }

    /**
     *
     * @return Session_Handler
     */
    public function get_session()
    {
        return $this->session;
    }

    public static function list_databox_templates()
    {
        $files = array();
        $dir = new DirectoryIterator(__DIR__ . '/../conf.d/data_templates/');
        foreach ($dir as $fileinfo) {
            if ($fileinfo->isFile()) {
                $files[] = substr($fileinfo->getFilename(), 0, (strlen($fileinfo->getFilename()) - 4));
            }
        }

        return $files;
    }

    /**
     *
     * @param  <type> $option
     * @return string
     */
    public function get_cache_key($option = null)
    {
        return 'appbox_' . ($option ? $option . '_' : '');
    }
}
