<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Alchemy\Phrasea\Command\Command;

class module_console_fieldsDelete extends Command
{

    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->setDescription('Deletes a documentation field from a Databox');

        $this->addArgument('meta_struct_id', InputArgument::REQUIRED, 'Metadata structure id destination');
        $this->addArgument('sbas_id', InputArgument::REQUIRED, 'Databox sbas_id');

        return $this;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        try {
            $databox = $this->getService('phraseanet.appbox')->get_databox((int) $input->getArgument('sbas_id'));
        } catch (\Exception $e) {
            $output->writeln("<error>Invalid databox id </error>");

            return 1;
        }

        try {
            $field = $databox
                ->get_meta_structure()
                ->get_element((int) $input->getArgument('meta_struct_id'));
        } catch (\Exception $e) {
            $output->writeln("<error>Invalid meta struct id </error>");

            return 1;
        }

        $dialog = $this->getHelperSet()->get('dialog');
        $continue = mb_strtolower(
            $dialog->ask(
                $output
                , "<question>About to delete " . $field->get_name() . " (y/N)</question>"
                , 'n'
            )
        );

        if ($continue != 'y') {
            $output->writeln("Request canceled by user");

            return 1;
        }

        $output->writeln("Deleting ... ");

        $field->delete();

        $output->writeln("Done with success !");

        return 0;
    }
}
