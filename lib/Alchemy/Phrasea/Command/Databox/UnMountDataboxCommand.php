<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Databox;

use Alchemy\Phrasea\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UnMountDataboxCommand extends Command
{
    /**
     * Constructor
     */
    public function __construct($name = null)
    {
        parent::__construct('databox:unmount');

        $this->setDescription('Unmount databox')
            ->addArgument('databox_id', InputArgument::REQUIRED, 'The id of the databox to unmount', null)
        ;

        return $this;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        try {
            $databox = $this->container->findDataboxById($input->getArgument('databox_id'));
            $dialog = $this->getHelperSet()->get('dialog');

            do {
                $continue = mb_strtolower($dialog->ask($output, '<question> Do you want really unmount this databox? (y/N)</question>', 'N'));
            } while ( ! in_array($continue, ['y', 'n']));

            if ($continue !== 'y') {
                $output->writeln('Aborting !');

                return;
            }

            $databox->unmount_databox();
            $output->writeln('<info>Unmount databox successful</info>');
        } catch (\Exception $e) {
            $output->writeln('<error>Unmount databox failed : '.$e->getMessage().'</error>');
        }

        return 0;
    }

}
