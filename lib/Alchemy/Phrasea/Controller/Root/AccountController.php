<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Controller\Root;

use Alchemy\Geonames\Connector;
use Alchemy\Geonames\Exception\ExceptionInterface as GeonamesExceptionInterface;
use Alchemy\Phrasea\Account\Command\UpdateAccountCommand;
use Alchemy\Phrasea\Account\Command\UpdateFtpCredentialsCommand;
use Alchemy\Phrasea\Application\Helper\EntityManagerAware;
use Alchemy\Phrasea\Application\Helper\NotifierAware;
use Alchemy\Phrasea\Authentication\Phrasea\PasswordEncoder;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Core\Configuration\RegistrationManager;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Form\Login\PhraseaRenewPasswordForm;
use Alchemy\Phrasea\Model\Entities\ApiApplication;
use Alchemy\Phrasea\Model\Entities\FtpCredential;
use Alchemy\Phrasea\Model\Entities\Session;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Manipulator\ApiAccountManipulator;
use Alchemy\Phrasea\Model\Manipulator\ApiApplicationManipulator;
use Alchemy\Phrasea\Model\Manipulator\BasketManipulator;
use Alchemy\Phrasea\Model\Manipulator\TokenManipulator;
use Alchemy\Phrasea\Model\Manipulator\UserManipulator;
use Alchemy\Phrasea\Model\Repositories\ApiAccountRepository;
use Alchemy\Phrasea\Model\Repositories\ApiApplicationRepository;
use Alchemy\Phrasea\Model\Repositories\BasketRepository;
use Alchemy\Phrasea\Model\Repositories\FeedPublisherRepository;
use Alchemy\Phrasea\Model\Repositories\TokenRepository;
use Alchemy\Phrasea\Notification\Mail\MailRequestAccountDelete;
use Alchemy\Phrasea\Notification\Mail\MailRequestEmailUpdate;
use Alchemy\Phrasea\Notification\Mail\MailSuccessAccountDelete;
use Alchemy\Phrasea\Notification\Receiver;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session as SymfonySession;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AccountController extends Controller
{
    use EntityManagerAware;
    use NotifierAware;

    public function resetPassword(Request $request)
    {
        $form = $this->app->form(new PhraseaRenewPasswordForm());

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();
                $user = $this->getAuthenticatedUser();

                if ($this->getPasswordEncoder()->isPasswordValid($user->getPassword(), $data['oldPassword'], $user->getNonce())) {
                    $this->getUserManipulator()->setPassword($user, $data['password']);
                    $this->app->addFlash('success', $this->app->trans('login::notification: Mise a jour du mot de passe avec succes'));

                    return $this->app->redirectPath('account');
                } else {
                    $this->app->addFlash('error', $this->app->trans('Invalid password provided'));
                }
            }
        }

        return $this->render('account/change-password.html.twig', array_merge(
            $this->getLoginController()->getDefaultTemplateVariables($request),
            ['form' => $form->createView()]
        ));
    }

    /**
     * Reset Email
     *
     * @param  Request $request
     * @return RedirectResponse
     */
    public function resetEmail(Request $request)
    {
        if (null === ($password = $request->request->get('form_password'))
            || null === ($email = trim($request->request->get('form_email')))
            || null === ($emailConfirm = trim($request->request->get('form_email_confirm')))
        ) {
            throw new BadRequestHttpException($this->app->trans('Could not perform request, please contact an administrator.'));
        }

        $user = $this->getAuthenticatedUser();

        if (!$this->getPasswordEncoder()->isPasswordValid($user->getPassword(), $password, $user->getNonce())) {
            $this->app->addFlash('error', $this->app->trans('admin::compte-utilisateur:ftp: Le mot de passe est errone'));

            return $this->app->redirectPath('account_reset_email');
        }

        if (!\Swift_Validate::email($email)) {
            $this->app->addFlash('error', $this->app->trans('forms::l\'email semble invalide'));

            return $this->app->redirectPath('account_reset_email');
        }

        if ($email !== $emailConfirm) {
            $this->app->addFlash('error', $this->app->trans('forms::les emails ne correspondent pas'));

            return $this->app->redirectPath('account_reset_email');
        }

        $token = $this->getTokenManipulator()->createResetEmailToken($user, $email);
        $url = $this->app->url('account_reset_email', ['token' => $token->getValue()]);

        try {
            $receiver = Receiver::fromUser($user);
        } catch (InvalidArgumentException $e) {
            $this->app->addFlash('error', $this->app->trans('phraseanet::erreur: echec du serveur de mail'));

            return $this->app->redirectPath('account_reset_email');
        }

        $mail = MailRequestEmailUpdate::create($this->app, $receiver, null);
        $mail->setButtonUrl($url);
        $mail->setExpiration($token->getExpiration());

        if (($locale = $user->getLocale()) != null) {
            $mail->setLocale($locale);
        }

        $this->deliver($mail);

        $this->app->addFlash('info', $this->app->trans('admin::compte-utilisateur un email de confirmation vient de vous etre envoye. Veuillez suivre les instructions contenue pour continuer'));

        return $this->app->redirectPath('account');
    }

    /**
     * Display reset email form
     *
     * @param  Request $request
     * @return Response
     */
    public function displayResetEmailForm(Request $request)
    {
        if (null !== $tokenValue = $request->query->get('token')) {
            if (null === $token = $this->getTokenRepository()->findValidToken($tokenValue)) {
                $this->app->addFlash('error', $this->app->trans('admin::compte-utilisateur: erreur lors de la mise a jour'));

                return $this->app->redirectPath('account');
            }

            $user = $token->getUser();
            $user->setEmail($token->getData());
            $this->getTokenManipulator()->delete($token);

            $this->app->addFlash('success', $this->app->trans('admin::compte-utilisateur: L\'email a correctement ete mis a jour'));

            return $this->app->redirectPath('account');
        }

        $context = $this->getLoginController()->getDefaultTemplateVariables($request);

        return $this->render('account/reset-email.html.twig', $context);
    }

    /**
     * @return LoginController
     */
    private function getLoginController()
    {
        return $this->app['login.controller'];
    }

    /**
     * Display authorized applications that can access user information
     *
     * @param Request        $request
     * @param ApiApplication $application
     *
     * @return JsonResponse
     */
    public function grantAccess(Request $request, ApiApplication $application)
    {
        if (!$request->isXmlHttpRequest() || !array_key_exists($request->getMimeType('json'), array_flip($request->getAcceptableContentTypes()))) {
            $this->app->abort(400, $this->app->trans('Bad request format, only JSON is allowed'));
        }

        if (null === $account = $this->getApiAccountRepository()->findByUserAndApplication($this->getAuthenticatedUser(), $application)) {
            return $this->app->json(['success' => false]);
        }

        $manipulator = $this->getApiAccountManipulator();
        if (false === (Boolean) $request->query->get('revoke')) {
            $manipulator->authorizeAccess($account);
        } else {
            $manipulator->revokeAccess($account);
        }

        return $this->app->json(['success' => true]);
    }

    /**
     * Display account base access
     *
     * @return Response
     */
    public function accountAccess()
    {
        //var_dump($this->getRegistrationManager()->getRegistrationSummary($this->getAuthenticatedUser()));die;
        return $this->render('account/access.html.twig', [
            'inscriptions' => $this->getRegistrationManager()->getRegistrationSummary($this->getAuthenticatedUser())
        ]);
    }

    /**
     * Display authorized applications that can access user information
     *
     * @return Response
     */
    public function accountAuthorizedApps()
    {
        $data = [];

        $nativeApp = [
            \API_OAuth2_Application_Navigator::CLIENT_NAME,
            \API_OAuth2_Application_OfficePlugin::CLIENT_NAME,
            \API_OAuth2_Application_AdobeCCPlugin::CLIENT_NAME,
        ];

        $user = $this->getAuthenticatedUser();
        foreach (
            $this->getApiApplicationRepository()->findByUser($user) as $application) {
            $account = $this->getApiAccountRepository()->findByUserAndApplication($user, $application);

            if(!in_array($application->getName(), $nativeApp)){
                $data[$application->getId()] = [
                    'application' => $application,
                    'user-account' => $account,
                ];
            }
        }

        return $this->render('account/authorized_apps.html.twig', [
            "applications" => $data,
        ]);
    }

    /**
     * Display account session accesses
     *
     * @return Response
     */
    public function accountSessionsAccess()
    {
        $dql = 'SELECT s FROM Phraseanet:Session s WHERE s.user = :usr_id ORDER BY s.created DESC';

        $query = $this->getEntityManager()->createQuery($dql);
        $query->setMaxResults(100);
        $query->setParameters(['usr_id' => $this->getSession()->get('usr_id')]);
        /** @var Session[] $sessions */
        $sessions = $query->getResult();

        $result = [];
        foreach ($sessions as $session) {
            $info = '';
            try {
                $geoname = $this->getGeonameConnector()->ip($session->getIpAddress());
                $country = $geoname->get('country');
                $city = $geoname->get('city');
                $region = $geoname->get('region');

                $countryName = isset($country['name']) ? $country['name'] : null;
                $regionName = isset($region['name']) ? $region['name'] : null;

                if (null !== $city) {
                    $info = $city . ($countryName ? ' (' . $countryName . ')' : null);
                } elseif (null !== $regionName) {
                    $info = $regionName . ($countryName ? ' (' . $countryName . ')' : null);
                } elseif (null !== $countryName) {
                    $info = $countryName;
                } else {
                    $info = '';
                }
            } catch (GeonamesExceptionInterface $e) {

            }

            $result[] = [
                'session' => $session,
                'info'    => $info,
            ];
        }

        return $this->render('account/sessions.html.twig', ['sessions' => $result]);
    }

    /**
     * Display account form
     *
     * @return Response
     */
    public function displayAccount()
    {
        $manager = $this->getEventManager();
        $user = $this->getAuthenticatedUser();

        $repo_baskets = $this->getBasketRepository();
        $baskets = $repo_baskets->findActiveValidationAndBasketByUser($user);

        $apiAccounts = $this->getApiAccountRepository()->findByUser($user);

        $ownedFeeds = $this->getFeedPublisherRepository()->findBy(['user' => $user, 'owner' => true]);

        $initiatedValidations = $this->getBasketRepository()->findby(['vote_initiator' => $user, ]);

        return $this->render('account/account.html.twig', [
            'user'                  => $user,
            'evt_mngr'              => $manager,
            'notifications'         => $manager->list_notifications_available($user),
            'baskets'               => $baskets,
            'api_accounts'          => $apiAccounts,
            'owned_feeds'           => $ownedFeeds,
            'initiated_validations' => $initiatedValidations,
        ]);
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function processDeleteAccount(Request $request)
    {
        $user = $this->getAuthenticatedUser();

        if($this->app['conf']->get(['user_account', 'deleting_policies', 'email_confirmation'])) {

            // send email confirmation

            try {
                $receiver = Receiver::fromUser($user);
            } catch (InvalidArgumentException $e) {
                $this->app->addFlash('error', $this->app->trans('phraseanet::erreur: echec du serveur de mail'));

                return $this->app->redirectPath('account');
            }

            $token = $this->getTokenManipulator()->createAccountDeleteToken($user, $user->getEmail());
            $url = $this->app->url('account_confirm_delete', ['token' => $token->getValue()]);


            $mail = MailRequestAccountDelete::create($this->app, $receiver);
            $mail->setUserOwner($user);
            $mail->setButtonUrl($url);
            $mail->setExpiration($token->getExpiration());

            if (($locale = $user->getLocale()) != null) {
                $mail->setLocale($locale);
            }

            $this->deliver($mail);

            $this->app->addFlash('info', $this->app->trans('phraseanet::account: A confirmation e-mail has been sent. Please follow the instructions contained to continue account deletion'));

            return $this->app->redirectPath('account');

        } else {
            $this->doDeleteAccount($user);

            $response = $this->app->redirectPath('homepage', [
                'redirect' => $request->query->get("redirect")
            ]);

            $response->headers->clearCookie('persistent');
            $response->headers->clearCookie('last_act');

            return $response;
        }

    }

    public function confirmDeleteAccount(Request $request)
    {
        if (($tokenValue = $request->query->get('token')) !== null ) {
            if (null === $token = $this->getTokenRepository()->findValidToken($tokenValue)) {
                $this->app->addFlash('error', $this->app->trans('Token not found'));

                return $this->app->redirectPath('account');
            }

            $user = $token->getUser();
            // delete account and datas
            $this->doDeleteAccount($user);

            $this->getTokenManipulator()->delete($token);
        }

        $response = $this->app->redirectPath('homepage', [
            'redirect' => $request->query->get("redirect")
        ]);

        $response->headers->clearCookie('persistent');
        $response->headers->clearCookie('last_act');

        return $response;
    }

    /**
     * Update account information
     *
     * @param  Request $request The current request
     * @return Response
     */
    public function updateAccount(Request $request)
    {
        $registrations = $request->request->get('registrations', []);

        if (false === is_array($registrations)) {
            $this->app->abort(400, '"registrations" parameter must be an array of base ids.');
        }

        $user = $this->getAuthenticatedUser();

        if (0 !== count($registrations)) {
            foreach ($registrations as $baseId) {
                $this->getRegistrationManipulator()
                    ->createRegistration($user, \collection::getByBaseId($this->app, $baseId));
            }
            $this->app->addFlash('success', $this->app->trans('Your registration requests have been taken into account.'));
        }

        $accountFields = [
            'form_gender',
            'form_firstname',
            'form_lastname',
            'form_address',
            'form_zip',
            'form_phone',
            'form_fax',
            'form_function',
            'form_company',
            'form_activity',
            'form_geonameid',
            'form_addressFTP',
            'form_loginFTP',
            'form_pwdFTP',
            'form_destFTP',
            'form_prefixFTPfolder'
        ];

        $service = $this->app['accounts.service'];

        if (0 === count(array_diff($accountFields, array_keys($request->request->all())))) {
            $command = new UpdateAccountCommand();
            $command
                ->setGender((int) $request->request->get("form_gender"))
                ->setFirstName($request->request->get("form_firstname"))
                ->setLastName($request->request->get("form_lastname"))
                ->setAddress($request->request->get("form_address"))
                ->setZipCode($request->request->get("form_zip"))
                ->setPhone($request->request->get("form_phone"))
                ->setFax($request->request->get("form_fax"))
                ->setJob($request->request->get("form_function"))
                ->setCompany($request->request->get("form_company"))
                ->setPosition($request->request->get("form_activity"))
                ->setNotifications((Boolean) $request->request->get("mail_notifications"));

            $service->updateAccount($command);

            $this->getUserManipulator()->setGeonameId($user, $request->request->get("form_geonameid"));

            $ftpCredential = $user->getFtpCredential();

            if (null === $ftpCredential) {
                $ftpCredential = new FtpCredential();
                $ftpCredential->setUser($user);
            }

            $command = new UpdateFtpCredentialsCommand();

            $command->setEnabled($request->request->get("form_activeFTP"));
            $command->setAddress($request->request->get("form_addressFTP"));
            $command->setLogin($request->request->get("form_loginFTP"));
            $command->setPassword($request->request->get("form_pwdFTP"));
            $command->setPassiveMode($request->request->get("form_passifFTP"));
            $command->setFolder($request->request->get("form_destFTP"));
            $command->setFolderPrefix($request->request->get("form_prefixFTPfolder"));

            $service->updateFtpSettings($command);

            $this->app->addFlash('success', $this->app->trans('login::notification: Changements enregistres'));
        }

        $requestedNotifications = (array) $request->request->get('notifications', []);

        $manipulator = $this->getUserManipulator();
        foreach ($this->getEventManager()->list_notifications_available($user) as $notifications) {
            foreach ($notifications as $notification) {
                $manipulator->setNotificationSetting($user, $notification['id'], isset($requestedNotifications[$notification['id']]));
            }
        }

        return $this->app->redirectPath('account');
    }

    /**
     * @param User $user
     */
    private function doDeleteAccount(User $user)
    {
        // basket
        $repo_baskets = $this->getBasketRepository();
        $baskets = $repo_baskets->findActiveByUser($user);
        $this->getBasketManipulator()->removeBaskets($baskets);

        // application
        $applications = $this->getApiApplicationRepository()->findByUser($user);

        $this->getApiApplicationManipulator()->deleteApiApplications($applications);


        //  get list of old granted base_id then revoke access and delete phraseanet user account

        $oldGrantedBaseIds = array_keys($this->app->getAclForUser($user)->get_granted_base());

        $list = array_keys($this->app['repo.collections-registry']->getBaseIdMap());

        try {
            $this->app->getAclForUser($user)->revoke_access_from_bases($list);
        }
        catch (\Exception $e) {
            // one or more access could not be revoked ? the user will not be phantom
            $this->app->addFlash('error', $this->app->trans('phraseanet::error: failed to revoke some user access'));
        }

        if ($this->app->getAclForUser($user)->is_phantom()) {
            // send confirmation email: the account has been deleted

            try {
                $receiver = Receiver::fromUser($user);
                $mail = MailSuccessAccountDelete::create($this->app, $receiver);
            }
            catch (InvalidArgumentException $e) {
                $this->app->addFlash('error', $this->app->trans('phraseanet::erreur: echec du serveur de mail'));
                $mail = null;
            }

            $mail = MailSuccessAccountDelete::create($this->app, $receiver);

            $this->app['manipulator.user']->delete($user, [$user->getId() => $oldGrantedBaseIds]);
            if($mail) {
                if (($locale = $user->getLocale()) != null) {
                    $mail->setLocale($locale);
                }
                $this->deliver($mail);
            }

            $this->getAuthenticator()->closeAccount();
            $this->app->addFlash('info', $this->app->trans('phraseanet::account The account has been deleted'));
        }
    }

    /**
     * @return PasswordEncoder
     */
    private function getPasswordEncoder()
    {
        return $this->app['auth.password-encoder'];
    }

    /**
     * @return UserManipulator
     */
    private function getUserManipulator()
    {
        return $this->app['manipulator.user'];
    }

    /**
     * @return TokenManipulator
     */
    private function getTokenManipulator()
    {
        return $this->app['manipulator.token'];
    }

    /**
     * @return TokenRepository
     */
    private function getTokenRepository()
    {
        return $this->app['repo.tokens'];
    }

    /**
     * @return ApiAccountRepository
     */
    private function getApiAccountRepository()
    {
        return $this->app['repo.api-accounts'];
    }

    /**
     * @return ApiAccountManipulator
     */
    private function getApiAccountManipulator()
    {
        return $this->app['manipulator.api-account'];
    }

    /**
     * @return RegistrationManager
     */
    private function getRegistrationManager()
    {
        return $this->app['registration.manager'];
    }

    /**
     * @return ApiApplicationRepository
     */
    private function getApiApplicationRepository()
    {
        return $this->app['repo.api-applications'];
    }

    /**
     * @return SymfonySession
     */
    private function getSession()
    {
        return $this->app['session'];
    }

    /**
     * @return Connector
     */
    private function getGeonameConnector()
    {
        return $this->app['geonames.connector'];
    }

    /**
     * @return mixed
     */
    private function getRegistrationManipulator()
    {
        return $this->app['manipulator.registration'];
    }

    /**
     * @return \eventsmanager_broker
     */
    private function getEventManager()
    {
        return $this->app['events-manager'];
    }

    /**
     * @return BasketManipulator
     */
    private function getBasketManipulator()
    {
        return $this->app['manipulator.basket'];
    }

    /**
     * @return BasketRepository
     */
    private function getBasketRepository()
    {
        return $this->app['repo.baskets'];
    }

    /**
     * @return ApiApplicationManipulator
     */
    private function getApiApplicationManipulator()
    {
        return $this->app['manipulator.api-application'];
    }

    /**
     * @return FeedPublisherRepository
     */
    private function getFeedPublisherRepository()
    {
        return $this->app['repo.feed-publishers'];
    }

}
