<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\SearchEngine\Debug;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class QueryParseCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('searchengine:query:parse')
            ->setDescription('Debug a search query')
            ->addArgument(
                'query',
                InputArgument::REQUIRED,
                'The query to debug'
            )
            ->addOption(
                'raw',
                false,
                InputOption::VALUE_NONE,
                'Only output query dump'
            )
        ;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $string = $input->getArgument('query');
        $raw = $input->getOption('raw');

        if (!$raw) {
            $output->writeln(sprintf('Parsing search query: <comment>%s</comment>', $string));
            $output->writeln(str_repeat('-', 20));
        }

        $query = $this->container['query_parser']->parse($string);
        $dump = $query->dump();

        if (!$raw) {
            $output->writeln($dump);
        } else {
            $output->write($dump);
        }
    }
}
