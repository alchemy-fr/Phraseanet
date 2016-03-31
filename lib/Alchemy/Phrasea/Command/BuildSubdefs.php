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

use Alchemy\Phrasea\Databox\SubdefGroup;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Media\SubdefGenerator;
use databox_subdef;
use databox_subdefsStructure;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use media_subdef;

class BuildSubdefs extends Command
{
    const OPTION_DISTINT_VALUES = 0;
    const OPTION_ALL_VALUES     = 1;

    /** @var InputInterface */
    private $input;
    /** @var OutputInterface */
    private $output;
    /** @var bool */
    var $argsOK;
    /** @var \Databox */
    var $databox;
    /** @var  connection */
    var $connection;
    var $recmin;
    var $recmax;
    var $substitutedOnly;
    var $withSubstituted;
    var $subdefsNameByType;

    /** @var  int */
    private $partitionIndex;
    /** @var  int */
    private $partitionCount;

    /** @var bool */
    var $dry;

    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->setDescription('Build subviews for given subview names and record types');
        $this->addArgument('databox',               InputArgument::REQUIRED,                                 'The id (or dbname or viewname) of the databox');
        $this->addOption('record_type',       null, InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 'Type(s) of records(s) to (re)build ex. "image,video", dafault=ALL');
        $this->addOption('name',              null, InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 'Name(s) of sub-definition(s) to (re)build, ex. "thumbnail,preview", default=ALL');
        $this->addOption('min_record',        null, InputOption::VALUE_OPTIONAL,                             'Min record id');
        $this->addOption('max_record',        null, InputOption::VALUE_OPTIONAL,                             'Max record id');
        $this->addOption('with_substituted',  null, InputOption::VALUE_NONE,                                 'Regenerate subdefs for substituted records as well');
        $this->addOption('substituted_only',  null, InputOption::VALUE_NONE,                                 'Regenerate subdefs for substituted records only');
        $this->addOption('partition',         null, InputOption::VALUE_REQUIRED,                             'n/N : work only on records belonging to partition \'n\'');
        $this->addOption('dry',               null, InputOption::VALUE_NONE,                                 'dry run, list but don\'t act');
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
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->argsOK = true;

        // find the databox / collection by id or by name
        $this->databox = null;
        $d = trim($input->getArgument('databox'));
        foreach ($this->container->getDataboxes() as $db) {
            if ($db->get_sbas_id() == (int)$d || $db->get_viewname() == $d || $db->get_dbname() == $d) {
                $this->databox = $db;
                $this->connection = $db->get_connection();
                break;
            }
        }
        if ($this->databox == null) {
            $output->writeln(sprintf("<error>Unknown databox \"%s\"</error>", $input->getArgument('databox')));
            $this->argsOK = false;
        }

        // get options
        $this->dry             = $input->getOption('dry') ? true : false;
        $this->recmin          = $input->getOption('min_record');
        $this->recmax          = $input->getOption('max_record');
        $this->substitutedOnly = $input->getOption('substituted_only') ? true : false;
        $this->withSubstituted = $input->getOption('with_substituted') ? true : false;
        if ($this->withSubstituted && $this->substitutedOnly) {
            $output->writeln("<error>--substituted_only and --with_substituted are mutually exclusive<error>");
            $this->argsOK = false;
        }

        // validate types and subdefs
        $this->subdefsNameByType = [];

        if($this->databox !== null) {
            $types = $this->getOptionAsArray($input, 'record_type', self::OPTION_DISTINT_VALUES);
            $names = $this->getOptionAsArray($input, 'name', self::OPTION_DISTINT_VALUES);

            /** @var SubdefGroup $sg */
            foreach ($this->databox->get_subdef_structure() as $sg) {
                if (empty($types) || in_array($sg->getName(), $types)) {
                    $this->subdefsNameByType[$sg->getName()] = [];
                    /** @var databox_subdef $sd */
                    foreach ($sg as $sd) {
                        if (empty($names) || in_array($sd->get_name(), $names)) {
                            $this->subdefsNameByType[$sg->getName()][] = $sd->get_name();
                        }
                    }
                }
            }
            foreach ($types as $t) {
                if (!array_key_exists($t, $this->subdefsNameByType)) {
                    $output->writeln(sprintf("<error>unknown type \"%s\"</error>", $t));
                    $this->argsOK = false;
                }
            }
        }

        // validate partition
        $this->partitionIndex = $this->partitionCount = null;
        if( ($arg = $input->getOption('partition')) !== null) {
            $arg = explode('/', $arg);
            if(count($arg) == 2 && ($arg0 = (int)trim($arg[0]))>0 && ($arg1 = (int)trim($arg[1]))>1 && $arg0<=$arg1 ) {
                $this->partitionIndex = $arg0;
                $this->partitionCount = $arg1;
            }
            else {
                $output->writeln(sprintf('<error>partition must be n/N</error>'));
                $this->argsOK = false;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        if(!$this->argsOK) {
            return -1;
        }
        $this->input  = $input;
        $this->output = $output;

        list($sql, $params, $types) = $this->getSQL();

        $sqlCount = sprintf('SELECT COUNT(*) FROM (%s) AS c', $sql);
        $output->writeln($sqlCount);

        $totalRecords = (int)$this->connection->executeQuery($sqlCount, $params, $types)->fetchColumn();

        if ($totalRecords === 0) {
            return 0;
        }

        $progress = null;
        if($output->getVerbosity() < OutputInterface::VERBOSITY_VERBOSE) {
            $progress = new ProgressBar($output, $totalRecords);
            $progress->start();
            $progress->display();
        }

        $rows = $this->connection->executeQuery($sql, $params, $types)->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $type = $row['type'];
            $output->write(sprintf(' [#%s] (%s)', $row['record_id'], $type));

            try {
                $record = $this->databox->get_record($row['record_id']);

                $subdefNamesToDo = array_flip($this->subdefsNameByType[$type]);    // do all subdefs ?

                /** @var media_subdef $subdef */
                foreach ($record->get_subdefs() as $subdef) {
                    if(!in_array($subdef->get_name(), $this->subdefsNameByType[$type])) {
                        continue;
                    }
                    if($subdef->is_substituted()) {
                        if(!$this->withSubstituted && !$this->substitutedOnly) {
                            unset($subdefNamesToDo[$subdef->get_name()]);
                            continue;
                        }
                    }
                    else {
                        if($this->substitutedOnly) {
                            unset($subdefNamesToDo[$subdef->get_name()]);
                            continue;
                        }
                    }
                    // here an existing subdef must be re-done
                    if(!$this->dry) {
                        $subdef->remove_file();
                        $subdef->set_substituted(false);
                    }
                }

                $subdefNamesToDo = array_keys($subdefNamesToDo);
                if(!empty($subdefNamesToDo)) {
                    if(!$this->dry) {
                        /** @var SubdefGenerator $subdefGenerator */
                        $subdefGenerator = $this->container['subdef.generator'];
                        $subdefGenerator->generateSubdefs($record, $subdefNamesToDo);
                    }

                    $this->verbose(sprintf(" subdefs[%s] done\n", join(',', $subdefNamesToDo)));
                }
                else {
                    $this->verbose(" nothing to do\n");
                }
            }
            catch(\Exception $e) {
                $this->verbose("failed\n");
            }

            if($progress) {
                $progress->advance();
            }
        }

        if($progress) {
            $progress->finish();
            $output->writeln('');
        }

        return 0;
    }

    /**
     * @return array
     */
    protected function getSQL()
    {
        $sql = "SELECT record_id, type FROM record WHERE parent_record_id=0";

        $recordTypes = array_keys($this->subdefsNameByType);
        $types = array_map(function($v) {return $this->connection->quote($v);}, $recordTypes);

        if(!empty($types)) {
            $sql .= ' AND type IN(' . join(',', $types) . ')';
        }
        if ($this->recmin !== null) {
            $sql .= ' AND (record_id >= ' . (int)($this->recmin) . ')';
        }
        if ($this->recmax) {
            $sql .= ' AND (record_id <= ' . (int)($this->recmax) . ')';
        }
        if($this->partitionCount !== null && $this->partitionIndex !== null) {
            $sql .= ' AND MOD(record_id, ' . $this->partitionCount . ')=' . ($this->partitionIndex-1);
        }

        return array($sql, [], []);
    }
}
