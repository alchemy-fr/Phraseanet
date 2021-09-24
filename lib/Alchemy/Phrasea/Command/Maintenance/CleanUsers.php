<?php

/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Maintenance;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Model\Manipulator\UserManipulator;
use Alchemy\Phrasea\Model\Repositories\BasketRepository;
use Alchemy\Phrasea\Model\Repositories\UserRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class CleanUsers extends Command
{

    const CLEAR_EMAIL = 8;
    const REVOKE = 4;
    const DELETE_DATA = 2;
    const DELETE_ALL = 1;

    static private $mapListToColumn = [
        'email' => 'email',
        'login' => 'login',
        'id'    => 'usr_id',
    ];

    public function __construct()
    {
        parent::__construct('maintenance:clean:users');

        $this
            ->setDescription('Delete "sleepy" users (not connected since a long time)')
//            ->addOption('action',     null, InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, '"clear_email" ; "revoke" ; "delete_data" ; "delete_all"')
            ->addOption('older_than', null, InputOption::VALUE_REQUIRED,                             'delete older than \<OLDER_THAN>')
            ->addOption('dry',        null, InputOption::VALUE_NONE,                                 'dry run, count but don\'t delete')
            ->addOption('list',       null, InputOption::VALUE_REQUIRED,                             'list only, don\'t delete')
            ->addOption('show_sql',   null, InputOption::VALUE_NONE,                                 'show sql pre-selecting users')
            ->addOption('yes',        'y',  InputOption::VALUE_NONE,                                 'don\'t ask for confirmation')

            ->setHelp(
                ""
//                . "<info>action=clear-email</info> will set the email to (null)\n"
//                . "<info>action=revoke</info> will delete all access rights (= make user a \"ghost\")\n"
//                . "<info>action=delete_data</info> will delete every appbox elements related to the user (rights, baskets, notifications, ...) but preserves dbox logs\n"
//                . "<info>action=delete_all</info> will delete all db elements, including the dbox logs and the user itself\n"
//                . "default = clear_email ; revoke ; delete_data"
//                . "\n"
                . "\<OLDER_THAN> can be absolute or relative from now, e.g.:\n"
                . "- <info>2022-01-01</info> (please use strict date format, do not add time)\n"
                . "- <info>10 days</info>\n"
                . "- <info>2 weeks</info>\n"
                . "- <info>6 months</info>\n"
                . "- <info>1 year</info>\n"
                . "\<LIST> sepcifies the user column to be listed, set one of:\n"
                . "- <info>id</info>\n"
                . "- <info>login</info>\n"
                . "- <info>email</info>"
            );
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $clauses = [];  // sql clauses
        $dry = false;
        $show_sql = false;
        $yes = false;

        // sanity check options
        //

        // --action (multiple values allowed)

        /*
        $action = 0;        // actions requested, as bitfield
        $actions = [
            'CLEAR_EMAIL' => self::CLEAR_EMAIL,
            'REVOKE'      => self::REVOKE,
            'DELETE_DATA' => self::DELETE_DATA,
            'DELETE_ALL'  => self::DELETE_ALL
        ];
        if(empty($action_parm = $input->getOption('action'))) {
            $action_parm = ['clear_email', 'revoke', 'delete_data'];
        }
        foreach($action_parm as $a) {
            $a = strtoupper($a);
            if(!array_key_exists($a, $actions)) {
                $output->writeln(sprintf("<error>Invalid value \"%s\" for --action option.</error>", $a));
                return 1;
            }
            $action |= $action[$a];
        }
        if($action & self::DELETE_ALL && $action !== self::DELETE_ALL) {
            $output->writeln(sprintf("<error>Action \"delete_all\" cannot be mixed with other action.</error>"));
            return 1;
        }
        */

        // --older_than

        $older_than = str_replace(['-', '/', ' '], '-', $input->getOption('older_than'));
        if($older_than === "") {
            $output->writeln("<error>set '--older_than' option.</error>");
            return 1;
        }
        $matches = [];
        preg_match("/(\d{4}-\d{2}-\d{2})|(\d+)-(day|week|month|year)s?/i", $older_than, $matches);
        $n = count($matches);
        if($n === 2) {
            // yyyy-mm-dd
            $clauses[] = "`last_connection` < " . $matches[1];
        }
        elseif($n === 4 && empty($matches[1])) {
            // 1-day ; 2-weeks ; ...
            $expr = (int)$matches[2];
            $unit = strtoupper($matches[3]);
            $clauses[] = sprintf("`last_connection` < DATE_SUB(NOW(), INTERVAL %d %s)", $expr, $unit);
        }
        else {
            $output->writeln("<error>invalid value form '--older_than' option.</error> (see possible values with --help)");
            return 1;
        }
        $clauses[] = "`admin`=0";                   // dont delete super admins
        $clauses[] = "`deleted`=0";                 // dont delete twice
        $clauses[] = "ISNULL(`model_of`)";          // dont delete models
        $clauses[] = "`login`!='autoregister'";     // dont delete "autoregister"
        $clauses[] = "`login`!='guest'";            // dont delete "guest"
        $clauses[] = "ISNULL(`ApiAccounts`.`id`)";      // dont delete api service accounts

        // --dry

        if($input->getOption('dry')) {
            $dry = true;
        }

        if($input->getOption('show_sql')) {
            $show_sql = true;
        }

        if($input->getOption('yes')) {
            $yes = true;
        }

        if(!is_null($list = $input->getOption('list'))) {
            if(!array_key_exists($list, self::$mapListToColumn)) {
                $output->writeln(sprintf("<error>bad \"list\" value '%s'</error> (see possible values with --help)", $list));
                return 1;
            }
        }

        // do the job
        //
        $sql_where = join(") AND (", $clauses);

        $cnx = $this->container->getApplicationBox()->get_connection();

        $sql_count = "SELECT COUNT(`Users`.`id`) AS n FROM (`Users` LEFT JOIN `ApiAccounts` ON `ApiAccounts`.`user_id`=`Users`.`id`) WHERE (" . $sql_where . ")";
        if($show_sql) {
            $output->writeln(sprintf("sql: \"<info>%s</info>\"", $sql_count));
        }
        $stmt = $cnx->prepare($sql_count);
        $stmt->execute();
        $n = $stmt->fetchColumn(0);
        $stmt->closeCursor();
        if(!$list) {
            $output->writeln(sprintf("Acting on %s users.", $n));
        }


        /** @var UserManipulator $userManipulator */
        $userManipulator = $this->container['manipulator.user'];
        /** @var UserRepository $userRepository */
        $userRepository = $this->container['repo.users'];
        /** @var BasketRepository $basketRepository */
        $basketRepository = $this->container['repo.baskets'];

        $sql_list = "SELECT u.*, GROUP_CONCAT(`basusr`.`base_id` SEPARATOR ',') AS `bids`\n"
                . "FROM\n"
                . "( SELECT `Users`.`id` AS `usr_id`, `Users`.`login`, `Users`.`email`, `Users`.`last_connection`, GROUP_CONCAT(`sbasusr`.`sbas_id` SEPARATOR ',') AS `sbids`\n"
                . "  FROM (`Users` LEFT JOIN `ApiAccounts` ON `ApiAccounts`.`user_id` = `Users`.`id`) \n"
                . "  LEFT JOIN `sbasusr` ON `sbasusr`.`usr_id` = `Users`.`id`\n"
                . "  WHERE (" . $sql_where . ")"
                . "  GROUP BY `sbasusr`.`usr_id`\n"
                . ") AS u\n"
                . "LEFT JOIN `basusr` ON `basusr`.`usr_id` = `u`.`usr_id` GROUP BY `basusr`.`usr_id`";

        if($show_sql) {
            $output->writeln(sprintf("sql: \"<info>%s</info>\"", $sql_list));
        }

        if(!$yes && !$list) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion(sprintf("Confirm deletion of %s user(s) [y/n] : ", $n), false);

            if (!$helper->ask($input, $output, $question)) {
                return 0;
            }
        }

        $stmt = $cnx->prepare($sql_list);
        $stmt->execute();
        while( $row = ($stmt->fetch(\PDO::FETCH_ASSOC)) ) {
            if( !is_null($user = $userRepository->find($row['usr_id'])) ) {

                if ($list) {
                    $s = $row[self::$mapListToColumn[$list]];
                    $output->write(sprintf("%s%s", $s, "\n"));
                }
                else {
                    $output->write(sprintf("%s : %s / %s (%s)", $row['usr_id'], $row['login'], $row['email'], $row['last_connection']));

                    if (!$dry) {
                        $acl = $this->container->getAclForUser($user);

                        // revoke bas rights
                        if (!is_null($row['bids'])) {
                            $bids = array_map(function ($bid) {
                                return (int)$bid;
                            }, explode(',', $row['bids']));
                            $acl->revoke_access_from_bases($bids);
                        }

                        // revoke sbas rights
                        $acl->revoke_unused_sbas_rights();

                        // delete user
                        $userManipulator->delete($user);

                        $output->writeln(" deleted.");
                    }
                    else {
                        $output->writeln(" not deleted (dry mode).");
                    }
                }
            }
        }
        $stmt->closeCursor();


/*
        // clear email

        if($action & self::CLEAR_EMAIL) {
            $sql = "UPDATE `Users` SET `email`=NULL WHERE (" . $sql_where . ")";
            if($show_sql) {
                $output->writeln(sprintf("sql: \"<info>%s</info>\"", $sql));
            }
            if(!$dry) {
                $cnx->exec($sql);
            }
        }

        // revoke rights

        if($action & self::REVOKE) {
            $sql = "DELETE "
        }
*/

        return 0;
    }

}
