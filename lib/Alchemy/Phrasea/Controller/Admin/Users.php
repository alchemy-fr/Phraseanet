<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Alchemy\Phrasea\Helper\User as UserHelper;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Users implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $appbox = $app['phraseanet.appbox'];

        $controllers = $app['controllers_factory'];

        $controllers->post('/rights/', function(Application $app) {
                $rights = new UserHelper\Edit($app['phraseanet.core'], $app['request']);

                return $app['twig']->render('admin/editusers.html.twig', $rights->get_users_rights());
            }
        );

        $controllers->get('/rights/', function(Application $app) {
                $rights = new UserHelper\Edit($app['phraseanet.core'], $app['request']);

                return $app['twig']->render('admin/editusers.html.twig', $rights->get_users_rights());
            }
        );

        $controllers->post('/rights/reset/', function(Application $app, Request $request) {
                try {
                    $core = $app['phraseanet.core'];
                    $datas = array('error' => false);

                    $helper = new UserHelper\Edit($core, $request);
                    $helper->resetRights();
                } catch (\Exception $e) {
                    $datas['error'] = true;
                    $datas['message'] = $e->getMessage();
                }

                return $app->json($datas);
            }
        );

        $controllers->post('/delete/', function(Application $app) {
                $module = new UserHelper\Edit($app['phraseanet.core'], $app['request']);
                $module->delete_users();

                return $app->redirect('/admin/users/search/');
            }
        );

        $controllers->post('/rights/apply/', function(Application $app) {
                $datas = array('error' => true);

                try {
                    $rights = new UserHelper\Edit($app['phraseanet.core'], $app['request']);
                    $rights->apply_rights();

                    if ($app['request']->request->get('template')) {
                        $rights->apply_template();
                    }

                    $rights->apply_infos();

                    $datas = array('error' => false);
                } catch (\Exception $e) {
                    $datas['message'] = $e->getMessage();
                }

                return $app->json($datas);
            }
        );

        $controllers->post('/rights/quotas/', function(Application $app) {
                $rights = new UserHelper\Edit($app['phraseanet.core'], $app['request']);

                return $app['twig']->render('admin/editusers_quotas.html.twig', $rights->get_quotas());
            }
        );

        $controllers->post('/rights/quotas/apply/', function(Application $app) {
                $rights = new UserHelper\Edit($app['phraseanet.core'], $app['request']);
                $rights->apply_quotas();

                return;
            }
        );

        $controllers->post('/rights/time/', function(Application $app) {
                $rights = new UserHelper\Edit($app['phraseanet.core'], $app['request']);

                return $app['twig']->render('admin/editusers_timelimit.html.twig', $rights->get_time());
            }
        );

        $controllers->post('/rights/time/apply/', function(Application $app) {
                $rights = new UserHelper\Edit($app['phraseanet.core'], $app['request']);
                $rights->apply_time();

                return;
            }
        );

        $controllers->post('/rights/masks/', function(Application $app) {
                $rights = new UserHelper\Edit($app['phraseanet.core'], $app['request']);

                return $app['twig']->render('admin/editusers_masks.html.twig', $rights->get_masks());
            }
        );

        $controllers->post('/rights/masks/apply/', function(Application $app) {
                $rights = new UserHelper\Edit($app['phraseanet.core'], $app['request']);
                $rights->apply_masks();

                return;
            }
        );

        $controllers->match('/search/', function(Application $app) {
                $users = new UserHelper\Manage($app['phraseanet.core'], $app['request']);

                return $app['twig']->render('admin/users.html.twig', $users->search());
            }
        );

        $controllers->post('/search/export/', function() use ($app) {
                $request = $app['request'];

                $users = new UserHelper\Manage($app['phraseanet.core'], $app['request']);

                $userTable = array(
                    array(
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
                    )
                );

                foreach ($users->export() as $user) {
                    /* @var $user \User_Adapter */
                    $userTable[] = array(
                        $user->get_id(),
                        $user->get_login(),
                        $user->get_lastname(),
                        $user->get_firstname(),
                        $user->get_email(),
                        $user->get_creation_date()->format(DATE_ATOM),
                        $user->get_modification_date()->format(DATE_ATOM),
                        $user->get_address(),
                        $user->get_city(),
                        $user->get_zipcode(),
                        $user->get_country(),
                        $user->get_tel(),
                        $user->get_fax(),
                        $user->get_job(),
                        $user->get_company(),
                        $user->get_position()
                    );
                }

                $CSVDatas = \format::arr_to_csv($userTable);

                $response = new Response($CSVDatas, 200, array('Content-Type' => 'text/plain'));
                $response->headers->set('Content-Disposition', 'attachment; filename=export.txt');

                return $response;
            }
        );

        $controllers->post('/apply_template/', function() use ($app) {
                $users = new UserHelper\Edit($app['phraseanet.core'], $app['request']);

                $users->apply_template();

                return $app->redirect('/admin/users/search/');
            }
        );

        $controllers->get('/typeahead/search/', function(Application $app) use ($appbox) {
                $request = $app['request'];

                $user_query = new \User_Query($appbox);

                $user = \User_Adapter::getInstance($appbox->get_session()->get_usr_id(), $appbox);

                $like_value = $request->query->get('term');
                $rights = $request->query->get('filter_rights') ? : array();
                $have_right = $request->query->get('have_right') ? : array();
                $have_not_right = $request->query->get('have_not_right') ? : array();
                $on_base = $request->query->get('on_base') ? : array();

                $elligible_users = $user_query
                    ->on_sbas_where_i_am($user->ACL(), $rights)
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

                $datas = array();

                foreach ($elligible_users as $user) {
                    $datas[] = array(
                        'email' => $user->get_email() ? : ''
                        , 'login' => $user->get_login() ? : ''
                        , 'name'  => $user->get_display_name() ? : ''
                        , 'id'    => $user->get_id()
                    );
                }

                return $app->json($datas);
            });

        $controllers->post('/create/', function(Application $app) {

                $datas = array('error'   => false, 'message' => '', 'data'    => null);
                try {
                    $request = $app['request'];
                    $module = new UserHelper\Manage($app['phraseanet.core'], $app['request']);
                    if ($request->request->get('template') == '1') {
                        $user = $module->create_template();
                    } else {
                        $user = $module->create_newuser();
                    }
                    if ( ! ($user instanceof \User_Adapter))
                        throw new \Exception('Unknown error');

                    $datas['data'] = $user->get_id();
                } catch (\Exception $e) {
                    $datas['error'] = true;
                    $datas['message'] = $e->getMessage();
                }

                return $app->json($datas);
            }
        );

        $controllers->post('/export/csv/', function(Application $app) use ($appbox) {
                $request = $app['request'];
                $user_query = new \User_Query($appbox, $app['phraseanet.core']);

                $user = \User_Adapter::getInstance($appbox->get_session()->get_usr_id(), $appbox);
                $like_value = $request->request->get('like_value');
                $like_field = $request->request->get('like_field');
                $on_base = $request->request->get('base_id') ? : null;
                $on_sbas = $request->request->get('sbas_id') ? : null;

                $elligible_users = $user_query->on_bases_where_i_am($user->ACL(), array('canadmin'))
                    ->like($like_field, $like_value)
                    ->on_base_ids($on_base)
                    ->on_sbas_ids($on_sbas);

                $offset = 0;
                $geoname = new \geonames();
                $buffer = array();

                $buffer[] = array(
                    'ID'
                    , 'Login'
                    , _('admin::compte-utilisateur nom')
                    , _('admin::compte-utilisateur prenom')
                    , _('admin::compte-utilisateur email')
                    , 'CreationDate'
                    , 'ModificationDate'
                    , _('admin::compte-utilisateur adresse')
                    , _('admin::compte-utilisateur ville')
                    , _('admin::compte-utilisateur code postal')
                    , _('admin::compte-utilisateur pays')
                    , _('admin::compte-utilisateur telephone')
                    , _('admin::compte-utilisateur fax')
                    , _('admin::compte-utilisateur poste')
                    , _('admin::compte-utilisateur societe')
                    , _('admin::compte-utilisateur activite')
                );
                do {
                    $elligible_users->limit($offset, 20);
                    $offset += 20;

                    $results = $elligible_users->execute()->get_results();

                    foreach ($results as $user) {
                        $buffer[] = array(
                            $user->get_id()
                            , $user->get_login()
                            , $user->get_lastname()
                            , $user->get_firstname()
                            , $user->get_email()
                            , \phraseadate::format_mysql($user->get_creation_date())
                            , \phraseadate::format_mysql($user->get_modification_date())
                            , $user->get_address()
                            , $user->get_city()
                            , $user->get_zipcode()
                            , $geoname->get_country($user->get_geonameid())
                            , $user->get_tel()
                            , $user->get_fax()
                            , $user->get_job()
                            , $user->get_company()
                            , $user->get_position()
                        );
                    }
                } while (count($results) > 0);

                $out = \format::arr_to_csv($buffer);

                $headers = array(
                    'Content-type'        => 'text/csv'
                    , 'Content-Disposition' => 'attachment; filename=export.txt'
                );
                $response = new Response($out, 200, $headers);
                $response->setCharset('UTF-8');

                return $response;
            }
        );

        $controllers->get('/demands/', function(Application $app, Request $request) use ($appbox) {
                $user = $app['phraseanet.core']->getAuthenticatedUser();

                $lastMonth = time() - (3 * 4 * 7 * 24 * 60 * 60);
                $sql = "DELETE FROM demand WHERE date_modif < :date";
                $stmt = $appbox->get_connection()->prepare($sql);
                $stmt->execute(array(':date' => date('Y-m-d', $lastMonth)));
                $stmt->closeCursor();

                $baslist = array_keys($user->ACL()->get_granted_base(array('canadmin')));

                $sql = 'SELECT usr_id, usr_login FROM usr WHERE model_of = :usr_id';

                $stmt = $appbox->get_connection()->prepare($sql);
                $stmt->execute(array(':usr_id' => $user->get_id()));
                $models = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                $stmt->closeCursor();


                $sql = "
                    SELECT demand.date_modif,demand.base_id, usr.usr_id , usr.usr_login ,usr.usr_nom,usr.usr_prenom,
                    usr.societe, usr.fonction, usr.usr_mail, usr.tel, usr.activite,
                    usr.adresse, usr.cpostal, usr.ville, usr.pays, CONCAT(usr.usr_nom,' ',usr.usr_prenom,'\n',fonction,' (',societe,')') AS info
                    FROM (demand INNER JOIN usr on demand.usr_id=usr.usr_id AND demand.en_cours=1 AND usr.usr_login NOT LIKE '(#deleted%' )
                    WHERE (base_id='" . implode("' OR base_id='", $baslist) . "') ORDER BY demand.usr_id DESC,demand.base_id ASC
                ";

                $stmt = $appbox->get_connection()->prepare($sql);
                $stmt->execute();
                $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                $stmt->closeCursor();

                $currentUsr = null;
                $table = array('user' => array(), 'coll' => array());

                foreach ($rs as $row) {
                    if ($row['usr_id'] != $currentUsr) {
                        $currentUsr = $row['usr_id'];
                        $row['date_modif'] = new \DateTime($row['date_modif']);
                        $table['user'][$row['usr_id']] = $row;
                    }

                    if ( ! isset($table['coll'][$row['usr_id']])) {
                        $table['coll'][$row['usr_id']] = array();
                    }

                    if ( ! in_array($row['base_id'], $table['coll'][$row['usr_id']])) {
                        $table['coll'][$row['usr_id']][] = $row['base_id'];
                    }
                }

                $stmt->closeCursor();

                return $app['twig']->render('admin/user/demand.html.twig', array(
                        'table'  => $table,
                        'models' => $models,
                    ));
            });

        $controllers->post('/demands/', function(Application $app, Request $request) use ($appbox) {

                $templates = $deny = $accept = $options = array();

                foreach ($request->get('template', array()) as $tmp) {
                    if (trim($tmp) != '') {
                        $tmp = explode('_', $tmp);

                        if (count($tmp) == 2) {
                            $templates[$tmp[0]] = $tmp[1];
                        }
                    }
                }

                foreach ($request->get('deny', array()) as $den) {
                    $den = explode('_', $den);
                    if (count($den) == 2 && ! isset($templates[$den[0]])) {
                        $deny[$den[0]][$den[1]] = $den[1];
                    }
                }

                foreach ($request->get('accept', array()) as $acc) {
                    $acc = explode('_', $acc);
                    if (count($acc) == 2 && ! isset($templates[$acc[0]])) {
                        $accept[$acc[0]][$acc[1]] = $acc[1];
                        $options[$acc[0]][$acc[1]] = array('HD' => false, 'WM' => false);
                    }
                }

                foreach ($request->get('accept_hd', array()) as $accHD) {
                    $accHD = explode('_', $accHD);
                    if (count($accHD) == 2 && isset($accept[$accHD[0]]) && isset($options[$accHD[0]][$accHD[1]])) {
                        $options[$accHD[0]][$accHD[1]]['HD'] = true;
                    }
                }

                foreach ($request->get('watermark', array()) as $wm) {
                    $wm = explode('_', $wm);
                    if (count($wm) == 2 && isset($accept[$wm[0]]) && isset($options[$wm[0]][$wm[1]])) {
                        $options[$wm[0]][$wm[1]]['WM'] = true;
                    }
                }


                if (count($templates) > 0 || count($deny) > 0 || count($accept) > 0) {
                    $done = array();
                    $cache_to_update = array();

                    foreach ($templates as $usr => $template_id) {
                        $user = \User_Adapter::getInstance($usr, $appbox);
                        $cache_to_update[$usr] = true;

                        $user_template = \User_Adapter::getInstance($template_id, $appbox);
                        $base_ids = array_keys($user_template->ACL()->get_granted_base());

                        $user->ACL()->apply_model($user_template, $base_ids);


                        if ( ! isset($done[$usr])) {
                            $done[$usr] = array();
                        }

                        foreach ($base_ids as $base_id) {
                            $done[$usr][$base_id] = true;
                        }

                        $sql = "
                            DELETE FROM demand
                            WHERE usr_id = :usr_id
                            AND (base_id = " . implode(' OR base_id = ', $base_ids) . ")";

                        $stmt = $appbox->get_connection()->prepare($sql);
                        $stmt->execute(array(':usr_id' => $usr));
                        $stmt->closeCursor();
                    }

                    $sql = "
                        UPDATE demand SET en_cours=0, refuser=1, date_modif=now()
                        WHERE usr_id = :usr_id
                        AND base_id = :base_id";

                    $stmt = $appbox->get_connection()->prepare($sql);

                    foreach ($deny as $usr => $bases) {
                        $cache_to_update[$usr] = true;
                        foreach ($bases as $bas) {
                            $stmt->execute(array(':usr_id'  => $usr, ':base_id' => $bas));

                            if ( ! isset($done[$usr])) {
                                $done[$usr] = array();
                            }

                            $done[$usr][$bas] = false;
                        }
                    }

                    $stmt->closeCursor();

                    foreach ($accept as $usr => $bases) {
                        $user = \User_Adapter::getInstance($usr, $appbox);
                        $cache_to_update[$usr] = true;

                        foreach ($bases as $bas) {
                            $user->ACL()->give_access_to_sbas(array(\phrasea::sbasFromBas($bas)));

                            $rights = array(
                                'canputinalbum'   => '1'
                                , 'candwnldhd'      => ($options[$usr][$bas]['HD'] ? '1' : '0')
                                , 'nowatermark'     => ($options[$usr][$bas]['WM'] ? '0' : '1')
                                , 'candwnldpreview' => '1'
                                , 'actif'           => '1'
                            );

                            $user->ACL()->give_access_to_base(array($bas));
                            $user->ACL()->update_rights_to_base($bas, $rights);

                            if ( ! isset($done[$usr])) {
                                $done[$usr] = array();
                            }

                            $done[$usr][$bas] = true;

                            $sql = "DELETE FROM demand WHERE usr_id = :usr_id AND base_id = :base_id";
                            $stmt = $appbox->get_connection()->prepare($sql);
                            $stmt->execute(array(':usr_id'  => $usr, ':base_id' => $bas));
                            $stmt->closeCursor();
                        }
                    }

                    foreach (array_keys($cache_to_update) as $usr_id) {
                        $user = \User_Adapter::getInstance($usr_id, $appbox);
                        $user->ACL()->delete_data_from_cache();
                        unset($user);
                    }

                    foreach ($done as $usr => $bases) {
                        $sql = 'SELECT usr_mail FROM usr WHERE usr_id = :usr_id';

                        $stmt = $appbox->get_connection()->prepare($sql);
                        $stmt->execute(array(':usr_id' => $usr));
                        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                        $stmt->closeCursor();

                        $accept = $deny = '';

                        if ($row) {

                            if (\PHPMailer::ValidateAddress($row['usr_mail'])) {
                                foreach ($bases as $bas => $isok) {
                                    if ($isok) {
                                        $accept .= '<li>' . \phrasea::bas_names($bas) . "</li>\n";
                                    } else {
                                        $deny .= '<li>' . \phrasea::bas_names($bas) . "</li>\n";
                                    }
                                }
                                if (($accept != '' || $deny != '')) {
                                    \mail::register_confirm($row['usr_mail'], $accept, $deny);
                                }
                            }
                        }
                    }
                }

                return $app->redirect('/admin/users/demands/?demands=ok');
            });

        return $controllers;
    }
}

