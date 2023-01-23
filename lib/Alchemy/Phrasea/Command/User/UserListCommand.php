<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\User;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Model\Entities\User;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Alchemy\Phrasea\Utilities\NullableDateTime;


class UserListCommand extends Command
{

    /**
     * Constructor
     */
    public function __construct($name = null)
    {
        parent::__construct('user:list');

        $this->setDescription('List of all user <comment>(experimental)</>')
            ->addOption('user_id', null, InputOption::VALUE_OPTIONAL, 'The id of user export only info this user ')
            ->addOption('user_email', null, InputOption::VALUE_OPTIONAL, 'The mail of user export only info this user .')
            ->addOption('database_id', null, InputOption::VALUE_OPTIONAL, 'Id of database.')
            ->addOption('collection_id', null, InputOption::VALUE_OPTIONAL, 'Id of the collection.')
            ->addOption('mail_lock_status', null, InputOption::VALUE_NONE, 'Status by mail locked')
            ->addOption('guest', null, InputOption::VALUE_NONE, 'Only guest user')
            ->addOption('created', null, InputOption::VALUE_OPTIONAL, 'Created at with operator,aaaa-mm-jj hh:mm:ss.')
            ->addOption('updated', null, InputOption::VALUE_OPTIONAL, 'Update at with operator,aaaa-mm-jj hh:mm:ss.')
            ->addOption('right', null, InputOption::VALUE_NONE, 'Show right information')
            ->addOption('adress', null, InputOption::VALUE_NONE, 'Show adress information')
            ->addOption('models', null, InputOption::VALUE_NONE, "Show only defined models, if --user_id is set with --models it's the template owner")
            ->addOption('jsonformat', null, InputOption::VALUE_NONE, 'Output in json format')
            ->setHelp('');

        return $this;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {

        $userId        = $input->getOption('user_id');
        $userEmail     = $input->getOption('user_email');
        $databaseId    = $input->getOption('database_id');
        $collectionId  = $input->getOption('collection_id');
        $lockStatus    = $input->getOption('mail_lock_status');
        $guest         = $input->getOption('guest');
        $withAdress    = $input->getOption('adress');
        $created       = $input->getOption('created');
        $updated       = $input->getOption('updated');
        $withRight     = $input->getOption('right');
        $models        = $input->getOption('models');
        $jsonformat    = $input->getOption('jsonformat');

        $query         = $this->container['phraseanet.user-query'];

        if($databaseId) $query->on_base_ids([$databaseId]);
        if($collectionId) $query->on_sbas_ids([$collectionId]);
        if($created) $this->addFilterDate($created,'created',$query);
        if($updated) $this->addFilterDate($updated,'updated',$query);
        if($userId && !$models) $query->addSqlFilter('Users.id = ?' ,[$userId]);
        if($userEmail && !$models) $query->addSqlFilter('Users.email = ?' ,[$userEmail]);
        if($lockStatus && !$models) $query->addSqlFilter('Users.mail_locked = 1');
        if($guest && !$models) $query->include_invite(true)->addSqlFilter('Users.guest = 1');

        /** @var UserRepository $userRepository */
        $userRepository = $this->container['repo.users'];

        if ($models && $userId) {
            $users = $userRepository->findBy(['templateOwner' => $userId]);            
        } elseif ($models) {
            $users = $userRepository->findTemplate();
        } else {
            $users = $query->execute()->get_results();
        }

        $userList = [];
        foreach ($users as $key => $user) {
            $userList[] = $this->listUser($user, $withAdress, $withRight);

            $userListRaw[] = array_combine($this->headerTable($withAdress, $withRight), $this->listUser($user, $withAdress, $withRight));
        }

        if ($jsonformat) {         
            echo json_encode($userListRaw);            
        } else {
                $table = $this->getHelperSet()->get('table');
            $table
                ->setHeaders($this->headerTable($withAdress, $withRight))
                ->setRows($userList)
                ->render($output);
            ;

        }

        
        return 0;
    }

    /**
     * @param $withAdress
     * @param $withRight
     * @return array
     */
    private function headerTable($withAdress,$withRight)
    {
        $defaultHeader = ['id', 'login', 'email','last_model','first_name','last_name','gender','created','updated','status','locale'];
        $adressHeader  = [ 'address', 'zip_code', 'city', 'country', 'phone', 'fax', 'job','position', 'company', 'geoname_id'];
        $rightHeader   = [ 'admin', 'guest', 'mail_notification', 'ldap_created', 'mail_locked'];

        return $this->createInformation($withAdress,$withRight,$defaultHeader,['adress' => $adressHeader,'right' =>$rightHeader]);
    }

    /**
     * @param User $user
     * @param $withAdress
     * @param $withRight
     * @return array
     */
    private function listUser(User $user,$withAdress,$withRight)
    {
        switch ($user->getGender()) {
            case User::GENDER_MRS:
                $gender = 'Mrs';
                break;
            case User::GENDER_MISS:
                $gender = 'Miss';
                break;
            case User::GENDER_MR:
            default:
                $gender = 'Mr';
        }

        $defaultInfo = [
            $user->getId(),
            $user->getLogin() ?: '-',
            $user->getEmail() ?: '-',
            $user->getLastAppliedTemplate() ? $user->getLastAppliedTemplate()->getLogin() : '-',
            $user->getFirstName() ?: '-',
            $user->getLastName() ?: '-',
            $gender,
            NullableDateTime::format($user->getCreated(),'Y-m-d H:i:s'),
            NullableDateTime::format($user->getUpdated(),'Y-m-d H:i:s'),
            'status',
            $user->getLocale() ?: '-',
        ];

        return $this->createInformation($withAdress,$withRight,$defaultInfo,['adress' => $this->userAdress($user),'right' => $this->userRight($user)]);
    }

    /**
     * @param User $user
     * @return array
     */
    private function userAdress(User $user)
    {
        return [
            $user->getAddress() ?: '-',
            $user->getZipCode() ?: '-',
            $user->getCity() ?: '-',
            $user->getCountry() ?: '-',
            $user->getPhone() ?: '-',
            $user->getFax() ?: '-',
            $user->getJob() ?: '-',
            $user->getActivity() ?: '-',
            $user->getCompany() ?: '-',
            $user->getGeonameId() ?: '-',
        ];
    }

    /**
     * @param User $user
     * @return array
     */
    private function userRight(User $user)
    {
        return [
            $user->isAdmin() ?: false,
            $user->isGuest() ?: false,
            $user->hasMailNotificationsActivated() ?: false,
            $user->hasLdapCreated() ?: false,
            $user->isMailLocked() ?: false,
        ];
    }

    /**
     * @param $withAdress
     * @param $withRight
     * @param $default
     * @param $infoToMerge
     * @return array
     */
    private function createInformation($withAdress,$withRight,$default,$infoToMerge)
    {
        if ($withAdress && $withRight) {
            $information =  array_merge($default, $infoToMerge['adress'],$infoToMerge['right']);
        } elseif ($withAdress && !$withRight) {
            $information = array_merge($default,  $infoToMerge['adress']);
        } elseif(!$withAdress && $withRight) {
            $information = array_merge($default, $infoToMerge['right']);
        } else {
            $information = $default;
        }

        return $information;
    }

    /**
     * @param $date
     * @param $type
     * @param $query
     */
    private function addFilterDate($date,$type,$query){

        list($operator,$dateAt) = explode(',', $date);

        if (!in_array($operator,['=','>=','<=','>','<'])) {
            throw new \InvalidArgumentException(" '=' or '<=' or '>=' or '>' or '<'");
        }

        $query->addSqlFilter($type.$operator.' ?' ,[$dateAt]);
    }

}
