<?php

namespace Alchemy\Phrasea\Command\Maintenance;

use Alchemy\Phrasea\Collection\Reference\DbalCollectionReferenceRepository;
use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Databox\DataboxRepository;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CleanRightsCommand extends Command
{
    public function __construct()
    {
        parent::__construct('clean:rights');

        $this
            ->setDescription('Remove right for non existing bas/sbas')
            ->addOption('orphan', null, InputOption::VALUE_NONE, 'remove rights that refers non existing bas/sbas')
            ->addOption('dry', null, InputOption::VALUE_NONE, 'list non existing bas/sbas')
        ;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $dry = $input->getOption('dry');
        $hasElementToDelete1 = $hasElementToDelete2 = false;

        if ($input->getOption('orphan') === true) {
            $output->writeln("<info>Remove rights that refers non existing bas/sbas</info>");

            $conn = $this->container->getApplicationBox()->get_connection();

            // check for base_id

            $sql = 'SELECT DISTINCT base_id FROM basusr';

            $stmt = $conn->prepare($sql);
            $stmt->execute();

            $baseIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            $stmt->closeCursor();

            /** @var DbalCollectionReferenceRepository $dbalCollectionReferenceRepository */
            $dbalCollectionReferenceRepository = $this->container['repo.collection-references'];

            if ($dry) {
                $outputTable1 = new Table($output);
                $outputTable1->setHeaders(['non existing base_id in basusr']);
            }

            foreach ($baseIds as $baseId) {
                if ($dbalCollectionReferenceRepository->find($baseId) == null) {
                    // not found the base_id reference, so delete the right in basusr
                    $hasElementToDelete1 = true;
                    if ($dry) {
                        $outputTable1->addRow([$baseId]);
                    } else {
                        $sqlDelete = "DELETE FROM basusr WHERE base_id = :base_id";
                        $stmt = $conn->prepare($sqlDelete);
                        $stmt->execute([':base_id' => $baseId]);
                        $stmt->closeCursor();

                        $output->writeln(sprintf("base_id = %s not found, basusr with base_id = %s => deleted", $baseId, $baseId));

                    }
                }
            }

            if ($dry && $hasElementToDelete1) {
                $outputTable1->render();
            }

            // check for sbas_id

            $sql = 'SELECT DISTINCT sbas_id FROM sbasusr';
            $stmt = $conn->prepare($sql);
            $stmt->execute();

            $sBaseIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            $stmt->closeCursor();

            /** @var DataboxRepository $databoxRepository */
            $databoxRepository = $this->container['repo.databoxes'];

            if ($dry) {
                $outputTable2 = new Table($output);
                $outputTable2->setHeaders(['non existing sbas_id in sbasusr']);
            }

            foreach ($sBaseIds as $sBaseId) {
                if ($databoxRepository->find($sBaseId) == null) {
                    // not found the reference sbas_id, so delete the right in sbasusr
                    $hasElementToDelete2 = true;

                    if ($dry) {
                        $outputTable2->addRow([$sBaseId]);
                    } else {
                        $sql = "DELETE FROM sbasusr WHERE sbas_id = :sbas_id";
                        $stmt = $conn->prepare($sql);
                        $stmt->execute([':sbas_id' => $sBaseId]);
                        $stmt->closeCursor();

                        $output->writeln(sprintf("sbas_id = %s not found, sbasusr with sbas_id = %s => deleted", $sBaseId, $sBaseId));
                    }
                }
            }

            if ($dry && $hasElementToDelete2) {
                $outputTable2->render();
            }

            if (!$dry && ($hasElementToDelete2 || $hasElementToDelete1)) {
                $output->writeln("Table basusr and sbasusr successfully cleaned!");
            } elseif (!$hasElementToDelete1 && !$hasElementToDelete2) {
                $output->writeln("Nothing to do!");
            }

        } else {
            $output->writeln("No given options! you can add option --orphan");
        }
    }
}
