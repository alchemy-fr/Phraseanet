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

class PublishCollectionCommand extends Command
{
    /**
     * Constructor
     */
    public function __construct($name = null)
    {
        parent::__construct('collection:publish');

        $this->setDescription('Publish collection in Phraseanet')
                  ->addOption('collection_id', null, InputOption::VALUE_REQUIRED, 'The base_id of the collection to publish but keep with existing right into present in application box.')
            ->setHelp('');

        return $this;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        try {

            $collection = \collection::getByBaseId($this->container,(int)$input->getOption('collection_id'));
            $dialog = $this->getHelperSet()->get('dialog');

            do {
                $continue = mb_strtolower($dialog->ask($output, '<question> Do you want really publish this collection? (y/N)</question>', 'N'));
            } while ( ! in_array($continue, ['y', 'n']));

            if ($continue !== 'y') {
                $output->writeln('Aborting !');

                return;
            }

            $collection->enable($this->container->getApplicationBox());
            $output->writeln('<info>Publish collection successful</info>');
        } catch (\Exception $e) {
            $output->writeln('<error>Publish collection failed : '.$e->getMessage().'</error>');
        }

        return 0;
    }

}
