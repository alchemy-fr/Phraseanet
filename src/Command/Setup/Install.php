<?php
namespace App\Command\Setup;

use Symfony\Component\Console\Command\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Doctrine\DBAL\Driver\Connection;
use Symfony\Component\Process\ExecutableFinder;

use App\Core\Configuration\StructureTemplate;


class Install extends Command
{

    private $executableFinder;

    private $structureTemplate;


    public function __construct( \App\Core\Configuration\StructureTemplate $structureTemplate)
    {
        $this->structureTemplate = $structureTemplate;
        $this->executableFinder = new ExecutableFinder();

        parent::__construct();

    }

    protected function configure()
    {
        $this
            ->setName('setup:install')
            ->setDescription('Installs Phraseanet.')
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
            ->addOption('indexer', null, InputOption::VALUE_OPTIONAL, 'Path to Phraseanet Indexer', 'auto')
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'Answer yes to all questions');
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $dialog = $this->getHelper('question');

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
</comment>");

        // ASK for databases
        if (!$input->getOption('yes') && !$input->getOption('appbox')) {
            $continue = new ConfirmationQuestion('Do you have these two DB handy ? (N/y)', false);

            if (!$dialog->ask($input, $output, $continue)) {
                $output->writeln("See you later !");
                return 0;
            }
        }

        // ASK for server name
        $serverName = $this->getServerName($input, $output, $dialog);

        // AB connection
        $abConn = $this->getABConn($input, $output, $dialog);
        if(!$abConn) {
            return 1;       // no ab is fatal
        }

        // DB connection & template
        list($dbConn, $templateName) = $this->getDBConn($input, $output, $abConn, $dialog);

        // User credentials
        list($email, $password) = $this->getCredentials($input, $output, $dialog);

        // data path
        $dataPath = $this->getDataPath($input, $output, $dialog);

        // CONFIRM instalation
        if (!$input->getOption('yes') && !$input->getOption('appbox')) {
            $continue = new ConfirmationQuestion('<question>Phraseanet is going to be installed, continue ? (N/y)</question>', false);

            if (!$dialog->ask($input, $output, $continue)) {
                $output->writeln("See you later !");
                return 0;
            }
        }

        // DO INSTALL
        $this->getApplication()->getKernel()->getContainer()->get('phraseanet.installer')->install($email, $password, $abConn, $serverName, $dataPath, $dbConn, $templateName, $this->detectBinaries());

        $output->writeln("<info>Install successful !</info>");


    }


    private function getServerName(InputInterface $input, OutputInterface $output, $dialog)
    {
        $serverName = $input->getOption('server-name');

        if (!$serverName && !$input->getOption('yes')) {
            do {
                $question =  new Question('Please provide the server name : ', null);
                $serverName = $dialog->ask($input, $output, $question);
            } while (!$serverName);
        }

        if (!$serverName) {
            throw new \RuntimeException('Server name is required');
        }

        return $serverName;

    }


    private function getABConn(InputInterface $input, OutputInterface $output, $dialog)
    {
        $abConn = $info = null;

        if (!$input->getOption('appbox')) {
            $output->writeln("<info>--- Database credentials ---</info>");

            do {

                $question =  new Question('DB hostname <comment>[default: "localhost"]</comment> : ', 'localhost');
                $hostname = $dialog->ask($input, $output, $question);

                $question =  new Question('DB port <comment>[default: "3306"]</comment> : ', '3306');
                $port = $dialog->ask($input, $output, $question);

                $question =  new Question('DB user : ');
                $dbUser = $dialog->ask($input, $output, $question);

                $question =  new Question('DB password (hidden) : ');
                $question->setHidden(true);
                $question->setHiddenFallback(false);
                $dbPassword = $dialog->ask($input, $output, $question);

                $question =  new Question('ApplicationBox name <comment>[default: "phraseanet"]</comment> : ', 'phraseanet');
                $abName = $dialog->ask($input, $output, $question);

                $info = [
                    'host'     => $hostname,
                    'port'     => $port,
                    'user'     => $dbUser,
                    'password' => $dbPassword,
                    'dbname'   => $abName,
                    'driver' => 'pdo_mysql',
                ];

                $config = new \Doctrine\DBAL\Configuration();
                $abConn = \Doctrine\DBAL\DriverManager::getConnection($info, $config);

                try {
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

            $config = new \Doctrine\DBAL\Configuration();
            $abConn = \Doctrine\DBAL\DriverManager::getConnection($info, $config);
            $abConn->connect();
            $output->writeln("<info>Application-Box : Connection successful !</info>");
        }

        // add dbs.option & orm.options services to use orm.em later
//        if ($abConn && $info) {
//            $this->container['dbs.options'] = array_merge($this->container['db.options.from_info']($info), $this->container['dbs.options']);
//            $this->container['orm.ems.options'] = array_merge($this->container['orm.em.options.from_info']($info), $this->container['orm.ems.options']);
//        }

        return $abConn;
    }


    private function getDBConn(InputInterface $input, OutputInterface $output, Connection $abConn, $dialog)
    {
        $dbConn = $info = null;
        $templateName = null;

        if (!$input->getOption('databox')) {
            do {
                $retry = false;

                $question =  new Question('Data-Box name, will not be created if empty : ', null);
                $dbName = $dialog->ask($input, $output, $question);

                if ($dbName) {
                    try {
                        $info = [
                            'host'     => $abConn->getHost(),
                            'port'     => $abConn->getPort(),
                            'user'     => $abConn->getUsername(),
                            'password' => $abConn->getPassword(),
                            'dbname'   => $dbName,
                            'driver' => 'pdo_mysql'
                        ];

                        $config = new \Doctrine\DBAL\Configuration();
                        $dbConn = \Doctrine\DBAL\DriverManager::getConnection($info, $config);

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
                'driver' => 'pdo_mysql'
            ];

            $config = new \Doctrine\DBAL\Configuration();
            $dbConn = \Doctrine\DBAL\DriverManager::getConnection($info, $config);

            $dbConn->connect();
            $output->writeln("<info>Data-Box : Connection successful !</info>");
        }

        // add dbs.option & orm.options services to use orm.em later
        if ($dbConn && $info) {
            /** @var StructureTemplate $templates */
            //$templates = $this->container['phraseanet.structure-template'];
            $templates = $this->getApplication()->getKernel()->getContainer()->get('phraseanet.structure-template');

            // if a template name is provided, check that this template exists
            $templateName = $input->getOption('db-template');
            if($templateName && !$templates->getByName($templateName)) {
                throw new \Exception_InvalidArgument(sprintf("Databox template \"%s\" not found.", $templateName));
            }
            if(!$templateName) {
                // propose a default template : the first available if "en-simple" does not exists.
                $defaultDBoxTemplate = $this->structureTemplate->getDefault();

                do {

                    $question =  new Question('Choose a template from ('.$templates->toString().') for metadata structure <comment>[default: "'.$defaultDBoxTemplate.'"]</comment> : ', $defaultDBoxTemplate);
                    $templateName = $dialog->ask($input, $output, $question);

                    //$templateName = $dialog->ask($output, 'Choose a template from ('.$templates->toString().') for metadata structure <comment>[default: "'.$defaultDBoxTemplate.'"]</comment> : ', $defaultDBoxTemplate);
                    if(!$templates->getByName($templateName)) {
                        $output->writeln("<error>Data-Box template : Template not found, try again.</error>");
                    }
                }
                while (!$templates->getByName($templateName));
            }

//            $this->container['dbs.options'] = array_merge($this->container['db.options.from_info']($info), $this->container['dbs.options']);
//            $this->container['orm.ems.options'] = array_merge($this->container['orm.em.options.from_info']($info), $this->container['orm.ems.options']);
        }

        return [$dbConn, $templateName];
    }


    private function getCredentials(InputInterface $input, OutputInterface $output, $dialog)
    {
        $email = $password = null;

        if (!$input->getOption('email') && !$input->getOption('password')) {
            $output->writeln("<info>--- Account Informations ---</info>");

            do {
                $question =  new Question('Please provide a valid e-mail address : ');
                $email = $dialog->ask($input, $output, $question);

                // } while (!\Swift_Validate::email($email));
            } while (!($email));

            do {
                $question =  new Question('Please provide a password (hidden, 6 character min) : ');
                $question->setHidden(true);
                $question->setHiddenFallback(false);
                $password = $dialog->ask($input, $output, $question);
            } while (strlen($password) < 6);

            $output->writeln("<info>Email / Password successfully set</info>");
        } elseif ($input->getOption('email') && $input->getOption('password')) {
            //if (!\Swift_Validate::email($input->getOption('email'))) {
            if (!($input->getOption('email'))) {
                throw new \RuntimeException('Invalid email addess');
            }
            $email = $input->getOption('email');
            $password = $input->getOption('password');
        } else {
            throw new \RuntimeException('You have to provide both email and password');
        }

        return [$email, $password];
    }


    private function getDataPath(InputInterface $input, OutputInterface $output, $dialog)
    {
        $dataPath = $input->getOption('data-path');

        if (!$input->getOption('yes')) {

            $continue = new ConfirmationQuestion('Would you like to change default data-path ? (N/y)', false);

            if ($dialog->ask($input, $output, $continue)) {

                do {
                    $question = new Question('Please provide the data path : ', null);
                    $dataPath = $dialog->ask($input, $output, $question);

                } while (!$dataPath || !is_writable($dataPath));
            }

        }


//        if (!$dataPath || !is_writable($dataPath)) {
//            throw new \RuntimeException(sprintf('Data path `%s` is not writable', $dataPath));
//        }

        return $dataPath;
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