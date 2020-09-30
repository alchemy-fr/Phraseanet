<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Model\Entities\AuthFailure;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Alchemy\Phrasea\Utilities\NullableDateTime;
use Doctrine\ORM\EntityManager;


class TruncateAuthFailure extends Command
{


    /**
     * Constructor
     */
    public function __construct($name = null)
    {

        parent::__construct('authFailures:truncate');
        $this->setDescription('Truncate AuthFailure table');

        return $this;

    }

    public function doExecute(InputInterface $input, OutputInterface $output)
    {
        /** @var AuthFailureRepository $authFailureRepository */
        $authFailureManipulator = $this->container['manipulator.auth-failures']->truncateTable('Alchemy\Phrasea\Model\Entities\AuthFailure');
        $output->writeln($authFailureManipulator);
    }

}
