<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Helper\Record as RecordHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BridgeController extends Controller
{
    private function requireConnection(\Bridge_Account $account)
    {
        $this->app['bridge.account'] = $account;

        if (!$account->get_api()->get_connector()->is_configured()) {
            throw new \Bridge_Exception_ApiConnectorNotConfigured("Bridge API Connector is not configured");
        }
        if (!$account->get_api()->get_connector()->is_connected()) {
            throw new \Bridge_Exception_ApiConnectorNotConnected("Bridge API Connector is not connected");
        }
    }

    public function doPostManager(Request $request)
    {
        $route = new RecordHelper\Bridge($this->app, $request);
        $params = [
            'user_accounts'      => \Bridge_Account::get_accounts_by_user($this->app, $this->getAuthenticatedUser()),
            'available_apis'     => \Bridge_Api::get_availables($this->app),
            'route'              => $route,
            'current_account_id' => '',
        ];

        return $this->render('prod/actions/Bridge/index.html.twig', $params);
    }

    public function doGetLogin($api_name)
    {
        $connector = \Bridge_Api::get_connector_by_name($this->app, $api_name);

        return $this->app->redirect($connector->get_auth_url());
    }

    public function doGetCallback($api_name)
    {
        $error_message = '';
        try {
            $api = \Bridge_Api::get_by_api_name($this->app, $api_name);
            $connector = $api->get_connector();
            $response = $connector->connect();
            $user_id = $connector->get_user_id();

            try {
                $account = \Bridge_Account::load_account_from_distant_id($this->app, $api, $this->getAuthenticatedUser(), $user_id);
            } catch (\Bridge_Exception_AccountNotFound $e) {
                $account = \Bridge_Account::create($this->app, $api, $this->getAuthenticatedUser(), $user_id, $connector->get_user_name());
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

        return $this->render('prod/actions/Bridge/callback.html.twig', $params);
    }

    public function doGetAccountLogout($account_id)
    {
        $account = \Bridge_Account::load_account($this->app, $account_id);
        $this->requireConnection($account);
        $account->get_api()->get_connector()->disconnect();

        return $this->app->redirectPath('bridge_load_elements', [
            'account_id' => $account_id,
            'type'       => $account->get_api()->get_connector()->get_default_element_type(),
        ]);
    }

    public function doPostAccountDelete($account_id)
    {
        $success = false;
        $message = '';
        try {
            $account = \Bridge_Account::load_account($this->app, $account_id);

            if ($account->get_user()->getId() !== $this->getAuthenticatedUser()->getId()) {
                throw new HttpException(403, 'Access forbiden');
            }

            $account->delete();
            $success = true;
        } catch (\Bridge_Exception_AccountNotFound $e) {
            $message = $this->app->trans('Account is not found.');
        } catch (\Exception $e) {
            $message = $this->app->trans('Something went wrong, please contact an administrator');
        }

        return $this->app->json(['success' => $success, 'message' => $message]);
    }

    public function doGetloadRecords(Request $request, $account_id)
    {
        $page = max((int) $request->query->get('page'), 0);
        $quantity = 10;
        $offset_start = max(($page - 1) * $quantity, 0);
        $account = \Bridge_Account::load_account($this->app, $account_id);
        $elements = \Bridge_Element::get_elements_by_account($this->app, $account, $offset_start, $quantity);

        $this->requireConnection($account);

        $params = [
            'adapter_action' => 'load-records',
            'account'        => $account,
            'elements'       => $elements,
            'error_message'  => $request->query->get('error'),
            'notice_message' => $request->query->get('notice'),
        ];

        return $this->render('prod/actions/Bridge/records_list.html.twig', $params);
    }

    public function doGetLoadElements(Request $request, $account_id, $type)
    {
        $page = max((int) $request->query->get('page'), 0);
        $quantity = 5;
        $offset_start = max(($page - 1) * $quantity, 0);
        $account = \Bridge_Account::load_account($this->app, $account_id);

        $this->requireConnection($account);

        $elements = $account->get_api()->list_elements($type, $offset_start, $quantity);

        $params = [
            'action_type'    => $type,
            'adapter_action' => 'load-elements',
            'account'        => $account,
            'elements'       => $elements,
            'error_message'  => $request->query->get('error'),
            'notice_message' => $request->query->get('notice'),
        ];

        return $this->render('prod/actions/Bridge/element_list.html.twig', $params);
    }

    public function doGetLoadContainers(Request $request, $account_id, $type)
    {
        $page = max((int) $request->query->get('page'), 0);
        $quantity = 5;
        $offset_start = max(($page - 1) * $quantity, 0);
        $account = \Bridge_Account::load_account($this->app, $account_id);

        $this->requireConnection($account);
        $elements = $account->get_api()->list_containers($type, $offset_start, $quantity);

        $params = [
            'action_type'    => $type,
            'adapter_action' => 'load-containers',
            'account'        => $account,
            'elements'       => $elements,
            'error_message'  => $request->query->get('error'),
            'notice_message' => $request->query->get('notice'),
        ];

        return $this->render('prod/actions/Bridge/element_list.html.twig', $params);
    }

    public function doGetAction(Request $request, $account_id, $action, $element_type)
    {
        $account = \Bridge_Account::load_account($this->app, $account_id);

        $this->requireConnection($account);
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
                    return $this->app->redirectPath('bridge_load_elements', [
                        'account_id' => $account_id,
                        'type'       => $element_type,
                        'page'       => '',
                        'error'      => $this->app->trans('Vous ne pouvez pas editer plusieurs elements simultanement'),
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
                throw new \Exception($this->app->trans('Vous essayez de faire une action que je ne connais pas !'));
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

        return $this->render($template, $params);
    }

    public function doPostAction(Request $request, $account_id, $action, $element_type)
    {
        $account = \Bridge_Account::load_account($this->app, $account_id);

        $this->requireConnection($account);

        $elements = $request->request->get('elements_list', []);
        $elements = is_array($elements) ? $elements : explode(';', $elements);

        $destination = $request->request->get('destination');

        $class = $account->get_api()->get_connector()->get_object_class_from_type($element_type);
        switch ($action) {
            case 'modify':
                if (count($elements) != 1) {
                    return $this->app->redirect('/prod/bridge/action/' . $account_id . '/' . $action . '/' . $element_type . '/?elements_list=' . implode(';', $elements) . '&error=' . $this->app->trans('Vous ne pouvez pas editer plusieurs elements simultanement'));
                }
                $element_id = reset($elements);
                try {
                    $datas = $account->get_api()->get_connector()->get_update_datas($request);
                    $errors = $account->get_api()->get_connector()->check_update_constraints($datas);

                    if (count($errors) > 0) {
                        $params = [
                            'element'           => $account->get_api()->get_element_from_id($element_id, $element_type),
                            'account'           => $account,
                            'destination'       => $destination,
                            'element_type'      => $element_type,
                            'action'            => $action,
                            'elements'          => $elements,
                            'adapter_action'    => $action,
                            'error_message'     => $this->app->trans('Request contains invalid datas'),
                            'constraint_errors' => $errors,
                            'notice_message'    => $request->request->get('notice'),
                        ];

                        $template = 'prod/actions/Bridge/' . $account->get_api()->get_connector()->get_name() . '/' . $element_type . '_' . $action . ($destination ? '_' . $destination : '') . '.html.twig';

                        return $this->render($template, $params);
                    }

                    $account->get_api()->update_element($element_type, $element_id, $datas);
                } catch (\Exception $e) {
                    return $this->app->redirect('/prod/bridge/action/' . $account_id . '/' . $action . '/' . $element_type . '/?elements_list[]=' . $element_id . '&error=' . get_class($e) . ' : ' . $e->getMessage());
                }

                return $this->app->redirect('/prod/bridge/adapter/' . $account_id . '/load-' . $class . 's/' . $element_type . '/?page=&update=success#anchor');
            case 'createcontainer':
                try {
                    $container_type = $request->request->get('f_container_type');

                    $account->get_api()->create_container($container_type, $request);
                } catch (\Exception $e) {
                    return $this->app->redirect('/prod/bridge/action/' . $account_id . '/' . $action . '/' . $element_type . '/?error=' . get_class($e) . ' : ' . $e->getMessage());
                }

                return $this->app->redirect('/prod/bridge/adapter/' . $account_id . '/load-' . $class . 's/' . $element_type . '/?page=&update=success#anchor');
            case 'moveinto':
                try {
                    $container_id = $request->request->get('container_id');
                    foreach ($elements as $element_id) {
                        $account->get_api()->add_element_to_container($element_type, $element_id, $destination, $container_id);
                    }
                } catch (\Exception $e) {
                    return $this->app->redirect('/prod/bridge/action/' . $account_id . '/' . $action . '/' . $element_type . '/?error=' . get_class($e) . ' : ' . $e->getMessage());
                }

                return $this->app->redirect('/prod/bridge/adapter/' . $account_id . '/load-containers/' . $destination . '/?page=&update=success#anchor');
            case 'deleteelement':
                try {
                    foreach ($elements as $element_id) {
                        $account->get_api()->delete_object($element_type, $element_id);
                    }
                } catch (\Exception $e) {
                    return $this->app->redirect('/prod/bridge/action/' . $account_id . '/' . $action . '/' . $element_type . '/?error=' . get_class($e) . $e->getMessage());
                }

                return $this->app->redirect('/prod/bridge/adapter/' . $account_id . '/load-' . $class . 's/' . $element_type . '/');
            default:
                throw new \Exception('Unknown action');
        }
    }

    public function doGetUpload(Request $request)
    {
        $account = \Bridge_Account::load_account($this->app, $request->query->get('account_id'));
        $this->requireConnection($account);

        $route = new RecordHelper\Bridge($this->app, $request);

        $route->grep_records($account->get_api()->acceptable_records());

        $params = [
            'route'             => $route,
            'account'           => $account,
            'error_message'     => $request->query->get('error'),
            'notice_message'    => $request->query->get('notice'),
            'constraint_errors' => null,
            'adapter_action'    => 'upload',
        ];

        return $this->render(
            'prod/actions/Bridge/' . $account->get_api()->get_connector()->get_name() . '/upload.html.twig', $params
        );
    }

    public function doPostUpload(Request $request)
    {
        $errors = [];
        $account = \Bridge_Account::load_account($this->app, $request->request->get('account_id'));
        $this->requireConnection($account);

        $route = new RecordHelper\Bridge($this->app, $request);
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
                'error_message'     => $this->app->trans('Request contains invalid datas'),
                'constraint_errors' => $errors,
                'notice_message'    => $request->request->get('notice'),
                'adapter_action'    => 'upload',
            ];

            return $this->render('prod/actions/Bridge/' . $account->get_api()->get_connector()->get_name() . '/upload.html.twig', $params);
        }

        foreach ($route->get_elements() as $record) {
            $datas = $connector->get_upload_datas($request, $record);
            $title = isset($datas["title"]) ? $datas["title"] : '';
            $default_type = $connector->get_default_element_type();
            \Bridge_Element::create($this->app, $account, $record, $title, \Bridge_Element::STATUS_PENDING, $default_type, $datas);
        }

        return $this->app->redirect('/prod/bridge/adapter/' . $account->get_id() . '/load-records/?notice=' . $this->app->trans('%quantity% elements en attente', ['%quantity%' => count($route->get_elements())]));
    }
}
