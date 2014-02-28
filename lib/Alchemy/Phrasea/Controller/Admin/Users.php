<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Admin;

use Alchemy\Phrasea\Helper\User as UserHelper;
use Alchemy\Phrasea\Model\Entities\FtpCredential;
use Alchemy\Phrasea\Model\Entities\User;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Alchemy\Phrasea\Notification\Receiver;
use Alchemy\Phrasea\Notification\Mail\MailSuccessEmailUpdate;

class Users implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['controller.admin.users'] = $this;

        $controllers = $app['controllers_factory'];

        $app['firewall']->addMandatoryAuthentication($controllers);

        $controllers->before(function (Request $request) use ($app) {
            $app['firewall']->requireAccessToModule('admin')
                ->requireRight('manageusers');
        });

        $controllers->post('/rights/', function (Application $app) {
            $rights = new UserHelper\Edit($app, $app['request']);

            return $app['twig']->render('admin/editusers.html.twig', $rights->get_users_rights());
        });

        $controllers->get('/rights/', function (Application $app) {
            $rights = new UserHelper\Edit($app, $app['request']);

            return $app['twig']->render('admin/editusers.html.twig', $rights->get_users_rights());
        });

        $controllers->post('/rights/reset/', function (Application $app, Request $request) {
            try {
                $datas = ['error' => false];

                $helper = new UserHelper\Edit($app, $request);
                $helper->resetRights();
            } catch (\Exception $e) {
                $datas['error'] = true;
                $datas['message'] = $e->getMessage();
            }

            return $app->json($datas);
        })->bind('admin_users_rights_reset');

        $controllers->post('/delete/', function (Application $app) {
            $module = new UserHelper\Edit($app, $app['request']);
            $module->delete_users();

            return $app->redirectPath('admin_users_search');
        });

        $controllers->post('/rights/apply/', function (Application $app) {
            $datas = ['error' => true];

            try {
                $rights = new UserHelper\Edit($app, $app['request']);

                if (!$app['request']->request->get('reset_before_apply')) {
                    $rights->apply_rights();
                }

                if ($app['request']->request->get('template')) {
                    if ($app['request']->request->get('reset_before_apply')) {
                        $rights->resetRights();
                    }
                    $rights->apply_template();
                }

                $rights->apply_infos();

                $datas = ['error' => false];
            } catch (\Exception $e) {
                $datas['message'] = $e->getMessage();
            }

            return $app->json($datas);
        })->bind('admin_users_rights_apply');

        $controllers->post('/rights/quotas/', function (Application $app) {
            $rights = new UserHelper\Edit($app, $app['request']);

            return $app['twig']->render('admin/editusers_quotas.html.twig', $rights->get_quotas());
        });

        $controllers->post('/rights/quotas/apply/', function (Application $app) {
            $rights = new UserHelper\Edit($app, $app['request']);
            $rights->apply_quotas();

            return $app->json(['message' => '', 'error'   => false]);
        });

        $controllers->post('/rights/time/', function (Application $app) {
            $rights = new UserHelper\Edit($app, $app['request']);

            return $app['twig']->render('admin/editusers_timelimit.html.twig', $rights->get_time());
        });

        $controllers->post('/rights/time/sbas/', function (Application $app) {
            $rights = new UserHelper\Edit($app, $app['request']);

            return $app['twig']->render('admin/editusers_timelimit_sbas.html.twig', $rights->get_time_sbas());
        });

        $controllers->post('/rights/time/apply/', function (Application $app) {
            $rights = new UserHelper\Edit($app, $app['request']);
            $rights->apply_time();

            return $app->json(['message' => '', 'error'   => false]);
        });

        $controllers->post('/rights/masks/', function (Application $app) {
            $rights = new UserHelper\Edit($app, $app['request']);

            return $app['twig']->render('admin/editusers_masks.html.twig', $rights->get_masks());
        });

        $controllers->post('/rights/masks/apply/', function (Application $app) {
            $rights = new UserHelper\Edit($app, $app['request']);
            $rights->apply_masks();

            return $app->json(['message' => '', 'error'   => false]);
        });

        $controllers->match('/search/', function (Application $app) {
            $users = new UserHelper\Manage($app, $app['request']);

            return $app['twig']->render('admin/users.html.twig', $users->search());
        })->bind('admin_users_search');

        $controllers->post('/search/export/', function () use ($app) {
            $request = $app['request'];

            $users = new UserHelper\Manage($app, $app['request']);

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

            $CSVDatas = \format::arr_to_csv($userTable);

            $response = new Response($CSVDatas, 200, ['Content-Type' => 'text/csv']);
            $response->headers->set('Content-Disposition', 'attachment; filename=export.csv');

            return $response;
        })->bind('admin_users_search_export');

        $controllers->post('/apply_template/', function () use ($app) {
            $users = new UserHelper\Edit($app, $app['request']);

            if ($app['request']->request->get('reset_before_apply')) {
                $users->resetRights();
            }
            $users->apply_template();

            return $app->redirectPath('admin_users_search');
        })->bind('admin_users_apply_template');

        $controllers->get('/typeahead/search/', function (Application $app) {
            $request = $app['request'];

            $user_query = new \User_Query($app);

            $like_value = $request->query->get('term');
            $rights = $request->query->get('filter_rights') ? : [];
            $have_right = $request->query->get('have_right') ? : [];
            $have_not_right = $request->query->get('have_not_right') ? : [];
            $on_base = $request->query->get('on_base') ? : [];

            $elligible_users = $user_query
                ->on_sbas_where_i_am($app['acl']->get($app['authentication']->getUser()), $rights)
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

            $datas = [];

            foreach ($elligible_users as $user) {
                $datas[] = [
                    'email' => $user->getEmail() ? : '',
                    'login' => $user->getLogin() ? : '',
                    'name'  => $user->getDisplayName(),
                    'id'    => $user->getId(),
                ];
            }

            return $app->json($datas);
        });

        $controllers->post('/create/', function (Application $app) {
            $datas = ['error'   => false, 'message' => '', 'data'    => null];
            try {
                $request = $app['request'];
                $module = new UserHelper\Manage($app, $app['request']);
                if ($request->request->get('template') == '1') {
                    $user = $module->create_template();
                } else {
                    $user = $module->create_newuser();
                }
                if (!$user instanceof User) {
                    throw new \Exception('Unknown error');
                }

                $datas['data'] = $user->getId();
            } catch (\Exception $e) {
                $datas['error'] = true;
                if ($request->request->get('template') == '1') {
                    $datas['message'] = $app->trans('Unable to create template, the name is already used.');
                } else {
                    $datas['message'] = $app->trans('Unable to create the user.');
                }
            }

            return $app->json($datas);
        });

        $controllers->post('/export/csv/', function (Application $app) {
            $request = $app['request'];
            $user_query = new \User_Query($app);

            $like_value = $request->request->get('like_value');
            $like_field = $request->request->get('like_field');
            $on_base = $request->request->get('base_id') ? : null;
            $on_sbas = $request->request->get('sbas_id') ? : null;

            $elligible_users = $user_query->on_bases_where_i_am($app['acl']->get($app['authentication']->getUser()), ['canadmin'])
                ->like($like_field, $like_value)
                ->on_base_ids($on_base)
                ->on_sbas_ids($on_sbas);

            $offset = 0;
            $buffer = [];

            $buffer[] = [
                'ID',
                'Login',
                $app->trans('admin::compte-utilisateur nom'),
                $app->trans('admin::compte-utilisateur prenom'),
                $app->trans('admin::compte-utilisateur email'),
                'CreationDate',
                'ModificationDate',
                $app->trans('admin::compte-utilisateur adresse'),
                $app->trans('admin::compte-utilisateur ville'),
                $app->trans('admin::compte-utilisateur code postal'),
                $app->trans('admin::compte-utilisateur pays'),
                $app->trans('admin::compte-utilisateur telephone'),
                $app->trans('admin::compte-utilisateur fax'),
                $app->trans('admin::compte-utilisateur poste'),
                $app->trans('admin::compte-utilisateur societe'),
                $app->trans('admin::compte-utilisateur activite'),
            ];
            do {
                $elligible_users->limit($offset, 20);
                $offset += 20;

                $results = $elligible_users->execute()->get_results();

                foreach ($results as $user) {
                    $buffer[] = [
                        $user->getId(),
                        $user->getLogin(),
                        $user->getLastName(),
                        $user->getFirstName(),
                        $user->getEmail(),
                        $app['date-formatter']->format_mysql($user->getCreated()),
                        $app['date-formatter']->format_mysql($user->getUpdated()),
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

            $out = \format::arr_to_csv($buffer);

            $response = new Response($out, 200, [
                'Content-type'        => 'text/csv',
                'Content-Disposition' => 'attachment; filename=export.csv',
            ]);

            $response->setCharset('UTF-8');

            return $response;
        })->bind('admin_users_export_csv');

        $controllers->get('/registrations/', function (Application $app) {
            $app['manipulator.registration']->deleteOldRegistrations();

            $models = $app['manipulator.user']->getRepository()->findTemplateOwner($app['authentication']->getUser());

            $userRegistrations = [];
            foreach ($app['manipulator.registration']->getRepository()->getUserRegistrations(
                 $app['authentication']->getUser(),
                 $app['acl']->get($app['authentication']->getUser())->get_granted_base(['canadmin'])
            ) as $registration) {
                $user = $registration->getUser();
                $userRegistrations[$user->getId()]['user'] = $user;
                $userRegistrations[$user->getId()]['registrations'][$registration->getBaseid()] = $registration;
            }

            return $app['twig']->render('admin/user/registrations.html.twig', [
                'user_registrations' => $userRegistrations,
                'models' => $models,
            ]);
        })->bind('users_display_registrations');

        $controllers->post('/registrations/', function (Application $app, Request $request) {
            $templates = $deny = $accept = $options = [];

            foreach ($request->request->get('template', []) as $tmp) {
                if ('' === trim($tmp)) {
                    continue;
                }

                $tmp = explode('_', $tmp);

                if (count($tmp) == 2) {
                    $templates[$tmp[0]] = $tmp[1];
                }
            }

            foreach ($request->request->get('deny', []) as $den) {
                $den = explode('_', $den);
                if (count($den) == 2 && !isset($templates[$den[0]])) {
                    $deny[$den[0]][$den[1]] = $den[1];
                }
            }

            foreach ($request->request->get('accept', []) as $acc) {
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

            if (count($templates) > 0 || count($deny) > 0 || count($accept) > 0) {
                $cacheToUpdate = $done = [];

                foreach ($templates as $usr => $template_id) {
                    if (null === $user = $app['manipulator.user']->getRepository()->find($usr)) {
                        $app->abort(400, srpintf("User with id % in provided in 'template' request variable could not be found", $usr));
                    }
                    $cacheToUpdate[$usr] = $user;

                    $user_template = $app['manipulator.user']->getRepository()->find($template_id);
                    $collections = $app['acl']->get($user_template)->get_granted_base();
                    $baseIds = array_keys($collections);

                    $app['acl']->get($user)->apply_model($user_template, $baseIds);

                    foreach ($collections as $collection) {
                        $done[$usr][$collection->get_base_id()] = true;
                    }

                    $app['manipulator.registration']->deleteUserRegistrations($user, $collections);
                }

                foreach ($deny as $usr => $bases) {
                    if (null === $user = $app['manipulator.user']->getRepository()->find($usr)) {
                        $app->abort(400, srpintf("User with id % in provided in 'deny' request variable could not be found", $usr));
                    }
                    $cacheToUpdate[$usr] = $user;
                    foreach ($app['manipulator.registration']->getRepository()->getUserRegistrations(
                        $user,
                        array_map(function ($baseId) use ($app) {
                            return \collection::get_from_base_id($app, $baseId);
                        }, $bases)
                    ) as $registration) {
                        $app['manipulator.registration']->rejectRegistration($registration);
                        $done[$usr][$registration->getBaseId()] = false;
                    }
                }

                foreach ($accept as $usr => $bases) {
                    if (null === $user = $app['manipulator.user']->getRepository()->find($usr)) {
                        $app->abort(400, srpintf("User with id % in provided in 'accept' request variable could not be found", $usr));
                    }
                    $cacheToUpdate[$usr] = $user;
                    foreach ($app['manipulator.registration']->getRepository()->getUserRegistrations(
                        $user,
                        array_map(function ($baseId) use ($app) {
                            return \collection::get_from_base_id($app, $baseId);
                        }, $bases)
                    ) as $registration) {
                        $done[$usr][$registration->getBaseId()] = true;
                        $app['manipulator.registration']->acceptRegistration(
                            $registration,
                            $options[$usr][$registration->getBaseId()]['HD'],
                            $options[$usr][$registration->getBaseId()]['WM']
                        );
                    }
                }

                array_walk($cacheToUpdate, function (User $user) use ($app) {
                    $app['acl']->get($user)->delete_data_from_cache();
                });
                unset ($cacheToUpdate);

                foreach ($done as $usr => $bases) {
                    $user = $app['manipulator.user']->getRepository()->find($usr);
                    $acceptColl = $denyColl = [];

                    foreach ($bases as $bas => $isok) {
                        $collection = \collection::get_from_base_id($app, $bas);

                        if ($isok) {
                            $acceptColl[] = $collection->get_label($app['locale']);
                            continue;
                        }

                        $denyColl[] = $collection->get_label($app['locale']);
                    }

                    if (0 !== count($acceptColl) || 0 !== count($denyColl)) {
                        $message = '';
                        if (0 !== count($acceptColl)) {
                            $message .= "\n" . $app->trans('login::register:email: Vous avez ete accepte sur les collections suivantes : ') . implode(', ', $acceptColl). "\n";
                        }
                        if (0 !== count($denyColl)) {
                            $message .= "\n" . $app->trans('login::register:email: Vous avez ete refuse sur les collections suivantes : ') . implode(', ', $denyColl) . "\n";
                        }

                        $receiver = new Receiver(null, $user->getEmail());
                        $mail = MailSuccessEmailUpdate::create($app, $receiver, null, $message);

                        $app['notification.deliverer']->deliver($mail);
                    }
                }
            }

            return $app->redirectPath('users_display_registrations', ['success' => 1]);
        })->bind('users_submit_registrations');

        $controllers->get('/import/file/', function (Application $app, Request $request) {
            return $app['twig']->render('admin/user/import/file.html.twig');
        })->bind('users_display_import_file');

        $controllers->post('/import/file/', function (Application $app, Request $request) {
            if ((null === $file = $request->files->get('files')) || !$file->isValid()) {
                return $app->redirectPath('users_display_import_file', ['error' => 'file-invalid']);
            }

            $equivalenceToMysqlField = self::getEquivalenceToMysqlField();
            $loginDefined = $pwdDefined = $mailDefined = false;
            $loginNew = [];
            $out = [
                'ignored_row' => [],
                'errors' => []
            ];
            $nbUsrToAdd = 0;

            $lines = \format::csv_to_arr($file->getPathname());

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
                return $app->redirectPath('users_display_import_file', ['error' => 'row-login']);
            }

            if (!$pwdDefined) {
                return $app->redirectPath('users_display_import_file', ['error' => 'row-pwd']);
            }

            if (!$mailDefined) {
                return $app->redirectPath('users_display_import_file', ['error' => 'row-mail']);
            }

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
                            $out['errors'][] = $app->trans("Login line %line% is empty", ['%line%' => $nbLine + 1]);
                        } elseif (in_array($loginToAdd, $loginNew)) {
                            $out['errors'][] = $app->trans("Login %login% is already defined in the file at line %line%", ['%login%' => $loginToAdd, '%line%' => $nbLine]);
                        } else {
                            if (null !== $app['manipulator.user']->getRepository()->findByLogin($loginToAdd)) {
                                $out['errors'][] = $app->trans("Login %login% already exists in database", ['%login%' => $loginToAdd]);
                            } else {
                                $loginValid = true;
                            }
                        }
                    }

                    if ($loginValid && $sqlField === 'usr_mail') {
                        $mailToAdd = $value;

                        if ($mailToAdd === "") {
                            $out['errors'][] = $app->trans("Mail line %line% is empty", ['%line%' => $nbLine + 1]);
                        } elseif (null !== $app['manipulator.user']->getRepository()->findByEmail($mailToAdd)) {
                            $out['errors'][] = $app->trans("Email '%email%' for login '%login%' already exists in database", ['%email%' => $mailToAdd, '%login%' => $loginToAdd]);
                        } else {
                            $mailValid = true;
                        }
                    }

                    if ($sqlField === 'usr_password') {
                        $passwordToVerif = $value;

                        if ($passwordToVerif === "") {
                            $out['errors'][] = $app->trans("Password is empty at line %line%", ['%line%' => $nbLine]);
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
                return $app['twig']->render('admin/user/import/file.html.twig', [
                    'errors' => $out['errors']
                ]);
            }

            if ($nbUsrToAdd === 0) {
                return $app->redirectPath('users_display_import_file', [
                    'error' => 'no-user'
                ]);
            }

            $basList = array_keys($app['acl']->get($app['authentication']->getUser())->get_granted_base(['manage']));
            $models = $app['EM.native-query']->getModelForUser($app['authentication']->getUser(), $basList);

            return $app['twig']->render('/admin/user/import/view.html.twig', [
                'nb_user_to_add'   => $nbUsrToAdd,
                'models'           => $models,
                'lines_serialized' => serialize($lines),
                'columns_serialized' => serialize($columns),
                'errors' => $out['errors']
            ]);
        })->bind('users_submit_import_file');

        $controllers->post('/import/', function (Application $app, Request $request) {
            $nbCreation = 0;

            if ((null === $serializedColumns = $request->request->get('sr_columns')) || ('' === $serializedColumns)) {
                $app->abort(400);
            }

            if ((null === $serializedLines = $request->request->get('sr_lines')) || ('' === $serializedLines)) {
                $app->abort(400);
            }

            if (null === $model = $request->request->get("modelToApply")) {
                $app->abort(400);
            }

            $lines = unserialize($serializedLines);
            $columns = unserialize($serializedColumns);

            $equivalenceToMysqlField = Users::getEquivalenceToMysqlField();

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

                if (isset($curUser['usr_login']) && trim($curUser['usr_login']) !== ''
                        && isset($curUser['usr_password']) && trim($curUser['usr_password']) !== ''
                        && isset($curUser['usr_mail']) && trim($curUser['usr_mail']) !== '') {
                    if (null === $app['manipulator.user']->getRepository()->findByLogin($curUser['usr_login'])
                            && false === $app['manipulator.user']->getRepository()->findByEmail($curUser['usr_mail'])) {

                        $newUser = $app['manipulator.user']->createUser($curUser['usr_login'], $curUser['usr_password'], $curUser['usr_mail']);

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
                            $newUser->setAdress($curUser['adresse']);
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
                            $newUser->setJob($curUser['activite']);
                        }
                        if (isset($curUser['fonction'])) {
                            $newUser->setPosition($curUser['fonction']);
                        }
                        if (isset($curUser['societe'])) {
                            $newUser->setCompany($curUser['societe']);
                        }

                        $app['acl']->get($newUser)->apply_model(
                            $app['manipulator.user']->getRepository()->find($model), array_keys($app['acl']->get($app['authentication']->getUser())->get_granted_base(['manage']))
                        );

                        $nbCreation++;
                    }
                }
            }

            return $app->redirectPath('admin_users_search', ['user-updated' => $nbCreation]);
        })->bind('users_submit_import');

        $controllers->get('/import/example/csv/', function (Application $app) {

            $file = new \SplFileInfo($app['root.path'] . '/lib/Fixtures/exampleImportUsers.csv');

            if (!$file->isFile()) {
                $app->abort(400);
            }

            $response = new Response();
            $response->setStatusCode(200);
            $response->headers->set('Pragma', 'public');
            $response->headers->set('Content-Disposition', 'attachment; filename=' . $file->getFilename());
            $response->headers->set('Content-Length', $file->getSize());
            $response->headers->set('Content-Type', 'text/csv');
            $response->setContent(file_get_contents($file->getPathname()));

            return $response;
        })->bind('users_import_csv');

        $controllers->get('/import/example/rtf/', function (Application $app) {

            $file = new \SplFileInfo($app['root.path'] . '/lib/Fixtures/Fields.rtf');

            if (!$file->isFile()) {
                $app->abort(400);
            }

            $response = new Response();
            $response->setStatusCode(200);
            $response->headers->set('Pragma', 'public');
            $response->headers->set('Content-Disposition', 'attachment; filename=' . $file->getFilename());
            $response->headers->set('Content-Length', $file->getSize());
            $response->headers->set('Content-Type', 'text/rtf');
            $response->setContent(file_get_contents($file->getPathname()));

            return $response;
        })->bind('users_import_rtf');

        return $controllers;
    }

    public static function getEquivalenceToMysqlField()
    {
        $equivalenceToMysqlField = [];

        $equivalenceToMysqlField['civilite'] = 'usr_sexe';
        $equivalenceToMysqlField['gender'] = 'usr_sexe';
        $equivalenceToMysqlField['usr_sexe'] = 'usr_sexe';
        $equivalenceToMysqlField['nom'] = 'usr_nom';
        $equivalenceToMysqlField['name'] = 'usr_nom';
        $equivalenceToMysqlField['last name'] = 'usr_nom';
        $equivalenceToMysqlField['last_name'] = 'usr_nom';
        $equivalenceToMysqlField['usr_nom'] = 'usr_nom';
        $equivalenceToMysqlField['first name'] = 'usr_prenom';
        $equivalenceToMysqlField['first_name'] = 'usr_prenom';
        $equivalenceToMysqlField['prenom'] = 'usr_prenom';
        $equivalenceToMysqlField['usr_prenom'] = 'usr_prenom';
        $equivalenceToMysqlField['identifiant'] = 'usr_login';
        $equivalenceToMysqlField['login'] = 'usr_login';
        $equivalenceToMysqlField['usr_login'] = 'usr_login';
        $equivalenceToMysqlField['usr_password'] = 'usr_password';
        $equivalenceToMysqlField['password'] = 'usr_password';
        $equivalenceToMysqlField['mot de passe'] = 'usr_password';
        $equivalenceToMysqlField['usr_mail'] = 'usr_mail';
        $equivalenceToMysqlField['email'] = 'usr_mail';
        $equivalenceToMysqlField['mail'] = 'usr_mail';
        $equivalenceToMysqlField['adresse'] = 'adresse';
        $equivalenceToMysqlField['adress'] = 'adresse';
        $equivalenceToMysqlField['address'] = 'adresse';
        $equivalenceToMysqlField['ville'] = 'ville';
        $equivalenceToMysqlField['city'] = 'ville';
        $equivalenceToMysqlField['zip'] = 'cpostal';
        $equivalenceToMysqlField['zipcode'] = 'cpostal';
        $equivalenceToMysqlField['zip_code'] = 'cpostal';
        $equivalenceToMysqlField['cpostal'] = 'cpostal';
        $equivalenceToMysqlField['cp'] = 'cpostal';
        $equivalenceToMysqlField['code_postal'] = 'cpostal';
        $equivalenceToMysqlField['tel'] = 'tel';
        $equivalenceToMysqlField['telephone'] = 'tel';
        $equivalenceToMysqlField['phone'] = 'tel';
        $equivalenceToMysqlField['fax'] = 'fax';
        $equivalenceToMysqlField['job'] = 'fonction';
        $equivalenceToMysqlField['fonction'] = 'fonction';
        $equivalenceToMysqlField['function'] = 'fonction';
        $equivalenceToMysqlField['societe'] = 'societe';
        $equivalenceToMysqlField['company'] = 'societe';
        $equivalenceToMysqlField['activity'] = 'activite';
        $equivalenceToMysqlField['activite'] = 'activite';
        $equivalenceToMysqlField['pays'] = 'pays';
        $equivalenceToMysqlField['country'] = 'pays';
        $equivalenceToMysqlField['ftp_active'] = 'activeFTP';
        $equivalenceToMysqlField['compte_ftp_actif'] = 'activeFTP';
        $equivalenceToMysqlField['ftpactive'] = 'activeFTP';
        $equivalenceToMysqlField['activeftp'] = 'activeFTP';
        $equivalenceToMysqlField['ftp_adress'] = 'addrFTP';
        $equivalenceToMysqlField['adresse_du_serveur_ftp'] = 'addrFTP';
        $equivalenceToMysqlField['addrftp'] = 'addrFTP';
        $equivalenceToMysqlField['ftpaddr'] = 'addrFTP';
        $equivalenceToMysqlField['loginftp'] = 'loginFTP';
        $equivalenceToMysqlField['ftplogin'] = 'loginFTP';
        $equivalenceToMysqlField['ftppwd'] = 'pwdFTP';
        $equivalenceToMysqlField['pwdftp'] = 'pwdFTP';
        $equivalenceToMysqlField['destftp'] = 'destFTP';
        $equivalenceToMysqlField['destination_folder'] = 'destFTP';
        $equivalenceToMysqlField['dossier_de_destination'] = 'destFTP';
        $equivalenceToMysqlField['passive_mode'] = 'passifFTP';
        $equivalenceToMysqlField['mode_passif'] = 'passifFTP';
        $equivalenceToMysqlField['passifftp'] = 'passifFTP';
        $equivalenceToMysqlField['retry'] = 'retryFTP';
        $equivalenceToMysqlField['nombre_de_tentative'] = 'retryFTP';
        $equivalenceToMysqlField['retryftp'] = 'retryFTP';
        $equivalenceToMysqlField['by_default__send'] = 'defaultftpdatasent';
        $equivalenceToMysqlField['by_default_send'] = 'defaultftpdatasent';
        $equivalenceToMysqlField['envoi_par_defaut'] = 'defaultftpdatasent';
        $equivalenceToMysqlField['defaultftpdatasent'] = 'defaultftpdatasent';
        $equivalenceToMysqlField['prefix_creation_folder'] = 'prefixFTPfolder';
        $equivalenceToMysqlField['prefix_de_creation_de_dossier'] = 'prefixFTPfolder';
        $equivalenceToMysqlField['prefixFTPfolder'] = 'prefixFTPfolder';

        return $equivalenceToMysqlField;
    }
}
