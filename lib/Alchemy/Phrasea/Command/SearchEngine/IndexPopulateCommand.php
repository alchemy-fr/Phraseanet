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
use Alchemy\Phrasea\SearchEngine\Elastic\Command\PopulateDataboxIndexCommand;
use Alchemy\Phrasea\SearchEngine\Elastic\ElasticSearchManagementService;
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
            ->setDescription('Populate search index')
            ->addOption('thesaurus', null, InputOption::VALUE_NONE, 'Only populate thesaurus data')
            ->addOption('records', null, InputOption::VALUE_NONE, 'Only populate record data')
            ->addOption(
                'databox_id',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Only populate chosen databox(es)',
                []
            );
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $databoxes = $input->getOption('databox_id');
        $indexMask = 0;

        if ($input->getOption('thesaurus')) {
            $indexMask |= Indexer::THESAURUS;
            $output->writeln('Adding "thesaurus" index to populate operation.');
        }

        if ($input->getOption('records')) {
            $indexMask |= Indexer::RECORDS;
            $output->writeln('Adding "records" index to populate operation.');
        }

        if ($indexMask == 0) {
            $indexMask = Indexer::RECORDS | Indexer::THESAURUS;
            $output->writeln('Add all entities to populate operation.');
        }

        /** @var ElasticSearchManagementService $managementService */
        $managementService = $this->container['elasticsearch.management-service'];
        $managementService->populateIndices(new PopulateDataboxIndexCommand($databoxes, $indexMask));

        $output->writeln('<info>Done</info>');
    }
}
