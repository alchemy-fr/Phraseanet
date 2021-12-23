<?php

namespace Alchemy\Phrasea\Command\Maintenance;

use Alchemy\Phrasea\Collection\Reference\DbalCollectionReferenceRepository;
use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Databox\DataboxRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CleanRights extends Command
{
    public function __construct()
    {
        parent::__construct('clean:rights');

        $this
            ->setDescription('Remove right for non existing bas/sbas')
            ->addOption('orphan', null, InputOption::VALUE_NONE, 'remove rights that refers non existing bas/sbas')
        ;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
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

            foreach ($baseIds as $baseId) {
                if ($dbalCollectionReferenceRepository->find($baseId) == null) {
                    // not found the base_id reference, so delete the right in basusr

                    $sqlDelete = "DELETE FROM basusr WHERE base_id = :base_id";
                    $stmt = $conn->prepare($sqlDelete);
                    $stmt->execute([':base_id' => $baseId]);
                    $stmt->closeCursor();
                }
            }

            // check for sbas_id

            $sql = 'SELECT DISTINCT sbas_id FROM sbasusr';
            $stmt = $conn->prepare($sql);
            $stmt->execute();

            $sBaseIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            $stmt->closeCursor();

            /** @var DataboxRepository $databoxRepository */
            $databoxRepository = $this->container['repo.databoxes'];

            foreach ($sBaseIds as $sBaseId) {
                if ($databoxRepository->find($sBaseId) == null) {
                    // not found the corresponding sbas_id, so delete the right in sbasusr
                    $sql = "DELETE FROM sbasusr WHERE sbas_id = :sbas_id";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([':sbas_id' => $sBaseId]);
                    $stmt->closeCursor();
                }
            }
        } else {
            $output->writeln("No given options!");
        }
    }
}
