<?php

namespace Alchemy\Phrasea\Command\Maintenance;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Model\Entities\BasketElement;
use Alchemy\Phrasea\Model\Repositories\BasketElementRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CleanRecords extends Command
{
    public function __construct()
    {
        parent::__construct('clean:records');

        $this
            ->setDescription('Remove orphans items')
            ->addOption('from_baskets', null, InputOption::VALUE_NONE, 'remove orphans items from baskets')
            ;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('from_baskets') === true) {
            $output->writeln("<info> Remove orphans records from basket</info>");
            $this->removeOrphansFromBasket();
        }
    }

    private function removeOrphansFromBasket()
    {
        /** @var BasketElementRepository $basketElementRepository */
        $basketElementRepository = $this->container['repo.basket-elements'];

        /** @var BasketElement $basketElement */
        foreach ($basketElementRepository->findAll() as $basketElement) {
            try {
                if ($this->container->findDataboxById($basketElement->getSbasId())->getRecordRepository()->find($basketElement->getRecordId()) == null) {
                    $this->container['orm.em']->remove($basketElement);
                }
            } catch (NotFoundHttpException $e) {
                // remove also if sbas_id not found
                $this->container['orm.em']->remove($basketElement);
            }
        }
        $this->container['orm.em']->flush();
    }
}
