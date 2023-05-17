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
use Alchemy\Phrasea\Core\Connection\ConnectionSettings;
use Alchemy\Phrasea\Core\Database\DatabaseMaintenanceService;
use Alchemy\Phrasea\Core\Version as PhraseaVersion;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class base implements cache_cacheableInterface
{

    const APPLICATION_BOX = 'APPLICATION_BOX';

    const DATA_BOX = 'DATA_BOX';

    /**
     * @var string
     */
    protected $version;

    /**
     * @var int
     */
    protected $id;

    /**
     * @var SimpleXMLElement
     */
    protected $schema;

    /**
     * @var ConnectionSettings
     */
    protected $connectionSettings;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var PhraseaVersion\VersionRepository
     */
    protected $versionRepository;

    /**
     * @param Application $application
     * @param Connection $connection
     * @param ConnectionSettings $connectionSettings
     * @param PhraseaVersion\VersionRepository $versionRepository
     */
    public function __construct(Application $application,
        Connection $connection,
        ConnectionSettings $connectionSettings,
        PhraseaVersion\VersionRepository $versionRepository)
    {
        $this->app = $application;
        $this->connection = $connection;
        $this->connectionSettings = $connectionSettings;
        $this->versionRepository = $versionRepository;
    }

    /**
     * @return string
     */
    abstract public function get_base_type();

    /**
     * @return SimpleXMLElement
     * @throws Exception
     */
    public function get_schema()
    {
        if ($this->schema) {
            return $this->schema;
        }

        $this->load_schema();

        return $this->schema;
    }

    /**
     * @return string
     */
    public function get_dbname()
    {
        return $this->connectionSettings->getDatabaseName();
    }

    /**
     * @return string
     */
    public function get_passwd()
    {
        return $this->connectionSettings->getPassword();
    }

    /**
     * @return string
     */
    public function get_user()
    {
        return $this->connectionSettings->getUser();
    }

    /**
     * @return int
     */
    public function get_port()
    {
        return $this->connectionSettings->getPort();
    }

    /**
     * @return string
     */
    public function get_host()
    {
        return $this->connectionSettings->getHost();
    }

    /**
     * @return Connection
     */
    public function get_connection()
    {
        if($this->connection->ping() === false){
            if(isset($this->app['task-manager.logger'])){
                $this->app['task-manager.logger']->info("MySQL server is not available : close and connect .....");
            }

            $this->connection->close();
            $this->connection->connect();
        }

        return $this->connection;
    }

    /**
     * @return \Alchemy\Phrasea\Cache\Cache
     */
    public function get_cache()
    {
        return $this->app['cache'];
    }

    public function get_data_from_cache($option = null)
    {
        if ($this->get_base_type() == self::DATA_BOX) {
            \cache_databox::refresh($this->app, $this->id);
        }

        $data = $this->get_cache()->get($this->get_cache_key($option));

        if (is_object($data) && method_exists($data, 'hydrate')) {
            $data->hydrate($this->app);
        }

        return $data;
    }

    public function set_data_to_cache($value, $option = null, $duration = 0)
    {
        return $this->get_cache()->save($this->get_cache_key($option), $value, $duration);
    }

    public function delete_data_from_cache($option = null)
    {
        $appbox = $this->get_base_type() == self::APPLICATION_BOX ? $this : $this->get_appbox();

        if ($option === appbox::CACHE_LIST_BASES) {
            $keys = [$this->get_cache_key(appbox::CACHE_LIST_BASES)];

            phrasea::reset_sbasDatas($appbox);
            phrasea::reset_baseDatas($appbox);
            phrasea::clear_sbas_params($this->app);

            return $this->get_cache()->deleteMulti($keys);
        }

        if (is_array($option)) {
            foreach ($option as $key => $value) {
                $option[$key] = $this->get_cache_key($value);
            }

            return $this->get_cache()->deleteMulti($option);
        } else {
            return $this->get_cache()->delete($this->get_cache_key($option));
        }
    }

    public function get_version()
    {
        if (! $this->version) {
            try {
                $this->version = $this->versionRepository->getVersion();
            } catch(\Throwable $e) {
                return PhraseaVersion\VersionRepository::DEFAULT_VERSION;
            }
        }

        return $this->version;
    }

    protected function setVersion(PhraseaVersion $version)
    {
        try {   
            return $this->versionRepository->saveVersion($version);
        } catch (\Exception $e) {
            throw new Exception('Unable to set the database version : ' . $e->getMessage());
        }
    }

    protected function upgradeDb($applyPatches, InputInterface $input, OutputInterface $output)
    {
        $dry = !!$input->getOption('dry');

        $output->writeln(sprintf("into upgradeDb(applyPatches=%s) for base \"%s\"", $applyPatches?'true':'false', $this->get_dbname()));
        $service = new DatabaseMaintenanceService($this->app, $this->connection);

        return $service->upgradeDatabase($this, $applyPatches, $input, $output);
    }

    /**
     * @return base
     * @throws Exception
     */
    protected function load_schema()
    {
        if ($this->schema) {
            return $this;
        }

        if (false === $structure = simplexml_load_file(__DIR__ . "/../../lib/conf.d/bases_structure.xml")) {
            throw new Exception('Unable to load schema');
        }

        if ($this->get_base_type() === self::APPLICATION_BOX) {
            $this->schema = $structure->appbox;
        } elseif ($this->get_base_type() === self::DATA_BOX) {
            $this->schema = $structure->databox;
        } else {
            throw new Exception('Unknown schema type');
        }

        return $this;
    }

    /**
     * @return base
     */
    public function insert_datas()
    {
        $this->load_schema();

        $service = new DatabaseMaintenanceService($this->app, $this->connection);

        foreach ($this->get_schema()->tables->table as $table) {
            $service->createTable($table);
        }

        $this->setVersion($this->app['phraseanet.version']);

        return $this;
    }

    public function apply_patches($from, $to, $post_process, InputInterface $input, OutputInterface $output)
    {
        $dry = !!$input->getOption('dry');

        $service = new DatabaseMaintenanceService($this->app, $this->connection);

        return $service->applyPatches($this, $from, $to, $post_process, $input, $output);
    }

    public function getPhraseApplication()
    {
        return $this->app;
    }
}
