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
use Symfony\Component\Console\Exception\RuntimeException;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class IndexManipulateCommand extends Command
{
    const ORDER_COLUMN_UPDATE_ON = 'updated_on';
    const ORDER_COLUMN_RECORD_ID = 'record_id';
    const ORDER_DIRECTION_ASC    = 'ASC';
    const ORDER_DIRECTION_DESC   = 'DESC';
    const ORDER_LIMIT_TYPE_DAY   = 'DAY';
    const ORDER_LIMIT_TYPE_MINUTE   = 'MINUTE';
    const ORDER_LIMIT_TYPE_HOUR   = 'HOUR';

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
            ->addOption('order', null, InputOption::VALUE_OPTIONAL, 'order (record_id|updated_on)[.asc|.desc]', null)
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'limit (day|hour|minute|).(1|n)', null)
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
        if($input->getOption('order')) {
            $order = explode('.', $input->getOption('order'));

            list($populateOrder) = $order;
            if(!in_array($populateOrder,[self::ORDER_COLUMN_UPDATE_ON,self::ORDER_COLUMN_RECORD_ID])){
               throw new RuntimeException($this->suggestionMessage());
            }

            $ordersNumberParameter = count($order);
            if ($ordersNumberParameter == 2) {
                list($populateOrder,$populateDirection) = $order;

                if(!in_array(strtoupper($populateDirection),[self::ORDER_DIRECTION_ASC,self::ORDER_DIRECTION_DESC])){
                    throw new RuntimeException($this->suggestionMessage());
                }

                try{
                    $options->setPopulateDirection($populateDirection);
                    $options->setPopulateOrder($populateOrder);
                }catch(\Exception $e){
                    throw new RuntimeException($this->suggestionMessage());
                }

            }elseif ($ordersNumberParameter == 1){
                try{
                    $options->setPopulateOrder($populateOrder);
                }catch(\Exception $e){
                    throw new RuntimeException($this->suggestionMessage());
                }
            }else{
                throw new RuntimeException($this->suggestionMessage());
            }
        }
        if($input->getOption('limit')){
            $limit = explode('.', $input->getOption('limit'));

            $limitNumberParameter = count($limit);
            if($limitNumberParameter != 2){
                throw new RuntimeException($this->suggestionMessage('limit'));
            }

            list($populateLimitType,$populateLimitDuration) = $limit;
            if(!in_array(strtoupper($populateLimitType),[self::ORDER_LIMIT_TYPE_DAY,self::ORDER_LIMIT_TYPE_HOUR,self::ORDER_LIMIT_TYPE_MINUTE]) || is_int($populateLimitDuration)){
                throw new RuntimeException($this->suggestionMessage('limit'));
            }

            try{
                $options->setPopulateLimitType($populateLimitType);
                $options->setPopulateLimitDuration($populateLimitDuration);

            }catch(\Exception $e){
                throw new RuntimeException($this->suggestionMessage('limit'));
            }

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

    /**
     * @param string $type
     * @return string
     */
    private function suggestionMessage($type = 'order')
    {
        $suggestion = 'Bad paramaters value for --'.$type.' retry with : (';

        switch ($type){
            case 'order':
                $suggestion .= $this->transformMessage([self::ORDER_COLUMN_RECORD_ID,self::ORDER_COLUMN_UPDATE_ON]).')['.$this->transformMessage([self::ORDER_DIRECTION_DESC,self::ORDER_DIRECTION_ASC],'direction').']';
                break;
            case 'limit':
                $suggestion .= $this->transformMessage([self::ORDER_LIMIT_TYPE_DAY,self::ORDER_LIMIT_TYPE_MINUTE,self::ORDER_LIMIT_TYPE_HOUR]).').[1..n]';
                break;
        }


        return $suggestion;
    }

    /**
     * @param array $type   ORDER_COLUMN|ORDER_DIRECTION|ORDER_LIMIT_TYPE
     * @return string
     */
    private function transformMessage(array $type,$what = '')
    {
        switch ($what){
            case 'direction':
                $directionTransform = array_map(function($item) { return "." . $item; },$type);
                $typeTransformer = strtolower(implode('|', $directionTransform));
                break;
            default:
                $typeTransformer = strtolower(implode('|',$type));
        }

        return $typeTransformer;
    }
}
