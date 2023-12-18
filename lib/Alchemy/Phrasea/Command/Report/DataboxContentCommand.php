<?php

namespace Alchemy\Phrasea\Command\Report;

use Alchemy\Phrasea\Report\ReportRecords;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DataboxContentCommand extends AbstractReportCommand
{
    public function __construct()
    {
        parent::__construct('databox:content');

        $this
            ->setDescription('BETA - Get all databox records')
            ->addOption('collection_id', 'c', InputOption::VALUE_REQUIRED| InputOption::VALUE_IS_ARRAY, 'Distant collection ID in the databox, get all available collection if not defined')
            ->addOption('field', 'f', InputOption::VALUE_REQUIRED| InputOption::VALUE_IS_ARRAY, 'The field name to include in the report, get all available report field if not defined')
            ->addOption('permalink', 'p', InputOption::VALUE_REQUIRED, 'the subdefinition name to retrieve permalink if exist')

            ->setHelp(
                "eg: bin/report databox:content --databox_id 2 --email 'noreply@mydomaine.com' --dmin '2022-12-01' --dmax '2023-01-01' \n"
                . "\<DMIN> \<DMAX> date filter on the updated_on (moddate of table record)"

            );
    }

    protected function getReport(InputInterface $input, OutputInterface $output)
    {
        $collectionIds = $input->getOption('collection_id');
        $fields = $input->getOption('field');

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

        // if empty get all field available, only field with is_report to true will display after in csv
        if (empty($fields)) {
            $fields = array_map(function ($databoxField) {
                return $databoxField['name'];
            }, $databox->get_meta_structure()->toArray());
        }

        return
            (new ReportRecords(
                $databox,
                [
                    'dmin'      => $this->dmin,
                    'dmax'      => $this->dmax,
                    'group'     => '',
                    'anonymize' => $this->container['conf']->get(['registry', 'modules', 'anonymous-report']),
                    'meta'      => $fields

                ]
            ))
                ->setCollIds($collIds)
                ->setPermalink($input->getOption('permalink'))
            ;
    }
}
