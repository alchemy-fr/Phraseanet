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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class module_console_checkExtension extends Command
{

    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->setDescription('Checks if the Phrasea PHP Extension is well installed & working properly.');

        $this->addArgument('usr_id', InputOption::VALUE_REQUIRED, 'Usr_id to use.');

        $this->addOption('query', '', InputOption::VALUE_OPTIONAL, 'The query', 'last');

        return $this;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {

        if ( ! extension_loaded('phrasea2')) {
            printf("Missing Extension php-phrasea");
        }

        $appbox = \appbox::get_instance(\bootstrap::getCore());
        $registry = $appbox->get_registry();

        $usrId = $input->getArgument('usr_id');

        try {
            $TestUser = \User_Adapter::getInstance($usrId, $appbox);
        } catch (\Exception $e) {
            $output->writeln("<error>Wrong user !</error>");

            return 1;
        }

        $output->writeln(
            sprintf(
                "\nWill do the check with user <info>%s</info> (%s)\n"
                , $TestUser->get_display_name()
                , $TestUser->get_email()
            )
        );

        $output->writeln("PHRASEA FUNCTIONS");

        foreach (get_extension_funcs("phrasea2") as $function) {
            $output->writeln("<info>$function</info>");
        }

        $Core = \bootstrap::getCore();
        $configuration = $Core->getConfiguration();
        $choosenConnection = $configuration->getPhraseanet()->get('database');
        $connexion = $configuration->getConnexion($choosenConnection);
        $hostname = $connexion->get('host');
        $port = $connexion->get('port');
        $user = $connexion->get('user');
        $password = $connexion->get('password');
        $dbname = $connexion->get('dbname');

        $output->writeln("\n-- phrasea_conn --");

        if (phrasea_conn($hostname, $port, $user, $password, $dbname) !== true) {
            $output->writeln("<error>Failed ! </error> got no connection");

            return 1;
        } else {
            $output->writeln("<info>Succes ! </info> got connection");
        }

        $output->writeln("");

        $output->writeln("\n-- phrasea_info --");

        foreach (phrasea_info() as $key => $value) {
            $output->writeln("\t$key => $value");
        }


        $output->writeln("");

        $output->writeln("\n-- phrasea_create_session --");

        $sessid = phrasea_create_session((string) $TestUser->get_id());

        if (ctype_digit((string) $sessid)) {
            $output->writeln("<info>Succes ! </info> got session id $sessid");
        } else {
            $output->writeln("<error>Failed ! </error> got no session id");

            return 1;
        }

        $output->writeln("\n-- phrasea_open_session --");

        $phSession = phrasea_open_session($sessid, $usrId);

        if ($phSession) {
            $output->writeln("<info>Succes ! </info> got session ");
        } else {
            $output->writeln("<error>Failed ! </error> got no session ");

            return 1;
        }

        $output->writeln("\n-- phrasea_clear_cache --");

        $ret = phrasea_clear_cache($sessid);

        if ($sessid) {
            $output->writeln("<info>Succes ! </info> got session ");
        } else {
            $output->writeln("<error>Failed ! </error> got no session ");

            return 1;
        }

        $tbases = array();

        foreach ($phSession["bases"] as $phbase) {
            $tcoll = array();
            foreach ($phbase["collections"] as $coll) {
                $tcoll[] = 0 + $coll["base_id"];
            }
            if (sizeof($tcoll) > 0) {
                $kbase = "S" . $phbase["sbas_id"];
                $tbases[$kbase] = array();
                $tbases[$kbase]["sbas_id"] = $phbase["sbas_id"];
                $tbases[$kbase]["searchcoll"] = $tcoll;
                $tbases[$kbase]["mask_xor"] = $tbases[$kbase]["mask_and"] = 0;

                $qp = new searchEngine_adapter_phrasea_queryParser();
                $treeq = $qp->parsequery($input->getOption('query'));
                $arrayq = $qp->makequery($treeq);

                $tbases[$kbase]["arrayq"] = $arrayq;
            }
        }


        $output->writeln("\n-- phrasea_query --");

        $nbanswers = 0;
        foreach ($tbases as $kb => $base) {
            $tbases[$kb]["results"] = NULL;

            $ret = phrasea_query2(
                $phSession["session_id"]
                , $base["sbas_id"]
                , $base["searchcoll"]
                , $base["arrayq"]
                , $registry->get('GV_sit')
                , $usrId
                , FALSE
                , PHRASEA_MULTIDOC_DOCONLY
                , ''
                , array()
            );


            if ($ret) {
                $output->writeln("<info>Succes ! </info> got result on sbas_id " . $base["sbas_id"]);
            } else {
                $output->writeln("<error>Failed ! </error> No results on sbas_id " . $base["sbas_id"]);

                return 1;
            }

            $tbases[$kb]["results"] = $ret;

            $nbanswers += $tbases[$kb]["results"]["nbanswers"];
        }


        $output->writeln("Got a total of <info>$nbanswers</info> answers");

        $output->writeln("\n-- phrasea_fetch_results --");

        $rs = phrasea_fetch_results($sessid, $usrId, 1, true, '[[em]]', '[[/em]]');

        if ($rs) {
            $output->writeln("<info>Succes ! </info> got result ");
        } else {
            $output->writeln("<error>Failed ! </error> got no result ");

            return 1;
        }

        $output->writeln("\n-- phrasea_close_session --");

        $rs = phrasea_close_session($sessid);

        if ($rs) {
            $output->writeln("<info>Succes ! </info> closed ! ");
        } else {
            $output->writeln("<error>Failed ! </error> not closed ");

            return 1;
        }

        return 0;
    }
}
