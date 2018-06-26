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

class IndexDropCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('searchengine:index:drop')
            ->setDescription('Deletes the search index <fg=yellow;>(Deprecated use searchengine:index instead)</>')
            ->addOption(
               'force',
               null,
               InputOption::VALUE_NONE,
               "Don't ask for the dropping of the index, but force the operation to run."
            )
        ;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {

        $question = '<question>You are about to delete the index and all contained data. Are you sure you wish to continue? (y/n)</question>';
        if ($input->getOption('force')) {
            $confirmation = true;
        } else {
            $confirmation = $this->getHelper('dialog')->askConfirmation($output, $question, false);
        }

        if ($confirmation) {
            $indexer = $this->container['elasticsearch.indexer'];
            if ($indexer->indexExists()) {
                $indexer->deleteIndex();
                $output->writeln('Search index was dropped');
            } else {
                $output->writeln('<error>The index was not dropped because it does not exists</error>');
            }
        } else {
            $output->writeln('Canceled.');
        }
    }
}
