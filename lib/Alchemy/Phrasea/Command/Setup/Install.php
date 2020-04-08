<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Setup;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Core\Configuration\StructureTemplate;
use Alchemy\Phrasea\SearchEngine\Elastic\ElasticsearchOptions;
use Doctrine\DBAL\Driver\Connection;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ExecutableFinder;

class Install extends Command
{
    private $executableFinder;
    /** @var StructureTemplate StructureTemplate */
    private $structureTemplate;

    /**
     * @param null|string $name
     * @param StructureTemplate $structureTemplate
     */
    public function __construct($name, $structureTemplate)
    {
        parent::__construct($name);

        $this->structureTemplate = $structureTemplate;
        $this->executableFinder = new ExecutableFinder();

        $this
            ->setDescription("Installs Phraseanet")
            ->addOption('email', null, InputOption::VALUE_OPTIONAL, 'Admin e-mail address', null)
            ->addOption('password', null, InputOption::VALUE_OPTIONAL, 'Admin password', null)
            ->addOption('db-host', null, InputOption::VALUE_OPTIONAL, 'MySQL server host', 'localhost')
            ->addOption('db-port', null, InputOption::VALUE_OPTIONAL, 'MySQL server port', 3306)
            ->addOption('db-user', null, InputOption::VALUE_OPTIONAL, 'MySQL server user', 'phrasea')
            ->addOption('db-password', null, InputOption::VALUE_OPTIONAL, 'MySQL server password', null)
            ->addOption('appbox', null, InputOption::VALUE_OPTIONAL, 'Database name for the ApplicationBox', null)
            ->addOption('databox', null, InputOption::VALUE_OPTIONAL, 'Database name for the DataBox', null)
            ->addOption('db-template', null, InputOption::VALUE_OPTIONAL, 'Databox template (' . $this->structureTemplate->toString() . ')', null)
            ->addOption('data-path', null, InputOption::VALUE_OPTIONAL, 'Path to data repository', realpath(__DIR__ . '/../../../../../datas'))
            ->addOption('server-name', null, InputOption::VALUE_OPTIONAL, 'Server name')
            ->addOption('es-host', null, InputOption::VALUE_OPTIONAL, 'ElasticSearch server HTTP host', 'localhost')
            ->addOption('es-port', null, InputOption::VALUE_OPTIONAL, 'ElasticSearch server HTTP port', 9200)
            ->addOption('es-index', null, InputOption::VALUE_OPTIONAL, 'ElasticSearch index name', null)
            ->addOption('download-path', null, InputOption::VALUE_OPTIONAL, 'Path to download repository', __DIR__ . '/../../../../../tmp/download')
            ->addOption('lazaret-path', null, InputOption::VALUE_OPTIONAL, 'Path to lazaret repository', __DIR__ . '/../../../../../tmp/lazaret')
            ->addOption('caption-path', null, InputOption::VALUE_OPTIONAL, 'Path to caption repository', __DIR__ . '/../../../../../tmp/caption')
            ->addOption('scheduler-locks-path', null, InputOption::VALUE_OPTIONAL, 'Path to scheduler-locks repository', __DIR__ . '/../../../../../tmp/locks')
            ->addOption('worker-tmp-files', null, InputOption::VALUE_OPTIONAL, 'Path to worker-tmp-files repository', __DIR__ . '/../../../../../tmp')
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'Answer yes to all questions')
            ->setHelp("Phraseanet can only be installed on 64 bits PHP.");
            ;

        return $this;
    }

    private function serverNameToAppBoxName($serverName)
    {
        return "ab_" . $serverName;
    }

    private function serverNameToDataBoxName($serverName)
    {
        return "db_" . $serverName;
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        if(PHP_INT_SIZE !== 8) {
            $output->writeln(sprintf(
                "<error>Phraseanet can only be installed on 64 bits PHP, your version is %d bits (PHP_INT_SIZE=%d).</error>",
                PHP_INT_SIZE<<3,PHP_INT_SIZE
            ));
            return -1;
        }

        /** @var DialogHelper $dialog */
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

        $serverName = $this->getServerName($input, $output, $dialog);

        $abConn = $this->getABConn($input, $output, $dialog, $serverName);
        if(!$abConn) {
            return 1;       // no ab is fatal
        }

        list($dbConn, $templateName) = $this->getDBConn($input, $output, $abConn, $dialog);
        list($email, $password) = $this->getCredentials($input, $output, $dialog);
        $dataPath = $this->getDataPath($input, $output, $dialog);

        if (! $input->getOption('yes')) {
            $output->writeln("<info>--- ElasticSearch connection settings ---</info>");
        }

        list($esHost, $esPort) = $this->getESHost($input, $output, $dialog);
        $esIndexName = $this->getESIndexName($input, $output, $dialog);

        $esOptions = ElasticsearchOptions::fromArray([
            'host'  => $esHost,
            'port'  => $esPort,
            'index' => $esIndexName
        ]);

        $output->writeln('');

        if (!$input->getOption('yes')) {
            $continue = $dialog->askConfirmation($output, "<question>Phraseanet is going to be installed, continue ? (N/y)</question>", false);

            if (!$continue) {
                $output->writeln("See you later !");

                return 0;
            }
        }

        $storagePaths = $this->getStoragePaths($input, $dataPath);

        $this->container['phraseanet.installer']->install($email, $password, $abConn, $serverName, $storagePaths, $dbConn, $templateName, $this->detectBinaries());
        $this->container['conf']->set(['main', 'search-engine', 'options'], $esOptions->toArray());

        if (null !== $this->getApplication()) {
            $command = $this->getApplication()->find('crossdomain:generate');
            $command->run(new ArrayInput([
                'command' => 'crossdomain:generate'
            ]), $output);
        }

        $output->writeln("<info>Install successful !</info>");

        return 0;
    }

    private function getABConn(InputInterface $input, OutputInterface $output, DialogHelper $dialog)
    {
        $abConn = $info = null;
        if (!$input->getOption('appbox')) {
            $output->writeln("<info>--- Database credentials ---</info>");

            do {
                $hostname = $dialog->ask($output, 'DB hostname <comment>[default: "localhost"]</comment> : ', 'localhost');
                $port = $dialog->ask($output, 'DB port <comment>[default: "3306"]</comment> : ', '3306');
                $dbUser = $dialog->ask($output, 'DB user : ');
                $dbPassword = $dialog->askHiddenResponse($output, 'DB password (hidden) : ');
                $abName = $dialog->ask($output, 'ApplicationBox name <comment>[default: "phraseanet"]</comment> : ', 'phraseanet');

                $info = [
                    'host'     => $hostname,
                    'port'     => $port,
                    'user'     => $dbUser,
                    'password' => $dbPassword,
                    'dbname'   => $abName,
                ];
                try {
                    $abConn = $this->container['dbal.provider']($info);
                    $abConn->connect();
                    $output->writeln("<info>Application-Box : Connection successful !</info>");
                } catch (\Exception $e) {
                    $output->writeln("<error>Application-Box : Failed to connect, try again.</error>");
                    $abConn = null;
                }
            } while (!$abConn);
        } else {
            $info = [
                'host'     => $input->getOption('db-host'),
                'port'     => $input->getOption('db-port'),
                'user'     => $input->getOption('db-user'),
                'password' => $input->getOption('db-password'),
                'dbname'   => $input->getOption('appbox'),
            ];

            $abConn = $this->container['dbal.provider']($info);
            $abConn->connect();
            $output->writeln("<info>Application-Box : Connection successful !</info>");
        }

        // add dbs.option & orm.options services to use orm.em later
        if ($abConn && $info) {
            $this->container['dbs.options'] = array_merge($this->container['db.options.from_info']($info), $this->container['dbs.options']);
            $this->container['orm.ems.options'] = array_merge($this->container['orm.em.options.from_info']($info), $this->container['orm.ems.options']);
        }

        return $abConn;
    }

    private function getDBConn(InputInterface $input, OutputInterface $output, Connection $abConn, DialogHelper $dialog)
    {
        $dbConn = $info = null;
        $templateName = null;

        if (!$input->getOption('databox')) {
            do {
                $retry = false;
                $dbName = $dialog->ask($output, 'Data-Box name, will not be created if empty : ', null);

                if ($dbName) {
                    try {
                        $info = [
                            'host'     => $abConn->getHost(),
                            'port'     => $abConn->getPort(),
                            'user'     => $abConn->getUsername(),
                            'password' => $abConn->getPassword(),
                            'dbname'   => $dbName,
                        ];

                        $dbConn = $this->container['dbal.provider']($info);
                        $dbConn->connect();
                        $output->writeln("<info>Data-Box : Connection successful !</info>");
                    } catch (\Exception $e) {
                        $output->writeln("    <error>Data-Box : Failed to connect, try again.</error>");
                        $retry = true;
                    }
                } else {
                    $output->writeln("No databox will be created");
                }
            } while ($retry);
        } else {
            $info = [
                'host'     => $input->getOption('db-host'),
                'port'     => $input->getOption('db-port'),
                'user'     => $input->getOption('db-user'),
                'password' => $input->getOption('db-password'),
                'dbname'   => $input->getOption('databox'),
            ];

            $dbConn = $this->container['dbal.provider']($info);
            $dbConn->connect();
            $output->writeln("<info>Data-Box : Connection successful !</info>");
        }

        // add dbs.option & orm.options services to use orm.em later
        if ($dbConn && $info) {
            /** @var StructureTemplate $templates */
            $templates = $this->container['phraseanet.structure-template'];

            // if a template name is provided, check that this template exists
            $templateName = $input->getOption('db-template');
            if($templateName && !$templates->getByName($templateName)) {
                throw new \Exception_InvalidArgument(sprintf("Databox template \"%s\" not found.", $templateName));
            }
            if(!$templateName) {
                // propose a default template : the first available if "en-simple" does not exists.
                $defaultDBoxTemplate = $this->structureTemplate->getDefault();

                do {
                    $templateName = $dialog->ask($output, 'Choose a template from ('.$templates->toString().') for metadata structure <comment>[default: "'.$defaultDBoxTemplate.'"]</comment> : ', $defaultDBoxTemplate);
                    if(!$templates->getByName($templateName)) {
                        $output->writeln("<error>Data-Box template : Template not found, try again.</error>");
                    }
                }
                while (!$templates->getByName($templateName));
            }

            $this->container['dbs.options'] = array_merge($this->container['db.options.from_info']($info), $this->container['dbs.options']);
            $this->container['orm.ems.options'] = array_merge($this->container['orm.em.options.from_info']($info), $this->container['orm.ems.options']);
        }

        return [$dbConn, $templateName];
    }

    private function getCredentials(InputInterface $input, OutputInterface $output, DialogHelper $dialog)
    {
        $email = $password = null;

        if (!$input->getOption('email') && !$input->getOption('password')) {
            $output->writeln("<info>--- Account Informations ---</info>");

            do {
                $email = $dialog->ask($output, 'Please provide a valid e-mail address : ');
            } while (!\Swift_Validate::email($email));

            do {
                $password = $dialog->askHiddenResponse($output, 'Please provide a password (hidden, 6 character min) : ');
            } while (strlen($password) < 6);

            $output->writeln("<info>Email / Password successfully set</info>");
        } elseif ($input->getOption('email') && $input->getOption('password')) {
            if (!\Swift_Validate::email($input->getOption('email'))) {
                throw new \RuntimeException('Invalid email addess');
            }
            $email = $input->getOption('email');
            $password = $input->getOption('password');
        } else {
            throw new \RuntimeException('You have to provide both email and password');
        }

        return [$email, $password];
    }

    private function getDataPath(InputInterface $input, OutputInterface $output, DialogHelper $dialog)
    {
        $dataPath = $input->getOption('data-path');

        if (!$input->getOption('yes')) {
            $continue = $dialog->askConfirmation($output, 'Would you like to change default data-path ? (N/y)', false);

            if ($continue) {
                do {
                    $dataPath = $dialog->ask($output, 'Please provide the data path : ', null);
                } while (!$dataPath || !is_writable($dataPath));
            }
        }

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
                $serverName = $dialog->ask($output, 'Please provide the server name : ', null);
            } while (!$serverName);
        }

        if (!$serverName) {
            throw new \RuntimeException('Server name is required');
        }

        return $serverName;
    }

    private function getESHost(InputInterface $input, OutputInterface $output, DialogHelper $dialog)
    {
        $host = $input->getOption('es-host');
        $port = (int) $input->getOption('es-port');

        if (! $input->getOption('yes')) {
            while (! $host) {
                $host = $dialog->ask($output, 'ElasticSearch server host : ', null);
            };

            while ($port <= 0 || $port >= 65535) {
                $port = (int) $dialog->ask($output, 'ElasticSearch server port : ', null);
            };
        }

        return [ $host, $port ];
    }

    private function getESIndexName(InputInterface $input, OutputInterface $output, DialogHelper $dialog)
    {
        $index = $input->getOption('es-index');

        if (! $input->getOption('yes')) {
            $index = $dialog->ask($output, 'ElasticSearch server index name (blank to autogenerate) : ', null);
        }

        return $index;
    }

    private function getStoragePaths(InputInterface $input, $dataPath)
    {
        $schedulerLocksPath = $input->getOption('scheduler-locks-path');

        if (!is_dir($schedulerLocksPath)) {
            mkdir($schedulerLocksPath, 0755, true);
        }

        if (($schedulerLocksPath = realpath($schedulerLocksPath)) === FALSE) {
            throw new \InvalidArgumentException(sprintf('Path %s does not exist.', $schedulerLocksPath));
        }

        return [
            'subdefs'           => $dataPath,
            'download'          => $input->getOption('download-path'),
            'lazaret'           => $input->getOption('lazaret-path'),
            'caption'           => $input->getOption('caption-path'),
            'worker_tmp_files'  => $input->getOption('worker-tmp-files')
        ];
    }

    private function detectBinaries()
    {
        return [
            'php_binary'           => $this->executableFinder->find('php'),
            'pdf2swf_binary'       => $this->executableFinder->find('pdf2swf'),
            'swf_extract_binary'   => $this->executableFinder->find('swfextract'),
            'swf_render_binary'    => $this->executableFinder->find('swfrender'),
            'unoconv_binary'       => $this->executableFinder->find('unoconv'),
            'ffmpeg_binary'        => $this->executableFinder->find('ffmpeg', $this->executableFinder->find('avconv')),
            'ffprobe_binary'       => $this->executableFinder->find('ffprobe', $this->executableFinder->find('avprobe')),
            'mp4box_binary'        => $this->executableFinder->find('MP4Box'),
            'pdftotext_binary'     => $this->executableFinder->find('pdftotext'),
            'ghostscript_binary'   => $this->executableFinder->find('gs'),
        ];
    }
}
