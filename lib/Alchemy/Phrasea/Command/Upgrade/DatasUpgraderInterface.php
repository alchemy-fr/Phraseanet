<?php

namespace Alchemy\Phrasea\Command\Upgrade;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface DatasUpgraderInterface
{

    /**
     * Executes the upgrade
     */
    public function execute(InputInterface $input, OutputInterface $output);

    /**
     * Return the duration estimation in seconds
     *
     * @return integer
     */
    public function getTimeEstimation();
}

