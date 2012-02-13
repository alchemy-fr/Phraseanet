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

class module_console_systemBackupDB extends Command
{

  public function __construct($name = null)
  {
    parent::__construct($name);

    $dir = sprintf(
            '%s/config/'
            , dirname(dirname(dirname(dirname(__DIR__))))
    );

    $this->setDescription('Backup Phraseanet Databases');

    $this->addArgument('directory', null, 'The directory where to backup', $dir);

    return $this;
  }

  public function execute(InputInterface $input, OutputInterface $output)
  {
    if (!setup::is_installed())
    {
      $output->writeln('Argument must be an Id.');

      return 1;
    }

    require_once __DIR__ . '/../../../../lib/bootstrap.php';

    $output->write('Phraseanet is going to be backup...', true);

    $appbox = appbox::get_instance();

    $ok = true;

    $ok = $this->dump_base($appbox, $input, $output) && $ok;

    foreach ($appbox->get_databoxes() as $databox)
    {
      $ok = $this->dump_base($databox, $input, $output) && $ok;
    }

    return (int) !$ok;
  }

  protected function dump_base(base $base, InputInterface $input, OutputInterface $output)
  {
    $date_obj = new DateTime();

    $filename = sprintf(
            '%s%s_%s.sql'
            , p4string::addEndSlash($input->getArgument('directory'))
            , $base->get_dbname()
            , $date_obj->format('Y_m_d_H_i_s')
    );

    $output->write(sprintf('Generating %s ... ', $filename));

    $command = sprintf(
            'mysqldump --host %s --port %s --user %s --password=%s'
            . ' --database %s --default-character-set=utf8 > %s'
            , $base->get_host()
            , $base->get_port()
            , $base->get_user()
            , $base->get_passwd()
            , $base->get_dbname()
            , escapeshellarg($filename)
    );

    system($command);

    if (file_exists($filename) && filesize($filename) > 0)
    {
      $output->writeln('OK');

      return true;
    }
    else
    {
      $output->writeln('<error>Failed</error>');

      return false;
    }


  }

}
