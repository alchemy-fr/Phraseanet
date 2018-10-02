<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Admin;

use Alchemy\Phrasea\Application\Helper\NotifierAware;
use Alchemy\Phrasea\Application\Helper\UserQueryAware;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Core\Response\CSVFileResponse;
use Alchemy\Phrasea\Helper\User as UserHelper;
use Alchemy\Phrasea\Model\Entities\FtpCredential;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Entities\WebhookEvent;
use Alchemy\Phrasea\Model\Manipulator\RegistrationManipulator;
use Alchemy\Phrasea\Model\Manipulator\UserManipulator;
use Alchemy\Phrasea\Model\NativeQueryProvider;
use Alchemy\Phrasea\Model\Repositories\RegistrationRepository;
use Alchemy\Phrasea\Model\Repositories\UserRepository;
use Alchemy\Phrasea\Notification\Mail\MailSuccessEmailUpdate;
use Alchemy\Phrasea\Notification\Receiver;
use Goodby\CSV\Export\Protocol\ExporterInterface;
use Goodby\CSV\Import\Standard\Interpreter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    use NotifierAware;
    use UserQueryAware;

    public function editRightsAction(Request $request)
    {
        $rights = $this->getUserEditHelper($request);
        return $this->render('admin/editusers.html.twig', $rights->get_users_rights());
    }

    public function resetRightsAction(Request $request)
    {
        try {
            $data = ['error' => false];

            $helper = $this->getUserEditHelper($request);
            $helper->resetRights();
        } catch (\Exception $e) {
            $data['error'] = true;
            $data['message'] = $e->getMessage();
        }

        return $this->app->json($data);
    }

    public function deleteUserAction(Request $request)
    {
        $module = $this->getUserEditHelper($request);
        $module->delete_users();

        return $this->app->redirectPath('admin_users_search');
    }

    public function applyRightsAction(Request $request)
    {
        $data = ['error' => true];

        try {
            $rights = $this->getUserEditHelper($request);

            $resetBeforeApply = (bool) $request->request->get('reset_before_apply', false);
            if (!$resetBeforeApply) {
                $rights->apply_rights();
            }

            if ($request->request->get('template')) {
                if ($resetBeforeApply) {
                    $rights->resetRights();
                }
                $rights->apply_template();
            }

            $rights->apply_infos();

            $data = ['error' => false];
        } catch (\Exception $e) {
            $data['message'] = $e->getMessage();
        }

        return $this->app->json($data);
    }

    public function editQuotasRightsAction(Request $request)
    {
        $rights = $this->getUserEditHelper($request);
        return $this->render('admin/editusers_quotas.html.twig', $rights->get_quotas());
    }

    public function applyQuotasAction(Request $request)
    {
        $rights = $this->getUserEditHelper($request);
        $rights->apply_quotas();

        return $this->app->json(['message' => '', 'error' => false]);
    }

    public function editTimeLimitAction(Request $request)
    {
        $rights = $this->getUserEditHelper($request);

        return $this->render('admin/editusers_timelimit.html.twig', $rights->get_time());
    }

    public function editTimeLimitSbasAction(Request $request)
    {
        $rights = $this->getUserEditHelper($request);

        return $this->render('admin/editusers_timelimit_sbas.html.twig', $rights->get_time_sbas());
    }

    public function applyTimeAction(Request $request)
    {
        $rights = $this->getUserEditHelper($request);
        $rights->apply_time();

        return $this->app->json(['message' => '', 'error' => false]);
    }

    public function editMasksAction(Request $request)
    {
        $rights = $this->getUserEditHelper($request);

        return $this->render('admin/editusers_masks.html.twig', $rights->get_masks());
    }

    public function applyMasksAction(Request $request)
    {
        $rights = $this->getUserEditHelper($request);
        $rights->apply_masks();

        return $this->app->json(['message' => '', 'error' => false]);
    }

    public function searchAction(Request $request)
    {
        return $this->render('admin/users.html.twig', $this->getUserManageHelper($request)->search());
    }

    public function searchExportAction(Request $request)
    {
        $users = $this->getUserManageHelper($request);
        $userTable = [
            [
                'ID',
                'Login',
                'Last Name',
                'First Name',
                'E-Mail',
                'Created',
                'Updated',
                'Address',
                'City',
                'Zip',
                'Country',
                'Phone',
                'Fax',
                'Job',
                'Company',
                'Position'
            ]
        ];

        foreach ($users->export() as $user) {
            $userTable[] = [
                $user->getId(),
                $user->getLogin(),
                $user->getLastName(),
                $user->getFirstName(),
                $user->getEmail(),
                $user->getCreated()->format(DATE_ATOM),
                $user->getUpdated()->format(DATE_ATOM),
                $user->getAddress(),
                $user->getCity(),
                $user->getZipCode(),
                $user->getCountry(),
                $user->getPhone(),
                $user->getFax(),
                $user->getJob(),
                $user->getCompany(),
                $user->getActivity()
            ];
        }

        $filename = sprintf('user_export_%s.csv', date('Ymd'));
        $exporter = $this->getCsvExporter();

        return new CSVFileResponse($filename, function () use ($exporter, $userTable) {
            $exporter->export('php://output', $userTable);
        });
    }

    public function applyTemplateAction(Request $request)
    {
        $users = $this->getUserEditHelper($request);
        if ($request->request->get('reset_before_apply')) {
            $users->resetRights();
        }
        $users->apply_template();

        return $this->app->redirectPath('admin_users_search');
    }

    public function typeAheadSearchAction(Request $request)
    {
        $user_query = $this->createUserQuery();

        $like_value = $request->query->get('term');
        $rights = $request->query->get('filter_rights') ? : [];
        $have_right = $request->query->get('have_right') ? : [];
        $have_not_right = $request->query->get('have_not_right') ? : [];
        $on_base = $request->query->get('on_base') ? : [];

        $eligible_users = $user_query
            ->on_sbas_where_i_am($this->getAclForConnectedUser(), $rights)
            ->like(\User_Query::LIKE_EMAIL, $like_value)
            ->like(\User_Query::LIKE_FIRSTNAME, $like_value)
            ->like(\User_Query::LIKE_LASTNAME, $like_value)
            ->like(\User_Query::LIKE_LOGIN, $like_value)
            ->like_match(\User_Query::LIKE_MATCH_OR)
            ->who_have_right($have_right)
            ->who_have_not_right($have_not_right)
            ->on_base_ids($on_base)
            ->execute()
            ->get_results();

        $data = [];
        foreach ($eligible_users as $user) {
            $data[] = [
                'email' => $user->getEmail() ? : '',
                'login' => $user->getLogin() ? : '',
                'name'  => $user->getDisplayName(),
                'id'    => $user->getId(),
            ];
        }

        return $this->app->json($data);
    }

    public function createAction(Request $request)
    {
        $data = ['error'   => false, 'message' => '', 'data'    => null];
        try {
            $module = $this->getUserManageHelper($request);
            if ($request->request->get('template') == '1') {
                $user = $module->createTemplate();
            } else {
                $user = $module->createNewUser();
            }
            if (!$user instanceof User) {
                throw new \Exception('Unknown error');
            }

            $data['data'] = $user->getId();
        } catch (\Exception $e) {
            $data['error'] = true;
            if ($request->request->get('template') == '1') {
                $data['message'] = $this->app->trans('Unable to create template, the name is already used.');
            } else {
                $data['message'] = $this->app->trans('Unable to create the user.');
            }
        }

        return $this->app->json($data);
    }

    public function exportAction(Request $request)
    {
        $user_query = $this->createUserQuery();

        $like_value = $request->request->get('like_value');
        $like_field = $request->request->get('like_field');
        $on_base = $request->request->get('base_id') ? : null;
        $on_sbas = $request->request->get('sbas_id') ? : null;

        $eligible_users = $user_query->on_bases_where_i_am($this->getAclForConnectedUser(), [\ACL::CANADMIN])
            ->like($like_field, $like_value)
            ->on_base_ids($on_base)
            ->on_sbas_ids($on_sbas);

        $offset = 0;
        $buffer = [];
        $buffer[] = [
            'ID',
            'Login',
            $this->app->trans('admin::compte-utilisateur nom'),
            $this->app->trans('admin::compte-utilisateur prenom'),
            $this->app->trans('admin::compte-utilisateur email'),
            'CreationDate',
            'ModificationDate',
            $this->app->trans('admin::compte-utilisateur adresse'),
            $this->app->trans('admin::compte-utilisateur ville'),
            $this->app->trans('admin::compte-utilisateur code postal'),
            $this->app->trans('admin::compte-utilisateur pays'),
            $this->app->trans('admin::compte-utilisateur telephone'),
            $this->app->trans('admin::compte-utilisateur fax'),
            $this->app->trans('admin::compte-utilisateur poste'),
            $this->app->trans('admin::compte-utilisateur societe'),
            $this->app->trans('admin::compte-utilisateur activite'),
        ];
        do {
            $eligible_users->limit($offset, 20);
            $offset += 20;

            $results = $eligible_users->execute()->get_results();

            foreach ($results as $user) {
                $buffer[] = [
                    $user->getId(),
                    $user->getLogin(),
                    $user->getLastName(),
                    $user->getFirstName(),
                    $user->getEmail(),
                    $this->app['date-formatter']->format_mysql($user->getCreated()),
                    $this->app['date-formatter']->format_mysql($user->getUpdated()),
                    $user->getAddress(),
                    $user->getCity(),
                    $user->getZipCode(),
                    $user->getCountry(),
                    $user->getPhone(),
                    $user->getFax(),
                    $user->getJob(),
                    $user->getCompany(),
                    $user->getActivity(),
                ];
            }
        } while (count($results) > 0);

        $filename = sprintf('user_export_%s.csv', date('Ymd'));
        $exporter = $this->getCsvExporter();
        return new CSVFileResponse($filename, function () use ($exporter, $buffer) {
            $exporter->export('php://output', $buffer);
        });
    }

    public function displayRegistrationsAction()
    {
        $this->getRegistrationManipulator()->deleteOldRegistrations();

        /** @var UserRepository $userRepository */
        $userRepository = $this->app['repo.users'];
        $authenticatedUser = $this->getAuthenticatedUser();
        $models = $userRepository->findTemplateOwner($authenticatedUser);

        $userRegistrations = [];
        /** @var RegistrationRepository $registrationRepository */
        $registrationRepository = $this->app['repo.registrations'];
        $collections = $this->getAclForConnectedUser()->get_granted_base([\ACL::CANADMIN]);
        $authenticatedUserId = $authenticatedUser->getId();
        foreach ($registrationRepository->getPendingRegistrations($collections) as $registration) {
            $user = $registration->getUser();
            $userId = $user->getId();
            // Can not handle self registration.
            if ($authenticatedUserId == $userId) {
                continue;
            }
            if (!isset($userRegistrations[$userId])) {
                $userRegistrations[$userId] = ['user' => $user, 'registrations' => []];
            }
            $userRegistrations[$userId]['registrations'][$registration->getBaseid()] = $registration;
        }

        return $this->render('admin/user/registrations.html.twig', [
            'user_registrations' => $userRegistrations,
            'models' => $models,
        ]);
    }

    public function submitRegistrationAction(Request $request)
    {
        $templates = $this->normalizeTemplateArray($request->request->get('template', []));
        $deny = $this->normalizeDenyArray($request->request->get('deny', []), $templates);

        $accepts = $request->request->get('accept', []);
        $accept = $options = [];
        foreach ($accepts as $acc) {
            $acc = explode('_', $acc);
            if (count($acc) == 2 && !isset($templates[$acc[0]])) {
                $accept[$acc[0]][$acc[1]] = $acc[1];
                $options[$acc[0]][$acc[1]] = ['HD' => false, 'WM' => false];
            }
        }

        foreach ($request->request->get('accept_hd', []) as $accHD) {
            $accHD = explode('_', $accHD);
            if (count($accHD) == 2 && isset($accept[$accHD[0]]) && isset($options[$accHD[0]][$accHD[1]])) {
                $options[$accHD[0]][$accHD[1]]['HD'] = true;
            }
        }

        foreach ($request->request->get('watermark', []) as $wm) {
            $wm = explode('_', $wm);
            if (count($wm) == 2 && isset($accept[$wm[0]]) && isset($options[$wm[0]][$wm[1]])) {
                $options[$wm[0]][$wm[1]]['WM'] = true;
            }
        }

        $registrationManipulator = $this->getRegistrationManipulator();
        if (count($templates) > 0 || count($deny) > 0 || count($accept) > 0) {
            $cacheToUpdate = $done = [];

            /** @var UserRepository $userRepository */
            $userRepository = $this->app['repo.users'];
            $searchedUserIds = array_unique(array_merge(
                array_keys($templates),
                array_keys($deny),
                array_keys($accept)
            ));
            // Load all user entities needed afterwards
            $userRepository->findBy(['id' => $searchedUserIds]);
            foreach ($templates as $usr => $template_id) {
                /** @var User $user */
                $user = $userRepository->find($usr);
                if (null === $user) {
                    $this->app->abort(400, sprintf("User with id % in provided in 'template' request variable could not be found", $usr));
                }
                $cacheToUpdate[$usr] = $user;

                /** @var User $user_template */
                $user_template = $userRepository->find($template_id);
                $collections = $this->getAclForUser($user_template)->get_granted_base();
                $baseIds = array_keys($collections);

                $this->getAclForUser($user)->apply_model($user_template, $baseIds);

                foreach ($collections as $collection) {
                    $done[$usr][$collection->get_base_id()] = true;
                }

                $registrationManipulator->deleteUserRegistrations($user, $collections);
            }

            /** @var RegistrationRepository $registrationRepository */
            $registrationRepository = $this->app['repo.registrations'];
            foreach ($deny as $usr => $bases) {
                /** @var User $user */
                $user = $userRepository->find($usr);
                if (null === $user) {
                    $this->app->abort(400, sprintf("User with id % in provided in 'deny' request variable could not be found", $usr));
                }
                $cacheToUpdate[$usr] = $user;
                foreach (
                    $registrationRepository->getUserRegistrations(
                    $user,
                    array_map(function ($baseId) {
                        return \collection::getByBaseId($this->app, $baseId);
                    }, $bases)
                ) as $registration) {
                    $registrationManipulator->rejectRegistration($registration);
                    $done[$usr][$registration->getBaseId()] = false;
                }
            }

            foreach ($accept as $usr => $bases) {
                /** @var User $user */
                $user = $userRepository->find($usr);
                if (null === $user) {
                    $this->app->abort(400, sprintf("User with id % in provided in 'accept' request variable could not be found", $usr));
                }
                $cacheToUpdate[$usr] = $user;
                foreach ($registrationRepository->getUserRegistrations(
                    $user,
                    array_map(function ($baseId) {
                        return \collection::getByBaseId($this->app, $baseId);
                    }, $bases)
                ) as $registration) {
                    $done[$usr][$registration->getBaseId()] = true;
                    $registrationManipulator->acceptRegistration(
                        $registration,
                        $options[$usr][$registration->getBaseId()]['HD'],
                        $options[$usr][$registration->getBaseId()]['WM']
                    );
                }
            }

            array_walk($cacheToUpdate, function (User $user) {
                $this->getAclForUser($user)->delete_data_from_cache();
            });
            unset ($cacheToUpdate);

            foreach ($done as $usr => $bases) {
                $user = $userRepository->find($usr);
                $acceptColl = $denyColl = [];

                $hookName = WebhookEvent::USER_REGISTRATION_REJECTED;
                $hookType = WebhookEvent::USER_REGISTRATION_TYPE;
                $hookData = [
                    'user_id' => $user->getId(),
                    'granted' => [],
                    'rejected' => []
                ];

                foreach ($bases as $bas => $isok) {
                    $collection = \collection::getByBaseId($this->app, $bas);
                    $label = $collection->get_label($this->app['locale']);

                    if ($isok) {
                        $acceptColl[] = $label;
                        $hookData['granted'][$bas] = $label;
                        $hookName = WebhookEvent::USER_REGISTRATION_GRANTED;
                    } else {
                        $denyColl[] = $label;
                        $hookData['rejected'][$bas] = $label;
                    }
                }

                $this->app['manipulator.webhook-event']->create($hookName, $hookType, $hookData);

                if ($user->hasMailNotificationsActivated() && (0 !== count($acceptColl) || 0 !== count($denyColl))) {
                    $message = '';
                    if (0 !== count($acceptColl)) {
                        $message .= "\n" . $this->app->trans('login::register:email: Vous avez ete accepte sur les collections suivantes : ') . implode(', ', $acceptColl). "\n";
                    }
                    if (0 !== count($denyColl)) {
                        $message .= "\n" . $this->app->trans('login::register:email: Vous avez ete refuse sur les collections suivantes : ') . implode(', ', $denyColl) . "\n";
                    }

                    $receiver = new Receiver(null, $user->getEmail());
                    $mail = MailSuccessEmailUpdate::create($this->app, $receiver, null, $message);

                    $this->deliver($mail);
                }
            }
        }

        return $this->app->redirectPath('users_display_registrations', ['success' => 1]);
    }

    public function displayImportFileAction()
    {
        return $this->render('admin/user/import/file.html.twig');
    }
    
    public function submitImportFileAction(Request $request)
    {
        if ((null === $file = $request->files->get('files')) || !$file->isValid()) {
            return $this->app->redirectPath('users_display_import_file', ['error' => 'file-invalid']);
        }

        $equivalenceToMysqlField = $this->getEquivalenceToMysqlField();
        $loginDefined = $pwdDefined = $mailDefined = false;
        $loginNew = [];
        $out = [
            'ignored_row' => [],
            'errors' => []
        ];
        $nbUsrToAdd = 0;

        $lines = [];
        /** @var Interpreter $interpreter */
        $interpreter = $this->app['csv.interpreter'];
        $interpreter->addObserver(function (array $row) use (&$lines) {
            $lines[] = $row;
        });
        $this->app['csv.lexer']->parse($file->getPathname(), $interpreter);

        $roughColumns = array_shift($lines);

        $columnsSanitized = array_map(function ($columnName) {
            return trim(mb_strtolower($columnName));
        }, $roughColumns);

        $columns = array_filter($columnsSanitized, function ($columnName) use (&$out, $equivalenceToMysqlField) {
            if (!isset($equivalenceToMysqlField[$columnName])) {
                $out['ignored_row'][] = $columnName;

                return false;
            }

            return true;
        });

        foreach ($columns as $columnName) {
            if ($equivalenceToMysqlField[$columnName] === 'usr_login') {
                $loginDefined = true;
            }

            if (($equivalenceToMysqlField[$columnName]) === 'usr_password') {
                $pwdDefined = true;
            }

            if (($equivalenceToMysqlField[$columnName]) === 'usr_mail') {
                $mailDefined = true;
            }
        }

        if (!$loginDefined) {
            return $this->app->redirectPath('users_display_import_file', ['error' => 'row-login']);
        }

        if (!$pwdDefined) {
            return $this->app->redirectPath('users_display_import_file', ['error' => 'row-pwd']);
        }

        if (!$mailDefined) {
            return $this->app->redirectPath('users_display_import_file', ['error' => 'row-mail']);
        }

        /** @var UserRepository $userRepository */
        $userRepository = $this->app['repo.users'];
        foreach ($lines as $nbLine => $line) {
            $loginValid = false;
            $pwdValid = false;
            $mailValid = false;

            foreach ($columns as $nbCol => $colName) {
                if (!isset($equivalenceToMysqlField[$colName])) {
                    unset($lines[$nbCol]);
                    continue;
                }

                $sqlField = $equivalenceToMysqlField[$colName];
                $value = $line[$nbCol];

                if ($sqlField === 'usr_login') {
                    $loginToAdd = $value;
                    if ($loginToAdd === "") {
                        $out['errors'][] = $this->app->trans("Login line %line% is empty", ['%line%' => $nbLine + 1]);
                    } elseif (in_array($loginToAdd, $loginNew)) {
                        $out['errors'][] = $this->app->trans(
                            "Login %login% is already defined in the file at line %line%",
                            ['%login%' => $loginToAdd, '%line%' => $nbLine]
                        );
                    } else {
                        if (null !== $userRepository->findByLogin($loginToAdd)) {
                            $out['errors'][] = $this->app->trans(
                                "Login %login% already exists in database",
                                ['%login%' => $loginToAdd]
                            );
                        } else {
                            $loginValid = true;
                        }
                    }
                }

                if ($loginValid && $sqlField === 'usr_mail') {
                    $mailToAdd = $value;

                    if ($mailToAdd === "") {
                        $out['errors'][] = $this->app->trans("Mail line %line% is empty", ['%line%' => $nbLine + 1]);
                    } elseif (null !== $userRepository->findByEmail($mailToAdd)) {
                        $out['errors'][] = $this->app->trans(
                            "Email '%email%' for login '%login%' already exists in database",
                            ['%email%' => $mailToAdd, '%login%' => $loginToAdd]
                        );
                    } else {
                        $mailValid = true;
                    }
                }

                if ($sqlField === 'usr_password') {
                    $passwordToVerif = $value;

                    if ($passwordToVerif === "") {
                        $out['errors'][] = $this->app->trans("Password is empty at line %line%", ['%line%' => $nbLine]);
                    } else {
                        $pwdValid = true;
                    }
                }
            }

            if ($loginValid && $pwdValid && $mailValid) {
                $loginNew[] = $loginToAdd;
                $nbUsrToAdd++;
            }
        }

        if (count($out['errors']) > 0 && $nbUsrToAdd === 0) {
            return $this->render('admin/user/import/file.html.twig', [
                'errors' => $out['errors']
            ]);
        }

        if ($nbUsrToAdd === 0) {
            return $this->app->redirectPath('users_display_import_file', [
                'error' => 'no-user'
            ]);
        }

        $basList = array_keys($this->getAclForConnectedUser()->get_granted_base([\ACL::COLL_MANAGE]));
        /** @var NativeQueryProvider $query */
        $query = $this->app['orm.em.native-query'];
        $models = $query->getModelForUser($this->getAuthenticatedUser(), $basList);

        return $this->render('/admin/user/import/view.html.twig', [
            'nb_user_to_add'   => $nbUsrToAdd,
            'models'           => $models,
            'lines_serialized' => serialize($lines),
            'columns_serialized' => serialize($columns),
            'errors' => $out['errors']
        ]);
    }

    public function submitImportAction(Request $request)
    {
        $nbCreation = 0;

        if ((null === $serializedColumns = $request->request->get('sr_columns')) || ('' === $serializedColumns)) {
            $this->app->abort(400);
        }

        if ((null === $serializedLines = $request->request->get('sr_lines')) || ('' === $serializedLines)) {
            $this->app->abort(400);
        }

        if (null === $model = $request->request->get("modelToApply")) {
            $this->app->abort(400);
        }

        $lines = unserialize($serializedLines);
        $columns = unserialize($serializedColumns);

        $equivalenceToMysqlField = $this->getEquivalenceToMysqlField();

        foreach ($lines as $nbLine => $line) {
            $curUser = [];
            foreach ($columns as $nbCol => $colName) {
                if (!isset($equivalenceToMysqlField[$colName]) || !isset($line[$nbCol])) {
                    continue;
                }

                $sqlField = $equivalenceToMysqlField[$colName];
                $value = trim($line[$nbCol]);

                if ($sqlField === "usr_sexe") {
                    switch ($value) {
                        case "Mlle":
                        case "Mlle.":
                        case "mlle":
                        case "Miss":
                        case "miss":
                        case "0":
                            $curUser[$sqlField] = 0;
                            break;

                        case "Mme":
                        case "Madame":
                        case "Ms":
                        case "Ms.":
                        case "1":
                            $curUser[$sqlField] = 1;
                            break;

                        case "M":
                        case "M.":
                        case "Mr":
                        case "Mr.":
                        case "Monsieur":
                        case "Mister":
                        case "2":
                            $curUser[$sqlField] =  2;
                            break;
                    }
                } else {
                    $curUser[$sqlField] = $value;
                }
            }

            /** @var UserRepository $userRepository */
            $userRepository = $this->app['repo.users'];
            /** @var UserManipulator $userManipulator */
            $userManipulator = $this->app['manipulator.user'];
            if (isset($curUser['usr_login']) && trim($curUser['usr_login']) !== ''
                && isset($curUser['usr_password']) && trim($curUser['usr_password']) !== ''
                && isset($curUser['usr_mail']) && trim($curUser['usr_mail']) !== '') {
                if (null === $userRepository->findByLogin($curUser['usr_login'])
                    && null === $userRepository->findByEmail($curUser['usr_mail'])) {

                    $newUser = $userManipulator
                        ->createUser($curUser['usr_login'], $curUser['usr_password'], $curUser['usr_mail']);

                    $ftpCredential = new FtpCredential();
                    $ftpCredential->setUser($newUser);

                    if (isset($curUser['activeFTP'])) {
                        $ftpCredential->setActive((int) $curUser['activeFTP']);
                    }
                    if (isset($curUser['addrFTP'])) {
                        $ftpCredential->setAddress((string) $curUser['addrFTP']);
                    }
                    if (isset($curUser['passifFTP'])) {
                        $ftpCredential->setPassive((int) $curUser['passifFTP']);
                    }
                    if (isset($curUser['destFTP'])) {
                        $ftpCredential->setReceptionFolder($curUser['destFTP']);
                    }
                    if (isset($curUser['prefixFTPfolder'])) {
                        $ftpCredential->setRepositoryPrefixName($curUser['prefixFTPfolder']);
                    }
                    if (isset($curUser['usr_prenom'])) {
                        $newUser->setFirstName($curUser['usr_prenom']);
                    }
                    if (isset($curUser['usr_nom'])) {
                        $newUser->setLastName($curUser['usr_nom']);
                    }
                    if (isset($curUser['adresse'])) {
                        $newUser->setAddress($curUser['adresse']);
                    }
                    if (isset($curUser['cpostal'])) {
                        $newUser->setZipCode($curUser['cpostal']);
                    }
                    if (isset($curUser['usr_sexe'])) {
                        $newUser->setGender((int) ($curUser['usr_sexe']));
                    }
                    if (isset($curUser['tel'])) {
                        $newUser->setPhone($curUser['tel']);
                    }
                    if (isset($curUser['fax'])) {
                        $newUser->setFax($curUser['fax']);
                    }
                    if (isset($curUser['activite'])) {
                        $newUser->setActivity($curUser['activite']);
                    }
                    if (isset($curUser['fonction'])) {
                        $newUser->setJob($curUser['fonction']);
                    }
                    if (isset($curUser['societe'])) {
                        $newUser->setCompany($curUser['societe']);
                    }

                    $this->getAclForUser($newUser)->apply_model(
                        $userRepository->find($model),
                        array_keys($this->getAclForConnectedUser()->get_granted_base([\ACL::COLL_MANAGE]))
                    );

                    $nbCreation++;
                }
            }
        }

        return $this->app->redirectPath('admin_users_search', ['user-updated' => $nbCreation]);
    }

    public function importCsvExampleAction()
    {
        $filename = $this->app['root.path'] . '/resources/examples/example_import_users.csv';
        $contentType = 'text/csv';
        return $this->returnExampleFile($filename, $contentType);
    }

    public function importRtfExampleAction()
    {
        $filename = $this->app['root.path'] . '/resources/examples/fields.rtf';
        $contentType = 'text/rtf';
        return $this->returnExampleFile($filename, $contentType);
    }

    public function getEquivalenceToMysqlField()
    {
        return [
            'civilite'                      => 'usr_sexe',
            'gender'                        => 'usr_sexe',
            'usr_sexe'                      => 'usr_sexe',
            'nom'                           => 'usr_nom',
            'name'                          => 'usr_nom',
            'last name'                     => 'usr_nom',
            'last_name'                     => 'usr_nom',
            'usr_nom'                       => 'usr_nom',
            'first name'                    => 'usr_prenom',
            'first_name'                    => 'usr_prenom',
            'prenom'                        => 'usr_prenom',
            'usr_prenom'                    => 'usr_prenom',
            'identifiant'                   => 'usr_login',
            'login'                         => 'usr_login',
            'usr_login'                     => 'usr_login',
            'usr_password'                  => 'usr_password',
            'password'                      => 'usr_password',
            'mot de passe'                  => 'usr_password',
            'usr_mail'                      => 'usr_mail',
            'email'                         => 'usr_mail',
            'mail'                          => 'usr_mail',
            'adresse'                       => 'adresse',
            'adress'                        => 'adresse',
            'address'                       => 'adresse',
            'ville'                         => 'ville',
            'city'                          => 'ville',
            'zip'                           => 'cpostal',
            'zipcode'                       => 'cpostal',
            'zip_code'                      => 'cpostal',
            'cpostal'                       => 'cpostal',
            'cp'                            => 'cpostal',
            'code_postal'                   => 'cpostal',
            'tel'                           => 'tel',
            'telephone'                     => 'tel',
            'phone'                         => 'tel',
            'fax'                           => 'fax',
            'job'                           => 'fonction',
            'fonction'                      => 'fonction',
            'function'                      => 'fonction',
            'societe'                       => 'societe',
            'company'                       => 'societe',
            'activity'                      => 'activite',
            'activite'                      => 'activite',
            'pays'                          => 'pays',
            'country'                       => 'pays',
            'ftp_active'                    => 'activeFTP',
            'compte_ftp_actif'              => 'activeFTP',
            'ftpactive'                     => 'activeFTP',
            'activeftp'                     => 'activeFTP',
            'ftp_adress'                    => 'addrFTP',
            'adresse_du_serveur_ftp'        => 'addrFTP',
            'addrftp'                       => 'addrFTP',
            'ftpaddr'                       => 'addrFTP',
            'loginftp'                      => 'loginFTP',
            'ftplogin'                      => 'loginFTP',
            'ftppwd'                        => 'pwdFTP',
            'pwdftp'                        => 'pwdFTP',
            'destftp'                       => 'destFTP',
            'destination_folder'            => 'destFTP',
            'dossier_de_destination'        => 'destFTP',
            'passive_mode'                  => 'passifFTP',
            'mode_passif'                   => 'passifFTP',
            'passifftp'                     => 'passifFTP',
            'retry'                         => 'retryFTP',
            'nombre_de_tentative'           => 'retryFTP',
            'retryftp'                      => 'retryFTP',
            'by_default__send'              => 'defaultftpdatasent',
            'by_default_send'               => 'defaultftpdatasent',
            'envoi_par_defaut'              => 'defaultftpdatasent',
            'defaultftpdatasent'            => 'defaultftpdatasent',
            'prefix_creation_folder'        => 'prefixFTPfolder',
            'prefix_de_creation_de_dossier' => 'prefixFTPfolder',
            'prefixFTPfolder'               => 'prefixFTPfolder',
        ];
    }

    /**
     * @param Request $request
     * @return UserHelper\Edit
     */
    private function getUserEditHelper(Request $request)
    {
        return new UserHelper\Edit($this->app, $request);
    }

    /**
     * @param Request $request
     * @return UserHelper\Manage
     */
    private function getUserManageHelper(Request $request)
    {
        return new UserHelper\Manage($this->app, $request);
    }

    /**
     * @return \ACL
     */
    private function getAclForConnectedUser()
    {
        return $this->getAclForUser($this->getAuthenticatedUser());

    }

    /**
     * @return ExporterInterface
     */
    private function getCsvExporter()
    {
        /** @var ExporterInterface $exporter */
        $exporter = $this->app['csv.exporter'];
        return $exporter;
    }

    /**
     * @param array $template
     * @return array
     */
    private function normalizeTemplateArray(array $template)
    {
        $templates = [];
        foreach ($template as $tmp) {
            if ('' === trim($tmp)) {
                continue;
            }

            $tmp = explode('_', $tmp);

            if (count($tmp) == 2) {
                $templates[$tmp[0]] = $tmp[1];
            }
        }
        return $templates;
    }

    /**
     * @param array $denials
     * @param array $templates
     * @return array
     */
    private function normalizeDenyArray(array $denials, array $templates)
    {
        $deny = [];
        foreach ($denials as $den) {
            $den = explode('_', $den);
            if (count($den) == 2 && !isset($templates[$den[0]])) {
                $deny[$den[0]][$den[1]] = $den[1];
            }
        }
        return $deny;
    }

    /**
     * @return RegistrationManipulator
     */
    private function getRegistrationManipulator()
    {
        return $this->app['manipulator.registration'];
    }

    /**
     * @param $filename
     * @param $contentType
     * @return Response
     */
    public function returnExampleFile($filename, $contentType)
    {
        $file = new \SplFileInfo($filename);

        if (!$file->isFile()) {
            $this->app->abort(400);
        }

        $response = new Response();
        $response->setStatusCode(200);
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Content-Disposition', 'attachment; filename=' . $file->getFilename());
        $response->headers->set('Content-Length', $file->getSize());
        $response->headers->set('Content-Type', $contentType);
        $response->setContent(file_get_contents($file->getPathname()));

        return $response;
    }
}
