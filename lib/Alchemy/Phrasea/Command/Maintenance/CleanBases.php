<?php

namespace Alchemy\Phrasea\Command\Maintenance;

use Alchemy\Phrasea\Collection\Reference\CollectionReference;
use Alchemy\Phrasea\Collection\Reference\DbalCollectionReferenceRepository;
use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Databox\DataboxRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CleanBases extends Command
{
    public function __construct()
    {
        parent::__construct('clean:bases');

        $this
            ->setDescription('remove orphan collections ')
            ->addOption('orphan', null, InputOption::VALUE_NONE, 'remove collections bas that refers a non existing sbas')
        ;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        /** @var DbalCollectionReferenceRepository $dbalCollectionReferenceRepository */
        $dbalCollectionReferenceRepository = $this->container['repo.collection-references'];

        /** @var DataboxRepository $databoxRepository */
        $databoxRepository = $this->container['repo.databoxes'];

        if ($input->getOption('orphan') === true) {
            $output->writeln("<info> Remove collections bas that refers a non existing sbas</info>");
            /** @var CollectionReference $collectionReference */
            foreach ($dbalCollectionReferenceRepository->findAll() as $collectionReference) {
                if ($databoxRepository->find($collectionReference->getDataboxId()) == null) {
                    // if the databox of the collectionReference not found, remove the collectionReference (bas)
                    try {
                        $dbalCollectionReferenceRepository->delete($collectionReference);
                    } catch (\Exception $e) {
                        $output->writeln("Can't delete bas with base_id = " . $collectionReference->getBaseId());
                    }
                }
            }
        } else {
            $output->writeln("No given options!");
        }
    }
}
