<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Alchemy\Phrasea\Helper\User as UserHelper;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Users implements ControllerProviderInterface
{

  public function connect(Application $app)
  {
    $appbox = \appbox::get_instance();

    $controllers = new ControllerCollection();


    $controllers->post('/rights/', function(Application $app)
            {
              $rights = new UserHelper\Edit($app['Core'], $app['request']);

              $template = 'admin/editusers.twig';
              /* @var $twig \Twig_Environment */
              $twig = $app['Core']->getTwig();

              return $twig->render($template, $rights->get_users_rights());
            }
    );

    $controllers->get('/rights/', function(Application $app)
            {
              $rights = new UserHelper\Edit($app['Core'], $app['request']);

              $template = 'admin/editusers.twig';
              /* @var $twig \Twig_Environment */
              $twig = $app['Core']->getTwig();

              return $twig->render($template, $rights->get_users_rights());
            }
    );

    $controllers->post('/delete/', function(Application $app)
            {
              $module = new UserHelper\Edit($app['Core'], $app['request']);
              $module->delete_users();

              return $app->redirect('/admin/users/search/');
            }
    );

    $controllers->post('/rights/apply/', function(Application $app)
            {
              $datas = array('error' => true);

              try
              {
                $rights = new UserHelper\Edit($app['Core'], $app['request']);
                $rights->apply_rights();

                if ($app['request']->get('template'))
                {
                  $rights->apply_template();
                }

                $rights->apply_infos();

                $datas = array('error' => false);
              }
              catch (\Exception $e)
              {
                $datas['message'] = $e->getMessage();
              }

              $Serializer = $app['Core']['Serializer'];

              return new Response(
                              $Serializer->serialize($datas, 'json')
                              , 200
                              , array('Content-Type' => 'application/json')
              );
            }
    );

    $controllers->post('/rights/quotas/', function(Application $app)
            {
              $rights = new UserHelper\Edit($app['Core'], $app['request']);

              $template = 'admin/editusers_quotas.twig';
              /* @var $twig \Twig_Environment */
              $twig = $app['Core']->getTwig();

              return $twig->render($template, $rights->get_quotas());
            }
    );

    $controllers->post('/rights/quotas/apply/', function(Application $app)
            {
              $rights = new UserHelper\Edit($app['Core'], $app['request']);
              $rights->apply_quotas();

              return;
            }
    );

    $controllers->post('/rights/time/', function(Application $app)
            {
              $rights = new UserHelper\Edit($app['Core'], $app['request']);

              $template = 'admin/editusers_timelimit.twig';
              /* @var $twig \Twig_Environment */
              $twig = $app['Core']->getTwig();

              return $twig->render($template, $rights->get_time());
            }
    );

    $controllers->post('/rights/time/apply/', function(Application $app)
            {
              $rights = new UserHelper\Edit($app['Core'], $app['request']);
              $rights->apply_time();

              return;
            }
    );

    $controllers->post('/rights/masks/', function(Application $app)
            {
              $rights = new UserHelper\Edit($app['Core'], $app['request']);

              $template = 'admin/editusers_masks.twig';
              /* @var $twig \Twig_Environment */
              $twig = $app['Core']->getTwig();

              return $twig->render($template, $rights->get_masks());
            }
    );

    $controllers->post('/rights/masks/apply/', function(Application $app)
            {
              $rights = new UserHelper\Edit($app['Core'], $app['request']);
              $rights->apply_masks();

              return;
            }
    );

    $controllers->match('/search/', function(Application $app)
            {
              $users = new UserHelper\Manage($app['Core'], $app['request']);
              $template = 'admin/users.html';

              /* @var $twig \Twig_Environment */
              $twig = $app['Core']->getTwig();

              return $twig->render($template, $users->search());
            }
    );

    $controllers->post('/search/export/', function() use ($app)
            {
              $request = $app['request'];

              $users = new UserHelper\Manage($app['Core'], $app['request']);

              $template = 'admin/users.html';

              /* @var $twig \Twig_Environment */
              $twig = $app['Core']->getTwig();

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

              foreach ($users->export() as $user)
              {
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

    $controllers->post('/apply_template/', function() use ($app)
            {
              $users = new UserHelper\Edit($app['Core'], $app['request']);

              $users->apply_template();

              return new RedirectResponse('/admin/users/search/');
            }
    );

    $controllers->get('/typeahead/search/', function(Application $app) use ($appbox)
            {
              $request = $app['request'];

              $user_query = new \User_Query($appbox);

              $user = \User_Adapter::getInstance($appbox->get_session()->get_usr_id(), $appbox);

              $like_value = $request->get('term');
              $rights = $request->get('filter_rights') ? : array();
              $have_right = $request->get('have_right') ? : array();
              $have_not_right = $request->get('have_not_right') ? : array();
              $on_base = $request->get('on_base') ? : array();


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

              foreach ($elligible_users as $user)
              {
                $datas[] = array(
                    'email' => $user->get_email() ? : ''
                    , 'login' => $user->get_login() ? : ''
                    , 'name' => $user->get_display_name() ? : ''
                    , 'id' => $user->get_id()
                );
              }

              $Serializer = $app['Core']['Serializer'];

              return new Response(
                              $Serializer->serialize($datas, 'json')
                              , 200
                              , array('Content-type' => 'application/json')
              );
            });


    $controllers->post('/create/', function(Application $app)
            {

              $datas = array('error' => false, 'message' => '', 'data' => null);
              try
              {
                $request = $app['request'];
                $module = new UserHelper\Manage($app['Core'], $app['request']);
                if ($request->get('template') == '1')
                {
                  $user = $module->create_template();
                }
                else
                {
                  $user = $module->create_newuser();
                }
                if (!($user instanceof \User_Adapter))
                  throw new \Exception('Unknown error');

                $datas['data'] = $user->get_id();
              }
              catch (\Exception $e)
              {
                $datas['error'] = true;
                $datas['message'] = $e->getMessage();
              }

              $Serializer = $app['Core']['Serializer'];

              return new Response($Serializer->serialize($datas, 'json'), 200, array("Content-Type" => "application/json"));
            }
    );

    $controllers->post('/export/csv/', function(Application $app) use ($appbox)
            {
              $request = $app['request'];
              $user_query = new \User_Query($appbox, $app['Core']);

              $user = \User_Adapter::getInstance($appbox->get_session()->get_usr_id(), $appbox);
              $like_value = $request->get('like_value');
              $like_field = $request->get('like_field');
              $on_base = $request->get('base_id') ? : null;
              $on_sbas = $request->get('sbas_id') ? : null;

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
              do
              {
                $elligible_users->limit($offset, 20);
                $offset += 20;

                $results = $elligible_users->execute()->get_results();

                foreach ($results as $user)
                {
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
              }
              while (count($results) > 0);

              $out = \format::arr_to_csv($buffer);

              $headers = array(
                  'Content-type' => 'text/csv'
                  , 'Content-Disposition' => 'attachment; filename=export.txt'
              );
              $response = new Response($out, 200, $headers);
              $response->setCharset('UTF-8');

              return $response;
            }
    );

    return $controllers;
  }

}

