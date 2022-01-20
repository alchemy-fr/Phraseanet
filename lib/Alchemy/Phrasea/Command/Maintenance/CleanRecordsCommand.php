<?php

namespace Alchemy\Phrasea\Command\Maintenance;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Model\Entities\BasketElement;
use Alchemy\Phrasea\Model\Repositories\BasketElementRepository;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CleanRecordsCommand extends Command
{
    private $dry;
    /** @var Table */
    private $outputTable;
    private $hasElementToDelete = false;

    public function __construct()
    {
        parent::__construct('clean:records');

        $this
            ->setDescription('Remove orphans items')
            ->addOption('from_baskets', null, InputOption::VALUE_NONE, 'remove orphans items from baskets')
            ->addOption('dry', null, InputOption::VALUE_NONE, 'list all basketElements with non existing record')
        ;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $this->dry = $input->getOption('dry');

        if ($this->dry) {
            $this->outputTable = new Table($output);
            $this->outputTable->setHeaders(['basketElement_id', 'basket_id', 'record_id', 'sbas_id']);
        }

        if ($input->getOption('from_baskets') === true) {
            $output->writeln("<info> Remove orphans records from basket</info>");
            $this->removeOrphansFromBasket($output);
        } else {
            $output->writeln("No given options! you can add option --from_baskets");
        }
    }

    private function removeOrphansFromBasket(OutputInterface $output)
    {
        /** @var BasketElementRepository $basketElementRepository */
        $basketElementRepository = $this->container['repo.basket-elements'];

        /** @var BasketElement $basketElement */
        foreach ($basketElementRepository->findAll() as $basketElement) {
            $sbasId = $basketElement->getSbasId();
            $recordId = $basketElement->getRecordId();
            $basketElementId = $basketElement->getId();

            try {
                if ($this->container->findDataboxById($sbasId)->getRecordRepository()->find($recordId) == null) {
                    $this->hasElementToDelete = true;
                    if ($this->dry) {
                        $this->outputTable->addRow([$basketElementId, $basketElement->getBasket()->getId(), $recordId, $sbasId]);
                    } else {
                        $this->container['orm.em']->remove($basketElement);
                        $output->writeln(sprintf("record_id = %s not found for basketElement_id = %s  => deleted", $recordId, $basketElementId));
                    }
                }
            } catch (NotFoundHttpException $e) {
                // remove also if sbas_id not found
                $this->hasElementToDelete = true;
                if ($this->dry) {
                    $this->outputTable->addRow([$basketElementId, $basketElement->getBasket()->getId(), $recordId, $sbasId]);
                } else {
                    $this->container['orm.em']->remove($basketElement);
                    $output->writeln(sprintf("sbas_id = %s not found for basketElement_id = %s  => deleted", $sbasId, $basketElementId));
                }
            }
        }

        if ($this->hasElementToDelete === false) {
            $output->writeln("Nothing to do!");
        } elseif ($this->hasElementToDelete && !$this->dry) {
            $this->container['orm.em']->flush();
            $output->writeln("Table BasketElements successfully cleaned!");
        } elseif ($this->dry) {
            $this->outputTable->render();
        }
    }
}
