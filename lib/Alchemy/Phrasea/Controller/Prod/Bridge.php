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

class Bridge implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['bridge.controller'] = $this;

        $controllers = $app['controllers_factory'];

        $controllers->before(function (Request $request) use ($app) {
            $app['firewall']->requireRight('bas_chupub');
        });

        $controllers
            ->post('/manager/', 'bridge.controller:doPostManager');

        $controllers
            ->get('/login/{api_name}/', 'bridge.controller:doGetLogin')
            ->bind('prod_bridge_login');

        $controllers
            ->get('/callback/{api_name}/', 'bridge.controller:doGetCallback')
            ->bind('prod_bridge_callback');

        $controllers
            ->get('/adapter/{account_id}/logout/', 'bridge.controller:doGetAccountLogout')
            ->bind('prod_bridge_account_logout')
            ->assert('account_id', '\d+');

        $controllers
            ->post('/adapter/{account_id}/delete/', 'bridge.controller:doPostAccountDelete')
            ->assert('account_id', '\d+');

        $controllers
            ->get('/adapter/{account_id}/load-records/', 'bridge.controller:doGetloadRecords')
            ->bind('prod_bridge_account_loadrecords')
            ->assert('account_id', '\d+');

        $controllers
            ->get('/adapter/{account_id}/load-elements/{type}/', 'bridge.controller:doGetLoadElements')
            ->bind('bridge_load_elements')
            ->assert('account_id', '\d+');

        $controllers
            ->get('/adapter/{account_id}/load-containers/{type}/', 'bridge.controller:doGetLoadContainers')
            ->bind('prod_bridge_account_loadcontainers')
            ->assert('account_id', '\d+');

        $controllers
            ->get('/action/{account_id}/{action}/{element_type}/', 'bridge.controller:doGetAction')
            ->bind('bridge_account_action')
            ->assert('account_id', '\d+');

        $controllers
            ->post('/action/{account_id}/{action}/{element_type}/', 'bridge.controller:doPostAction')
            ->bind('bridge_account_do_action')
            ->assert('account_id', '\d+');

        $controllers
            ->get('/upload/', 'bridge.controller:doGetUpload')
            ->bind('prod_bridge_upload');

        $controllers
            ->post('/upload/', 'bridge.controller:doPostUpload')
            ->bind('prod_bridge_do_upload');

        return $controllers;
    }

    private function requireConnection(Application $app, \Bridge_Account $account)
    {
        $app['bridge.account'] = $account;

        if (!$account->get_api()->get_connector()->is_configured()) {
            throw new \Bridge_Exception_ApiConnectorNotConfigured("Bridge API Connector is not configured");
        }
        if (!$account->get_api()->get_connector()->is_connected()) {
            throw new \Bridge_Exception_ApiConnectorNotConnected("Bridge API Connector is not connected");
        }
    }

    public function doPostManager(Application $app, Request $request)
    {
        $route = new RecordHelper\Bridge($app, $request);
        $params = [
            'user_accounts'      => \Bridge_Account::get_accounts_by_user($app, $app['authentication']->getUser()),
            'available_apis'     => \Bridge_Api::get_availables($app),
            'route'              => $route,
            'current_account_id' => '',
        ];

        return $app['twig']->render('prod/actions/Bridge/index.html.twig', $params);
    }

    public function doGetLogin(Application $app, Request $request, $api_name)
    {
        $connector = \Bridge_Api::get_connector_by_name($app, $api_name);

        return $app->redirect($connector->get_auth_url());
    }

    public function doGetCallback(Application $app, Request $request, $api_name)
    {
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

            if (isset($response['auth_token'])) {
                $settings->set('auth_token', $response['auth_token']);
            }
            if (isset($response['refresh_token'])) {
                $settings->set('refresh_token', $response['refresh_token']);
            }

            $connector->set_auth_settings($settings);
            $connector->reconnect();
        } catch (\Exception $e) {
            $error_message = $e->getMessage();
        }

        $params = ['error_message' => $error_message];

        return $app['twig']->render('prod/actions/Bridge/callback.html.twig', $params);
    }

    public function doGetAccountLogout(Application $app, Request $request, $account_id)
    {
        $account = \Bridge_Account::load_account($app, $account_id);
        $this->requireConnection($app, $account);
        $account->get_api()->get_connector()->disconnect();

        return $app->redirectPath('bridge_load_elements', [
            'account_id' => $account_id,
            'type'       => $account->get_api()->get_connector()->get_default_element_type(),
        ]);
    }

    public function doPostAccountDelete(Application $app, Request $request, $account_id)
    {
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

        return $app->json(['success' => $success, 'message' => $message]);
    }

    public function doGetloadRecords(Application $app, Request $request, $account_id)
    {
        $page = max((int) $request->query->get('page'), 0);
        $quantity = 10;
        $offset_start = max(($page - 1) * $quantity, 0);
        $account = \Bridge_Account::load_account($app, $account_id);
        $elements = \Bridge_Element::get_elements_by_account($app, $account, $offset_start, $quantity);

        $this->requireConnection($app, $account);

        $params = [
            'adapter_action' => 'load-records'
            , 'account'        => $account
            , 'elements'       => $elements
            , 'error_message'  => $request->query->get('error')
            , 'notice_message' => $request->query->get('notice')
        ];

        return $app['twig']->render('prod/actions/Bridge/records_list.html.twig', $params);
    }

    public function doGetLoadElements(Application $app, Request $request, $account_id, $type)
    {
        $page = max((int) $request->query->get('page'), 0);
        $quantity = 5;
        $offset_start = max(($page - 1) * $quantity, 0);
        $account = \Bridge_Account::load_account($app, $account_id);

        $this->requireConnection($app, $account);

        $elements = $account->get_api()->list_elements($type, $offset_start, $quantity);

        $params = [
            'action_type'    => $type,
            'adapter_action' => 'load-elements',
            'account'        => $account,
            'elements'       => $elements,
            'error_message'  => $request->query->get('error'),
            'notice_message' => $request->query->get('notice'),
        ];

        return $app['twig']->render('prod/actions/Bridge/element_list.html.twig', $params);
    }

    public function doGetLoadContainers(Application $app, Request $request, $account_id, $type)
    {
        $page = max((int) $request->query->get('page'), 0);
        $quantity = 5;
        $offset_start = max(($page - 1) * $quantity, 0);
        $account = \Bridge_Account::load_account($app, $account_id);

        $this->requireConnection($app, $account);
        $elements = $account->get_api()->list_containers($type, $offset_start, $quantity);

        $params = [
            'action_type'    => $type,
            'adapter_action' => 'load-containers',
            'account'        => $account,
            'elements'       => $elements,
            'error_message'  => $request->query->get('error'),
            'notice_message' => $request->query->get('notice'),
        ];

        return $app['twig']->render('prod/actions/Bridge/element_list.html.twig', $params);
    }

    public function doGetAction(Application $app, Request $request, $account_id, $action, $element_type)
    {
        $account = \Bridge_Account::load_account($app, $account_id);

        $this->requireConnection($app, $account);
        $elements = $request->query->get('elements_list', []);
        $elements = is_array($elements) ? $elements : explode(';', $elements);

        $destination = $request->query->get('destination');
        $route_params = [];
        $class = $account->get_api()->get_connector()->get_object_class_from_type($element_type);

        switch ($action) {
            case 'createcontainer':
                break;

            case 'modify':
                if (count($elements) != 1) {
                    return $app->redirectPath('bridge_load_elements', [
                        'account_id' => $account_id,
                        'type'       => $element_type,
                        'page'       => '',
                        'error'      => _('Vous ne pouvez pas editer plusieurs elements simultanement'),
                    ]);
                }
                foreach ($elements as $element_id) {
                    if ($class === \Bridge_Api_Interface::OBJECT_CLASS_ELEMENT) {
                        $route_params = ['element' => $account->get_api()->get_element_from_id($element_id, $element_type)];
                    }
                    if ($class === \Bridge_Api_Interface::OBJECT_CLASS_CONTAINER) {
                        $route_params = ['element' => $account->get_api()->get_container_from_id($element_id, $element_type)];
                    }
                }
                break;

            case 'moveinto':
                $route_params = ['containers' => $account->get_api()->list_containers($destination, 0, 0)];
                break;

            case 'deleteelement':
                break;

            default:
                throw new \Exception(_('Vous essayez de faire une action que je ne connais pas !'));
                break;
        }

        $params = [
            'account'           => $account,
            'destination'       => $destination,
            'element_type'      => $element_type,
            'action'            => $action,
            'constraint_errors' => null,
            'adapter_action'    => $action,
            'elements'          => $elements,
            'error_message'     => $request->query->get('error'),
            'notice_message'    => $request->query->get('notice'),
        ];

        $params = array_merge($params, $route_params);
        $template = 'prod/actions/Bridge/' . $account->get_api()->get_connector()->get_name() . '/' . $element_type . '_' . $action . ($destination ? '_' . $destination : '') . '.html.twig';

        return $app['twig']->render($template, $params);
    }

    public function doPostAction(Application $app, Request $request, $account_id, $action, $element_type)
    {
        $account = \Bridge_Account::load_account($app, $account_id);

        $this->requireConnection($app, $account);

        $elements = $request->request->get('elements_list', []);
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
                        $datas = $account->get_api()->get_connector()->get_update_datas($request);
                        $errors = $account->get_api()->get_connector()->check_update_constraints($datas);
                    }

                    if (count($errors) > 0) {
                        $params = [
                            'element'           => $account->get_api()->get_element_from_id($element_id, $element_type),
                            'account'           => $account,
                            'destination'       => $destination,
                            'element_type'      => $element_type,
                            'action'            => $action,
                            'elements'          => $elements,
                            'adapter_action'    => $action,
                            'error_message'     => _('Request contains invalid datas'),
                            'constraint_errors' => $errors,
                            'notice_message'    => $request->request->get('notice'),
                        ];

                        $template = 'prod/actions/Bridge/' . $account->get_api()->get_connector()->get_name() . '/' . $element_type . '_' . $action . ($destination ? '_' . $destination : '') . '.html.twig';

                        return $app['twig']->render($template, $params);
                    }

                    foreach ($elements as $element_id) {
                        $datas = $account->get_api()->get_connector()->get_update_datas($request);
                        $account->get_api()->update_element($element_type, $element_id, $datas);
                    }
                } catch (\Exception $e) {
                    return $app->redirect('/prod/bridge/action/' . $account_id . '/' . $action . '/' . $element_type . '/?elements_list[]=' . $element_id . '&error=' . get_class($e) . ' : ' . $e->getMessage());
                }

                return $app->redirect('/prod/bridge/adapter/' . $account_id . '/load-' . $class . 's/' . $element_type . '/?page=&update=success#anchor');
            case 'createcontainer':
                try {
                    $container_type = $request->request->get('f_container_type');

                    $account->get_api()->create_container($container_type, $request);
                } catch (\Exception $e) {
                    return $app->redirect('/prod/bridge/action/' . $account_id . '/' . $action . '/' . $element_type . '/?error=' . get_class($e) . ' : ' . $e->getMessage());
                }

                return $app->redirect('/prod/bridge/adapter/' . $account_id . '/load-' . $class . 's/' . $element_type . '/?page=&update=success#anchor');
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
            case 'deleteelement':
                try {
                    foreach ($elements as $element_id) {
                        $account->get_api()->delete_object($element_type, $element_id);
                    }
                } catch (\Exception $e) {
                    return $app->redirect('/prod/bridge/action/' . $account_id . '/' . $action . '/' . $element_type . '/?error=' . get_class($e) . $e->getMessage());
                }

                return $app->redirect('/prod/bridge/adapter/' . $account_id . '/load-' . $class . 's/' . $element_type . '/');
            default:
                throw new \Exception('Unknown action');
                break;
        }

        return new Response($html);
    }

    public function doGetUpload(Application $app, Request $request)
    {
        $account = \Bridge_Account::load_account($app, $request->query->get('account_id'));
        $this->requireConnection($app, $account);

        $route = new RecordHelper\Bridge($app, $request);

        $route->grep_records($account->get_api()->acceptable_records());

        $params = [
            'route'             => $route,
            'account'           => $account,
            'error_message'     => $request->query->get('error'),
            'notice_message'    => $request->query->get('notice'),
            'constraint_errors' => null,
            'adapter_action'    => 'upload',
        ];

        return $app['twig']->render(
            'prod/actions/Bridge/' . $account->get_api()->get_connector()->get_name() . '/upload.html.twig', $params
        );
    }

    public function doPostUpload(Application $app, Request $request)
    {
        $errors = [];
        $account = \Bridge_Account::load_account($app, $request->request->get('account_id'));
        $this->requireConnection($app, $account);

        $route = new RecordHelper\Bridge($app, $request);
        $route->grep_records($account->get_api()->acceptable_records());
        $connector = $account->get_api()->get_connector();

        // check constraints
        foreach ($route->get_elements() as $record) {
            $datas = $connector->get_upload_datas($request, $record);
            $errors = array_merge($errors, $connector->check_upload_constraints($datas, $record));
        }

        if (count($errors) > 0) {
            $params = [
                'route'             => $route,
                'account'           => $account,
                'error_message'     => _('Request contains invalid datas'),
                'constraint_errors' => $errors,
                'notice_message'    => $request->request->get('notice'),
                'adapter_action'    => 'upload',
            ];

            return $app['twig']->render('prod/actions/Bridge/' . $account->get_api()->get_connector()->get_name() . '/upload.html.twig', $params);
        }

        foreach ($route->get_elements() as $record) {
            $datas = $connector->get_upload_datas($request, $record);
            $title = isset($datas["title"]) ? $datas["title"] : '';
            $default_type = $connector->get_default_element_type();
            \Bridge_Element::create($app, $account, $record, $title, \Bridge_Element::STATUS_PENDING, $default_type, $datas);
        }

        return $app->redirect('/prod/bridge/adapter/' . $account->get_id() . '/load-records/?notice=' . sprintf(_('%d elements en attente'), count($route->get_elements())));
    }
}
