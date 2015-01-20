<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
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
use Alchemy\Phrasea\SearchEngine\Phrasea\PhraseaEngineQueryParser;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class module_console_checkExtension extends Command
{

    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->setDescription('Performs a serie of tests against Phrasea Engine PHP Extension');

        $this->addArgument('usr_id', InputOption::VALUE_REQUIRED, 'Usr_id to use.');

        $this->addOption('query', '', InputOption::VALUE_OPTIONAL, 'The query', 'last');

        return $this;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        if ( ! extension_loaded('phrasea2')) {
            $output->writeln("<error>Missing Extension php-phrasea.</error>");

            return 1;
        }

        $usrId = $input->getArgument('usr_id');

        try {
            $TestUser = \User_Adapter::getInstance($usrId, $this->container);
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

        $configuration = $this->getService('phraseanet.configuration');
        $connexion = $configuration['main']['database'];
        $hostname = $connexion['host'];
        $port = $connexion['port'];
        $user = $connexion['user'];
        $password = $connexion['password'];
        $dbname = $connexion['dbname'];

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

                $qp = new PhraseaEngineQueryParser($this->container);
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
                , $this->container['phraseanet.configuration']['main']['key']
                , $usrId
                , false
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

        // test disconnected mode if available
        // prepare the test before closing session
        if (function_exists("phrasea_public_query")) {
            // fill an array for each sbas to query
            $tbases = array();
            foreach ($phSession["bases"] as $phbase) {
                // fill an array of collections to query for this sbas
                $tcoll = array();
                foreach ($phbase["collections"] as $coll) {
                    $tcoll[] = 0 + $coll["base_id"];
                }

                if (sizeof($tcoll) > 0) {
                    // parse the query for this sbas
                    $qp = new PhraseaEngineQueryParser($this->container);
                    $treeq = $qp->parsequery($input->getOption('query'));
                    $arrayq = $qp->makequery($treeq);

                    $tbases["S".$phbase["sbas_id"]] = array(    // key does no matter
                        "sbas_id" => $phbase["sbas_id"],        // sbas_id
                        "searchcoll" => $tcoll,                 // colls to query
                        "arrayq" => $arrayq                     // parsed query
                    );
                }
            }
        }

        $output->writeln("\n-- phrasea_close_session --");

        $rs = phrasea_close_session($sessid);

        if ($rs) {
            $output->writeln("<info>Succes ! </info> closed ! ");
        } else {
            $output->writeln("<error>Failed ! </error> not closed ");

            return 1;
        }

        // session is closed, test disconnected mode if available
        if (function_exists("phrasea_public_query")) {
            $output->writeln("\n-- phrasea_public_query(...0, 5,...) --");

            $ret = phrasea_public_query(
                $tbases                         // array of sbas with colls and query
                , PHRASEA_MULTIDOC_DOCONLY      // mode
                , ''                            // sortfield
                , array()                       // search business fields
                , ''                            // lng for stemmed search
                , 0                             // offset for first answer (start=0)
                , 5                             // nbr of answers
                , true                          // verbose output (chrono, sql...)
            );

            if (is_array($ret) && array_key_exists("results", $ret) && is_array($ret["results"])) {
                $output->writeln( sprintf("<info>Succes ! </info> returned %d answers", count($ret["results"])) );
            } else {
                $output->writeln("<error>Failed ! </error>");

                return 1;
            }

            foreach ($ret['results'] as $result) {
                $sbid = $result["sbid"];
                $rid  = $result["rid"];
                $q = $tbases["S".$sbid]["arrayq"];  // query tree

                $h = phrasea_highlight(
                    $sbid                       // sbas_id
                    , $rid                      // record_id
                    , $q                        // query parsed
                    , ""                        // lng for stemmed
                    , false                     // verbose output (chrono, sql...)
                    );

                $output->writeln(sprintf("\n-- phrasea_highlight(%d, %d,...) --", $sbid, $rid));

                if(is_array($h) && array_key_exists("results", $h) && is_array($h["results"])
                    && count($h["results"])==1
                    && array_key_exists("sbid", $h["results"][0]) && $h["results"][0]["sbid"]==$sbid
                    && array_key_exists("rid", $h["results"][0]) && $h["results"][0]["rid"]==$rid
                    && array_key_exists("spots", $h["results"][0]) && is_array($h["results"][0]["spots"]) )
                {

                    $output->writeln( sprintf("<info>Succes ! </info> sbid=%d, rid=%d (%d spots)",
                        $sbid,
                            $h["results"][0]["rid"],
                        count($h["results"][0]["spots"]))
                    );
                } else {
                    $output->writeln("<error>Failed ! </error>");

                    return 1;
                }
            }

        } // disconnected mode

        return 0;
    }
}
