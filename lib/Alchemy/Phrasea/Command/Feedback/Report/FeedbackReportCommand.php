<?php

namespace Alchemy\Phrasea\Command\Feedback\Report;


use Alchemy\Phrasea\Command\Command as phrCommand;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Model\Repositories\UserRepository;
use appbox;
use Doctrine\DBAL\DBALException;
use Exception;
use PDO;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class FeedbackReportCommand extends phrCommand
{
    /** @var InputInterface $input */
    private $input;
    /** @var OutputInterface $output */
    private $output;

    /** @var GlobalConfiguration */
    private $config;

    public function configure()
    {
        $this->setName('feedback:report')
            ->setDescription('Report ended feedback results (votes) on records (set status-bits)')
            ->addOption('report',    null,  InputOption::VALUE_REQUIRED, "Report output format (all|condensed)", "all")
            ->addOption('min_date',    null,  InputOption::VALUE_REQUIRED, "Run only for feedbacks expired from this date (yyyy-mm-dd)", null)
            ->addOption('dry',    null,  InputOption::VALUE_NONE, "list records but don't apply.", null)
            ->setHelp("")
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws DBALException
     * @throws \Throwable
     */
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        // add cool styles
        $style = new OutputFormatterStyle('black', 'yellow'); // , array('bold'));
        $output->getFormatter()->setStyle('warning', $style);

        $this->input = $input;
        $this->output = $output;

        // config must be ok
        //
        try {
            $this->config = new GlobalConfiguration(
                $this->getConf(),
                $this->container['twig'],
                $this->container['phraseanet.appbox'],
                $input->getOption('dry'),
                $input->getOption('report')
            );
        }
        catch(Exception $e) {
            $output->writeln(sprintf("<error>missing or bad configuration: %s</error>", $e->getMessage()));

            return -1;
        }

        if(!$this->config->isEnabled()) {
            $output->writeln(sprintf("<info>configuration is not enabled</info>"));

            return 0;
        }

        $min_date_filter = '';
        if( ($min_date = $input->getOption('min_date')) !== null) {
            $matches = [];
            if(preg_match('/(\d\d\d\d)\D(\d\d)\D(\d\d)/', $min_date, $matches) !== 1) {
                $output->writeln(sprintf("<error>bad format for --min_date</error>"));

                return -1;
            }

            $min_date_filter = ' AND b.`created` >= \'' . $matches[1] . '-' . $matches[2] . '-' . $matches[3] . '\'';
        }

        $appbox = $this->getAppBox();

        $sql_update = "UPDATE `BasketElements` SET `vote_expired` = :expired WHERE `id` = :id";
        $stmt_update = $appbox->get_connection()->prepare($sql_update);

        $sql_select = "SELECT q1.*, 
       b.name, b.`vote_created` AS `created`, b.`vote_expires` AS expired, b.`vote_initiator_id`,
       be.id AS be_id, be.vote_expired AS be_vote_expired,
        COUNT(bp.id) AS `voters_count`,
        SUM(IF(ISNULL(`agreement`), 1 , 0)) AS `votes_unvoted`,
        SUM(IF((`agreement`=0), 1, 0)) AS `votes_no`,
        SUM(IF((`agreement`=1), 1, 0)) AS `votes_yes`
FROM
(
    SELECT SUBSTRING_INDEX(GROUP_CONCAT(b.`id` ORDER BY `vote_expires` DESC), ',', 1) AS `basket_id`,
            be.`sbas_id`, be.`record_id`, CONCAT(be.`sbas_id`, '_', be.`record_id`) AS `sbid_rid`
        FROM `BasketElements` AS be INNER JOIN `Baskets` AS b ON b.`id`=be.`basket_id`
        WHERE b.`vote_expires` < NOW() " . $min_date_filter . "
        GROUP BY `sbid_rid`
) AS q1
INNER JOIN `Baskets` AS b ON b.`id`=q1.`basket_id`
INNER JOIN `BasketElements` AS be ON be.basket_id=b.id AND be.sbas_id=q1.sbas_id AND be.record_id=q1.record_id
                                     AND (ISNULL(be.`vote_expired`) OR b.`vote_expires` > be.`vote_expired`)
INNER JOIN `BasketParticipants` AS bp ON bp.`can_agree`=1 AND bp.`basket_id`=b.id
LEFT JOIN `BasketElementVotes` AS bv ON bv.`participant_id`=bp.`id` AND bv.`basket_element_id`=be.id
GROUP BY be.id";

        $last_basket_id = null;
        $condensed = null;
        $vote_initiator = null;
        $stmt_select = $appbox->get_connection()->query($sql_select);
        while ($row = $stmt_select->fetch(PDO::FETCH_ASSOC)) {
            if($row['basket_id'] !== $last_basket_id) {
                $this->outputCondensed($condensed);
                $condensed = [
                    'voters_count' => $row['voters_count'],
                    'records_count' => 0,
                    'votes_unvoted' => 0,
                    'votes_no' => 0,
                    'votes_yes' => 0,
                ];

                $vote_initiator = $this->findUser($row['vote_initiator_id']);

                $this->output->writeln(sprintf("basket \"%s\" (id %s), initiated on %s by %s (id %s), expired %s",
                        $row['name'],
                        $last_basket_id = $row['basket_id'],
                        $row['created'],
                        $vote_initiator ? $vote_initiator->getEmail() : "<error>unknown</error>",
                        $row['vote_initiator_id'] ?? 'null',
                        $row['expired'])
                );
            }
            if($this->config->getReportFormat() === 'all') {
                $this->output->writeln(sprintf("\tdatabox: %s, record id: %s", $row['sbas_id'], $row['record_id']));
            }

            if( ($databox = $this->config->getDatabox($row['sbas_id'])) === null) {
                $this->output->writeln(sprintf("\t\t<error>unknown databox</error> (ignored)"));
                continue;
            }

            try {
                $record = $databox->get_record($row['record_id']);
            }
            catch(Exception $e) {
                $this->output->writeln(sprintf("\t\t<error>unknown record</error> (ignored)"));
                continue;
            }

            $condensed['records_count']++;
            foreach(['votes_unvoted', 'votes_no', 'votes_yes'] as $k) {
                if($this->config->getReportFormat() !== 'condensed') {
                    $this->output->writeln(sprintf("\t\t%s: %s", $k, $row[$k]));
                }
                $condensed[$k] += $row[$k];
            }

            $setMetasActions = [];
            foreach($this->config->getActions($databox) as $action) {
                $action->addAction(
                    $setMetasActions,
                    [
                        'initiator' => $vote_initiator,
                        'vote' => $row,
                    ]
                );
            }

            if(count($setMetasActions) > 0) {
                $jsActions = json_encode($setMetasActions, JSON_PRETTY_PRINT);
                if($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
                    $this->output->writeln(sprintf("<info>JS : %s</info>", $jsActions));
                }

                if(!$this->config->isDryRun()) {
                    $record->setMetadatasByActions(json_decode($jsActions));
                }
            }
            if(!$this->config->isDryRun()) {
                $stmt_update->execute([
                    ':expired' => $row['expired'],
                    ':id'      => $row['be_id']
                ]);
            }
        }
        $this->outputCondensed($condensed);
        $stmt_select->closeCursor();

        return 0;
    }

    /**
     * @return appbox
     */
    private function getAppBox(): appbox
    {
        return $this->container['phraseanet.appbox'];
    }

    /**
     * @return PropertyAccess
     */
    protected function getConf()
    {
        return $this->container['conf'];
    }

    private function findUser($user_id)
    {
        /** @var UserRepository $repo */
        $repo = $this->container['repo.users'];
        try {
            return $repo->find($user_id);
        }
        catch (Exception $e) {
            return null;
        }
    }

    /**
     * @param array|null $condensed
     * @return void
     */
    private function outputCondensed($condensed)
    {
        if($condensed !== null && $this->config->getReportFormat() === 'condensed') {
            foreach($condensed as $k => $v) {
                $this->output->writeln(sprintf("\t%s: %s", $k, $v));
            }
        }
    }

}
