<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\SearchEngine;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class IndexPopulateCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('searchengine:index:populate')
            ->setDescription('Populate search index <fg=yellow;>(Deprecated use searchengine:index instead)</>')
            ->addOption(
                'thesaurus',
                null,
                InputOption::VALUE_NONE,
                'Only populate thesaurus data'
            )
            ->addOption(
                'records',
                null,
                InputOption::VALUE_NONE,
                'Only populate record data'
            )
            ->addOption(
                'databox_id',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Only populate chosen databox'
            )
        ;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $what = Indexer::THESAURUS | Indexer::RECORDS;

        if ($thesaurusOnly = $input->getOption('thesaurus')) {
            $what = Indexer::THESAURUS;
        }
        if ($recordsOnly = $input->getOption('records')) {
            $what = Indexer::RECORDS;
        }
        if ($thesaurusOnly && $recordsOnly) {
            throw new \RuntimeException("Could not provide --thesaurus and --records option at the same time.");
        }

        $databoxes_id = $input->getOption('databox_id');

        $app = $this->container;
        foreach($app->getDataboxes() as $databox) {
            if(!$databoxes_id || in_array($databox->get_sbas_id(), $databoxes_id)) {
                $this->container['elasticsearch.indexer']->populateIndex($what, $databox);
            }
        }
    }
}
