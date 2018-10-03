<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;

class module_console_systemBackupDB extends Command
{

    public function __construct($name = null)
    {
        parent::__construct($name);

        $dir = sprintf(
            '%s/config/'
            , dirname(dirname(dirname(dirname(__DIR__))))
        );

        $this
            ->setDescription('Backups Phraseanet Databases')
            ->addArgument('directory', null, 'The directory where to backup', $dir)
            ->addOption('timeout', 't', InputOption::VALUE_REQUIRED, 'The timeout for this command (default is 3600s / 1h). Set 0 to disable timeout.', 3600)
            ->addOption('gzip', 'g', null, 'Gzip the output (requires gzip utility)')
            ->addOption('bzip', 'b', null, 'Bzip the output (requires bzip2 utility)');

        return $this;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $output->write('Phraseanet is going to be backup...', true);

        $res = 0;
        $res += $this->dump_base($this->getService('phraseanet.appbox'), $input, $output) && $ok;

        foreach ($this->getService('phraseanet.appbox')->get_databoxes() as $databox) {
            $res += $this->dump_base($databox, $input, $output) && $ok;
        }

        return $res;
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

        $command = sprintf(
            'mysqldump %s %s %s %s %s %s --default-character-set=utf8',
            '--host='.escapeshellarg($base->get_host()),
            '--port='.escapeshellarg($base->get_port()),
            '--user='.escapeshellarg($base->get_user()),
            '--password='.escapeshellarg($base->get_passwd()),
            '--databases',
            escapeshellarg($base->get_dbname())
        );

        if ($input->getOption('gzip')) {
            $filename .= '.gz';
            $command .= ' | gzip -9';
        } elseif ($input->getOption('bzip')) {
            $filename .= '.bz2';
            $command .= ' | bzip2 -9';
        }

        $output->write(sprintf('Generating <info>%s</info> ... ', $filename));

        $command .= ' > ' . escapeshellarg($filename);

        $process = new Process($command);
        $process->setTimeout((int) $input->getOption('timeout'));
        $process->run();

        if (!$process->isSuccessful()) {
            $output->writeln('<error>Failed</error>');

            return 1;
        }

        if (file_exists($filename) && filesize($filename) > 0) {
            $output->writeln('OK');

            return 0;
        } else {
            $output->writeln('<error>Failed</error>');

            return 1;
        }
    }
}
