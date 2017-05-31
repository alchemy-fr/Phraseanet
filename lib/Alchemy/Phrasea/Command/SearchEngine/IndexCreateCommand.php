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
        /** @var Indexer $indexer */
        $indexer = $this->container['elasticsearch.indexer'];

        $drop = $input->getOption('drop');
        $indexExists = $indexer->indexExists();

        if (! $drop && $indexExists) {
            $output->writeln('<error>The search index already exists.</error>');

            return 1;
        }

        if ($drop && $indexExists) {
            $output->writeln('<info>Dropping existing search index</info>');

            $indexer->deleteIndex();
        }

        $indexer->createIndex();

        $options = $this->container['elasticsearch.options'];
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "http://localhost:9200/" . $options->getIndexName() . "/_settings",
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_POSTFIELDS => "{ \"index\" : { \"max_result_window\" : 500000 } }",
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err)
        {
            echo "cURL Error #:" . $err;
        }
        else
        {
            //echo $response;
        }

        $output->writeln('Search index was created');
    }
}
