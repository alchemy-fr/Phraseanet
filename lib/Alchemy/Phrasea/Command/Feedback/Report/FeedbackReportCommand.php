<?php

namespace Alchemy\Phrasea\Command\Feedback\Report;


use Alchemy\Phrasea\Command\Command as phrCommand;
use appbox;
use Doctrine\DBAL\DBALException;
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
            ->addOption('dry',    null,  InputOption::VALUE_NONE, "list translations but don't apply.", null)
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
            $this->config = GlobalConfiguration::create(
                $this->container['twig'],
                $this->container['phraseanet.appbox'],
                $this->container['root.path'],
                $input->getOption('dry'),
                $output
            );
        }
        catch(\Exception $e) {
            $output->writeln(sprintf("<error>missing or bad configuration: %s</error>", $e->getMessage()));

            return -1;
        }

//
//        $sql = "
//ALTER TABLE `BasketElements`
//            ADD `vote_basket_id` INT NULL,
//            ADD `vote_expired` DATETIME NULL,
//            ADD `vote_participants` INT UNSIGNED NULL,
//            ADD `vote_blank` INT UNSIGNED NULL,
//            ADD `vote_false` INT UNSIGNED NULL,
//            ADD `vote_true` INT UNSIGNED NULL,
//            ADD INDEX `vote_expired` (`vote_expired`);
//";



//        $sql = "
//UPDATE BasketElements AS be INNER JOIN (
//    SELECT q1.*,
//    COUNT(bp.id) AS n_participants,
//    SUM(IF(ISNULL(agreement),1,0)) AS unvoted,
//    SUM(IF((agreement=0), 1,0)) AS voted_false,
//    SUM(IF((agreement=1),1,0)) AS voted_true
//    FROM (
//        SELECT SUBSTRING_INDEX(GROUP_CONCAT(b.id ORDER BY vote_expires DESC), ',', 1) AS basket_id,
//            MAX(b.vote_expires) AS expired, be.id AS be_id, CONCAT(be.sbas_id, '_', be.record_id) AS sbid_rid
//        FROM `BasketElements` AS be INNER JOIN Baskets AS b ON b.id=be.basket_id
//        WHERE b.vote_expires < NOW()
//        GROUP BY sbid_rid
//    ) AS q1
//    INNER JOIN BasketParticipants AS bp ON bp.basket_id=q1.basket_id
//    LEFT JOIN BasketElementVotes AS bv ON bv.participant_id=bp.id AND bv.basket_element_id=be_id
//    GROUP BY q1.sbid_rid
//) AS q2 ON be.id=q2.be_id
//SET vote_basket_id=q2.basket_id,
//    vote_expired=q2.expired,
//    vote_participants = q2.n_participants,
//    vote_blank = q2.unvoted,
//    vote_false = q2.voted_false,
//    vote_true = q2.voted_true
//WHERE q2.expired > vote_expired
//";

        $appbox = $this->getAppBox();

        $sql_select = "SELECT q1.*,
    COUNT(bp.id) AS `voters_count`,
    SUM(IF(ISNULL(`agreement`),1,0)) AS `votes_unvoted`,
    SUM(IF((`agreement`=0), 1,0)) AS `votes_no`,
    SUM(IF((`agreement`=1),1,0)) AS `votes_yes`
    FROM (
        SELECT SUBSTRING_INDEX(GROUP_CONCAT(b.id ORDER BY vote_expires DESC), ',', 1) AS basket_id,
            MAX(b.`vote_expires`) AS `expired`, be.`id` AS `be_id`, be.`vote_expired` AS `be_vote_expired`,
            be.`sbas_id`, be.`record_id`, CONCAT(be.`sbas_id`, '_', be.`record_id`) AS `sbid_rid`
        FROM `BasketElements` AS be INNER JOIN `Baskets` AS b ON b.`id`=be.`basket_id`
        WHERE b.`vote_expires` < NOW()
        GROUP BY `sbid_rid`
    ) AS q1
    INNER JOIN `BasketParticipants` AS bp ON bp.`basket_id`=q1.`basket_id`
    LEFT JOIN `BasketElementVotes` AS bv ON bv.`participant_id`=bp.`id` AND bv.`basket_element_id`=`be_id`
    GROUP BY q1.`sbid_rid`
    HAVING ISNULL(`be_vote_expired`) OR `expired` > `be_vote_expired`";


        $sql_update = "UPDATE `BasketElements` SET `vote_expired` = :expired WHERE `id` = :id";
        $stmt_update = $appbox->get_connection()->prepare($sql_update);

        $stmt_select = $appbox->get_connection()->query($sql_select);
        while ($row = $stmt_select->fetch(PDO::FETCH_ASSOC)) {
            if( ($databox = $this->config->getDatabox($row['sbas_id'])) === null) {
                continue;
            }

            $setMetasActions = [];
            foreach($this->config->getActions($databox) as $action) {
                $action->addAction(
                    $setMetasActions,
                    [
                        'vote' => $row,
                    ]
                );
            }

            if(count($setMetasActions) > 0) {
                $this->output->writeln(var_export($setMetasActions, true));
                $record = $databox->get_record($row['record_id']);
                $record->setMetadatasByActions(json_decode(json_encode($setMetasActions)));
            }
            $stmt_update->execute([
                ':expired' => $row['expired'],
                ':id' => $row['be_id']
            ]);
        }
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

}
