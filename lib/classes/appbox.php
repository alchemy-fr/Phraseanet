<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Collection\CollectionService;
use Alchemy\Phrasea\Core\Configuration\AccessRestriction;
use Alchemy\Phrasea\Core\Connection\ConnectionSettings;
use Alchemy\Phrasea\Core\LazyLocator;
use Alchemy\Phrasea\Core\Version\AppboxVersionRepository;
use Alchemy\Phrasea\Databox\DataboxConnectionProvider;
use Alchemy\Phrasea\Databox\DataboxRepository;
use Alchemy\Phrasea\Filesystem\PhraseanetFilesystem as Filesystem;
use Alchemy\Phrasea\Setup\Version\PreSchemaUpgrade\PreSchemaUpgradeCollection;
use Doctrine\ORM\Tools\SchemaTool;
use MediaAlchemyst\Alchemyst;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\File as SymfoFile;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use vierbergenlars\SemVer\version;

// use Symfony\Component\Filesystem\Filesystem;

class appbox extends base
{
    /**
     * constant defining the app type
     */
    const BASE_TYPE = self::APPLICATION_BOX;

    const CACHE_LIST_BASES = 'list_bases';

    const CACHE_SBAS_IDS = 'sbas_ids';

    /**
     * @var int
     */
    protected $id;
    /**
     * @var \databox[]
     */
    protected $databoxes;
    /**
     * @var CollectionService
     */
    protected $collectionService;

    public function __construct(Application $app)
    {
        $connectionConfig = $app['conf']->get(['main', 'database']);
        $connection = $app['db.provider']($connectionConfig);

        $connectionSettings = new ConnectionSettings(
            $connectionConfig['host'],
            $connectionConfig['port'],
            $connectionConfig['dbname'],
            $connectionConfig['user'],
            $connectionConfig['password']
        );

        $versionRepository = new AppboxVersionRepository($connection);

        parent::__construct($app, $connection, $connectionSettings, $versionRepository);
    }

    public function write_collection_pic(Alchemyst $alchemyst, Filesystem $filesystem, collection $collection, SymfoFile $pathfile = null, $pic_type)
    {
        $manager = new \Alchemy\Phrasea\Core\Thumbnail\CollectionThumbnailManager(
            $this->app,
            $alchemyst,
            $filesystem,
            $this->app['root.path']
        );

        $manager->setThumbnail($collection, $pic_type, $pathfile);

        return $this;
    }

    public function write_databox_pic(Alchemyst $alchemyst, Filesystem $filesystem, databox $databox, SymfoFile $pathfile = null, $pic_type)
    {
        $manager = new \Alchemy\Phrasea\Core\Thumbnail\DataboxThumbnailManager(
            $this->app,
            $alchemyst,
            $filesystem,
            $this->app['root.path']
        );

        $manager->setThumbnail($databox, $pic_type, $pathfile);

        return $this;
    }

    public function write_application_logo(Filesystem $filesystem, $blob)
    {
        $logo_path = $this->app['root.path'] . '/www/custom/minilogos/personalize_logo.';

        list($type, $imageData) = explode(';', $blob);
        list(,$extension) = explode('/',$type);
        list(,$imageData)      = explode(',', $imageData);

        $data = str_replace(' ', '+', $imageData);
        $data = base64_decode($data);
        $extension= ($extension=='svg+xml')?'svg':$extension;

        try{
            $filesystem->dumpFile($logo_path.$extension, $data);
        }catch(\Exception $e){
            return $e->getMessage();
        }

        return 'success';
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
        $stmt->execute([':ordre'   => $ordre, ':base_id' => $collection->get_base_id()]);
        $stmt->closeCursor();

        $collection->get_databox()->delete_data_from_cache(\databox::CACHE_COLLECTIONS);

        return $this;
    }

    /**
     * @param  databox $databox
     * @param  bool  $boolean
     * @return appbox
     */
    public function set_databox_indexable(databox $databox, $boolean)
    {
        $boolean = !!$boolean;
        $sql = 'UPDATE sbas SET indexable = :indexable WHERE sbas_id = :sbas_id';

        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute([
            ':indexable' => ($boolean ? '1' : '0'),
            ':sbas_id'   => $databox->get_sbas_id()
        ]);
        $stmt->closeCursor();

        return $this;
    }

    /**
     * @param  databox $databox
     * @return bool
     */
    public function is_databox_indexable(databox $databox)
    {
        $sql = 'SELECT indexable FROM sbas WHERE sbas_id = :sbas_id';

        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute([':sbas_id' => $databox->get_sbas_id()]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $indexable = $row ? $row['indexable'] : null;

        return (Boolean) $indexable;
    }

    /**
     * @return string
     */
    public function get_base_type()
    {
        return self::BASE_TYPE;
    }

    public function forceUpgrade(Setup_Upgrade $upgrader, Application $app, InputInterface $input, OutputInterface $output)
    {
        $dry = !!$input->getOption('dry');

        $from_version = $this->get_version();

        $app['phraseanet.cache-service']->flushAll();

        // Executes stuff before applying patches
        /** @var PreSchemaUpgradeCollection $psu */
        $psu = $app['phraseanet.pre-schema-upgrader'];
        $psu->apply($app, $input, $output);

        $finder = new Finder();
        $in = [];
        $path = $app['cache.path'];
        $in[] = $path.'/minify/';
        $in[] = $path.'/twig/';
        $in[] = $path.'/translations/';
        $in[] = $path.'/profiler/';
        $in[] = $path.'/doctrine/';
        $in[] = $path.'/serializer/';
        $finder->in(array_filter($in, function($path) {
            return is_dir($path);
        }))
            ->depth(0)
            ->ignoreVCS(true)
            ->ignoreDotFiles(true);


        $app['filesystem']->remove($finder);

        foreach ([
            'config/custom_files/' => 'www/custom/',
            'config/minilogos/'    => 'www/custom/minilogos/',
            'config/stamp/'        => 'www/custom/stamp/',
            'config/status/'       => 'www/custom/status/',
            'config/wm/'           => 'www/custom/wm/',
        ] as $source => $target) {
            $app['filesystem']->mirror($this->app['root.path'] . '/' . $source, $this->app['root.path'] . '/' . $target, null, array('override' => true));
        }

        // do not apply patches
        // just update old database schema
        // it is needed before applying patches
        $advices = $this->upgradeDB(false, $input, $output);

        // update also the doctrine table schema before applying patch
        if ($app['orm.em']->getConnection()->getDatabasePlatform()->supportsAlterTable()) {
            $tool = new SchemaTool($app['orm.em']);
            $metas = $app['orm.em']->getMetadataFactory()->getAllMetadata();
            $tool->updateSchema($metas, true);
        }

        foreach ($this->get_databoxes() as $s) {
            $advices = array_merge($advices, $s->upgradeDB(false, $input, $output));
        }

        // then apply patches
        $advices = $this->upgradeDB(true, $input, $output);

        foreach ($this->get_databoxes() as $s) {
            $advices = array_merge($advices, $s->upgradeDB(true, $input, $output));
        }

        $this->post_upgrade($app, $input, $output);

        $app['phraseanet.cache-service']->flushAll();

        if (version::lt($from_version, '3.1')) {
            $upgrader->addRecommendation($app->trans('Your install requires data migration, please execute the following command'), 'bin/setup system:upgrade-datas --from=3.1');
        } elseif (version::lt($from_version, '3.5')) {
            $upgrader->addRecommendation($app->trans('Your install requires data migration, please execute the following command'), 'bin/setup system:upgrade-datas --from=3.5');
        }

        if (version::lt($from_version, '3.7')) {
            $upgrader->addRecommendation($app->trans('Your install might need to re-read technical datas'), 'bin/console records:rescan-technical-datas');
            $upgrader->addRecommendation($app->trans('Your install might need to build some sub-definitions'), 'bin/console records:build-missing-subdefs');
        }

        return $advices;
    }

    protected function post_upgrade(Application $app, InputInterface $input, OutputInterface $output)
    {
        $dry = !!$input->getOption('dry');

        $output->writeln(sprintf("into post_upgrade()"));
        $this->apply_patches($this->get_version(), $app['phraseanet.version']->getNumber(), true, $input, $output);
        /** @var Alchemy\Phrasea\Core\Version $phrVersion */
        $phrVersion = $app['phraseanet.version'];
        if($dry) {
            $output->writeln(sprintf("dry : NOT setting version of \"%s\" to %s", $this->get_dbname(), $phrVersion->getNumber()));
        }
        else {
            $output->writeln(sprintf("setting version of \"%s\" to %s", $this->get_dbname(), $phrVersion->getNumber()));
            $this->setVersion($phrVersion);
        }

        foreach ($this->get_databoxes() as $databox) {
            $databox->apply_patches($databox->get_version(), $app['phraseanet.version']->getNumber(), true, $input, $output);
            if($dry) {
                $output->writeln(sprintf("dry : NOT setting version of \"%s\" to %s", $databox->get_dbname(), $phrVersion->getNumber()));
            }
            else {
                $output->writeln(sprintf("setting version of \"%s\" to %s", $databox->get_dbname(), $phrVersion->getNumber()));
                $databox->setVersion($app['phraseanet.version']);
            }
        }

        return $this;
    }

    /**
     * @return databox[]
     */
    public function get_databoxes()
    {
        if (!$this->databoxes) {
            $this->databoxes = $this->getAccessRestriction()
                ->filterAvailableDataboxes($this->getDataboxRepository()->findAll());
        }

        return $this->databoxes;
    }

    /**
     * @param int $sbas_id
     * @return databox
     */
    public function get_databox($sbas_id)
    {
        $databoxes = $this->getDataboxRepository()->findAll();

        if (!isset($databoxes[$sbas_id]) && !array_key_exists($sbas_id, $databoxes)) {
            throw new NotFoundHttpException('Databox `' . $sbas_id . '` not found');
        }

        return $databoxes[$sbas_id];
    }

    /**
     * @param int $base_id
     * @return collection
     */
    public function get_collection($base_id)
    {
        $sbas_id = phrasea::sbasFromBas($this->app, $base_id);

        if ($sbas_id === false) {
            throw new \RuntimeException('Collection not found.');
        }

        $collections = $this->get_databox($sbas_id)->get_collections();

        foreach ($collections as $collection) {
            if ($collection->get_base_id() == $base_id) {
                return $collection;
            }
        }

        // This should not happen, but I'd rather be safe than sorry.
        throw new \RuntimeException('Collection not found.');
    }

    /**
     * @param string $option
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

    public function getCollectionService()
    {
        if ($this->collectionService === null) {
            $this->collectionService = new CollectionService(
                $this->app,
                $this->connection,
                new DataboxConnectionProvider($this),
                new LazyLocator($this->app, 'phraseanet.user-query')
            );
        }

        return $this->collectionService;
    }

    /**
     * @return AccessRestriction
     */
    public function getAccessRestriction()
    {
        return $this->app['conf.restrictions'];
    }

    /**
     * @return DataboxRepository
     */
    private function getDataboxRepository()
    {
        return $this->app['repo.databoxes'];
    }
}
