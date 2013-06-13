<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Helper\Record as RecordHelper;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Bridge implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->before(function(Request $request) use ($app) {
            $app['firewall']
                ->requireNotGuest()
                ->requireRight('bas_chupub');
        });

        $app['require_connection'] = $app->protect(function(\Bridge_Account $account) use ($app) {
            $app['current_account'] = function() use ($account) {
                    return $account;
                };

            if (!$account->get_api()->get_connector()->is_configured())
                throw new \Bridge_Exception_ApiConnectorNotConfigured("Bridge API Connector is not configured");
            if (!$account->get_api()->get_connector()->is_connected())
                throw new \Bridge_Exception_ApiConnectorNotConnected("Bridge API Connector is not connected");

            return;
        });

        $controllers->post('/manager/', function(Application $app) {
            $route = new RecordHelper\Bridge($app, $app['request']);

            $params = array(
                'user_accounts'      => \Bridge_Account::get_accounts_by_user($app, $app['authentication']->getUser())
                , 'available_apis'     => \Bridge_Api::get_availables($app)
                , 'route'              => $route
                , 'current_account_id' => ''
            );

            return $app['twig']->render('prod/actions/Bridge/index.html.twig', $params);
        });

        $controllers->get('/login/{api_name}/', function(Application $app, $api_name) {
            $connector = \Bridge_Api::get_connector_by_name($app, $api_name);

            return $app->redirect($connector->get_auth_url());
        })->bind('prod_bridge_login');

        $controllers->get('/callback/{api_name}/', function(Application $app, $api_name) {
            $error_message = '';
            try {
                $api = \Bridge_Api::get_by_api_name($app, $api_name);
                $connector = $api->get_connector();

                $response = $connector->connect();

                $user_id = $connector->get_user_id();

                try {
                    $account = \Bridge_Account::load_account_from_distant_id($app, $api, $app['authentication']->getUser(), $user_id);
                } catch (\Bridge_Exception_AccountNotFound $e) {
                    $account = \Bridge_Account::create($app, $api, $app['authentication']->getUser(), $user_id, $connector->get_user_name());
                }
                $settings = $account->get_settings();

                if (isset($response['auth_token']))
                    $settings->set('auth_token', $response['auth_token']);
                if (isset($response['refresh_token']))
                    $settings->set('refresh_token', $response['refresh_token']);

                $connector->set_auth_settings($settings);

                $connector->reconnect();
            } catch (\Exception $e) {
                $error_message = $e->getMessage();
            }

            $params = array('error_message' => $error_message);

            return $app['twig']->render('prod/actions/Bridge/callback.html.twig', $params);
        })->bind('prod_bridge_callback');

        $controllers->get('/adapter/{account_id}/logout/', function(Application $app, $account_id) {
            $account = \Bridge_Account::load_account($app, $account_id);
            $app['require_connection']($account);
            $account->get_api()->get_connector()->disconnect();

            return $app->redirectPath('bridge_load_elements', array(
                'account_id' => $account_id,
                'type'       => $account->get_api()->get_connector()->get_default_element_type(),
            ));
        })
            ->bind('prod_bridge_account_logout')
            ->assert('account_id', '\d+');

        $controllers->post('/adapter/{account_id}/delete/'
            , function($account_id) use ($app) {
                $success = false;
                $message = '';
                try {
                    $account = \Bridge_Account::load_account($app, $account_id);

                     if ($account->get_user()->get_id() !== $app['authentication']->getUser()->get_id()) {
                         throw new HttpException(403, 'Access forbiden');
                     }

                    $account->delete();
                    $success = true;
                } catch (\Bridge_Exception_AccountNotFound $e) {
                    $message = _('Account is not found.');
                } catch (\Exception $e) {
                    $message = _('Something went wrong, please contact an administrator');
                }

                return $app->json(array('success' => $success, 'message' => $message));
            })->assert('account_id', '\d+');

        $controllers->get('/adapter/{account_id}/load-records/', function(Application $app, $account_id) {
            $page = max((int) $app['request']->query->get('page'), 0);
            $quantity = 10;
            $offset_start = max(($page - 1) * $quantity, 0);
            $account = \Bridge_Account::load_account($app, $account_id);
            $elements = \Bridge_Element::get_elements_by_account($app, $account, $offset_start, $quantity);

            $app['require_connection']($account);

            $params = array(
                'adapter_action' => 'load-records'
                , 'account'        => $account
                , 'elements'       => $elements
                , 'error_message'  => $app['request']->query->get('error')
                , 'notice_message' => $app['request']->query->get('notice')
            );

            return $app['twig']->render('prod/actions/Bridge/records_list.html.twig', $params);
        })
            ->bind('prod_bridge_account_loadrecords')
            ->assert('account_id', '\d+');

        $controllers->get('/adapter/{account_id}/load-elements/{type}/', function($account_id, $type) use ($app) {
            $page = max((int) $app['request']->query->get('page'), 0);
            $quantity = 5;
            $offset_start = max(($page - 1) * $quantity, 0);
            $account = \Bridge_Account::load_account($app, $account_id);

            $app['require_connection']($account);

            $elements = $account->get_api()->list_elements($type, $offset_start, $quantity);

            $params = array(
                'action_type'    => $type
                , 'adapter_action' => 'load-elements'
                , 'account'        => $account
                , 'elements'       => $elements
                , 'error_message'  => $app['request']->query->get('error')
                , 'notice_message' => $app['request']->query->get('notice')
            );

            return $app['twig']->render('prod/actions/Bridge/element_list.html.twig', $params);
        })
            ->bind('bridge_load_elements')
            ->assert('account_id', '\d+');

        $controllers->get('/adapter/{account_id}/load-containers/{type}/', function(Application $app, $account_id, $type) {

            $page = max((int) $app['request']->query->get('page'), 0);
            $quantity = 5;
            $offset_start = max(($page - 1) * $quantity, 0);
            $account = \Bridge_Account::load_account($app, $account_id);

            $app['require_connection']($account);
            $elements = $account->get_api()->list_containers($type, $offset_start, $quantity);

            $params = array(
                'action_type'    => $type
                , 'adapter_action' => 'load-containers'
                , 'account'        => $account
                , 'elements'       => $elements
                , 'error_message'  => $app['request']->query->get('error')
                , 'notice_message' => $app['request']->query->get('notice')
            );

            return $app['twig']->render('prod/actions/Bridge/element_list.html.twig', $params);
        })
            ->bind('prod_bridge_account_loadcontainers')
            ->assert('account_id', '\d+');

        $controllers->get('/action/{account_id}/{action}/{element_type}/', function(Application $app, $account_id, $action, $element_type) {

            $account = \Bridge_Account::load_account($app, $account_id);

            $app['require_connection']($account);
            $request = $app['request'];
            $elements = $request->query->get('elements_list', array());
            $elements = is_array($elements) ? $elements : explode(';', $elements);

            $destination = $request->query->get('destination');
            $route_params = array();
            $class = $account->get_api()->get_connector()->get_object_class_from_type($element_type);

            switch ($action) {
                case 'createcontainer':
                    break;

                case 'modify':
                    if (count($elements) != 1) {
                        return $app->redirectPath('bridge_load_elements', array(
                            'account_id' => $account_id,
                            'type'       => $element_type,
                            'page'       => '',
                            'error'      => _('Vous ne pouvez pas editer plusieurs elements simultanement'),
                        ));
                    }
                    foreach ($elements as $element_id) {
                        if ($class === \Bridge_Api_Interface::OBJECT_CLASS_ELEMENT) {
                            $route_params = array('element' => $account->get_api()->get_element_from_id($element_id, $element_type));
                        }
                        if ($class === \Bridge_Api_Interface::OBJECT_CLASS_CONTAINER) {
                            $route_params = array('element' => $account->get_api()->get_container_from_id($element_id, $element_type));
                        }
                    }
                    break;

                case 'moveinto':

                    $route_params = array('containers' => $account->get_api()->list_containers($destination, 0, 0));
                    break;

                case 'deleteelement':

                    break;

                default:
                    throw new \Exception(_('Vous essayez de faire une action que je ne connais pas !'));
                    break;
            }

            $params = array(
                'account'           => $account
                , 'destination'       => $destination
                , 'element_type'      => $element_type
                , 'action'            => $action
                , 'constraint_errors' => null
                , 'adapter_action'    => $action
                , 'elements'          => $elements
                , 'error_message'     => $app['request']->query->get('error')
                , 'notice_message'    => $app['request']->query->get('notice')
            );

            $params = array_merge($params, $route_params);

            $template = 'prod/actions/Bridge/' . $account->get_api()->get_connector()->get_name() . '/' . $element_type . '_' . $action . ($destination ? '_' . $destination : '') . '.html.twig';

            return $app['twig']->render($template, $params);
        })
            ->bind('bridge_account_action')
            ->assert('account_id', '\d+');

        $controllers->post('/action/{account_id}/{action}/{element_type}/', function(Application $app, $account_id, $action, $element_type) {
            $account = \Bridge_Account::load_account($app, $account_id);

            $app['require_connection']($account);

            $request = $app['request'];
            $elements = $request->request->get('elements_list', array());
            $elements = is_array($elements) ? $elements : explode(';', $elements);

            $destination = $request->request->get('destination');

            $class = $account->get_api()->get_connector()->get_object_class_from_type($element_type);
            $html = '';
            switch ($action) {
                case 'modify':
                    if (count($elements) != 1) {
                        return $app->redirect('/prod/bridge/action/' . $account_id . '/' . $action . '/' . $element_type . '/?elements_list=' . implode(';', $elements) . '&error=' . _('Vous ne pouvez pas editer plusieurs elements simultanement'));
                    }
                    try {
                        foreach ($elements as $element_id) {
                            $datas = $account->get_api()->get_connector()->get_update_datas($app['request']);
                            $errors = $account->get_api()->get_connector()->check_update_constraints($datas);
                        }

                        if (count($errors) > 0) {
                            $params = array(
                                'element'           => $account->get_api()->get_element_from_id($element_id, $element_type)
                                , 'account'           => $account
                                , 'destination'       => $destination
                                , 'element_type'      => $element_type
                                , 'action'            => $action
                                , 'elements'          => $elements
                                , 'adapter_action'    => $action
                                , 'error_message'     => _('Request contains invalid datas')
                                , 'constraint_errors' => $errors
                                , 'notice_message'    => $app['request']->request->get('notice')
                            );

                            $template = 'prod/actions/Bridge/' . $account->get_api()->get_connector()->get_name() . '/' . $element_type . '_' . $action . ($destination ? '_' . $destination : '') . '.html.twig';

                            return $app['twig']->render($template, $params);
                        }

                        foreach ($elements as $element_id) {
                            $datas = $account->get_api()->get_connector()->get_update_datas($app['request']);
                            $account->get_api()->update_element($element_type, $element_id, $datas);
                        }
                    } catch (\Exception $e) {
                        return $app->redirect('/prod/bridge/action/' . $account_id . '/' . $action . '/' . $element_type . '/?elements_list[]=' . $element_id . '&error=' . get_class($e) . ' : ' . $e->getMessage());
                    }

                    return $app->redirect('/prod/bridge/adapter/' . $account_id . '/load-' . $class . 's/' . $element_type . '/?page=&update=success#anchor');

                    break;
                case 'createcontainer':
                    try {

                        $container_type = $request->request->get('f_container_type');

                        $account->get_api()->create_container($container_type, $app['request']);
                    } catch (\Exception $e) {
                        return $app->redirect('/prod/bridge/action/' . $account_id . '/' . $action . '/' . $element_type . '/?error=' . get_class($e) . ' : ' . $e->getMessage());
                    }

                    return $app->redirect('/prod/bridge/adapter/' . $account_id . '/load-' . $class . 's/' . $element_type . '/?page=&update=success#anchor');

                    break;
                case 'moveinto':
                    try {
                        $container_id = $request->request->get('container_id');
                        foreach ($elements as $element_id) {
                            $account->get_api()->add_element_to_container($element_type, $element_id, $destination, $container_id);
                        }
                    } catch (\Exception $e) {
                        return $app->redirect('/prod/bridge/action/' . $account_id . '/' . $action . '/' . $element_type . '/?error=' . get_class($e) . ' : ' . $e->getMessage());
                    }

                    return $app->redirect('/prod/bridge/adapter/' . $account_id . '/load-containers/' . $destination . '/?page=&update=success#anchor');

                    break;

                case 'deleteelement':
                    try {
                        foreach ($elements as $element_id) {
                            $account->get_api()->delete_object($element_type, $element_id);
                        }
                    } catch (\Exception $e) {
                        return $app->redirect('/prod/bridge/action/' . $account_id . '/' . $action . '/' . $element_type . '/?error=' . get_class($e) . $e->getMessage());
                    }

                    return $app->redirect('/prod/bridge/adapter/' . $account_id . '/load-' . $class . 's/' . $element_type . '/');
                    break;
                default:
                    throw new \Exception('Unknown action');
                    break;
            }

            return new Response($html);
        })
            ->bind('bridge_account_do_action')
            ->assert('account_id', '\d+');

        $controllers->get('/upload/', function(Application $app) {
            $request = $app['request'];
            $account = \Bridge_Account::load_account($app, $request->query->get('account_id'));
            $app['require_connection']($account);

            $route = new RecordHelper\Bridge($app, $app['request']);

            $route->grep_records($account->get_api()->acceptable_records());

            $params = array(
                'route'             => $route
                , 'account'           => $account
                , 'error_message'     => $app['request']->query->get('error')
                , 'notice_message'    => $app['request']->query->get('notice')
                , 'constraint_errors' => null
                , 'adapter_action'    => 'upload'
            );

            return $app['twig']->render(
                'prod/actions/Bridge/' . $account->get_api()->get_connector()->get_name() . '/upload.html.twig', $params
            );
        })->bind('prod_bridge_upload');

        $controllers->post('/upload/', function(Application $app) {
            $errors = array();
            $request = $app['request'];
            $account = \Bridge_Account::load_account($app, $request->request->get('account_id'));
            $app['require_connection']($account);

            $route = new RecordHelper\Bridge($app, $app['request']);
            $route->grep_records($account->get_api()->acceptable_records());
            $connector = $account->get_api()->get_connector();

            /**
             * check constraints
             */
            foreach ($route->get_elements() as $record) {
                $datas = $connector->get_upload_datas($request, $record);
                $errors = array_merge($errors, $connector->check_upload_constraints($datas, $record));
            }

            if (count($errors) > 0) {

                $params = array(
                    'route'             => $route
                    , 'account'           => $account
                    , 'error_message'     => _('Request contains invalid datas')
                    , 'constraint_errors' => $errors
                    , 'notice_message'    => $app['request']->request->get('notice')
                    , 'adapter_action'    => 'upload'
                );

                return $app['twig']->render('prod/actions/Bridge/' . $account->get_api()->get_connector()->get_name() . '/upload.html.twig', $params);
            }

            foreach ($route->get_elements() as $record) {
                $datas = $connector->get_upload_datas($request, $record);
                $title = isset($datas["title"]) ? $datas["title"] : '';
                $default_type = $connector->get_default_element_type();
                \Bridge_Element::create($app, $account, $record, $title, \Bridge_Element::STATUS_PENDING, $default_type, $datas);
            }

            return $app->redirect('/prod/bridge/adapter/' . $account->get_id() . '/load-records/?notice=' . sprintf(_('%d elements en attente'), count($route->get_elements())));
        })->bind('prod_bridge_do_upload');

        return $controllers;
    }
}
