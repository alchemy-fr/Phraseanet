<?php

namespace Alchemy\Phrasea\Command\Feedback\Report;


use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use appbox;
use collection;
use databox;
use databox_field;
use Twig_Environment;

Class GlobalConfiguration
{
    const CONFIG_DIR = "/config/feedbackreport/";
    const CONFIG_FILE = "configuration.yml";

    private $configuration = null;

    private $actions = [];      // ActionInterface[], by sbas_id

    private $databoxes = [];

    /**
     * @var bool
     */
    private $dryRun;
    /**
     * @var Twig_Environment
     */
    private $twig;
    /**
     * @var string
     */
    private $reportFormat;


    /**
     * @param PropertyAccess $conf
     * @param Twig_Environment $twig
     * @param appbox $appBox
     * @param bool $dryRun
     * @param string $reportFormat
     * @throws ConfigurationException
     */
    public function __construct(PropertyAccess $conf, Twig_Environment $twig, appbox $appBox, bool $dryRun, string $reportFormat)
    {
        $this->twig = $twig;
        $this->configuration = $conf->get(['feedback-report'], ['enabled' => false, 'actions' => []]);
        $this->dryRun = $dryRun;
        $this->reportFormat = $reportFormat;

        if($this->isEnabled()) {
            // sanitize sb
            foreach ($this->configuration['actions'] as $action_name => $action_conf) {
                if (array_key_exists('status_bit', $action_conf)) {
                    $bit = (int)($sbit = trim($action_conf['status_bit']));
                    if ($bit < 4 || $bit > 31) {
                        throw new ConfigurationException(sprintf("bad status bit (%s)", $sbit));
                    }
                }
            }
            // nb: "metadata" cannot be sanitized because validity depends on databox, and a basket may contain records from many dbx.
            //     unknown field will be ignored during actions creation.
        }

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
     * @return bool
     */
    public function isEnabled(): bool
    {
        return !!$this->configuration['enabled'];
    }

    /**
     * @return string
     */
    public function getReportFormat(): string
    {
        return $this->reportFormat;
    }


    /**
     * @return ActionInterface[]
     */
    public function getActions(\databox $databox): array
    {
        $sbas_id = $databox->get_sbas_id();
        if(!array_key_exists($sbas_id, $this->actions)) {
            $this->actions[$sbas_id] = [];

            foreach($this->configuration['actions'] as $action_name => $action_conf) {
                if(array_key_exists('status_bit', $action_conf)) {
                    $this->actions[$sbas_id][] = new StatusBitAction($this->twig, $action_conf);
                }
                else if(array_key_exists('metadata', $action_conf)) {
                    if(($f = $this->getField($databox->get_sbas_id(), $action_conf['metadata'])) !== null) {
                        $this->actions[$sbas_id][] = new MetadataAction($this->twig, $f->get_name(), $action_conf);
                    }
                }
            }
        }

        return $this->actions[$sbas_id];
    }
}
