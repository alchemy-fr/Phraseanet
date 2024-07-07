<?php

namespace Alchemy\Phrasea\Command\Report;

use Alchemy\Phrasea\Report\ReportActions;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DownloadsCommand extends AbstractReportCommand
{
    const TYPES = ['user', 'record', 'field'];

    public function __construct()
    {
        parent::__construct('downloads:all');

        $this
            ->setDescription('BETA - Get all downloads report')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'type of report downloads, if not defined or empty it is for all downloads')
            ->addOption('collection_id', 'c', InputOption::VALUE_REQUIRED| InputOption::VALUE_IS_ARRAY, 'Distant collection ID in the databox, get all available collection if not defined')
            ->addOption('permalink', 'p', InputOption::VALUE_REQUIRED, 'the subdefinition name to retrieve permalink if exist, available only for type record and for all downloads type ""')
            ->addOption('meta_struct_id', 'm', InputOption::VALUE_REQUIRED, 'the meta_struct_id of the field to do grouping, available only for type field')

            ->setHelp(
                "eg: bin/report downloads:all --databox_id 2 --email 'noreply@mydomaine.com' --dmin '2022-12-01' --dmax '2023-01-01' --type 'user' \n"
                . "\<TYPE>type of report\n"
                . "- <info>'' or not defined </info>all downloads\n"
                . "- <info>'user' </info> downloads by user\n"
                . "- <info>'record' </info> downloads by record\n"
                . "- <info>'field' </info> downloads by field, need --meta_struct_id option\n"
            );
    }

    /**
     * @inheritDoc
     */
    protected function getReport(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getOption('type');
        $collectionIds = $input->getOption('collection_id');
        $permalink = $input->getOption('permalink');
        $metaStructId = $input->getOption('meta_struct_id');

        if (!empty($type) && !in_array($type, self::TYPES)) {
            $output->writeln("<error>wrong '--type' option (--help for available value)</error>");

            return 1;
        }

        if (!empty($permalink) && $type == 'user') {
            $output->writeln("<error>--permalink is not used with type=user </error>");

            return 1;
        }

        if ($type == 'field' && empty($metaStructId)) {
            $output->writeln("<error>you have to set option --meta_struct_id with type=field</error>");

            return 1;
        }

        $databox = $this->findDbOr404($this->sbasId);
        $collIds = [];

        // treat coollection Id to send to the sql request
        // if empty get all databox active collection
        if (empty($collectionIds)) {
            foreach ($databox->get_collections() as $collection) {
                $collIds[] = $databox->get_connection()->quote($collection->get_coll_id());
            }
        } else {
            foreach ($collectionIds as $colId) {
                $collIds[] = $databox->get_connection()->quote($colId);
            }
        }

        if (!$this->isFieldExist($databox, $metaStructId)) {
            $output->writeln("<error>there is no field find with this meta_struct_id!</error>");

            return 1;
        }

        return
            (new ReportActions(
                $databox,
                [
                    'dmin'              => $this->dmin,
                    'dmax'              => $this->dmax,
                    'group'             => $type,
                    'anonymize'         => $this->container['conf']->get(['registry', 'modules', 'anonymous-report']),
                    'meta_struct_id'    => $metaStructId
                ]
            ))
                ->setAppKey($this->container['conf']->get(['main', 'key']))
                ->setCollIds($collIds)
                ->setPermalink($permalink)
                ->setAsDownloadReport(true)
            ;
    }

    private function isFieldExist(\databox $databox, $metaStructId)
    {
        foreach($databox->get_meta_structure() as $field) {
            if ($field->get_id() == $metaStructId) {
                return true;
            }
        }

        return false;
    }
}
