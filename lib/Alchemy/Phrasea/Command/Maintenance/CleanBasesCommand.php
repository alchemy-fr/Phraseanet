<?php

namespace Alchemy\Phrasea\Command\Maintenance;

use Alchemy\Phrasea\Collection\Reference\CollectionReference;
use Alchemy\Phrasea\Collection\Reference\DbalCollectionReferenceRepository;
use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Databox\DataboxRepository;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CleanBasesCommand extends Command
{
    public function __construct()
    {
        parent::__construct('clean:bases');

        $this
            ->setDescription('remove orphan collections ')
            ->addOption('orphan', null, InputOption::VALUE_NONE, 'remove collections bas that refers a non existing sbas')
            ->addOption('dry', null, InputOption::VALUE_NONE, 'list all collections with non existing sbas')
        ;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $dry = $input->getOption('dry');
        $hasElementToDelete = false;

        /** @var DbalCollectionReferenceRepository $dbalCollectionReferenceRepository */
        $dbalCollectionReferenceRepository = $this->container['repo.collection-references'];

        /** @var DataboxRepository $databoxRepository */
        $databoxRepository = $this->container['repo.databoxes'];

        if ($dry) {
            $outputTable = new Table($output);
            $outputTable->setHeaders(['base_id', 'server_coll_id', 'sbas_id']);
        }

        if ($input->getOption('orphan') === true) {
            $output->writeln("<info> Remove collections bas that refers a non existing sbas</info>");
            /** @var CollectionReference $collectionReference */
            foreach ($dbalCollectionReferenceRepository->findAll() as $collectionReference) {
                $sbasId = $collectionReference->getDataboxId();
                $baseId = $collectionReference->getBaseId();

                if ($databoxRepository->find($sbasId) == null) {
                    $hasElementToDelete = true;
                    if ($dry) {
                        $outputTable->addRow([$baseId, $collectionReference->getCollectionId(), $sbasId]);
                    } elseif ($input->getOption('orphan')) {
                        // if the databox of the collectionReference not found, remove the collectionReference (bas)
                        try {
                            $dbalCollectionReferenceRepository->delete($collectionReference);
                            $output->writeln(sprintf("sbas_id = %s not found for the base_id = %s  ==> deleted", $sbasId, $baseId));
                        } catch (\Exception $e) {
                            $output->writeln("Can't delete bas with base_id = " . $baseId);
                        }
                    }
                }
            }

            if ($dry) {
                $outputTable->render();
            } elseif ($hasElementToDelete && !$dry) {
                $output->writeln("Table bas successfully cleaned!");
            }
        } elseif (isset($outputTable) && $input->getOption('orphan') && !$hasElementToDelete) {
            $output->writeln("Nothing to do!");
        } else {
            $output->writeln("No given options! you can add option --orphan");
        }
    }
}
