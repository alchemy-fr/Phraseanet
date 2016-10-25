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

use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Exception\SessionNotFound;
use Alchemy\Phrasea\Status\StatusStructureProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class RootController extends Controller
{
    public function indexAction(Request $request)
    {
        try {
            \Session_Logger::updateClientInfos($this->app, 3);
        } catch (SessionNotFound $e) {
            return $this->app->redirectPath('logout');
        }

        $params = $this->getSectionParameters($request->query->get('section', false));

        return $this->render('admin/index.html.twig', array_merge([
            'module'        => 'admin',
            'events'        => $this->app['events-manager'],
            'module_name'   => 'Admin',
            'notice'        => $request->query->get("notice")
        ], $params));
    }

    public function displayTreeAction(Request $request)
    {
        try {
            \Session_Logger::updateClientInfos($this->app, 3);
        } catch (SessionNotFound $e) {
            return $this->app->redirectPath('logout');
        }

        $params = $this->getSectionParameters($request->query->get('position', false));

        return $this->render('admin/tree.html.twig', $params);
    }
    
    public function testPathsAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            $this->app->abort(400);
        }
        if (!array_key_exists($request->getMimeType('json'), array_flip($request->getAcceptableContentTypes()))) {
            $this->app->abort(400, $this->app->trans('Bad request format, only JSON is allowed'));
        }

        if (0 === count($tests = $request->query->get('tests', []))) {
            $this->app->abort(400, $this->app->trans('Missing tests parameter'));
        }

        if (null === $path = $request->query->get('path')) {
            $this->app->abort(400, $this->app->trans('Missing path parameter'));
        }

        $result = false;
        foreach ($tests as $test) {
            switch ($test) {
                case 'writeable':
                    $result = is_writable($path);
                    break;
                case 'readable':
                default:
                    $result = is_readable($path);
            }
        }

        return $this->app->json(['results' => $result]);
    }

    /**
     * @param int $databox_id
     * @return string
     * @throws \Exception
     */
    public function displayStatusBitAction($databox_id)
    {
        if (!$this->getAclForUser()->has_right_on_sbas($databox_id, \ACL::BAS_MODIFY_STRUCT)) {
            $this->app->abort(403);
        }

        return $this->render('admin/statusbit.html.twig', [
            'databox' => $this->findDataboxById($databox_id),
        ]);
    }

    /**
     * @param Request $request
     * @param int     $databox_id
     * @return string
     * @throws \Exception
     */
    public  function displayDataboxStructureAction(Request $request, $databox_id)
    {
        if (!$this->getAclForUser()->has_right_on_sbas($databox_id, \ACL::BAS_MODIFY_STRUCT)) {
            $this->app->abort(403);
        }

        $databox = $this->findDataboxById((int) $databox_id);
        $structure = $databox->get_structure();
        $errors = \databox::get_structure_errors($this->app['translator'], $structure);

        if ($updateOk = !!$request->query->get('success', false)) {
            $updateOk = true;
        }

        if (false !== $errorsStructure = $request->query->get('error', false)) {
            $errorsStructure = true;
        }

        return $this->render('admin/structure.html.twig', [
            'databox'         => $databox,
            'errors'          => $errors,
            'structure'       => $structure,
            'errorsStructure' => $errorsStructure,
            'updateOk'        => $updateOk
        ]);
    }

    public function submitDatabaseStructureAction(Request $request, $databox_id)
    {
        if (!$this->getAclForUser()->has_right_on_sbas($databox_id, \ACL::BAS_MODIFY_STRUCT)) {
            $this->app->abort(403);
        }

        if (null === $structure = $request->request->get('structure')) {
            $this->app->abort(400, $this->app->trans('Missing "structure" parameter'));
        }

        $errors = \databox::get_structure_errors($this->app['translator'], $structure);

        $domst = new \DOMDocument('1.0', 'UTF-8');
        $domst->preserveWhiteSpace = false;
        $domst->formatOutput = true;

        if (count($errors) == 0 && $domst->loadXML($structure)) {
            $databox = $this->findDataboxById($databox_id);
            $databox->saveStructure($domst);

            return $this->app->redirectPath('database_display_stucture', ['databox_id' => $databox_id, 'success' => 1]);
        }

        return $this->app->redirectPath('database_display_stucture', [
            'databox_id' => $databox_id,
            'success' => 0,
            'error' => 'struct',
        ]);
    }

    public function displayDatabaseStatusBitFormAction(Request $request, $databox_id, $bit)
    {
        if (!$this->getAclForUser()->has_right_on_sbas($databox_id, \ACL::BAS_MODIFY_STRUCT)) {
            $this->app->abort(403);
        }

        $databox = $this->findDataboxById($databox_id);

        $statusStructure = $databox->getStatusStructure();

        switch ($errorMsg = $request->query->get('error')) {
            case 'rights':
                $errorMsg = $this->app->trans('You do not enough rights to update status');
                break;
            case 'too-big':
                $errorMsg = $this->app->trans('File is too big : 64k max');
                break;
            case 'upload-error':
                $errorMsg = $this->app->trans('Status icon upload failed : upload error');
                break;
            case 'wright-error':
                $errorMsg = $this->app->trans('Status icon upload failed : can not write on disk');
                break;
            case 'unknow-error':
                $errorMsg = $this->app->trans('Something wrong happend');
                break;
        }

        if ($statusStructure->hasStatus($bit)) {
            $status = $statusStructure->getStatus($bit);
        } else {
            $status = [
                "labeloff" => '',
                "labelon" => '',
                "img_off" => '',
                "img_on" => '',
                "path_off" => '',
                "path_on" => '',
                "searchable" => false,
                "printable" => false,
            ];

            foreach ($this->app['locales.available'] as $code => $language) {
                $status['labels_on'][$code] = null;
                $status['labels_off'][$code] = null;
            }
        }

        return $this->render('admin/statusbit/edit.html.twig', [
            'status' => $status,
            'errorMsg' => $errorMsg
        ]);
    }

    public  function deleteStatusBitAction(Request $request, $databox_id, $bit)
    {
        if (!$request->isXmlHttpRequest() || !array_key_exists($request->getMimeType('json'), array_flip($request->getAcceptableContentTypes()))) {
            $this->app->abort(400, $this->app->trans('Bad request format, only JSON is allowed'));
        }

        if (!$this->getAclForUser()->has_right_on_sbas($databox_id, \ACL::BAS_MODIFY_STRUCT)) {
            $this->app->abort(403);
        }

        $databox = $this->findDataboxById($databox_id);

        $error = false;

        try {
            $this->app['status.provider']->deleteStatus($databox->getStatusStructure(), $bit);
        } catch (\Exception $e) {
            $error = true;
        }

        return $this->app->json(['success' => !$error]);
    }
    
    public function submitStatusBitAction(Request $request, $databox_id, $bit) {
        if (!$this->getAclForUser()->has_right_on_sbas($databox_id, \ACL::BAS_MODIFY_STRUCT)) {
            $this->app->abort(403);
        }

        $properties = [
            'searchable' => $request->request->get('searchable') ? '1' : '0',
            'printable'  => $request->request->get('printable') ? '1' : '0',
            'name'       => $request->request->get('name', ''),
            'labelon'    => $request->request->get('label_on', ''),
            'labeloff'   => $request->request->get('label_off', ''),
            'labels_on'  => $request->request->get('labels_on', []),
            'labels_off' => $request->request->get('labels_off', []),
        ];

        $databox = $this->findDataboxById($databox_id);

        /** @var StatusStructureProviderInterface $statusProvider */
        $statusProvider = $this->app['status.provider'];
        $statusProvider->updateStatus($databox->getStatusStructure(), $bit, $properties);

        if (null !== $request->request->get('delete_icon_off')) {
            \databox_status::deleteIcon($this->app, $databox_id, $bit, 'off');
        }

        if (null !== $file = $request->files->get('image_off')) {
            try {
                \databox_status::updateIcon($this->app, $databox_id, $bit, 'off', $file);
            } catch (AccessDeniedHttpException $e) {
                return $this->app->redirectPath('database_display_statusbit_form', [
                    'databox_id' => $databox_id,
                    'bit'        => $bit,
                    'error'      => 'rights',
                ]);
            } catch (\Exception_InvalidArgument $e) {
                return $this->app->redirectPath('database_display_statusbit_form', [
                    'databox_id' => $databox_id,
                    'bit'        => $bit,
                    'error'      => 'unknow-error',
                ]);
            } catch (\Exception_Upload_FileTooBig $e) {
                return $this->app->redirectPath('database_display_statusbit_form', [
                    'databox_id' => $databox_id,
                    'bit'        => $bit,
                    'error'      => 'too-big',
                ]);
            } catch (\Exception_Upload_Error $e) {
                return $this->app->redirectPath('database_display_statusbit_form', [
                    'databox_id' => $databox_id,
                    'bit'        => $bit,
                    'error'      => 'upload-error',
                ]);
            } catch (\Exception_Upload_CannotWriteFile $e) {
                return $this->app->redirectPath('database_display_statusbit_form', [
                    'databox_id' => $databox_id,
                    'bit'        => $bit,
                    'error'      => 'wright-error',
                ]);
            } catch (\Exception $e) {
                return $this->app->redirectPath('database_display_statusbit_form', [
                    'databox_id' => $databox_id,
                    'bit'        => $bit,
                    'error'      => 'unknow-error',
                ]);
            }
        }

        if (null !== $request->request->get('delete_icon_on')) {
            \databox_status::deleteIcon($this->app, $databox_id, $bit, 'on');
        }

        if (null !== $file = $request->files->get('image_on')) {
            try {
                \databox_status::updateIcon($this->app, $databox_id, $bit, 'on', $file);
            } catch (AccessDeniedHttpException $e) {
                return $this->app->redirectPath('database_display_statusbit_form', [
                    'databox_id' => $databox_id,
                    'bit'        => $bit,
                    'error'      => 'rights',
                ]);
            } catch (\Exception_InvalidArgument $e) {
                return $this->app->redirectPath('database_display_statusbit_form', [
                    'databox_id' => $databox_id,
                    'bit'        => $bit,
                    'error'      => 'unknow-error',
                ]);
            } catch (\Exception_Upload_FileTooBig $e) {
                return $this->app->redirectPath('database_display_statusbit_form', [
                    'databox_id' => $databox_id,
                    'bit'        => $bit,
                    'error'      => 'too-big',
                ]);
            } catch (\Exception_Upload_Error $e) {
                return $this->app->redirectPath('database_display_statusbit_form', [
                    'databox_id' => $databox_id,
                    'bit'        => $bit,
                    'error'      => 'upload-error',
                ]);
            } catch (\Exception_Upload_CannotWriteFile $e) {
                return $this->app->redirectPath('database_display_statusbit_form', [
                    'databox_id' => $databox_id,
                    'bit'        => $bit,
                    'error'      => 'wright-error',
                ]);
            } catch (\Exception $e) {
                return $this->app->redirectPath('database_display_statusbit_form', [
                    'databox_id' => $databox_id,
                    'bit'        => $bit,
                    'error'      => 'unknow-error',
                ]);
            }
        }

        return $this->app->redirectPath('database_display_statusbit', ['databox_id' => $databox_id, 'success' => 1]);
    }
    
    /**
     * @param string $section
     * @return array
     */
    private function getSectionParameters($section)
    {
        $available = [
            'connected',
            'registrations',
            'taskmanager',
            'base',
            'bases',
            'collection',
            'user',
            'users',
        ];

        $feature = 'connected';
        $featured = false;
        $position = explode(':', $section);
        if (count($position) > 0) {
            if (in_array($position[0], $available)) {
                $feature = $position[0];

                if (isset($position[1])) {
                    $featured = $position[1];
                }
            }
        }

        $databoxes = $off_databoxes = [];
        $acl = $this->getAclForUser();
        foreach ($this->getApplicationBox()->get_databoxes() as $databox) {
            try {
                if (!$acl->has_access_to_sbas($databox->get_sbas_id())) {
                    continue;
                }
                $databox->get_connection();
            } catch (\Exception $e) {
                $off_databoxes[] = $databox;
                continue;
            }

            $databoxes[] = $databox;
        }

        return [
            'feature'       => $feature,
            'featured'      => $featured,
            'databoxes'     => $databoxes,
            'off_databoxes' => $off_databoxes,
        ];
    }
}
