<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2020 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\User;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\ControllerProvider\Api\V2;
use Alchemy\Phrasea\Model\Entities\ApiApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;


class UserCreateApplicationCommand extends Command
{

    /**
     * Constructor
     */
    public function __construct($name = null)
    {
        parent::__construct('user:application-create');

        $this->setDescription('Create application for user in Phraseanet')
            ->addOption('user_id', 'u', InputOption::VALUE_REQUIRED, 'The desired login for created user.')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'The desired name for application user.')
            ->addOption('type', 't', InputOption::VALUE_OPTIONAL, 'The desired name for application user.',ApiApplication::WEB_TYPE)
            ->addOption('description', 'd', InputOption::VALUE_REQUIRED, 'The desired description for application user.')
            ->addOption('website', 'w', InputOption::VALUE_OPTIONAL, 'The desired url for application user.')
            ->addOption('callback', 'c', InputOption::VALUE_OPTIONAL, 'The desired url for application user.')
            ->addOption('create_token', null, InputOption::VALUE_NONE, 'Generate an access token when app is created')
            ->addOption('activate_password', null, InputOption::VALUE_NONE, 'Activate password OAuth2 grant type')
            ->addOption('jsonformat', null, InputOption::VALUE_NONE, 'Output in json format')

            ->setHelp('');

        return $this;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $userId      = $input->getOption('user_id');
        $name        = $input->getOption('name');
        $type        = $input->getOption('type');
        $description = $input->getOption('description');
        $website     = $input->getOption('website');
        $urlCallback = $input->getOption('callback');
        $createToken        = $input->getOption('create_token');
        $activatePassword   = $input->getOption('activate_password');
        $jsonformat         = $input->getOption('jsonformat');

        $userRepository = $this->container['repo.users'];
        if (null === $user = $userRepository->find($userId)) {
            $output->writeln('<error>User not found</error>');
            return 0;
        }

        if (!$name) {
            $output->writeln('<error>Name of application must be provide with option --name.</error>');
            return 0;
        }

        if (!$description) {
            $output->writeln('<error>Desciption of application must be provide.</error>');
            return 0;
        }

        try {
            $applicationManipulator = $this->container['manipulator.api-application'];
            $application = $applicationManipulator
                ->create(
                    $name,
                    $type,
                    $description,
                    $website,
                    $user,
                    $urlCallback
                );

            $apiAccountManipulator = $this->container['manipulator.api-account'];
            $apiAccountManipulator->create($application, $user, V2::VERSION);
            $apiOauthTokenManipulator = $this->container['manipulator.api-oauth-token'];

            $accountRepository = $this->container['repo.api-accounts'];
            $apiOauthRepository = $this->container['repo.api-oauth-tokens'];
            $account =  $accountRepository->findByUserAndApplication($user, $application);

            if ($createToken) {
                $apiOauthTokenManipulator->create($account);
            }

            if ($activatePassword) {
                $application->setGrantPassword(true);
                $applicationManipulator->update($application);
            }


            $token = $account ? $apiOauthRepository->findDeveloperToken($account) : null;

            $applicationCreated = [
                $application->getClientSecret(),
                $application->getClientId(),
                $this->container["conf"]->get("servername") . "api/oauthv2/authorize",
                $this->container["conf"]->get("servername") . "api/oauthv2/token",
                ($token) ? $token->getOauthToken() : '-',
                $application->isPasswordGranted() ? "true":  "false" 
            ];

            $headers = ['client secret', 'client ID', 'Authorize endpoint url', 'Access endpoint', 'generated token', 'activate_password status'];
            if ($jsonformat ) {
                $createdAppInfo = array_combine($headers, $applicationCreated);
                echo json_encode($createdAppInfo);
            } else {
                $table = $this->getHelperSet()->get('table');
                $table
                    ->setHeaders($headers)
                    ->setRows([$applicationCreated])
                    ->render($output)
                ;   

                 $output->writeln('<info>Application user created successful !</info>');
            }       
           
        } catch (\Exception $e) {
            $output->writeln('<error>Create an application for user failed : '.$e->getMessage().'</error>');
        }

        return 0;
    }

}
