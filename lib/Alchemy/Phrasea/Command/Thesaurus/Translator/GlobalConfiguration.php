<?php

namespace Alchemy\Phrasea\Command\Thesaurus\Translator;

use appbox;
use collection;
use databox;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Unicode;

Class GlobalConfiguration
{
    const CONFIG_DIR = "/config/translator/";
    const CONFIG_FILE = "configuration.yml";

    private $configuration = null;

    /** @var Job[] */
    private $jobs = [];

    private $databoxes = [];

    /**
     * @var bool
     */
    private $dryRun;
    /**
     * @var string
     */
    private $reportFormat;

    /**
     * @param appbox $appBox
     * @param array $global_conf
     */
    private function __construct($appBox, Unicode $unicode, $global_conf, bool $dryRun, string $reportFormat, OutputInterface $output)
    {
        $this->configuration = $global_conf;
        $this->dryRun = $dryRun;
        $this->reportFormat = $reportFormat;

        // list databoxes and collections to access by id or by name
        $this->databoxes = [];
        foreach ($appBox->get_databoxes() as $databox) {
            $sbas_id = $databox->get_sbas_id();
            $sbas_name = $databox->get_dbname();
            $this->databoxes[$sbas_id] = [
                'dbox' => $databox,
                'collections' => []
            ];
            $this->databoxes[$sbas_name] = &$this->databoxes[$sbas_id];
            // list all collections
            foreach ($databox->get_collections() as $collection) {
                $coll_id = $collection->get_coll_id();
                $coll_name = $collection->get_name();
                $this->databoxes[$sbas_id]['collections'][$coll_id] = $collection;
                $this->databoxes[$sbas_id]['collections'][$coll_name] = &$this->databoxes[$sbas_id]['collections'][$coll_id];
            }
        }

        foreach($global_conf['jobs'] as $job_name => $job_conf) {
            $this->jobs[$job_name] = new Job($this, $job_conf, $unicode, $output);
        }
    }

    /**
     * @param appbox $appBox
     * @param string $root
     * @return GlobalConfiguration
     * @throws ConfigurationException
     */
    public static function create(appbox $appBox, Unicode $unicode, string $root, bool $dryRun, string $reportFormat, OutputInterface $output): GlobalConfiguration
    {
        try {
            $config_file = ($config_dir = $root . self::CONFIG_DIR) . self::CONFIG_FILE;

            @mkdir($config_dir, 0777, true);

            $config = Yaml::parse(file_get_contents($config_file));
            return new self($appBox, $unicode, $config['translator'], $dryRun, $reportFormat, $output);
        }
        catch (\Exception $e) {
            throw new ConfigurationException(sprintf("missing or bad configuration (%s)", $e->getMessage()));
        }
    }

    public function getJobs()
    {
        return $this->jobs;
    }

    /**
     * @param string|int $sbasIdOrName
     * @return databox|null
     */
    public function getDatabox($sbasIdOrName)
    {
        return isset($this->databoxes[$sbasIdOrName]) ? $this->databoxes[$sbasIdOrName]['dbox'] : null;
    }

    /**
     * @param string|int $sbasIdOrName
     * @param string|int $collIdOrName
     * @return collection|null
     */
    public function getCollection($sbasIdOrName, $collIdOrName)
    {
        return $this->databoxes[$sbasIdOrName]['collections'][$collIdOrName] ?? null;
    }

    /**
     * @return bool
     */
    public function isDryRun(): bool
    {
        return $this->dryRun;
    }

    /**
     * @return string
     */
    public function getReportFormat(): string
    {
        return $this->reportFormat;
    }
}
