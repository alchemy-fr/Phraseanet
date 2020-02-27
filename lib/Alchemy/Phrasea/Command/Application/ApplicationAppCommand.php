<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2020 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Application;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Core\LazyLocator;
use Alchemy\Phrasea\Model\Entities\ApiApplication;
use Alchemy\Phrasea\Model\Entities\ApiAccount;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Notification\Mail\MailRequestPasswordSetup;
use Alchemy\Phrasea\Notification\Mail\MailRequestEmailConfirmation;
use Alchemy\Phrasea\Notification\Receiver;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;


class ApplicationAppCommand extends Command
{

    /**
     * Constructor
     */
    public function __construct($name = null)
    {
        parent::__construct('application:app');

        $this->setDescription('List, Create, Edit, Delete application in Phraseanet <comment>(experimental)</>')
            ->addOption('user_id', 'u', InputOption::VALUE_REQUIRED, 'The id of user.')
            ->addOption('app_id', 'a', InputOption::VALUE_REQUIRED, 'The application ID')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'The desired name for application user.')
            ->addOption('type', 't', InputOption::VALUE_OPTIONAL, 'The kind of application user Desktop or Web.',ApiApplication::WEB_TYPE)
            ->addOption('description', 'd', InputOption::VALUE_REQUIRED, 'The desired description for application user.')
            ->addOption('website', 'w', InputOption::VALUE_OPTIONAL, 'The desired url for application user.')
            ->addOption('callback', 'c', InputOption::VALUE_OPTIONAL, 'The desired callback endpoint for application user.')
            ->addOption('webhook_url', null, InputOption::VALUE_REQUIRED, 'The desired webhook url for application')
            ->addOption('create_token', null, InputOption::VALUE_NONE, 'Generate or regenerate an access token')
            ->addOption('activate_password', null, InputOption::VALUE_OPTIONAL, 'Activateor deactivate password OAuth2 grant type , values true or false', 'false')
            ->addOption('create', null, InputOption::VALUE_NONE, 'Create application for user in Phraseanet')
            ->addOption('edit', null, InputOption::VALUE_NONE, 'Edit application in Phraseanet work only if app_id and user_id are set')
            ->addOption('delete', null, InputOption::VALUE_NONE, 'Delete application in Phraseanet, need app_id')
            ->addOption('list', null, InputOption::VALUE_NONE, 'List all application or user application if --user_id is set')
            ->addOption('jsonformat', null, InputOption::VALUE_NONE, 'Output in json format')

            ->setHelp('');

        return $this;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $userId      = $input->getOption('user_id');
        $appId       = $input->getOption('app_id');
        $name        = $input->getOption('name');
        $type        = $input->getOption('type');
        $description = $input->getOption('description');
        $website     = $input->getOption('website');
        $urlCallback = $input->getOption('callback');
        $webhookUrl  = $input->getOption('webhook_url');
        $createToken        = $input->getOption('create_token');
        $activatePassword   = $input->getOption('activate_password');
        $create             = $input->getOption('create');
        $edit               = $input->getOption('edit');
        $delete             = $input->getOption('delete');
        $list               = $input->getOption('list');
        $jsonformat         = $input->getOption('jsonformat');

        $applicationManipulator   = $this->container['manipulator.api-application'];
        $apiOauthTokenManipulator = $this->container['manipulator.api-oauth-token'];
        $accountRepository        = $this->container['repo.api-accounts'];
        $apiApllicationConverter  = $this->container['converter.api-application'];
        $userRepository           = $this->container['repo.users'];
        $apiOauthRepository       = $this->container['repo.api-oauth-tokens'];

        if ($create) {
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

                $account =  $accountRepository->findByUserAndApplication($user, $application);

                if ($createToken) {
                    $apiOauthTokenManipulator->create($account);
                }

                if ($activatePassword) {
                    if (in_array($activatePassword, ['true', 'false'])) {
                        $application->setGrantPassword(($activatePassword == 'true') ? true : false);
                        $applicationManipulator->update($application);
                    } else {
                        $output->writeln('<error> Value of option --activate_password should be "true" or "false"</error>');
                
                        return 0;
                    }
                }

                $this->showApllicationInformation($apiOauthRepository, $account, $application, $jsonformat, $output);                
            } catch (\Exception $e) {
                $output->writeln('<error>Create an application for user failed : '.$e->getMessage().'</error>');
            }
        } elseif ($edit) {
            if (!$appId) {
                $output->writeln('<error>ID of the application must be provided with option --app_id to edit the application.</error>');

                return 0;
            }

            if (null === $user = $userRepository->find($userId)) {
                $output->writeln('<error>User not found</error>');
                return 0;
            }

            $application = $apiApllicationConverter->convert($appId);
            $account =  $accountRepository->findByUserAndApplication($user, $application);

            if (!$account) {
                $output->writeln('<error>ApiAccount not found!Check the given user_id and app_id!</error>');

                return 0;
            }

            if ($name) {
                $application->setName($name);
            }
            if ($type) {
                $applicationManipulator->setType($application, $type);
            }
            if ($description) {
                $application->setDescription($description);
            }
            if ($website) {
                $applicationManipulator->setWebsiteUrl($application, $website);
            }
            if ($urlCallback) {
                $applicationManipulator->setRedirectUri($application, $urlCallback);
            }
            if ($createToken) {
                $apiOauthTokenManipulator->create($account);
            }
            if ($activatePassword) {
                if (in_array($activatePassword, ['true', 'false'])) { 
                    $application->setGrantPassword(($activatePassword == 'true') ? true : false);
                } else {
                    $output->writeln('<error> Value of option --activate_password should be "true" or "false"</error>');
            
                    return 0;
                }
            }
            if ($webhookUrl) {
                $applicationManipulator->setWebhookUrl($application, $webhookUrl);
            }

            $applicationManipulator->update($application);
            
            $this->showApllicationInformation($apiOauthRepository, $account, $application, $jsonformat, $output);
        } elseif ($list) {
            if ($userId) {
                if (null === $user = $userRepository->find($userId)) {
                    $output->writeln('<error>User not found</error>');

                    return 0;
                }

                $accounts = $accountRepository->findByUser($user);
            } else {
                $accounts = $accountRepository->findAll();
            }
            
            $applicationList = [];

            foreach ($accounts as $account) {
                $application = $account->getApplication();
                $token = $apiOauthRepository->findDeveloperToken($account);

                $applicationList[] = [
                    $application->getId(),
                    $account->getUser()->getId(),
                    $application->getName(),                    
                    $application->getClientId(),
                    $application->getRedirectUri(),
                    ($token) ? $token->getOauthToken() : '-',
                    $application->isPasswordGranted() ? "true":  "false"
                ];
            }

            $applicationTable = $this->getHelperSet()->get('table');
            $headers = ['ID', 'user ID', 'Name', 'client ID', 'Callback Url', 'generated token', 'activate_password status'];

            if ($jsonformat ) {
                foreach ($applicationList as $appList) {
                    $appInfo[] = array_combine($headers, $appList);
                }
                
                echo json_encode($appInfo);
            } else {
                $applicationTable = $this->getHelperSet()->get('table');
                $applicationTable
                    ->setHeaders($headers)
                    ->setRows($applicationList)
                    ->render($output)
                ;   
            }
        } elseif ($delete) {
            if (!$appId) {
                $output->writeln('<error>ID of the application must be provided with option --app_id to delete the app.</error>');

                return 0;
            }

            $application = $apiApllicationConverter->convert($appId);

            $applicationManipulator->delete($application);

            $output->writeln("<info>Application ID $appId deleted successfully !</info>");
        }

        return 0;
    }

    private function showApllicationInformation($apiOauthRepository, ApiAccount $account, ApiApplication $application, $jsonformat, $output)
    {
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
        }
    }
}
