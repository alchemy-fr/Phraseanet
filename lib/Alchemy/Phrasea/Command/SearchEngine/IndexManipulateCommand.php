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
use Alchemy\Phrasea\ControllerProvider\Admin\Databox;
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
            ->setName('searchengine:index')
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
            )
            ->addOption('order', null,InputOption::VALUE_OPTIONAL | InputArgument::IS_ARRAY,'Order',null)
            ->addOption('limit', null,InputOption::VALUE_OPTIONAL | InputArgument::IS_ARRAY,'Limit populate by minute or hours or day',null);

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
        $order = $input->getOption('order');
        $limit = $input->getOption('limit');


        if($temporary && (!$populate || $databoxes_id)) {
            $output->writeln(sprintf('<error>temporary must be used to populate all databoxes</error>', $idx));

            return 1;
        }

        $indexExists = $indexer->indexExists();

        if ($drop && $indexExists) {
            $indexer->deleteIndex();
            $output->writeln(sprintf('<info>Search index "%s" was dropped.</info>', $idx));
        }

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

        if($populate) {
            if(!$indexExists) {
                $indexer->createIndex();
                $output->writeln(sprintf('<info>Search index "%s" was created</info>', $idx));
            }

            $oldAliasName = $indexer->getIndex()->getName();
            $newAliasName = $newIndexName = null;
            if($temporary) {
                // change the name to create a new index
                $now = sprintf("%s.%06d", Date('YmdHis'), 1000000*explode(' ', microtime())[0]) ;
                $indexer->getIndex()->getOptions()->setIndexName("temp_". $now);

                $r = $indexer->createIndex("phraseanetjy");
                $newIndexName = $r['index'];
                $newAliasName = $r['alias'];
            }

            foreach ($this->container->getDataboxes() as $databox) {
                if (!$databoxes_id || in_array($databox->get_sbas_id(), $databoxes_id)) {
                    $databox->setOrderType($order);
                    $databox->setLimit($limit);
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

                $indexer->replaceIndex($newIndexName, $newAliasName);
            }
        }
    }
}
