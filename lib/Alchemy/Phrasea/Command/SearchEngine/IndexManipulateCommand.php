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
use Alchemy\Phrasea\Command\Helper;
use Alchemy\Phrasea\SearchEngine\Elastic\ElasticsearchOptions;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class IndexManipulateCommand extends Command
{
    /** @var  OutputInterface */
    private $output = null;

    /**
     * print a string if verbosity >= verbose (-v)
     *
     * @param string $s
     * @param int $verbosity
     */
    private function verbose($s, $verbosity = OutputInterface::VERBOSITY_VERBOSE)
    {
        if ($this->output->getVerbosity() >= $verbosity) {
            $this->output->writeln($s);
        }
    }

    protected function configure()
    {
        $this
            ->setName('searchengine:index')
            ->setDescription('Manipulates search index')
            ->addOption('list',      'l', InputOption::VALUE_NONE, 'List aliases & indexes')
            ->addOption('drop',      'd', InputOption::VALUE_NONE, 'Drops the index.')
            ->addOption('create',    'c', InputOption::VALUE_NONE, 'Creates the index.')
            ->addOption('populate',  'p', InputOption::VALUE_NONE, 'Populates the index.')
            ->addOption('temporary', 't', InputOption::VALUE_NONE, 'Populates using temporary index.')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'index name', null)
            ->addOption('host', null, InputOption::VALUE_REQUIRED, 'host', null)
            ->addOption('port', null, InputOption::VALUE_REQUIRED, 'port', null)
            ->addOption('order', null, InputOption::VALUE_REQUIRED, 'order (record_id|modification_date)[.asc|.desc]', null)
            ->addOption(
                'databox',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'databox(es) to populate, by id or dbname (default: all databoxes)'
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                "Don't ask for for the dropping of the index, but force the operation to run."
            );

    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        /** @var Indexer $indexer */
        $indexer = $this->container['elasticsearch.indexer'];

        /** @var ElasticsearchOptions $options */
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

        if($input->getOption('order')) {
            $order = explode('.', $input->getOption('order'));
            if (!$options->setPopulateOrder($order[0])) {
                $output->writeln(sprintf('<error>bad order value for --order</error>'));

                return 1;
            }
            if (count($order) > 1) {
                if (!$options->setPopulateDirection($order[1])) {
                    $output->writeln(sprintf('<error>bad direction value for --order</error>'));

                    return 1;
                }
            }
        }

        $list         = $input->getOption('list');
        $drop         = $input->getOption('drop');
        $create       = $input->getOption('create');
        $populate     = $input->getOption('populate');
        $temporary    = $input->getOption('temporary');

        $matchMethod = Helper::MATCH_ALL_DB_IF_EMPTY | Helper::MATCH_DB_BY_ID | Helper::MATCH_DB_BY_NAME;
        $databoxes = Helper::getDataboxesByIdOrName($this->container, $input, 'databox', $matchMethod);


        if ($list) {
            $this->output->writeln($indexer->listIndexes());
        }

        if ($drop && !$input->getOption('force')) {
            $question = '<question>You are about to delete the indexes and all contained data. Are you sure you wish to continue? (y/n)</question>';
            if(!$this->getHelper('dialog')->askConfirmation($output, $question, false)) {
                $this->output->writeln('Canceled.');

                return 0;
            }
        }

        foreach($databoxes as $databox) {

            $idx = sprintf("%s@%s:%s", $databox->get_dbname(), $options->getHost(), $options->getPort());

            $indexExists = $indexer->indexExists($databox);

            if ($drop && $indexExists) {
                $indexer->deleteIndex($databox);
                $this->verbose(sprintf('<info>Search index "%s" was dropped.</info>', $idx));
            }

            if ($create) {
                if ($indexExists) {
                    $output->writeln(sprintf('<error>The search index "%s" already exists.</error>', $idx));

                    return 1;
                }
                else {
                    $indexName = $indexer->createIndex($databox);
                    $this->verbose(sprintf('<info>Search index "%s@%s:%s" was created</info>'
                        , $indexName
                        , $options->getHost()
                        , $options->getPort()
                    ));

                    $indexer->createAliases($indexName, $databox->get_dbname(), $options->getIndexName());
                }
            }

            $indexExists = $indexer->indexExists($databox);

            if ($populate) {
                if (!$indexExists) {
                    $indexName = $indexer->createIndex($databox);
                    $this->verbose(sprintf('<info>Search index "%s@%s:%s" -> "%s" was created</info>'
                        , $indexName
                        , $options->getHost()
                        , $options->getPort()
                    ));
                }
                /*
                $oldAliasName = $indexer->getIndex()->getName();
                $newAliasName = $newIndexName = null;
                if ($temporary) {
                    // change the name to create a new index
                    $now = explode(' ', microtime());
                    $now = sprintf("%X%X", $now[1], 1000000 * $now[0]);
                    $indexer->getIndex()->getOptions()->setIndexName($oldAliasName . "_T" . $now);

                    $r = $indexer->createIndex($oldAliasName);
                    $newIndexName = $r['index'];
                    $newAliasName = $r['alias'];

                    $this->verbose(sprintf('<info>Temporary index "%s@%s:%s" -> "%s" was created</info>'
                        , $r['alias']
                        , $options->getHost()
                        , $options->getPort()
                        , $r['index']
                    ));
                }
                */
                $r = $indexer->populateIndex($databox, Indexer::THESAURUS | Indexer::RECORDS);
//                $r = $indexer->populateIndex($databox, Indexer::RECORDS);
                $output->writeln(sprintf(
                        "Indexation of databox \"%s\" finished in %0.2f sec (Mem. %0.2f Mo)",
                        $databox->get_dbname(),
                        $r['duration'] / 1000,
                        $r['memory'] / 1048576)
                );
                /*
                if ($temporary) {
                    $this->verbose('<info>Renaming temporary :</info>');

                    $indexer->getIndex()->getOptions()->setIndexName($oldAliasName);

                    $r = $indexer->replaceIndex($newIndexName, $newAliasName);
                    foreach ($r as $action) {
                        $this->verbose(sprintf('  <info>%s</info>', $action['msg']));
                    }
                }
                */
            }
        }

        return 0;
    }
}
