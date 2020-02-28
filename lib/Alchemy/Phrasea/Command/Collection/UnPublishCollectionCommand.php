<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Collection;

use Alchemy\Phrasea\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class UnPublishCollectionCommand extends Command
{
    /**
     * Constructor
     */
    public function __construct($name = null)
    {
        parent::__construct('collection:unpublish');

        $this->setDescription('Unpublish collection in Phraseanet')
            ->addOption('collection_id', null, InputOption::VALUE_REQUIRED, 'The base_id of the collection to unpublish, the base_id is the same id used in API.')
            ->setHelp('');

        return $this;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        try {

            $collection = \collection::getByBaseId($this->container,(int)$input->getOption('collection_id'));
            $dialog = $this->getHelperSet()->get('dialog');

            do {
                $continue = mb_strtolower($dialog->ask($output, sprintf("<question> Do you want really unpublish the collection %s? (y/N)</question>", $collection->get_name()), 'N'));
            } while ( ! in_array($continue, ['y', 'n']));

            if ($continue !== 'y') {
                $output->writeln('<info>Aborting !</>');

                return;
            }

            $collection->disable($this->container->getApplicationBox());
            $output->writeln('<info>Unpublish collection successful</info>');
        } catch (\Exception $e) {
            $output->writeln('<error>Unpublish collection failed : '.$e->getMessage().'</error>');
        }

        return 0;
    }

}
