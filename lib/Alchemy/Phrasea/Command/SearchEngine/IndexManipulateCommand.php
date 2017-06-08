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

class IndexManipulateCommand extends Command
{
    protected function configure()
    {
        /** @var Indexer $indexer */
        //$indexer = $this->container['elasticsearch.indexer'];
        //$options = $indexer->getIndex()->getOptions();

        $this
            ->setName('searchengine:index:manipulate')
            ->setDescription('Manipulates search index')
            ->addOption('drop',      'd', InputOption::VALUE_NONE, 'Drops the index.')
            ->addOption('create',    'c', InputOption::VALUE_NONE, 'Creates the index.')
            ->addOption('populate',  'p', InputOption::VALUE_NONE, 'Populates the index.')
            ->addOption('temporary', 't', InputOption::VALUE_NONE, 'Populates using temporary index.')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'index name', null)
            ->addOption('host', null, InputOption::VALUE_REQUIRED, 'host', null)
            ->addOption('port', null, InputOption::VALUE_REQUIRED, 'port', null)
            ->addOption(
                'databox_id',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Only populate chosen databox'
            );

    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        /** @var Indexer $indexer */
        $indexer = $this->container['elasticsearch.indexer'];
        $options = $indexer->getIndex()->getOptions();

        if($input->getOption('name')) {
            $options->setIndexName($input->getOption('name'));
        }
        if($input->getOption('host')) {
            $options->setHost($input->getOption('host'));
        }
        if($input->getOption('port')) {
            $options->setPort($input->getOption('port'));
        }

        $idx = sprintf("%s@%s:%s", $options->getIndexName(), $options->getHost(), $options->getPort());

        $drop         = $input->getOption('drop');
        $create       = $input->getOption('create');
        $populate     = $input->getOption('populate');
        $temporary    = $input->getOption('temporary');
        $databoxes_id = $input->getOption('databox_id');


        if($temporary && (!$populate || $databoxes_id)) {
            $output->writeln(sprintf('<error>temporary must be used to populate all databoxes</error>', $idx));

            return 1;
        }

        $indexExists = $indexer->indexExists();

        if ($drop && $indexExists) {
            $indexer->deleteIndex();
            $output->writeln(sprintf('<info>Search index "%s" was dropped.</info>', $idx));
        }

        $indexExists = $indexer->indexExists();

        if ($create) {
            if($indexExists) {
                $output->writeln(sprintf('<error>The search index "%s" already exists.</error>', $idx));

                return 1;
            }
            else {
                $indexer->createIndex();
                $output->writeln(sprintf('<info>Search index "%s" was created</info>', $idx));
            }
        }

        $indexExists = $indexer->indexExists();

        if($populate) {
            if(!$indexExists) {
                $indexer->createIndex();
                $output->writeln(sprintf('<info>Search index "%s" was created</info>', $idx));
            }

            file_put_contents("/tmp/phraseanet-log.txt", sprintf("%s (%d) currentIndexName = %s\n", __FILE__, __LINE__, $indexer->getIndex()->getOptions()->getIndexName()), FILE_APPEND);
            $oldAliasName = $indexer->getIndex()->getName();
            $newIndexName = null;
            if($temporary) {
                $oldIndexName = $indexer->getIndex()->getOptions()->getIndexName();
                // change the name to create a new index
                $indexer->getIndex()->getOptions()->setIndexName("temp_". date('YmdHis'));
                $r = $indexer->createIndex("phraseanetjy");
                $newIndexName = $r['index'];
                file_put_contents("/tmp/phraseanet-log.txt", sprintf("%s (%d) newIndexName = %s\n", __FILE__, __LINE__, $newIndexName), FILE_APPEND);
            }

            foreach ($this->container->getDataboxes() as $databox) {
                if (!$databoxes_id || in_array($databox->get_sbas_id(), $databoxes_id)) {
                    $r = $indexer->populateIndex(Indexer::THESAURUS | Indexer::RECORDS, $databox, false); // , $temporary);
                    $output->writeln(sprintf(
                        "Indexation of databox \"%s\" finished in %0.2f sec (Mem. %0.2f Mo)",
                        $databox->get_dbname(),
                        $r['duration']/1000,
                        $r['memory']/1048576)
                    );
                }
            }

            if($temporary) {
                $indexer->getIndex()->getOptions()->setIndexName($oldAliasName);

                $indexer->replaceIndex($newIndexName);
            }
        }
    }
}
