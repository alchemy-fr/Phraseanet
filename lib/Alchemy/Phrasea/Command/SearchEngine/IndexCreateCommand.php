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
use Alchemy\Phrasea\SearchEngine\Elastic\ElasticSearchManagementService;
use Alchemy\Phrasea\SearchEngine\Elastic\IndexAlreadyExistsException;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class IndexCreateCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('searchengine:index:create')
            ->setDescription('Creates search index')
            ->addOption('drop', 'd', InputOption::VALUE_NONE, 'Drops the index if it already exists.');
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $force = $input->getOption('drop');
        /** @var ElasticSearchManagementService $managementService */
        $managementService = $this->container['elasticsearch.management-service'];

        try {
            if ($managementService->indexExists()) {
                $output->writeln('<info>Dropping existing search index before creation</info>');
            }

            $managementService->createIndices($force);
            $output->writeln('Search index was created');
        }
        catch (IndexAlreadyExistsException $exception) {
            $output->writeln('<error>The search index already exists.</error>');

            return 1;
        }

    }
}
