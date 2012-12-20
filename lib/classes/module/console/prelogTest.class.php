<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
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
use Alchemy\Phrasea\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

$Core = null;   // global used by prelog.class.php

class module_console_prelogTest extends Command
{
    const EXITCODE_EXCEPTION = 1;
    const EXITCODE_PRELOG_NOT_FOUND = 2;

    public function __construct($name = null)
    {
        parent::__construct($name);

      //  $this->addArgument('login', InputArgument::REQUIRED, 'login to test');
      //  $this->addArgument('password', InputArgument::REQUIRED, 'password');

        $this->setDescription('Test the prelog module');

        $this->addOption(
            'login'
            , 'l'
            , InputOption::VALUE_OPTIONAL
            , "login to test"
            , ""
        );

        $this->addOption(
            'password'
            , 'p'
            , InputOption::VALUE_OPTIONAL
            , "password of login"
            , ""
        );

        $this->addOption(
            'class'
            , NULL
            , InputOption::VALUE_OPTIONAL
            , "file to test '<info>class</info>.class.php'"
            , "prelog"
        );

        return $this;
    }

    public function requireSetup()
    {
        return false;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        global $Core;
        $exitCode = 0;

        try {
            $this->checkSetup();
        } catch (\RuntimeException $e) {
            return self::EXITCODE_SETUP_ERROR;
        }

        $arg_login = trim($input->getOption('login'));
        $arg_password = trim($input->getOption('password'));

        $Core = \bootstrap::getCore();
        $appbox = \appbox::get_instance($Core);
        $registry = $appbox->get_registry();

        // escape some bad chars from classfile
        $from = str_split("/#&;`|*?~<>^()[]{}$\\\x0A\xFF");
        $to   = $from;
        foreach($to as $k=>$v)
            $to[$k] = "\\".$v;
        $classFile = $registry->get('GV_RootPath') . 'config/personnalisation/' .
                str_replace($from, $to, $input->getOption('class')) .
                '.class.php';

        if (file_exists($classFile)) {
            $output->writeln(sprintf("testing 'prelog' file '%s'", $classFile));
            try {
                include($classFile);
                new prelog($arg_login, $arg_password, true);    // true : debug
                $output->writeln(sprintf("after prelog : login='%s', pwd='%s'", $arg_login, $arg_password));
            } catch (Exception $e) {
                $output->writeln("Exception '%s'", $e->getMessage());
                $exitCode = self::EXITCODE_EXCEPTION;
            }
        } else {
            $output->writeln(sprintf("file '%s' not found", $classFile));
            $exitCode = self::EXITCODE_PRELOG_NOT_FOUND;
        }
        return $exitCode;
    }
}

class truc
{

}


