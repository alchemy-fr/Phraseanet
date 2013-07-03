<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Setup;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Setup\Installer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Console\Helper\DialogHelper;

class Install extends Command
{
    private $executableFinder;

    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->executableFinder = new ExecutableFinder();

        $this
            ->setDescription("Install Phraseanet")
            ->addOption('email', null, InputOption::VALUE_OPTIONAL, 'Admin e-mail address', null)
            ->addOption('password', null, InputOption::VALUE_OPTIONAL, 'Admin password', null)
            ->addOption('db-host', null, InputOption::VALUE_OPTIONAL, 'MySQL server host', 'localhost')
            ->addOption('db-port', null, InputOption::VALUE_OPTIONAL, 'MySQL server port', 3306)
            ->addOption('db-user', null, InputOption::VALUE_OPTIONAL, 'MySQL server user', 'phrasea')
            ->addOption('db-password', null, InputOption::VALUE_OPTIONAL, 'MySQL server password', null)
            ->addOption('db-template', null, InputOption::VALUE_OPTIONAL, 'Metadata structure language template', null)
            ->addOption('databox', null, InputOption::VALUE_OPTIONAL, 'Database name for the DataBox', null)
            ->addOption('appbox', null, InputOption::VALUE_OPTIONAL, 'Database name for the ApplicationBox', null)
            ->addOption('indexer', null, InputOption::VALUE_OPTIONAL, 'Path to Phraseanet Indexer', 'auto')
            ->addOption('data-path', null, InputOption::VALUE_OPTIONAL, 'Path to data repository', realpath(__DIR__ . '/../../../../../datas'))
            ->addOption('server-name', null, InputOption::VALUE_OPTIONAL, 'Server name')
            ->addOption('indexer', null, InputOption::VALUE_OPTIONAL, 'Path to Phraseanet Indexer', 'auto')
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'Answer yes to all questions');

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getHelperSet()->get('dialog');

        $output->writeln("<comment>
                                                      ,-._.-._.-._.-._.-.
                                                      `-.             ,-'
 .----------------------------------------------.       |             |
|                                                |      |             |
|  Hello !                                       |      |             |
|                                                |      |             |
|  You are on your way to install Phraseanet,    |     ,';\".________.-.
|  You will need access to 2 MySQL databases.    |     ;';_'         )]
|                                                |    ;             `-|
|                                                `.    `T-            |
 `----------------------------------------------._ \    |             |
                                                  `-;   |             |
                                                        |..________..-|
                                                       /\/ |________..|
                                                  ,'`./  >,(           |
                                                  \_.-|_/,-/   ii  |   |
                                                   `.\"' `-/  .-\"\"\"||    |
                                                    /`^\"-;   |    ||____|
                                                   /     /   `.__/  | ||
                                                        /           | ||
                                                                    | ||
</comment>"
        );

        if (!$input->getOption('yes') && !$input->getOption('appbox')) {
            $continue = $dialog->askConfirmation($output, 'Do you have these two DB handy ? (N/y)', false);

            if (!$continue) {
                $output->writeln("See you later !");

                return 0;
            }
        }

        $abConn = $this->getABConn($input, $output, $dialog);
        list($dbConn, $template) = $this->getDBConn($input, $output, $abConn, $dialog);
        list($email, $password) = $this->getCredentials($input, $output, $dialog);
        $dataPath = $this->getDataPath($input, $output);
        $serverName = $this->getServerName($input, $output, $dialog);
        $indexer = $this->getindexer($input, $output);

        if (!$input->getOption('yes')) {
            $continue = $dialog->askConfirmation($output, "<question>Phraseanet is going to be installed, continue ? (N/y)</question>", false);

            if (!$continue) {
                $output->writeln("See you later !");

                return 0;
            }
        }

        $this->container['phraseanet.installer']->setPhraseaIndexerPath($indexer);
        $this->container['phraseanet.installer']->install($email, $password, $abConn, $serverName, $dataPath, $dbConn, $template, $this->detectBinaries());

        $output->writeln("<info>Install successful !</info>");

        return;
    }

    private function getABConn(InputInterface $input, OutputInterface $output, DialogHelper $dialog)
    {
        $abConn = null;
        if (!$input->getOption('appbox')) {
            $output->writeln("\n<info>--- Database credentials ---</info>\n");

            do {
                $hostname = $dialog->ask($output, "DB hostname (localhost) : ", 'localhost');
                $port = $dialog->ask($output, "DB port (3306) : ", 3306);
                $dbUser = $dialog->ask($output, "DB user : ");
                $dbPassword = $dialog->ask($output, "DB password : ");
                $abName = $dialog->ask($output, "DB name (phraseanet) : ", 'phraseanet');

                try {
                    $abConn = new \connection_pdo('appbox', $hostname, $port, $dbUser, $dbPassword, $abName, array(), $this->container['debug']);
                    $output->writeln("\n\t<info>Application-Box : Connection successful !</info>\n");
                } catch (\Exception $e) {
                    $output->writeln("\n\t<error>Invalid connection parameters</error>\n");
                }
            } while (!$abConn);
        } else {
            $abConn = new \connection_pdo('appbox', $input->getOption('db-host'), $input->getOption('db-port'), $input->getOption('db-user'), $input->getOption('db-password'), $input->getOption('appbox'), array(), $this->container['debug']);
            $output->writeln("\n\t<info>Application-Box : Connection successful !</info>\n");
        }

        return $abConn;
    }

    private function getDBConn(InputInterface $input, OutputInterface $output, \connection_pdo $abConn, DialogHelper $dialog)
    {
        $credentials = $abConn->get_credentials();

        $dbConn = null;
        if (!$input->getOption('databox')) {
            do {
                $retry = false;
                $dbName = $dialog->ask($output, 'DataBox name, will not be created if empty : ', null);

                if ($dbName) {
                    try {
                        $dbConn = new \connection_pdo('databox', $credentials['hostname'], $credentials['port'], $credentials['user'], $credentials['password'], $dbName, array(), $this->container['debug']);
                        $output->writeln("\n\t<info>Data-Box : Connection successful !</info>\n");

                        do {
                            $template = $dialog->ask($output, 'Choose a language template for metadata structure (en) : ', 'en');
                        } while (!in_array($template, array('en', 'fr')));

                        $output->writeln("\n\tLanguage selected is <info>'$template'</info>\n");
                    } catch (\Exception $e) {
                        $retry = true;
                    }
                } else {
                    $output->writeln("\n\tNo databox will be created\n");
                }
            } while ($retry);
        } else {
            $dbConn = new \connection_pdo('databox', $input->getOption('db-host'), $input->getOption('db-port'), $input->getOption('db-user'), $input->getOption('db-password'), $input->getOption('databox'), array(), $this->container['debug']);
            $output->writeln("\n\t<info>Data-Box : Connection successful !</info>\n");
            $template = $input->getOption('db-template') ? : 'en';
        }

        return array($dbConn, $template);
    }

    private function getCredentials(InputInterface $input, OutputInterface $output, DialogHelper $dialog)
    {
        $email = $password = null;

        if (!$input->getOption('email') && !$input->getOption('password')) {
            $output->writeln("\n<info>--- Account Informations ---</info>\n");

            do {
                $email = $dialog->ask($output, 'Please provide a valid e-mail address : ');
            } while (!\Swift_Validate::email($email));

            do {
                $password = $dialog->ask($output, 'Please provide a password (6 character min) : ');
            } while (strlen($password) < 6);

            $output->writeln("\n\t<info>Email / Password successfully set</info>\n");
        } elseif ($input->getOption('email') && $input->getOption('password')) {
            if (!\Swift_Validate::email($input->getOption('email'))) {
                throw new \RuntimeException('Invalid email addess');
            }
            $email = $input->getOption('email');
            $password = $input->getOption('password');
        } else {
            throw new \RuntimeException('You have to provide both email and password');
        }

        return array($email, $password);
    }

    private function getDataPath(InputInterface $input, OutputInterface $output)
    {
        $dataPath = $input->getOption('data-path');

        if (!$dataPath || !is_writable($dataPath)) {
            throw new \RuntimeException(sprintf('Data path `%s` is not writable', $dataPath));
        }

        return $dataPath;
    }

    private function getServerName(InputInterface $input, OutputInterface $output, DialogHelper $dialog)
    {
        $serverName = $input->getOption('server-name');

        if (!$serverName && !$input->getOption('yes')) {
            do {
                $retry = false;
                $serverName = $dialog->ask($output, 'Please provide the server name : ', null);
            } while (!$serverName);
        }

        if (!$serverName) {
            throw new \RuntimeException('Server name is required');
        }

        return $serverName;
    }

    private function getindexer(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('indexer') == 'auto') {
            $indexer = $this->executableFinder->find('phraseanet_indexer');
        } else {
            $indexer = $input->getOption('indexer');
        }

        return $indexer;
    }

    private function detectBinaries()
    {
        return array(
            'php_binary'           => $this->executableFinder->find('php'),
            'phraseanet_indexer'   => $this->executableFinder->find('phraseanet_indexer'),
            'convert_binary'       => $this->executableFinder->find('convert'),
            'composite_binary'     => $this->executableFinder->find('composite'),
            'pdf2swf_binary'       => $this->executableFinder->find('pdf2swf'),
            'swf_extract_binary'   => $this->executableFinder->find('swfextract'),
            'swf_render_binary'    => $this->executableFinder->find('swfrender'),
            'unoconv_binary'       => $this->executableFinder->find('unoconv'),
            'ffmpeg_binary'        => $this->executableFinder->find('ffmpeg', $this->executableFinder->find('avconv')),
            'ffprobe_binary'       => $this->executableFinder->find('ffprobe', $this->executableFinder->find('avprobe')),
            'mp4box_binary'        => $this->executableFinder->find('MP4Box'),
            'pdftotext_binary'     => $this->executableFinder->find('pdftotext'),
            'ghostscript_binary'   => $this->executableFinder->find('gs'),
        );
    }
}
