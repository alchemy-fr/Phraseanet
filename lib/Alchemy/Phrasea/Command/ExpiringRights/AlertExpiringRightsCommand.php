<?php

/*
 * This file is part of phraseanet-plugins.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\ExpiringRights;


use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Model\Manipulator\WebhookEventManipulator;
use appbox;
use collection;
use databox;
use Doctrine\DBAL\DBALException;
use PDO;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function igorw\get_in;


class AlertExpiringRightsCommand extends Command
{
    const RIGHT_SHORTENED = 'shortened';
    const RIGHT_EXTENDED  = 'extended';
    const RIGHT_EXPIRING  = 'expiring';

    /** @var InputInterface $input */
    private $input;
    /** @var OutputInterface $output */
    private $output;
    /** @var  appbox $appbox */
    private $appbox;
    /** @var array $databoxes */
    private $databoxes;

    private $now = null;

    public function configure()
    {
        $this->setName("workflow:expiring:run")
            ->setDescription('alert owners and users of expiring records')
            ->addOption('dry', null, InputOption::VALUE_NONE, "Dry run (list alerts but don't insert in webhooks).")
            ->addOption('show-sql', null, InputOption::VALUE_NONE, "Show the selection sql.")
            ->addOption('dump-webhooks', null, InputOption::VALUE_NONE, "Show the webhooks data.")
            ->addOption('now', null, InputOption::VALUE_REQUIRED, "for testing : fake the 'today' date (format=YYYYMMDD).")
            // ->setHelp('')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        // add cool styles
        $style = new OutputFormatterStyle('black', 'yellow'); // , array('bold'));
        $output->getFormatter()->setStyle('warning', $style);
        $this->output = $output;

        // sanitize parameters
        if(($now = $input->getOption('now')) !== null) {
            if(preg_match("/^[0-9]{8}$/", $now) === 1) {
                $this->now = $now;
            }
            else {
                $this->output->writeln(sprintf("<error>bad format for 'now' (%s) option (must be YYYYMMDD)</error>", $now));
                return -1;
            }
        }

        $this->appbox = $this->container['phraseanet.appbox'];

        // list databoxes and collections to access by id or by name
        $this->databoxes = [];
        foreach ($this->appbox->get_databoxes() as $databox) {
            $sbas_id = $databox->get_sbas_id();
            $sbas_name = $databox->get_dbname();
            $this->databoxes[$sbas_id] = [
                'dbox' => $databox,
                'collections' => []
            ];
            $this->databoxes[$sbas_name] = &$this->databoxes[$sbas_id];
            // list all collections
            foreach ($databox->get_collections() as $collection) {
                $coll_id = $collection->get_coll_id();
                $coll_name = $collection->get_name();
                $this->databoxes[$sbas_id]['collections'][$coll_id] = $collection;
                $this->databoxes[$sbas_id]['collections'][$coll_name] = &$this->databoxes[$sbas_id]['collections'][$coll_id];
            }
        }

        // play jobs
        $ret = 0;
        foreach ($this->container['conf']->get(['expiring-rights', 'jobs'], []) as $jobname => &$job) {
            if($job['active']) {
                if (!$this->playJob($jobname, $job)) {
                    $this->output->writeln(sprintf("<error>job skipped</error>"));
                    $ret = -1;
                }
            }
        }

        return $ret;
    }

    /**
     * @param $jobname
     * @param $job
     * @return bool
     * @throws DBALException
     */
    private function playJob($jobname, $job)
    {
        $this->output->writeln(sprintf("\n\n<info>======== Playing job \"%s\" ========</info>\n", $jobname));

        // ensure that the job syntax is ok
        if (!$this->sanitizeJob($job)) {
            return false;
        }

        switch ($job['target']) {
            case "owners":
                return $this->playJobOwners($jobname, $job);
                break;
            case "downloaders":
                return $this->playJobDownloaders($jobname, $job);
                break;
            default:
                $this->output->writeln(sprintf("alert>bad target \"%s\" (should be \"owners\" or \"downloaders\")</alert>\n", $job['target']));
                break;
        }
        return false;
    }

    private function playJobOwners($jobname, $job)
    {
        // ensure that the job syntax is ok
        if (!$this->sanitizeJobOwners($job)) {
            return false;
        }

        if (get_in($job, ['active'], false) === false) {
            return true;
        }

        // build sql where clause
        $wheres = [];

        // clause on databox ?
        $d = $job['databox'];
        if (!is_string($d) && !is_int($d)) {
            $this->output->writeln(sprintf("<error>bad databox clause</error>"));
            return false;
        }
        if (!array_key_exists($d, $this->databoxes)) {
            $this->output->writeln(sprintf("<error>unknown databox (%s)</error>", $d));
            return false;
        }

        // find the sbas_id for the databox of this job
        /** @var Databox $dbox */
        $dbox = $this->databoxes[$d]['dbox'];
        $sbas_id = $dbox->get_sbas_id();

        // filter on collections ?
        $collList = [];
        foreach (get_in($job, ['collection'], []) as $c) {
            /** @var collection $coll */
            if (($coll = get_in($this->databoxes[$sbas_id], ['collections', $c])) !== null) {
                $collList[] = $dbox->get_connection()->quote($coll->get_coll_id());
            }
        }
        if (!empty($collList)) {
            if(count($collList) === 1) {
                $wheres[] = "r.`coll_id`=" . $collList[0];
            }
            else {
                $wheres[] = "r.`coll_id` IN(" . join(',', $collList) . ")";
            }
        }

        // filter on sb
        $mask = get_in($job, ['status']);
        if ($mask !== null) {
            $m = preg_replace('/[^0-1]/', 'x', trim($mask));
            if (strlen($m) > 32) {
                $this->output->writeln(sprintf("<error>status mask (%s) too long</error>", $mask));

                return false;
            }
            $mask_xor = str_replace(' ', '0', ltrim(str_replace(['0', 'x'], [' ', ' '], $m)));
            $mask_and = str_replace(' ', '0', ltrim(str_replace(['x', '0'], [' ', '1'], $m)));
            if ($mask_xor && $mask_and) {
                $wheres[] = '((r.`status` ^ 0b' . $mask_xor . ') & 0b' . $mask_and . ') = 0';
            }
            elseif ($mask_xor) {
                $wheres[] = '(r.`status` ^ 0b' . $mask_xor . ') = 0';
            }
            elseif ($mask_and) {
                $wheres[] = '(r.`status` & 0b' . $mask_and . ') = 0';
            }
        }

        // clause on sb (negated)
        $mask = get_in($job, ['set_status']);
        if ($mask === null) {
            $this->output->writeln(sprintf("<error>missing 'set_status' clause</error>"));
            return false;
        }
        $m = preg_replace('/[^0-1]/', 'x', trim($mask));
        if (strlen($m) > 32) {
            $this->output->writeln(sprintf("<error>set_status mask (%s) too long</error>", $mask));
            return false;
        }
        $mask_xor = str_replace(' ', '0', ltrim(str_replace(array('0', 'x'), array(' ', ' '), $m)));
        $mask_and = str_replace(' ', '0', ltrim(str_replace(array('x', '0'), array(' ', '1'), $m)));
        if ($mask_xor && $mask_and) {
            $wheres[] = '((r.`status` ^ 0b' . $mask_xor . ') & 0b' . $mask_and . ') != 0';
        } elseif ($mask_xor) {
            $wheres[] = '(r.`status` ^ 0b' . $mask_xor . ') != 0';
        } elseif ($mask_and) {
            $wheres[] = '(r.`status` & 0b' . $mask_and . ') != 0';
        } else {
            $this->output->writeln(sprintf("<error>empty status mask</error>"));
            return false;
        }
        // set status
        $set_status = "`status`";
        $set_or = str_replace(' ', '0', ltrim(str_replace(array('0', 'x'), array(' ', ' '), $m)));
        $set_nand = str_replace(' ', '0', ltrim(str_replace(array('x', '1', '0'), array(' ', ' ', '1'), $m)));
        if($set_or) {
            $set_status = "(" . $set_status . " | 0b" . $set_or . ")";
        }
        if($set_nand) {
            $set_status = "(" . $set_status . " & ~0b" . $set_nand . ")";
        }

        // clause on expiration date
        // the NOW() can be faked for testing
        $expire_field_id = null;
        foreach ($dbox->get_meta_structure() as $dbf) {
            if ($dbf->get_name() === $job['expire_field']) {
                $expire_field_id = $dbf->get_id();
                break;
            }
        }
        if ($expire_field_id === null) {
            $this->output->writeln(sprintf("<error>unknown field (%s)</error>", $job['expire_field']));
            return false;
        }
        $now = $this->now === null ? "NOW()" : $this->appbox->get_connection()->quote($this->now);
        $delta = (int)$job['prior_notice'];
        if ($delta > 0) {
            $value = "(`expire`+INTERVAL " . $delta . " DAY)";
        }
        elseif ($delta < 0) {
            $value = "(`expire`-INTERVAL " . -$delta . " DAY)";
        }
        else {
            $value = "`expire`";
        }

        $sql_where = count($wheres) > 0 ? "   WHERE " . join(" AND ", $wheres) : "";
        $sql = "SELECT t.*, DATEDIFF(`expire`, " . $now . ") AS 'expire_in' FROM (\n"
            . "    SELECT r.`record_id`, CAST(CAST(m.`value` AS DATE) AS DATETIME) AS `expire`\n"
            . "    FROM `record` AS r INNER JOIN `metadatas` AS m\n"
            . "    ON m.`record_id`=r.`record_id` AND m.`meta_struct_id`=" . $dbox->get_connection()->quote($expire_field_id, PDO::PARAM_INT) . "\n"
            . $sql_where
            . ") AS t WHERE " . $now . ">=" . $value;

        if ($this->input->getOption('show-sql')) {
            $this->output->writeln(sprintf("sql: %s", $sql));
        }

        // play sql
        $records = [];
        $stmt = $dbox->get_connection()->prepare($sql);
        $stmt->execute();

        if ($this->input->getOption('dry')) {
            $this->output->writeln(sprintf("<info>dry mode: updates on %d records NOT executed</info>", $stmt->rowCount()));
        }

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $record = $dbox->get_record($row['record_id']);
            $row['collection'] = $record->getCollection()->get_name();
            $row['title'] = $record->get_title();
            $records[] = $row;

            $sql = "UPDATE `record` SET `status`=" . $set_status . " WHERE record_id=" . $dbox->get_connection()->quote($row['record_id']);
            if ($this->input->getOption('show-sql')) {
                $this->output->writeln(sprintf("sql: %s", $sql));
            }
            if (!$this->input->getOption('dry')) {
                $dbox->get_connection()->exec($sql);
            }
        }
        $stmt->closeCursor();

        $n_records = count($records);
        if($n_records === 0) {
            return true;
        }
        foreach ($job['alerts'] as $alert) {
            $method = get_in($alert, ['method']);
            switch($method) {
                case "webhook":
                    $payload = [
                        'job'     => $jobname,
                        'delta'   => $delta,
                        'email'   => $alert['recipient'],
                        'sbas_id' => $dbox->get_sbas_id(),
                        'base'    => $dbox->get_viewname(),
                        'records' => $records
                    ];
                    if ($this->input->getOption('dry')) {
                        $this->output->writeln(
                            sprintf(
                                "dry run : webhook about %d record(s) to [%s] NOT inserted",
                                $n_records, join(',', $alert['recipient'])
                            )
                        );
                    }
                    else {
                        $this->output->writeln(
                            sprintf(
                                "Inserting webhook about %d record(s) to [%s]",
                                $n_records, join(',', $alert['recipient'])
                            )
                        );
                        /** @var WebhookEventManipulator $manipulator */
                        $webhookManipulator = $this->container['manipulator.webhook-event'];
                        $webhookManipulator->create(
                            "Expiring.Rights.Records",
                            "Expiring.Rights",
                            $payload
                        );
                    }
                    if($this->input->getOption("dump-webhooks")) {
                        $this->output->writeln("webhook: \"Expiring.Rights.Records\", \"Expiring.Rights\"\npayload=");
                        $this->output->writeln(json_encode($payload, JSON_PRETTY_PRINT));
                    }
                    break;
                default:
                    $this->output->writeln(sprintf("<warning>bad or undefined alert method (%s), ignored</warning>", $method));
                    break;
            }
        }

        return true;
    }

    private function playJobDownloaders($jobname, $job)
    {
        // ensure that the job syntax is ok
        if (!$this->sanitizeJobDownloaders($job)) {
            return false;
        }

        if (get_in($job, ['active'], false) === false) {
            return true;
        }

        // build sql where clause
        $wheres = [
            '`job` = ' . $this->appbox->get_connection()->quote($jobname)
        ];

        // clause on databox
        $databox = $job['databox'];
        if(!is_string($databox) && !is_int($databox)) {
            $this->output->writeln(sprintf("<error>bad databox clause</error>"));
            return false;
        }
        if (!array_key_exists($databox, $this->databoxes)) {
            $this->output->writeln(sprintf("<error>unknown databox (%s)</error>", $job['databox']));
            return false;
        }
        // find the sbas_id for the databox of this job
        /** @var Databox $dbox */
        $dbox = $this->databoxes[$databox]['dbox'];
        $sbas_id = $dbox->get_sbas_id();
        $wheres[] = "(`sbas_id`=" . $dbox->get_connection()->quote($sbas_id) . ")";

        $wheres[] = "(ISNULL(`alerted`) OR !ISNULL(`new_expire`))";

        // clause on expiration date
        // the NOW() can be faked for testing
        $now = $this->now === null ? "NOW()" : $this->appbox->get_connection()->quote($this->now);
        $delta = (int)$job['prior_notice'];
        if ($delta > 0) {
            $value = "(real_expire+INTERVAL " . $delta . " DAY)";
        } elseif ($delta < 0) {
            $value = "(real_expire-INTERVAL " . -$delta . " DAY)";
        } else {
            $value = "real_expire";
        }

        // build SELECT sql
        $sql_where = join("\n        AND", $wheres);
        $sql = "SELECT t.*, DATEDIFF(real_expire, " . $now . ") AS 'expire_in' FROM (\n"
            . "    SELECT *, COALESCE(`expire`, `new_expire`) AS `real_expire` FROM `ExpiringRights`\n"
            . "    WHERE " . $sql_where . "\n"
            . ") AS t\n"
            . "WHERE !ISNULL(real_expire) AND " . $now . ">=" . $value . "\n"
            . "ORDER BY `user_id`";

        if($this->input->getOption('show-sql')) {
            $this->output->writeln(sprintf("%s", $sql));
        }

        // play sql
        $usersById = [];

        $n_records = [
            "all" => 0,
            self::RIGHT_EXPIRING => 0,
            self::RIGHT_EXTENDED => 0,
            self::RIGHT_SHORTENED => 0
        ];
        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute();
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $type = null;
            if($row['new_expire'] !== null) {
                if($row['expire'] === null || $row['alerted'] === null) {
                    // same thing as new expire
                    $type = self::RIGHT_EXPIRING;
                }
                elseif($row['new_expire'] < $row['expire']) {
                    // shortened
                    $type = self::RIGHT_SHORTENED;
                }
                elseif($row['new_expire'] > $row['expire']) {
                    // extended
                    $type = self::RIGHT_EXTENDED;
                }
                else {
                    // same date (should not happen ?)
                    $type = self::RIGHT_EXPIRING;
                }
            }
            else {
                // new_expire === null
                if($row['alerted'] === null) {
                    $type = self::RIGHT_EXPIRING;
                }
                else {
                    // nothing to do (should not happen)
                }
            }

            if($type !== null) {
                $user_id = $row['user_id'];
                if(!array_key_exists($user_id, $usersById)) {
                    $usersById[$user_id] = [
                        'user_id' => $user_id,
                        'email' => $row['email'],
                        'records' => []
                    ];
                }
                unset($row['id'], $row['job'], $row['user_id'], $row['email'], $row['sbas_id'], $row['base']);
                $row['type'] = $type;
                $usersById[$user_id]['records'][] = $row;

                $n_records[$type]++;
            }
            $n_records['all']++;
        }
        $stmt->closeCursor();

        $payload = [
            'job'     => $jobname,
            'sbas_id' => $sbas_id,
            'base'    => $dbox->get_viewname(),
            'delta'   => $delta,
            'users'   => array_values($usersById)
        ];
        unset($usersById);

        if($n_records['all'] > 0 && !$this->input->getOption('dry')) {
            // build UPDATE sql
            $sql = "UPDATE `ExpiringRights` SET expire=COALESCE(new_expire, expire), new_expire=NULL, alerted=" . $now . "\n"
                . " WHERE " . $sql_where;
            $stmt = $this->appbox->get_connection()->prepare($sql);
            $stmt->execute([]);
            $this->appbox->get_connection()->exec($sql);
            $stmt->closeCursor();
        }

        $this->output->writeln(
            sprintf(
                "%d records selected (%s expiring, %s shortened, %s extended)",
                $n_records['all'],
                $n_records[self::RIGHT_EXPIRING],
                $n_records[self::RIGHT_SHORTENED],
                $n_records[self::RIGHT_EXTENDED]
            )
        );

        if ($n_records['all'] > 0 ) {
            foreach ($job['alerts'] as $alert) {
                switch ($alert['method']) {
                    case 'webhook':
                        if ($this->input->getOption('dry')) {
                            $this->output->writeln(
                                sprintf(
                                    "<info>dry run : webhook about %d record(s) NOT inserted</info>",
                                    $n_records['all']
                                )
                            );
                        }
                        else {
                            $this->output->writeln(
                                sprintf(
                                    "<info>webhook about %d record(s) inserted</info>",
                                    $n_records['all']
                                )
                            );
                            /** @var WebhookEventManipulator $manipulator */
                            $webhookManipulator = $this->container['manipulator.webhook-event'];
                            $webhookManipulator->create(
                                "Expiring.Rights.Downloaded",
                                "Expiring.Rights",
                                $payload
                            );
                        }
                        if($this->input->getOption("dump-webhooks")) {
                            $this->output->writeln("webhook: \"Expiring.Rights.Downloaded\", \"Expiring.Rights\"\npayload=");
                            $this->output->writeln(json_encode($payload, JSON_PRETTY_PRINT));
                        }
                        break;
                    default :
                        $this->output->writeln(sprintf("<error>unknown alert method \"%s\"</error>", $alert['method']));
                        break;
                }
            }
        }

        return true;
    }

    // ================================================================================================

    /**
     * check that a yaml->php block is ok against rules
     *
     * @param array $object
     * @param array $rules
     * @return bool
     */
    private function sanitize(array $object, array $rules)
    {
        $object_ok = true;

        foreach ($rules as $key => $fsanitize) {
            if (!array_key_exists($key, $object) || !($fsanitize($object[$key]))) {
                $this->output->writeln(sprintf("<error>missing or bad format setting \"%s\"</error>", $key));
                $object_ok = false;
            }
        }

        return $object_ok;
    }

    /**
     * check that a job (first level block) is ok
     *
     * @param $job
     * @return bool
     */
    private function sanitizeJob($job)
    {
        return $this->sanitize(
            $job,
            [
                'active' => "is_bool",
                'target' => function($v) {return in_array($v, ['owners', 'downloaders']);},
                'databox' => "is_string",
                'prior_notice' => 'is_int',
                'expire_field' => 'is_string',
                'alerts' => 'is_array'
            ]
        );
    }

    private function sanitizeJobOwners($job)
    {
        return $this->sanitize(
            $job,
            [
                'set_status' => "is_string",
            ]
        );
    }

    private function sanitizeJobDownloaders($job)
    {
        return true;    // sanitizeJob is enough
    }

}
