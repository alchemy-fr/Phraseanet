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
use databox_subdef;
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
    private $argsOK;
    /** @var \Databox */
    private $databox;
    /** @var  connection */
    private $connection;
    /** @var array */
    private $presets;

    /** @var string */
    private $mode;
    /** @var int */
    private $min_record_id;
    /** @var int */
    private $max_record_id;
    /** @var bool */
    private $substituted_only;
    /** @var bool */
    private $with_substituted;
    /** @var bool */
    private $missing_only;
    /** @var bool */
    private $prune;
    /** @var bool */
    private $all;
    /** @var bool */
    private $scheduled;
    /** @var bool */
    private $reverse;
    /** @var  int */
    private $partitionIndex;
    /** @var  int */
    private $partitionCount;
    /** @var bool */
    private $reset_subdef_flag;
    /** @var bool */
    private $set_writemeta_flag;
    /** @var integer */
    private $ttl;
    /** @var integer */
    private $maxrecs;
    /** @var integer */
    private $maxduration;
    /** @var bool */
    private $dry;
    /** @var bool */
    private $show_sql;

    /** @var  array */
    private $subdefsByType;

    /** @var  array */
    private $subdefsTodoByType;

    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->presets = [
            'scheduled' => [
                'scheduled' => true,
                'reset_subdef_flag' => true,
                'set_writemeta_flag' => true,
                'ttl' => 10
            ],
            'repair'    => [
                'missing_only' => true,
                'prune' => true,
                'reset_subdef_flag' => true,
                'set_writemeta_flag' => true
            ],
            'all'       => [
                'all' => true,
                'reset_subdef_flag' => true,
                'set_writemeta_flag' => true
            ]
        ];

        $this->setDescription('Build subviews');
        $this->addOption('databox',            null, InputOption::VALUE_REQUIRED,                             'Mandatory : The id (or dbname or viewname) of the databox');
        $this->addOption('mode',               null, InputOption::VALUE_REQUIRED,                             'preset working mode : ' . implode('|', array_keys($this->presets)));
        $this->addOption('record_type',        null, InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 'Type(s) of records(s) to (re)build ex. "image,video", dafault=ALL');
        $this->addOption('min_record_id',      null, InputOption::VALUE_OPTIONAL,                             'Min record id');
        $this->addOption('max_record_id',      null, InputOption::VALUE_OPTIONAL,                             'Max record id');
        $this->addOption('partition',          null, InputOption::VALUE_REQUIRED,                             'n/N : work only on records belonging to partition \'n\'');
        $this->addOption('reverse',            null, InputOption::VALUE_NONE,                                 'Build records from the last to the oldest');
        $this->addOption('name',               null, InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 'Name(s) of sub-definition(s) to (re)build, ex. "thumbnail,preview", default=ALL');
        $this->addOption('all',                null, InputOption::VALUE_NONE,                                 'Enforce listing of all records');
        $this->addOption('scheduled',          null, InputOption::VALUE_NONE,                                 'Only records flagged with \"jeton\" subdef');
        $this->addOption('with_substituted',   null, InputOption::VALUE_NONE,                                 'Regenerate subdefs for substituted records as well');
        $this->addOption('substituted_only',   null, InputOption::VALUE_NONE,                                 'Regenerate subdefs for substituted records only');
        $this->addOption('missing_only',       null, InputOption::VALUE_NONE,                                 'Regenerate only missing subdefs');
        $this->addOption('prune',              null, InputOption::VALUE_NONE,                                 'Delete subdefs not in structure anymore');
        $this->addOption('reset_subdef_flag',  null, InputOption::VALUE_NONE,                                 'Reset "make-subdef" flag (should only be used when working on all subdefs, that is NO --name filter)');
        $this->addOption('set_writemeta_flag', null, InputOption::VALUE_NONE,                                 'Set "write-metadata" flag (should only be used when working on all subdefs, that is NO --name filter)');
        $this->addOption('maxrecs',            null, InputOption::VALUE_REQUIRED,                             'Maximum count of records to do.');
        $this->addOption('maxduration',        null, InputOption::VALUE_REQUIRED,                             'Maximum duration (seconds) of job. (job will do at least one record)');
        $this->addOption('ttl',                null, InputOption::VALUE_REQUIRED,                             'Wait time (seconds) before quit if no records were changed');
        $this->addOption('dry',                null, InputOption::VALUE_NONE,                                 'dry run, list but don\'t act');
        $this->addOption('show_sql',           null, InputOption::VALUE_NONE,                                 'show sql pre-selecting records');

        $this->setHelp(""
            . "Record filters :\n"
            . " --record_type=image,video : Select records of those types ('image','video','audio','document','flash').\n"
            . " --min_record_id=100       : Select records with record_id >= 100.\n"
            . " --max_record_id=500       : Select records with record_id <= 500.\n"
            . " --partition=2/5           : Split databox records in 5 buckets, select records in bucket #2.\n"
            . " --scheduled               : Select records flagged as \"make subdef\".\n"
            . " --missing_only            : Select only records with a missing and/or unknown subdef name.\n"
            . " --all                     : Select all records.\n"
            . "Subdef filters :\n"
            . " --name=thumbnail,preview  : (re)build only thumbnail and preview.\n"
            . " --with_substituted        : (re)build substituted subdefs also (normally ignored).\n"
            . " --substituted_only        : rebuild substituted subdefs from document (= remove substitution).\n"
            . "Actions :\n"
            . " --prune                   : remove unknown subdefs.\n"
            . " --reset_subdef_flag       : reset the \"make subdef\" scheduling flag (= mark record as done).\n"
            . " --set_writemeta_flag      : raise the \"write meta\" flag (= mark subdefs as missing metadata).\n"
            . "Job limits :\n"
            . " --maxrecs=100             : quit anyway after 100 records are done.\n"
            . " --maxduration=3600        : quit anyway after 1 hour (at least one record is done).\n"
            . " --ttl=10                  : if nothing was done, wait 10 seconds (sleep) before quit.\n"
            . "Preset modes :\n"
            . " --mode=scheduled : Create subdefs for records flagged \"make subdef\", same as \"subview creation\" task.\n"
            . "                    (= --scheduled --reset_subdef_flag --set_writemeta_flag --ttl=10)\n"
            . " --mode=repair    : Create only missing subdefs, prune obsolete subdefs.\n"
            . "                    (= --missing_only --prune --reset_subdef_flag --set_writemeta_flag)\n"
            . " --mode=all       : Re-creates all subdefs.\n"
            . "                    (= --all --reset_subdef_flag --set_writemeta_flag\n"
        );

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

        // get options
        $this->mode = $input->getOption('mode');
        if($this->mode) {
            if(!in_array($this->mode, array_keys($this->presets))) {
                $output->writeln(sprintf("<error>invalid --mode \"%s\"<error>", $this->mode));
                $argsOK = false;
            }
            else {
                $explain = "";
                foreach($this->presets[$this->mode] as $k=>$v) {
                    if($input->getOption($k) !== false && $input->getOption($k) !== null) {
                        $output->writeln(sprintf("<error>--mode=%s and --%s are mutually exclusive</error>", $this->mode, $k));
                        $argsOK = false;
                    }
                    else {
                        $input->setOption($k, $v);
                    }
                    $explain .= ' --' . $k . ($v===true ? '' : ('='.$v));
                }

                $output->writeln(sprintf("mode \"%s\" ==>%s", $this->mode, $explain));
            }
        }

        $this->show_sql           = $input->getOption('show_sql') ? true : false;
        $this->dry                = $input->getOption('dry') ? true : false;
        $this->min_record_id      = $input->getOption('min_record_id');
        $this->max_record_id      = $input->getOption('max_record_id');
        $this->substituted_only   = $input->getOption('substituted_only') ? true : false;
        $this->with_substituted   = $input->getOption('with_substituted') ? true : false;
        $this->missing_only       = $input->getOption('missing_only') ? true : false;
        $this->prune              = $input->getOption('prune') ? true : false;
        $this->all                = $input->getOption('all') ? true : false;
        $this->scheduled          = $input->getOption('scheduled') ? true : false;
        $this->reverse            = $input->getOption('reverse') ? true : false;
        $this->reset_subdef_flag  = $input->getOption('reset_subdef_flag') ? true : false;
        $this->set_writemeta_flag = $input->getOption('set_writemeta_flag') ? true : false;
        $this->maxrecs            = (int)$input->getOption('maxrecs');
        $this->maxduration        = (int)$input->getOption('maxduration');
        $this->ttl                = (int)$input->getOption('ttl');

        $types = $this->getOptionAsArray($input, 'record_type', self::OPTION_DISTINT_VALUES);
        $names = $this->getOptionAsArray($input, 'name', self::OPTION_DISTINT_VALUES);

        if ($this->with_substituted && $this->substituted_only) {
            $output->writeln("<error>--substituted_only and --with_substituted are mutually exclusive<error>");
            $argsOK = false;
        }
        if($this->prune && !empty($names)) {
            $output->writeln("<error>--prune and --name are mutually exclusive<error>");
            $argsOK = false;
        }

        $n = ($this->scheduled?1:0) + ($this->missing_only?1:0) + ($this->all?1:0);
        if($n != 1) {
            $output->writeln("<error>set one an only one option --scheduled, --missing_only, --all<error>");
            $argsOK = false;
        }

        // validate types and subdefs
        $this->subdefsTodoByType = [];
        $this->subdefsByType = [];

        if($this->databox !== null) {

            /** @var SubdefGroup $sg */
            foreach ($this->databox->get_subdef_structure() as $sg) {
                if (empty($types) || in_array($sg->getName(), $types)) {
                    $all = [];
                    $todo = [];
                    /** @var databox_subdef $sd */
                    foreach ($sg as $sd) {
                        $all[] = $sd->get_name();
                        if (empty($names) || in_array($sd->get_name(), $names)) {
                            $todo[] = $sd->get_name();
                        }
                    }
                    asort($all);
                    $this->subdefsByType[$sg->getName()] = $all;
                    asort($todo);
                    $this->subdefsTodoByType[$sg->getName()] = $todo;
                }
            }
            foreach ($types as $t) {
                if (!array_key_exists($t, $this->subdefsTodoByType)) {
                    $output->writeln(sprintf("<error>unknown type \"%s\"</error>", $t));
                    $argsOK = false;
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
                $argsOK = false;
            }
        }

        // warning about changing jeton when not working on all subdefs
        if(!empty($names) && ($this->reset_subdef_flag || $this->set_writemeta_flag)) {
            $output->writeln("<warning>changing record flag(s) but working on a subset of subdefs</warning>");
        }
        
        return $argsOK;
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $time_start = new \DateTime();

        if(!$this->sanitizeArgs($input, $output)) {
            return -1;
        }

        $this->input  = $input;
        $this->output = $output;
        $progress = null;

        $sql = $this->getSQL();

        if($this->show_sql) {
            $this->output->writeln($sql);
        }

        $sqlCount = sprintf('SELECT COUNT(*) FROM (%s) AS c', $sql);

        $totalRecords = (int)$this->connection->executeQuery($sqlCount)->fetchColumn();

        $nRecordsDone = 0;

        $again = true;

        $stmt = $this->connection->executeQuery($sql);
        while($again && ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) ) {

            if($output->getVerbosity() < OutputInterface::VERBOSITY_VERBOSE && $progress === null) {
                $progress = new ProgressBar($output, $totalRecords);
                $progress->start();
                $progress->display();
            }

            $recordChanged = false;
            $type = $row['type'];
            $msg = [];

            $msg[] = sprintf(' record %s (%s) :', $row['record_id'], $type);

            try {
                $record = $this->databox->get_record($row['record_id']);

                $subdefNamesToDo = array_flip($this->subdefsTodoByType[$type]);    // do all subdefs ?

                /** @var media_subdef $subdef */
                $subdefsDeleted = [];
                foreach ($record->get_subdefs() as $subdef) {
                    $name = $subdef->get_name();
                    if($name == "document") {
                        continue;
                    }
                    if(!in_array($name, $this->subdefsByType[$type])) {
                        // this existing subdef is unknown in structure
                        if($this->prune) {
                            if(!$this->dry) {
                                $subdef->delete();
                            }
                            $recordChanged = true;
                            $subdefsDeleted[] = $name;
                            $msg[] = sprintf(" \"%s\" pruned", $name);
                        }
                        continue;
                    }
                    if($this->missing_only) {
                        unset($subdefNamesToDo[$name]);
                        continue;
                    }
                    if($subdef->is_substituted()) {
                        if(!$this->with_substituted && !$this->substituted_only) {
                            unset($subdefNamesToDo[$name]);
                            continue;
                        }
                    }
                    else {
                        if($this->substituted_only) {
                            unset($subdefNamesToDo[$name]);
                            continue;
                        }
                    }

                    // here an existing subdef must be (re)done
                    if(isset($subdefNamesToDo[$name])) {
                        if (!$this->dry) {
                            $subdef->remove_file();
                            $subdef->set_substituted(false);
                        }
                        $recordChanged = true;
                        $msg[] = sprintf(" [\"%s\"] deleted", $name);
                    }
                }

                $subdefNamesToDo = array_keys($subdefNamesToDo);
                if(!empty($subdefNamesToDo)) {
                    if(!$this->dry) {
                        /** @var SubdefGenerator $subdefGenerator */
                        $subdefGenerator = $this->container['subdef.generator'];
                        $subdefGenerator->generateSubdefs($record, $subdefNamesToDo);
                    }
                    $recordChanged = true;
                    $msg[] = sprintf(" [\"%s\"] built", implode('","', $subdefNamesToDo));
                }
                else {
                    // $msg .= " nothing to build";
                }

                unset($record);

                if($this->reset_subdef_flag || $this->set_writemeta_flag) {
                    // subdef created, ask to rewrite metadata
                    $sql = 'UPDATE record'
                        . ' SET jeton=(jeton & ~(:flag_and)) | :flag_or'
                        . ' WHERE record_id=:record_id';

                    if($this->reset_subdef_flag) {
                        $msg[] = "jeton[\"make_subdef\"]=0";
                    }
                    if($this->set_writemeta_flag) {
                        $msg[] = "jeton[\"write_met_subdef\"]=1";
                    }
                    if(!$this->dry) {
                        $this->connection->executeUpdate($sql, [
                            ':record_id' => $row['record_id'],
                            ':flag_and' => ($this->reset_subdef_flag ? PhraseaTokens::MAKE_SUBDEF : 0),
                            ':flag_or' => ($this->set_writemeta_flag ? PhraseaTokens::WRITE_META_SUBDEF : 0)
                        ]);
                    }
                    $recordChanged = true;
                }

                if($recordChanged) {
                    $nRecordsDone++;
                }
            }
            catch(\Exception $e) {
                $output->write("failed\n");
            }

            if($progress) {
                $progress->advance();
                //$output->write(implode(' ', $msg));
            }
            else {
                $output->writeln(implode("\n", $msg));
            }

            if($this->maxrecs > 0 && $nRecordsDone >= $this->maxrecs) {
                if($progress) {
                    $output->writeln('');
                }
                $output->writeln(sprintf("Maximum number (%d >= %d) of records done, quit.", $nRecordsDone, $this->maxrecs));
                $again = false;
            }

            $time_end = new \DateTime();
            $dt = $time_end->getTimestamp() - $time_start->getTimestamp();
            if($this->maxduration > 0 && $dt >= $this->maxduration && $nRecordsDone > 0) {
                if($progress) {
                    $output->writeln('');
                }
                $output->writeln(sprintf("Maximum duration (%d >= %d) done, quit.", $dt, $this->maxduration));
                $again = false;
            }

        }

        unset($stmt);

        if($progress) {
            $output->writeln('');
        }

        if($nRecordsDone == 0) {
            while($this->ttl > 0) {
                sleep(1);
                $this->ttl--;
            }
        }

        return 0;
    }

    /**
     * @return string
     */
    protected function getSQL()
    {
        $sql = "SELECT r.`record_id`, r.`type`, GROUP_CONCAT(s.`name` ORDER BY `name`) AS `exists`,\n"
            . " CASE r.`type`\n";

        foreach($this->subdefsByType as $type=>$names) {
            $sql .= "  WHEN " . $this->connection->quote($type) . " THEN " . $this->connection->quote(join(',', $names)) . "\n";
        }

        $sql .= "  END\n"
            . " AS `waited`\n"
            . "FROM `record` AS r LEFT JOIN `subdef` AS s ON((s.`record_id` = r.`record_id`) AND s.`name` != 'document')\n"
            . "WHERE r.`parent_record_id`=0\n";

        $recordTypes = array_keys($this->subdefsTodoByType);
        $types = array_map(function($v) {return $this->connection->quote($v);}, $recordTypes);

        if(!empty($types)) {
            $sql .= " AND r.`type` IN(" . implode(',', $types) . ")\n";
        }
        if ($this->min_record_id !== null) {
            $sql .= " AND (r.`record_id` >= " . (int)($this->min_record_id) . ")\n";
        }
        if ($this->max_record_id) {
            $sql .= " AND (r.`record_id` <= " . (int)($this->max_record_id) . ")\n";
        }
        if($this->partitionCount !== null && $this->partitionIndex !== null) {
            $sql .= " AND MOD(r.`record_id`, " . $this->partitionCount . ")=" . ($this->partitionIndex-1) . "\n";
        }
        if($this->scheduled) {
            $sql .= " AND r.`jeton` & " . PhraseaTokens::MAKE_SUBDEF . "\n";
        }

        $sql .= "GROUP BY r.`record_id`";

        if(!$this->scheduled && !$this->all) {
            $sql .= "\nHAVING `exists` != `waited`";
        }

        $sql .= "\nORDER BY r.`record_id` " . ($this->reverse ? "DESC" : "ASC");

        return $sql;
    }
}
