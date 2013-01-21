<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Version;
use MediaAlchemyst\Alchemyst;
use MediaAlchemyst\Specification\Image as ImageSpecification;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File as SymfoFile;

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
    protected $cache;
    protected $connection;
    protected $app;

    protected $databoxes;

    const CACHE_LIST_BASES = 'list_bases';
    const CACHE_SBAS_IDS = 'sbas_ids';

    /**
     * Constructor
     *
     * @return appbox
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->connection = connection::getPDOConnection($app);
        $choosenConnexion = $app['phraseanet.configuration']->getPhraseanet()->get('database');

        $connexion = $app['phraseanet.configuration']->getConnexion($choosenConnexion);

        $this->host = $connexion->get('host');
        $this->port = $connexion->get('port');
        $this->user = $connexion->get('user');
        $this->passwd = $connexion->get('password');
        $this->dbname = $connexion->get('dbname');

        return $this;
    }

    public function write_collection_pic(Alchemyst $alchemyst, Filesystem $filesystem, collection $collection, SymfoFile $pathfile = null, $pic_type)
    {
        $filename = null;

        if (!is_null($pathfile)) {

            if (!in_array(mb_strtolower($pathfile->getMimeType()), array('image/gif', 'image/png', 'image/jpeg', 'image/jpg', 'image/pjpeg'))) {
                throw new \InvalidArgumentException('Invalid file format');
            }

            $filename = $pathfile->getPathname();

            if ($pic_type === collection::PIC_LOGO) {
                //resize collection logo
                $imageSpec = new ImageSpecification();

                $media = $this->app['mediavorus']->guess($filename);

                if($media->getWidth() > 120 || $media->getHeight() > 24) {
                    $imageSpec->setResizeMode(ImageSpecification::RESIZE_MODE_INBOUND_FIXEDRATIO);
                    $imageSpec->setDimensions(120, 24);
                }

                $tmp = tempnam(sys_get_temp_dir(), 'tmpdatabox') . '.jpg';

                try {
                    $alchemyst
                        ->open($pathfile->getPathname())
                        ->turninto($tmp, $imageSpec)
                        ->close();
                    $filename = $tmp;
                } catch (\MediaAlchemyst\Exception $e) {

                }
            } else if ($pic_type === collection::PIC_PRESENTATION) {
                //resize collection logo
                $imageSpec = new ImageSpecification();
                $imageSpec->setResizeMode(ImageSpecification::RESIZE_MODE_INBOUND_FIXEDRATIO);
                $imageSpec->setDimensions(650, 200);

                $tmp = tempnam(sys_get_temp_dir(), 'tmpdatabox') . '.jpg';

                try {
                    $alchemyst
                        ->open($pathfile->getPathname())
                        ->turninto($tmp, $imageSpec)
                        ->close();
                    $filename = $tmp;
                } catch (\MediaAlchemyst\Exception $e) {

                }
            }
        }

        switch ($pic_type) {
            case collection::PIC_WM;
                $collection->reset_watermark();
                break;
            case collection::PIC_LOGO:
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

        $file = $this->app['phraseanet.registry']->get('GV_RootPath') . 'config/' . $pic_type . '/' . $collection->get_base_id();
        $custom_path = $this->app['phraseanet.registry']->get('GV_RootPath') . 'www/custom/' . $pic_type . '/' . $collection->get_base_id();

        foreach (array($file, $custom_path) as $target) {

            if (is_file($target)) {

                $filesystem->remove($target);
            }

            if (null === $target || null === $filename) {
                continue;
            }

            $filesystem->mkdir(dirname($target), 0750);
            $filesystem->copy($filename, $target, true);
            $filesystem->chmod($target, 0760);
        }

        return $this;
    }

    public function write_databox_pic(Alchemyst $alchemyst, Filesystem $filesystem, databox $databox, SymfoFile $pathfile = null, $pic_type)
    {
        $filename = null;

        if (!is_null($pathfile)) {

            if (!in_array(mb_strtolower($pathfile->getMimeType()), array('image/jpeg', 'image/jpg', 'image/pjpeg', 'image/png', 'image/gif'))) {
                throw new \InvalidArgumentException('Invalid file format');
            }
        }

        if (!in_array($pic_type, array(databox::PIC_PDF))) {
            throw new \InvalidArgumentException('unknown pic_type');
        }

        if ($pathfile) {

            $filename = $pathfile->getPathname();

            $imageSpec = new ImageSpecification();
            $imageSpec->setResizeMode(ImageSpecification::RESIZE_MODE_INBOUND_FIXEDRATIO);
            $imageSpec->setDimensions(120, 35);

            $tmp = tempnam(sys_get_temp_dir(), 'tmpdatabox') . '.jpg';

            try {
                $alchemyst
                    ->open($pathfile->getPathname())
                    ->turninto($tmp, $imageSpec)
                    ->close();
                $filename = $tmp;
            } catch (\MediaAlchemyst\Exception $e) {

            }
        }

        $file = $this->app['phraseanet.registry']->get('GV_RootPath') . 'config/minilogos/' . $pic_type . '_' . $databox->get_sbas_id() . '.jpg';
        $custom_path = $this->app['phraseanet.registry']->get('GV_RootPath') . 'www/custom/minilogos/' . $pic_type . '_' . $databox->get_sbas_id() . '.jpg';

        foreach (array($file, $custom_path) as $target) {

            if (is_file($target)) {
                $filesystem->remove($target);
            }

            if (is_null($filename)) {
                continue;
            }

            $filesystem->mkdir(dirname($target));
            $filesystem->copy($filename, $target);
            $filesystem->chmod($target, 0760);
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
        $boolean = !!$boolean;
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

        $this->delete_data_from_cache(appbox::CACHE_LIST_BASES);
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

    public function forceUpgrade(Setup_Upgrade $upgrader, Application $app)
    {
        $from_version = $this->get_version();

        $upgrader->add_steps(7 + count($this->get_databoxes()));

        /**
         * Step 1
         */
        $upgrader->set_current_message(_('Flushing cache'));

        $app['phraseanet.cache-service']->flushAll();

        $upgrader->add_steps_complete(1);

        $upgrader->set_current_message(_('Creating new tables'));
        //create schema

        if ($app['EM']->getConnection()->getDatabasePlatform()->supportsAlterTable()) {
            $tool = new \Doctrine\ORM\Tools\SchemaTool($app['EM']);
            $metas = $app['EM']->getMetadataFactory()->getAllMetadata();
            $tool->updateSchema($metas, true);
        }

        $upgrader->add_steps_complete(1);

        /**
         * Step 2
         */
        $upgrader->set_current_message(_('Purging directories'));

        $finder = new Symfony\Component\Finder\Finder();
        $finder->in(array(
            $this->app['phraseanet.registry']->get('GV_RootPath') . 'tmp/cache_minify/',
            $this->app['phraseanet.registry']->get('GV_RootPath') . 'tmp/cache_minify/',
        ))->ignoreVCS(true)->ignoreDotFiles(true);

        foreach ($finder as $file) {
            $app['filesystem']->remove($file);
        }

        $upgrader->add_steps_complete(1);

        /**
         * Step 5
         */
        $upgrader->set_current_message(_('Copying files'));

        foreach (array(
        'config/custom_files/' => 'www/custom/',
        'config/minilogos/'    => 'www/custom/minilogos/',
        'config/stamp/'        => 'www/custom/stamp/',
        'config/status/'       => 'www/custom/status/',
        'config/wm/'           => 'www/custom/wm/',
        ) as $source => $target) {
            $app['filesystem']->mirror($this->app['phraseanet.registry']->get('GV_RootPath') . $source, $this->app['phraseanet.registry']->get('GV_RootPath') . $target);
        }

        $upgrader->add_steps_complete(1);

        $advices = array();

        /**
         * Step 6
         */
        $upgrader->set_current_message(_('Upgrading appbox'));
        $advices = $this->upgradeDB(true, $upgrader, $app);
        $upgrader->add_steps_complete(1);

        /**
         * Step 7
         */
        foreach ($this->get_databoxes() as $s) {
            $upgrader->set_current_message(sprintf(_('Upgrading %s'), $s->get_viewname()));
            $advices = array_merge($advices, $s->upgradeDB(true, $upgrader, $app));
            $upgrader->add_steps_complete(1);
        }

        /**
         * Step 8
         */
        $upgrader->set_current_message(_('Post upgrade'));
        $this->post_upgrade($upgrader, $app);
        $upgrader->add_steps_complete(1);

        /**
         * Step 9
         */
        $upgrader->set_current_message(_('Flushing cache'));

        $app['phraseanet.cache-service']->flushAll();

        $upgrader->add_steps_complete(1);

        if (version_compare($from_version, '3.1') < 0) {
            $upgrader->addRecommendation(_('Your install requires data migration, please execute the following command'), 'bin/setup system:upgrade-datas --from=3.1');
        } elseif (version_compare($from_version, '3.5') < 0) {
            $upgrader->addRecommendation(_('Your install requires data migration, please execute the following command'), 'bin/setup system:upgrade-datas --from=3.5');
        }

        if (version_compare($from_version, '3.7') < 0) {
            $upgrader->addRecommendation(_('Your install might need to re-read technical datas'), 'bin/console records:rescan-technical-datas');
            $upgrader->addRecommendation(_('Your install might need to re-read technical datas'), 'bin/console records:build-missing-subdefs');
        }

        return $advices;
    }

    protected function post_upgrade(Setup_Upgrade $upgrader, Application $app)
    {
        $upgrader->add_steps(1 + count($this->get_databoxes()));
        $this->apply_patches($this->get_version(), $app['phraseanet.version']->getNumber(), true, $upgrader, $app);
        $this->setVersion($app['phraseanet.version']);
        $upgrader->add_steps_complete(1);

        foreach ($this->get_databoxes() as $databox) {
            $databox->apply_patches($databox->get_version(), $app['phraseanet.version']->getNumber(), true, $upgrader, $app);
            $databox->setVersion($app['phraseanet.version']);
            $upgrader->add_steps_complete(1);
        }

        return $this;
    }

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
                $ret[$sbas_id] = new \databox($this->app, $sbas_id);
            } catch (\Exception_DataboxNotFound $e) {

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

        if (!array_key_exists($sbas_id, $databoxes)) {
            throw new Exception_DataboxNotFound('Databox `' . $sbas_id . '` not found');
        }

        return $databoxes[$sbas_id];
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

    public function delete_data_from_cache($option = null)
    {
        if ($option === appbox::CACHE_LIST_BASES) {
            $this->databoxes = null;
        }

        parent::delete_data_from_cache($option);
    }
}
