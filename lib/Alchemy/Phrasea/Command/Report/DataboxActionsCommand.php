<?php

namespace Alchemy\Phrasea\Command\Report;

use Alchemy\Phrasea\Report\ReportActions;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DataboxActionsCommand extends AbstractReportCommand
{
    public function __construct()
    {
        parent::__construct('databox:action');

        $this
            ->setDescription('BETA - Get all databox actions report')
            ->addOption('collection_id', 'c', InputOption::VALUE_REQUIRED| InputOption::VALUE_IS_ARRAY, 'Distant collection ID in the databox, get all available collection if not defined')
            ->addOption('permalink', 'p', InputOption::VALUE_REQUIRED, 'the subdefinition name to retrieve permalink if exist')
            ->addOption('actions', 'a', InputOption::VALUE_REQUIRED| InputOption::VALUE_IS_ARRAY, 'the databox action to get ,if not defined get all actions report')

            ->setHelp(
                "eg: bin/report databox:action --databox_id 2 --email 'noreply@mydomaine.com' --dmin '2022-12-01' --dmax '2023-01-01' -a add -a edit \n"
                . "\<ACTIONS>one or more databox actions : push ,add ,validate ,edit ,collection ,status ,print ,substit ,publish ,download ,mail ,ftp ,delete"
            );
    }

    /**
     * @inheritDoc
     */
    protected function getReport(InputInterface $input, OutputInterface $output)
    {
        $collectionIds = $input->getOption('collection_id');
        $actions = $input->getOption('actions');
        $permalink = $input->getOption('permalink');

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

        return
            (new ReportActions(
                $databox,
                [
                    'dmin'      => $this->dmin,
                    'dmax'      => $this->dmax,
                    'group'     => null,
                    'anonymize' => $this->container['conf']->get(['registry', 'modules', 'anonymous-report'])
                ]
            ))
                ->setAppKey($this->container['conf']->get(['main', 'key']))
                ->setCollIds($collIds)
                ->setPermalink($permalink)
                ->setActions($actions)
            ;
    }
}
