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

use Alchemy\Phrasea\Application\Helper\NotifierAware;
use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Core\LazyLocator;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Manipulator\BasketManipulator;
use Alchemy\Phrasea\Model\Manipulator\UserManipulator;
use Alchemy\Phrasea\Model\Repositories\BasketRepository;
use Alchemy\Phrasea\Model\Repositories\UserRepository;
use Alchemy\Phrasea\Notification\Mail\MailRequestInactifAccount;
use Alchemy\Phrasea\Notification\Mail\MailSuccessAccountInactifDelete;
use Alchemy\Phrasea\Notification\Receiver;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class CleanUsersCommand extends Command
{
    use NotifierAware;

    public function __construct()
    {
        parent::__construct('clean:users');

        $this
            ->setDescription('BETA - Delete "sleepy" users (not connected since a long time)')
            ->addOption('inactivity_period', null, InputOption::VALUE_REQUIRED,                             'cleanup older than \<inactivity_period> days')
            ->addOption('usertype',       null, InputOption::VALUE_REQUIRED,                             'can specify type of user to clean, if not set types ghost, basket_owner, basket_participant, story_owner are included')
            ->addOption('grace_duration',       null, InputOption::VALUE_REQUIRED,                             'grace period in days after sending email')
            ->addOption('max_relances',       null, InputOption::VALUE_REQUIRED,                             'number of email reminders, if 0 no email sent, no grace email, no account deletion confirmation email')
            ->addOption('remove_basket', null, InputOption::VALUE_NONE,                                 'remove basket for user')
            ->addOption('dry-run',        null, InputOption::VALUE_NONE,                                 'dry run, list result users')
            ->addOption('show_sql',   null, InputOption::VALUE_NONE,                                 'show sql pre-selecting users')
            ->addOption('yes',        'y',  InputOption::VALUE_NONE,                                 'don\'t ask for confirmation')

            ->setHelp(
                ""
                . "\<INACTIVITY_PERIOD> <info>integer to specify the number of inactivity days, value not 0 (zero)</info>\n"
                . "\<USERTYPE>can specify the only type of user to be clean  : \n"
                . "- <info>admin</info> \n"
                . "- <info>appowner</info> \n"
                . "- <info>ghost</info> \n"
                . "- <info>basket_owner</info> \n"
                . "- <info>basket_participant</info> \n"
                . "- <info>story_owner</info> \n"
            );
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $clauses = [];  // sql clauses
        $dry = false;
        $show_sql = false;
        $yes = false;
        $this->setDelivererLocator(new LazyLocator($this->container, 'notification.deliverer'));

        $cnx = $this->container->getApplicationBox()->get_connection();

        $inactivityPeriod = $input->getOption('inactivity_period');

        if (!preg_match("/^\d+$/", $inactivityPeriod)) {
            $output->writeln("<error>invalid value form '--inactivity_period' option</error>(see possible value with --help)");

            return 1;
        }

        $graceDuration = $input->getOption('grace_duration');

        if (!preg_match("/^\d+$/", $graceDuration)) {
            $output->writeln("<error>invalid value form '--grace_duration' option</error>(see possible value with --help)");

            return 1;
        }

        $maxRelances = $input->getOption('max_relances');

        if (!preg_match("/^\d+$/", $maxRelances)) {
            $output->writeln("<error>invalid value form '--max_relances' option</error>(see possible value with --help)");

            return 1;
        }

        $clauses[] = sprintf("`last_connection` < DATE_SUB(NOW(), INTERVAL %d day)", $inactivityPeriod);

        $sql_where_u = 1;
        $sql_where_ub = 1;

        if ($input->getOption('usertype') == 'admin') {
            $clauses[] = "`admin`=1";
        } else {
            $clauses[] = "`admin`=0";  // dont delete super admins
        }

        if ($input->getOption('usertype') == 'appowner') {
            $clauses[] = "`ApiAccounts`.`id` IS NOT NULL";
        } else {
            $clauses[] = "ISNULL(`ApiAccounts`.`id`)";
        }

        if ($input->getOption('usertype') == 'ghost') {
            $sql_where_u = "`u`.`bids` IS NULL";
            $sql_where_ub = "`ub`.`sbids` IS NULL";
        }

        if ($input->getOption('usertype') == 'basket_owner') {
            $clauses[] = "`Baskets`.`id` IS NOT NULL";
        }

        if ($input->getOption('usertype') == 'basket_participant') {
            $clauses[] = "`BasketParticipants`.`id` IS NOT NULL";
            $clauses[] = "`B`.`user_id` !=  `BasketParticipants`.`user_id`";
        }

        if ($input->getOption('usertype') == 'story_owner') {
            $clauses[] = "`StoryWZ`.`id` IS NOT NULL";
        }

        $clauses[] = "`deleted`=0";                 // dont delete twice
        $clauses[] = "ISNULL(`model_of`)";          // dont delete models
        $clauses[] = "`login`!='autoregister'";     // dont delete "autoregister"
        $clauses[] = "`login`!='guest'";            // dont delete "guest"


        if ($input->getOption('dry-run')) {
            $dry = true;
        }

        if ($input->getOption('show_sql')) {
            $show_sql = true;
        }

        if ($input->getOption('yes')) {
            $yes = true;
        }

        $sql_where = join(") AND (", $clauses);

        /** @var UserManipulator $userManipulator */
        $userManipulator = $this->container['manipulator.user'];
        /** @var UserRepository $userRepository */
        $userRepository = $this->container['repo.users'];
        /** @var BasketRepository $basketRepository */
        $basketRepository = $this->container['repo.baskets'];

        $sql_list = "SELECT * FROM \n"
                . "(SELECT ub.*, GROUP_CONCAT(`basusr`.`base_id` SEPARATOR ',') AS `bids`\n"
                . "FROM\n"
                . "( SELECT `Users`.`id` AS `usr_id`, `Users`.`login`, `Users`.`email`, `Users`.`last_connection`, GROUP_CONCAT(`sbasusr`.`sbas_id` SEPARATOR ',') AS `sbids`\n"
                . "  FROM (`Users` LEFT JOIN `ApiAccounts` ON `ApiAccounts`.`user_id` = `Users`.`id`) \n"
                . "  LEFT JOIN `sbasusr` ON `sbasusr`.`usr_id` = `Users`.`id`\n"
                . "  LEFT JOIN Baskets ON Baskets.user_id = `Users`.`id`\n"
                . "  LEFT JOIN BasketParticipants ON BasketParticipants.user_id = `Users`.`id`\n"
                . "  LEFT JOIN Baskets as B ON B.id = BasketParticipants.basket_id \n"
                . "  LEFT JOIN StoryWZ ON StoryWZ.user_id = `Users`.`id`\n"
                . "  WHERE (" . $sql_where . ")"
                . "  GROUP BY `Users`.`id`\n"
                . ") AS ub\n"
                . "LEFT JOIN `basusr` ON `basusr`.`usr_id` = `ub`.`usr_id`"
                . " WHERE " . $sql_where_ub ."\n"
                . " GROUP BY `ub`.`usr_id`) AS u\n"
                . " WHERE ". $sql_where_u ;

        if ($show_sql) {
            $output->writeln(sprintf("sql: \"<info>%s</info>\"", $sql_list));
        }

        $stmt = $cnx->prepare($sql_list);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (!$yes && !$dry) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion(sprintf("Confirm cleanup for %s user(s) [y/n] : ", count($rows)), false);

            if (!$helper->ask($input, $output, $question)) {
                return 0;
            }
        }

        $usersList = [];
        $nbUserRelanced = 0;
        $nbUserDeleted = 0;

        foreach ( $rows as $row ) {
            if( !is_null($user = $userRepository->find($row['usr_id'])) ) {
                $lastInactivityEmail = $user->getLastInactivityEmail();
                $nbRelance = $user->getNbInactivityEmail();
                $nowDate = new \DateTime();

                $interval = sprintf('P%dD', $graceDuration);

                $nowDate->sub(new \DateInterval($interval));
                $action = "in grace period";

                $isValidMail = true;
                if (!\Swift_Validate::email($user->getEmail())) {
                    $isValidMail = false;
                }

                if (empty($lastInactivityEmail) || $lastInactivityEmail < $nowDate) {
                    // first, relance the user by email to have a grace period
                    if (($nbRelance < $maxRelances) && $isValidMail) {
                        if (!$dry) {
                            $this->relanceUser($user, $graceDuration);
                            $user->setNbInactivityEmail($nbRelance+1);
                            $user->setLastInactivityEmail(new \DateTime());
                            $userManipulator->updateUser($user);
                        }
                        $action = sprintf("max_relances=%d , found %d times relanced (will be relance if not --dry-run)", $maxRelances, $nbRelance);
                        $nbUserRelanced++;
                    } else {
                        if (!$dry) {
                            if ($input->getOption('remove_basket')) {
                                $baskets = $basketRepository->findBy(['user' => $user]);
                                $this->getBasketManipulator()->removeBaskets($baskets);
                            }
                            // delete user and notify by mail
                            $this->doDelete($user, $userManipulator, $isValidMail, $maxRelances);

                            $output->write(sprintf("%s : %s / %s (%s)", $row['usr_id'], $row['login'], $row['email'], $row['last_connection']));

                            $output->writeln(" deleted.");
                        }

                        if ($isValidMail) {
                            $action = sprintf("max_relances=%d , found %d times relanced (will be deleted if not --dry-run)", $maxRelances, $nbRelance);
                        } else {
                            $action = "no valid address email for the user (will be deleted if not --dry-run)";
                        }

                        $nbUserDeleted++;
                    }
                }
                // else we are in grace period, nothing to do

                $usersList[] = [
                    $user->getId(),
                    $user->getLogin(),
                    $user->getLastConnection()->format('Y-m-d h:m:s'),
                    $action
                ];
            }
        }

        $stmt->closeCursor();

        if ($dry) {
            $output->writeln(sprintf("dry-run , %d users included in the given inactivity_period", count($rows)));
            $userTable = $this->getHelperSet()->get('table');
            $headers = ['id', 'login', 'last_connection', 'action'];
            $userTable
                ->setHeaders($headers)
                ->setRows($usersList)
                ->render($output);
        } else {
            $output->writeln(sprintf("%d users relanced , %d in grace period, %d users deleted", $nbUserRelanced, (count($rows)-$nbUserDeleted-$nbUserRelanced), $nbUserDeleted));
        }

        return 0;
    }

    private function relanceUser(User $user, $graceDuration)
    {
        try {
            $receiver = Receiver::fromUser($user);
            $mail = MailRequestInactifAccount::create($this->container, $receiver);

            $mail->setLogin($user->getLogin());
            $mail->setLocale($user->getLocale());
            $mail->setLastConnection($user->getLastConnection()->format('Y-m-d'));
            $mail->setDeleteDate((new \DateTime("+{$graceDuration} day"))->format('Y-m-d'));

            // return 0 on failure
            $this->deliver($mail);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    private function doDelete(User $user, UserManipulator $userManipulator, $validMail, $maxRelances)
    {
        try {
            if ($validMail && !empty($maxRelances)) {
                $receiver = Receiver::fromUser($user);
                $mail = MailSuccessAccountInactifDelete::create($this->container, $receiver);
                $mail->setLastConnection($user->getLastConnection()->format('Y-m-d'));

                // if --max_relances=0  there is no inactivity email
                if ($user->getLastInactivityEmail() !== null) {
                    $mail->setLastInactivityEmail($user->getLastInactivityEmail()->format('Y-m-d'));
                }

                $mail->setLocale($user->getLocale());
                $mail->setDisplayFooterText(false);
            }

            $userManipulator->delete($user);

            if ($validMail && !empty($maxRelances)) {
                // return 0 on failure
                $this->deliver($mail);
            }
        } catch (\Exception $e) {
        }
    }

    /**
     * @return BasketManipulator
     */
    private function getBasketManipulator()
    {
        return $this->container['manipulator.basket'];
    }

}
