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
use Alchemy\Phrasea\Model\Manipulator\ApiApplicationManipulator;
use Symfony\Component\Console\Helper\DialogHelper;
use Alchemy\Phrasea\ControllerProvider\Api\V2;
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

class UserApplicationsCommand extends Command
{

    /**
     * Constructor
     */
    public function __construct($name = null)
    {
        parent::__construct('user:applications');

        $this->setDescription('List, Create, Edit, Delete application in Phraseanet <comment>(experimental)</comment>')
            ->addOption('list', null, InputOption::VALUE_NONE, 'List all applications or user applications if --user_id is set')
            ->addOption('create', null, InputOption::VALUE_NONE, 'Create application for user in Phraseanet')
            ->addOption('edit', null, InputOption::VALUE_NONE, 'Edit application in Phraseanet work only if app_id is set')
            ->addOption('delete', null, InputOption::VALUE_NONE, 'Delete application in Phraseanet, require an app_id')
            ->addOption('user_id', 'u', InputOption::VALUE_REQUIRED, 'The Id of user owner of application (user_id), required to Create, Edit and Delete.')
            ->addOption('app_id', 'a', InputOption::VALUE_REQUIRED, 'The application ID, required for Edit and Delete')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'The desired name for application, required for Create and Edit.')
            ->addOption('type', 't', InputOption::VALUE_OPTIONAL, 'The kind of application, Desktop or Web.',ApiApplication::WEB_TYPE)
            ->addOption('description', 'd', InputOption::VALUE_REQUIRED, 'The desired description for application.')
            ->addOption('website', 'w', InputOption::VALUE_OPTIONAL, 'The desired url, eg: -w "https://www.alchemy.fr".')
            ->addOption('callback', 'c', InputOption::VALUE_OPTIONAL, 'The desired endpoint for callback, required for web kind eg: -c "https://www.alchemy.fr/callback"')
            ->addOption('webhook_url', null, InputOption::VALUE_REQUIRED, 'The webhook url')
            ->addOption('active', null, InputOption::VALUE_OPTIONAL, 'Activate or deactivate  the app, values true or false', 'true')
            ->addOption('webhook_active', null, InputOption::VALUE_OPTIONAL, 'Activate or deactivate webhook, values true or false', 'false')
            ->addOption('generate_token', null, InputOption::VALUE_NONE, 'Generate or regenerate the access token')
            ->addOption('password_oauth2_gt', null, InputOption::VALUE_OPTIONAL, 'Activate or deactivate password OAuth2 grant type , values true or false', 'false')           
            ->addOption('jsonformat', null, InputOption::VALUE_NONE, 'Output in json format')

            ->setHelp('');

        return $this;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $userId             = $input->getOption('user_id');
        $appId              = $input->getOption('app_id');
        $name               = $input->getOption('name');
        $type               = $input->getOption('type');
        $description        = $input->getOption('description');
        $website            = $input->getOption('website');
        $urlCallback        = $input->getOption('callback');
        $webhookUrl         = $input->getOption('webhook_url');
        $active             = $input->getOption('active');
        $webhookActive      = $input->getOption('webhook_active');
        $generateToken      = $input->getOption('generate_token');
        $passwordOauth2Gt   = $input->getOption('password_oauth2_gt');
        $create             = $input->getOption('create');
        $edit               = $input->getOption('edit');
        $delete             = $input->getOption('delete');
        $list               = $input->getOption('list');
        $jsonformat         = $input->getOption('jsonformat');

        /** @var ApiApplicationManipulator $applicationManipulator */
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

                if ($generateToken) {
                    $apiOauthTokenManipulator->create($account);
                }

                if ($passwordOauth2Gt) {
                    if (in_array($passwordOauth2Gt, ['true', 'false'])) {
                        $application->setGrantPassword(($passwordOauth2Gt == 'true') ? true : false);
                        $applicationManipulator->update($application);
                    } else {
                        $output->writeln('<error> Value of option --password_oauth2_gt should be "true" or "false"</error>');
                
                        return 0;
                    }
                }

                if ($webhookUrl) {
                    $applicationManipulator->setWebhookUrl($application, $webhookUrl);
                    $applicationManipulator->update($application);
                }

                if ($webhookActive !== null) {
                    if (in_array($webhookActive, ['true', 'false'])) {
                        if ($webhookActive == 'true' && !empty($application->getWebhookUrl())) {
                            $application->setWebhookActive(true);
                        } else {
                            $application->setWebhookActive(false);
                        }

                        $applicationManipulator->update($application);
                    } else {
                        $output->writeln('<error>Value of option --webhook_active should be "true" or "false"</error>');

                        return 0;
                    }
                }

                if ($active) {
                    if (in_array($active, ['true', 'false'])) {
                        $application->setActivated(($active == 'true') ? true : false);
                        $applicationManipulator->update($application);
                    } else {
                        $output->writeln('<error>Value of option --active should be "true" or "false"</error>');
                
                        return 0;
                    }
                } else {
                    $application->setActivated(true);
                    $applicationManipulator->update($application);
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

            $application = $apiApllicationConverter->convert($appId);
            $account =  $accountRepository->findByUserAndApplication($application->getCreator(), $application);

            if (!$account) {
                $output->writeln('<error>ApiAccount not found!</error>');

                return 0;
            }

            if ($name) {
                $application->setName($name);
            }
            if ($type) {
                $applicationManipulator->setType($application, $type);
                if ($type == ApiApplication::DESKTOP_TYPE) {
                    $applicationManipulator->setRedirectUri($application, ApiApplication::NATIVE_APP_REDIRECT_URI);
                }
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
            if ($generateToken) {
                if (null !== $devToken = $apiOauthRepository->findDeveloperToken($account)) {
                    $apiOauthTokenManipulator->renew($devToken);
                } else {
                    $apiOauthTokenManipulator->create($account);
                }
            }
            if ($passwordOauth2Gt) {
                if (in_array($passwordOauth2Gt, ['true', 'false'])) { 
                    $application->setGrantPassword(($passwordOauth2Gt == 'true') ? true : false);
                } else {
                    $output->writeln('<error> Value of option --password_oauth2_gt should be "true" or "false"</error>');
            
                    return 0;
                }
            }
            if ($webhookUrl) {
                $applicationManipulator->setWebhookUrl($application, $webhookUrl);
            }

            if ($webhookActive !== null) {
                if (in_array($webhookActive, ['true', 'false'])) {
                    if ($webhookActive == 'true' && !empty($application->getWebhookUrl())) {
                        $application->setWebhookActive(true);
                    } else {
                        $application->setWebhookActive(false);
                    }

                    $applicationManipulator->update($application);
                } else {
                    $output->writeln('<error>Value of option --webhook_active should be "true" or "false"</error>');

                    return 0;
                }
            }

            if ($active) {
                if (in_array($active, ['true', 'false'])) {
                    $application->setActivated(($active == 'true') ? true : false);
                } else {
                    $output->writeln('<error>Value of option --active should be "true" or "false"</error>');
            
                    return 0;
                }
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
                    $application->getClientSecret(),
                    $application->getRedirectUri(),
                    ($token) ? $token->getOauthToken() : '-',
                    $application->isPasswordGranted() ? "true":  "false"
                ];
            }

            $applicationTable = $this->getHelperSet()->get('table');
            $headers = ['app_id', 'user_id', 'name', 'client_id', 'client_secret', 'callback_url', 'generated token', 'grant_password status'];

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

            if (is_null($application->getCreator())) {
                /** @var DialogHelper $dialog */
                $dialog = $this->getHelperSet()->get('dialog');

                $continue = $dialog->askConfirmation($output, "<question>It's a special phraseanet application, do you want really to delete it? (N/y)</question>", false);

                if (!$continue) {
                    $output->writeln("<info>See you later !</info>");

                    return 0;
                }
            }

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

        $headers = ['client_secret', 'client_id', 'Authorize endpoint url', 'Access endpoint', 'generated token', 'grant_password status'];
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
