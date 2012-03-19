<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package     KonsoleKomander
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class module_console_fieldsRename extends Command
{

  public function __construct($name = null)
  {
    parent::__construct($name);

    $this->setDescription('Rename a documentation field from a Databox');

    $this->addOption('sbas_id', 's', InputOption::VALUE_REQUIRED, 'Databox sbas_id');

    $this->addOption('meta_struct_id', 'm', InputOption::VALUE_REQUIRED, 'Databox meta structure Id');

    $this->addOption('name', 'n', InputOption::VALUE_REQUIRED, 'The new name');

    return $this;
  }

  public function execute(InputInterface $input, OutputInterface $output)
  {

    if (!$input->getOption('sbas_id'))
      throw new \Exception('Missing argument sbas_id');

    if (!$input->getOption('meta_struct_id'))
      throw new \Exception('Missing argument meta_struct_id');

    if (!$input->getOption('name'))
      throw new \Exception('Missing argument name');

    $new_name = $input->getOption('name');

    try
    {
      $databox = \databox::get_instance((int) $input->getOption('sbas_id'));
    }
    catch (\Exception $e)
    {
      $output->writeln("<error>Invalid databox id </error>");

      return 1;
    }

    try
    {
      $field = $databox->get_meta_structure()->get_element((int) $input->getArgument('meta_struct_id'));
    }
    catch (\Exception $e)
    {
      $output->writeln("<error>Invalid meta struct id </error>");

      return 1;
    }


    $dialog   = $this->getHelperSet()->get('dialog');
    $continue = mb_strtolower(
      $dialog->ask(
        $output
        , "<question>About to rename " . $field->get_name() . " into ".$new_name." (y/N)</question>"
        , 'n'
      )
    );

    if($continue != 'y')
    {
      $output->writeln("Request canceled by user");

      return 1;
    }

    $output->writeln("Renaming ... ");

    $field->set_name($new_name);
    $field->save();

    $output->writeln("Done with success !");

    return 0;
  }

}
