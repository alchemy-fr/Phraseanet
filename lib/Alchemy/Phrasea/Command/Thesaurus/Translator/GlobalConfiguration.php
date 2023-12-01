<?php

namespace Alchemy\Phrasea\Command\Thesaurus\Translator;

use appbox;
use collection;
use databox;
use databox_field;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Unicode;

Class GlobalConfiguration
{
    const REPORT_FORMAT_ALL = "all";
    const REPORT_FORMAT_TRANSLATED = "translated";

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
                'collections' => [],
                'fields' => [],
            ];
            $this->databoxes[$sbas_name] = &$this->databoxes[$sbas_id];
            // list all collections
            foreach ($databox->get_collections() as $collection) {
                $coll_id = $collection->get_coll_id();
                $coll_name = $collection->get_name();
                $this->databoxes[$sbas_id]['collections'][$coll_id] = $collection;
                $this->databoxes[$sbas_id]['collections'][$coll_name] = &$this->databoxes[$sbas_id]['collections'][$coll_id];
            }
            // list all fields
            /** @var databox_field $dbf */
            foreach($databox->get_meta_structure() as $dbf) {
                $field_id = $dbf->get_id();
                $field_name = $dbf->get_name();
                $this->databoxes[$sbas_id]['fields'][$field_id] = $dbf;
                $this->databoxes[$sbas_id]['fields'][$field_name] = &$this->databoxes[$sbas_id]['fields'][$field_id];
            }
        }

        foreach($global_conf['jobs'] as $job_name => $job_conf) {
            $job = new Job($this, $job_name, $job_conf, $unicode, $output);
            if($job->isActive()) {
                if($job->isValid()) {
                    $this->jobs[$job_name] = $job;
                }
                else {
                    $output->writeln("<warning>Configuration error(s)... :</warning>");
                    foreach ($job->getErrors() as $err) {
                        $output->writeln(sprintf(" - %s", $err));
                    }
                    $output->writeln("<warning>...Job ignored</warning>");
                }
            }
            else {
                unset($job);
                $output->writeln(sprintf("job \"%s\" is inactive: ignored.", $job_name));
            }
        }
    }

    /**
     * @param appbox $appBox
     * @param Unicode $unicode
     * @param string $root
     * @param bool $dryRun
     * @param string $reportFormat
     * @param OutputInterface $output
     * @return GlobalConfiguration
     * @throws ConfigurationException
     */
    public static function create(appbox $appBox, Unicode $unicode, string $root, bool $dryRun, string $reportFormat, OutputInterface $output): GlobalConfiguration
    {
        try {
            $app = $appBox->getPhraseApplication();

            return new self($appBox, $unicode, $app['conf']->get(['translator']), $dryRun, $reportFormat, $output);
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
     * @param string|int $sbasIdOrName
     * @return databox_field[]|null
     */
    public function getFields($sbasIdOrName)
    {
        return $this->databoxes[$sbasIdOrName] ?? null;
    }

    /**
     * @param string|int $sbasIdOrName
     * @param string|int $collIdOrName
     * @return databox_field|null
     */
    public function getField($sbasIdOrName, $fieldIdOrName)
    {
        return $this->databoxes[$sbasIdOrName]['fields'][$fieldIdOrName] ?? null;
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
