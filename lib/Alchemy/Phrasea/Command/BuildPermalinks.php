<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command;

use Alchemy\Phrasea\Core\PhraseaTokens;
use Alchemy\Phrasea\Databox\SubdefGroup;
use Alchemy\Phrasea\Media\SubdefGenerator;
use Databox;
use databox_subdef;
use DateTime;
use Doctrine\DBAL\Connection;
use media_Permalink_Adapter;
use PDO;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use media_subdef;

class BuildPermalinks extends Command
{
    const OPTION_DISTINT_VALUES = 0;
    const OPTION_ALL_VALUES     = 1;

    /** @var InputInterface */
    private $input;
    /** @var OutputInterface */
    private $output;
    /** @var bool */
    private $argsOK;
    /** @var Databox */
    private $databox;
    /** @var  connection */
    private $connection;

    /** @var bool */
    private $clear_cache;
    /** @var bool */
    private $prune;
    /** @var bool */
    private $create;
    /** @var bool */
    private $force_create;
    /** @var bool */
    private $dry;
    /** @var bool */
    private $show_sql;
    /** @var array */
    private $names;


    public function __construct()
    {
        parent::__construct('records:build-permalinks');

        $this->setDescription('Build permalinks');
        $this->addOption('databox',            null, InputOption::VALUE_REQUIRED,                             'Mandatory : The id (or dbname or viewname) of the databox');
        $this->addOption('clear_cache',        null, InputOption::VALUE_NONE,                                 'Clear the cache before running');
        $this->addOption('create',             null, InputOption::VALUE_NONE,                                 'Create missing permalinks');
        $this->addOption('force_create',       null, InputOption::VALUE_NONE,                                 '(re)create all permalinks (enforce clear_cache)');
        $this->addOption('name',               null, InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 'Create only for those subdefs, ex. "thumbnail,preview", default=ALL');
        $this->addOption('prune',              null, InputOption::VALUE_NONE,                                 'Delete permalinks on non-existing subdefs');
        $this->addOption('dry',                null, InputOption::VALUE_NONE,                                 'dry run, list but don\'t act');
        $this->addOption('show_sql',           null, InputOption::VALUE_NONE,                                 'show sql');
    }

    /**
     * merge options so one can mix csv-option and/or multiple options
     * ex. with keepUnique = false :  --opt=a,b --opt=c --opt=b  ==> [a,b,c,b]
     * ex. with keepUnique = true  :  --opt=a,b --opt=c --opt=b  ==> [a,b,c]
     *
     * @param InputInterface $input
     * @param string $optionName
     * @param int $option
     * @return array
     */
    private function getOptionAsArray(InputInterface $input, $optionName, $option)
    {
        $ret = [];
        foreach($input->getOption($optionName) as $v0) {
            foreach(explode(',', $v0) as $v) {
                $v = trim($v);
                if($option & self::OPTION_ALL_VALUES || !in_array($v, $ret)) {
                    $ret[] = $v;
                }
            }
        }

        return $ret;
    }

    /**
     * print a string if verbosity >= verbose (-v)
     * @param string $s
     */
    private function verbose($s)
    {
        if($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $this->output->write($s);
        }
    }

    /**
     * sanity check the cmd line options
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool
     */
    protected function sanitizeArgs(InputInterface $input, OutputInterface $output)
    {
        $argsOK = true;

        // find the databox / collection by id or by name
        $this->databox = null;
        if(($d = $input->getOption('databox')) !== null) {
            $d = trim($d);
            foreach ($this->container->getDataboxes() as $db) {
                if ($db->get_sbas_id() == (int)$d || $db->get_viewname() == $d || $db->get_dbname() == $d) {
                    $this->databox = $db;
                    $this->connection = $db->get_connection();
                    break;
                }
            }
            if ($this->databox == null) {
                $output->writeln(sprintf("<error>Unknown databox \"%s\"</error>", $input->getOption('databox')));
                $argsOK = false;
            }
        }
        else {
            $output->writeln(sprintf("<error>Missing mandatory options --databox</error>"));
            $argsOK = false;
        }

        $this->clear_cache        = $input->getOption('clear_cache') ? true : false;
        $this->show_sql           = $input->getOption('show_sql') ? true : false;
        $this->dry                = $input->getOption('dry') ? true : false;
        $this->create             = $input->getOption('create') ? true : false;
        $this->force_create       = $input->getOption('force_create') ? true : false;
        $this->prune              = $input->getOption('prune') ? true : false;
        $this->names              = $this->getOptionAsArray($input, 'name', self::OPTION_DISTINT_VALUES);

        if(!$this->create && !$this->force_create && !$this->prune) {
            $output->writeln(sprintf("<error>Spercifiy at least one action : --prune, --create, --force_create</error>"));
            $argsOK = false;
        }

        if($this->dry && $this->force_create) {
            $output->writeln(sprintf("<error>--dry can't be used with --force_create</error>"));
            $argsOK = false;
        }
        if($this->force_create) {
            $this->clear_cache = true;
        }

        return $argsOK;
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        if(!$this->sanitizeArgs($input, $output)) {
            return -1;
        }

        $this->input  = $input;
        $this->output = $output;
        $progress = null;

        if($this->clear_cache) {
            $output->writeln(sprintf("=== clear cache ==="));
            $command = $this->getApplication()->find('system:clear-cache');
            $return_code = $command->run(new ArrayInput([]), $output);
            if($return_code !== 0) {
                $output->writeln("system:clear-cache failed, quit.");
            }
        }

        /**
         * prune
         */
        if($this->prune) {
            $output->writeln(sprintf("=== prune ==="));
            $s = "`permalinks` p LEFT JOIN `subdef` s USING(subdef_id) WHERE ISNULL(s.subdef_id)";
            $sqlDel = "DELETE p FROM " . $s;
            if($this->show_sql) {
                $this->output->writeln($sqlDel);
            }
            $sqlCount = "SELECT COUNT(*) AS n FROM " . $s;
            $stmt = $this->connection->executeQuery($sqlCount);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            $output->writeln(sprintf("%d permalinks to be deleted", $row['n']));

            if($this->dry) {
                $output->writeln(sprintf("dry : not executed"));
            }
            else {
                $stmt = $this->connection->executeQuery($sqlDel);
                $output->writeln(sprintf("%d permalinks deleted", $stmt->rowCount()));
            }
        }

        /*
         * force_create : delete all plinks to force recreate
         */
        if($this->force_create) {
            $output->writeln(sprintf("=== force create ==="));
            $s = "`permalinks` p INNER JOIN `subdef` s USING(subdef_id)";
            if (!empty($this->names)) {
                $n = join(',', array_map(function ($v) {
                    return $this->connection->quote($v);
                }, $this->names));
                $s .=  " WHERE s.name IN(" . $n . ")";
            }

            $sqlDel = "DELETE p FROM " . $s;
            if($this->show_sql) {
                $this->output->writeln($sqlDel);
            }
            $sqlCount = "SELECT COUNT(*) AS n FROM " . $s;
            $stmt = $this->connection->executeQuery($sqlCount);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            $output->writeln(sprintf("%d permalinks to be deleted", $row['n']));

            if($this->dry) {
                $output->writeln(sprintf("dry : not executed"));
            }
            else {
                $stmt = $this->connection->executeQuery($sqlDel);
                $output->writeln(sprintf("%d permalinks deleted", $stmt->rowCount()));
            }
        }

        /**
         * create
         */
        if($this->create || $this->force_create) {
            $output->writeln(sprintf("=== create ==="));
            $s = "subdef s LEFT JOIN permalinks p USING(subdef_id)";
            $w = [];
            if(!$this->force_create) {
                $w[] = "ISNULL(p.subdef_id)";
            }
            if (!empty($this->names)) {
                $n = join(',', array_map(function ($v) {
                    return $this->connection->quote($v);
                }, $this->names));
                $w[] = "s.name IN(" . $n . ")";
            }
            if(($w = join(' AND ', $w)) !== '') {
                $s .= " WHERE " . $w;
            }

            $sqlSelect = "SELECT s.record_id, GROUP_CONCAT(s.subdef_id) AS subdef_ids FROM " . $s . " GROUP BY record_id";
            if ($this->show_sql) {
                $this->output->writeln($sqlSelect);
            }
            $sqlCount = "SELECT COUNT(*) AS n FROM " . $s;
            $stmt = $this->connection->executeQuery($sqlCount);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            $output->writeln(sprintf("%d permalinks to be created", $row['n']));
            if ($this->dry) {
                $output->writeln(sprintf("dry : not executed"));
            }
            else {
                $stmt = $this->connection->executeQuery($sqlSelect);
                $time_start = microtime(true);
                $duration = 0.0;
                $n_created = 0;
                $lmax = 0;  // max length of msg (displayed on same line), useful to clear the line after run
                $app = $this->getContainer();
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $record_id = $row['record_id'];
                    $subdef_ids = $row['subdef_ids'];

                    $record = null;
                    try {
                        $record = $this->databox->get_record($record_id);
                    }
                    catch(\Exception $e) {
                        $output->writeln(sprintf("Failed to get record %s, skip.", $record_id ));
                    }

                    if($record) {
                        try {
                            /*
                             * really this will create the plink if it does not exists...
                             * ... so we can't test existance that way.
                             */
                            //$record->get_subdef($name)->get_permalink();
                            /*
                             * todo : use permalink adapter
                             */
                            $subdef_ids = explode(',', $subdef_ids);
                            $n_created += media_Permalink_Adapter::createFromRecord($app, $record, $subdef_ids);
                        }
                        catch (\Exception $e) {
                            // cant get record ? ignore
                        }
                    }
                    $duration = microtime(true) - $time_start ;
                    if($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                        $lmax = max($lmax, strlen($msg = sprintf("rid: %-6s %4.1f\r", $record_id, $n_created / $duration)));
                        $output->write($msg, $this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE);
                    }

                }
                $stmt->closeCursor();

                $output->write(str_repeat(' ', $lmax) . "\r");    // clear the line
                $duration = max(.000001, $duration);  // avoid division by 0 (anyway n_created is 0 in this case)
                $output->writeln(sprintf("%s permalinks created in %.1f seconds : %.1f p/s", $n_created, $duration, $n_created / $duration ));
            }
        }

        return 0;
    }
}
