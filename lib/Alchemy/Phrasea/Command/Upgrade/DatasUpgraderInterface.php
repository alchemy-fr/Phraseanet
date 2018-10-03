<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Upgrade;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The data upgrader interface
 */
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
