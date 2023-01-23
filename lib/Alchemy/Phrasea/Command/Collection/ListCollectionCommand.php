<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Command\Collection;

use Alchemy\Phrasea\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCollectionCommand extends Command
{
    /**
     * Constructor
     */
    public function __construct($name = null)
    {
        parent::__construct('collection:list');
        $this->setDescription('List all collection in Phraseanet')
            ->addOption('databox_id', 'd', InputOption::VALUE_REQUIRED, 'The id of the databox to list collection')
            ->addOption('jsonformat', null, InputOption::VALUE_NONE, 'Output in json format')
            ->setHelp('');
        return $this;
    }
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        try {
            $jsonformat     = $input->getOption('jsonformat');
            $databox        = $this->container->findDataboxById($input->getOption('databox_id'));
            $collections    = $this->listDataboxCollections($databox);

            if ($jsonformat) {
                foreach ($collections as $collection) {
                    $collectionList[] = array_combine(['id local for API', 'id distant', 'name','label','status','total records'], $collection);
                }
                echo json_encode($collectionList); 
            } else {
                $table = $this->getHelperSet()->get('table');
                $table
                    ->setHeaders(['id local for API', 'id distant', 'name','label','status','total records'])
                    ->setRows($collections)
                    ->render($output);
            }

        } catch (\Exception $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
        }
        return 0;
    }

    private function listDataboxCollections(\databox $databox)
    {
        return array_map(function (\collection $collection) {
            return $this->listCollection($collection);
        }, array_merge($databox->get_collections(),$this->getUnabledCollection($databox->get_activable_colls())));
    }

    private function getUnabledCollection($collections)
    {
        return array_map(function ($colId){
            return \collection::getByBaseId($this->container, $colId);
        },$collections);

    }

    private function listCollection(\collection $collection)
    {
        return [
            $collection->get_base_id(),
            $collection->get_coll_id(),
            $collection->get_name(),
            'en: '   . $collection->get_label('en') .
            ', de: ' . $collection->get_label('de') .
            ', fr: ' . $collection->get_label('fr') .
            ', nl: ' . $collection->get_label('nl'),
            ($collection->is_active()) ? 'enabled' : 'disabled',
            $collection->get_record_amount()
        ];
    }
}