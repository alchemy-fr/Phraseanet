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

use Alchemy\Phrasea\Application\Helper\UserQueryAware;
use Alchemy\Phrasea\Authentication\ACLProvider;
use Alchemy\Phrasea\Authentication\Authenticator;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Core\Configuration\DisplaySettingService;
use Alchemy\Phrasea\Model\Manipulator\TaskManipulator;
use Alchemy\Phrasea\SearchEngine\Elastic\ElasticsearchOptions;
use Alchemy\Phrasea\WorkerManager\Event\PopulateIndexEvent;
use Alchemy\Phrasea\WorkerManager\Event\WorkerEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DataboxController extends Controller
{
    use UserQueryAware;

    /**
     * @param Request $request
     * @param integer $databox_id
     * @return Response
     */
    public function getDatabase(Request $request, $databox_id)
    {
        $databox = $this->findDataboxById($databox_id);

        switch ($errorMsg = $request->query->get('error')) {
            case 'file-error':
                $errorMsg = $this->app->trans('Error while sending the file');
                break;
            case 'file-invalid':
                $errorMsg = $this->app->trans('Invalid file format');
                break;
            case 'file-too-big':
                $errorMsg = $this->app->trans('The file is too big');
                break;
        }

        return $this->render('admin/databox/databox.html.twig', [
            'databox'    => $databox,
            'showDetail' => (int)$request->query->get("sta") < 1,
            'errorMsg'   => $errorMsg,
            'reloadTree' => $request->query->get('reload-tree') === '1'
        ]);
    }

    /**
     * Get databox CGU's
     *
     * @param  integer     $databox_id The requested databox
     * @return Response
     */
    public function getDatabaseCGU($databox_id)
    {
        return $this->render('admin/databox/cgus.html.twig', [
            'languages'      => $this->app['locales.available'],
            'cgus'           => $this->findDataboxById($databox_id)->get_cgus(),
            'current_locale' => $this->app['locale'],
        ]);
    }

    /**
     * Delete a databox
     *
     * @param  Request $request    The current HTTP request
     * @param  integer $databox_id The requested databox
     * @return JsonResponse|RedirectResponse
     */
    public function deleteBase(Request $request, $databox_id)
    {
        $databox = null;
        $success = false;
        $msg = $this->app->trans('An error occured');
        try {
            $databox = $this->findDataboxById($databox_id);

            if ($databox->get_record_amount() > 0) {
                $msg = $this->app->trans('admin::base: vider la base avant de la supprimer');
            } else {
                $databox->unmount_databox();
                $this->getApplicationBox()->write_databox_pic(
                    $this->app['media-alchemyst'],
                    $this->app['filesystem'],
                    $databox,
                    null,
                    \databox::PIC_PDF
                );
                $databox->delete();
                $success = true;
                $msg = $this->app->trans('Successful removal');
            }
        } catch (\Exception $e) {

        }
        if (!$databox) {
            $this->app->abort(404, $this->app->trans('admin::base: databox not found', ['databox_id' => $databox_id]));
        }

        if ('json' === $request->getRequestFormat()) {
            return $this->app->json([
                'success' => $success,
                'msg'     => $msg,
                'sbas_id' => $databox->get_sbas_id()
            ]);
        }

        $params = [
            'databox_id' => $databox->get_sbas_id(),
            'success'    => (int) $success,
        ];

        if ($databox->get_record_amount() > 0) {
            $params['error'] = 'databox-not-empty';
        }

        return $this->app->redirectPath('admin_database', $params);
    }

    public function setLabels(Request $request, $databox_id)
    {
        if (null === $labels = $request->request->get('labels')) {
            $this->app->abort(400, $this->app->trans('Missing labels parameter'));
        }
        if (false === is_array($labels)) {
            $this->app->abort(400, $this->app->trans('Invalid labels parameter'));
        }

        $databox = $this->findDataboxById($databox_id);
        $success = true;

        try {
            foreach ($this->app['locales.available'] as $code => $language) {
                if (!isset($labels[$code])) {
                    continue;
                }
                $value = $labels[$code] ?: null;
                $databox->set_label($code, $value);
            }
        } catch (\Exception $e) {
            $success = false;
        }

        if ('json' === $request->getRequestFormat()) {
            return $this->app->json([
                'success' => $success,
                'msg'     => $success ? $this->app->trans('Successful update') : $this->app->trans('An error occured'),
            ]);
        }

        return $this->app->redirect(sprintf(
            '/admin/databox/%d/?success=%d&reload-tree=1',
            $databox->get_sbas_id(),
            (int) $success
        ));
    }

    /**
     * Reindex databox content
     *
     * @param  Request $request    The current HTTP request
     * @param  integer $databox_id The requested databox
     * @return JsonResponse|RedirectResponse
     */
    public function reindex(Request $request, $databox_id)
    {
        $success = false;
        $options = $this->getElasticsearchOptions();

        $populateInfo = [
            'host'          => $options->getHost(),
            'port'          => $options->getPort(),
            'indexName'     => $options->getIndexName(),
            'databoxIds'    => [$databox_id]
        ];

        try {
            $this->getDispatcher()->dispatch(WorkerEvents::POPULATE_INDEX, new PopulateIndexEvent($populateInfo));
            $success = true;
        } catch (\Exception $e) {
        }

        if ('json' === $request->getRequestFormat()) {
            return $this->app->json([
                'success' => $success,
                'sbas_id' => $databox_id
            ]);
        }

        return $this->app->redirectPath('admin_database', [
            'databox_id' => $databox_id,
            'success'    => (int) $success,
        ]);
    }

    /**
     * Make a databox indexable
     *
     * @param  Request $request    The current HTTP request
     * @param  integer $databox_id The requested databox
     * @return JsonResponse|RedirectResponse
     */
    public function setIndexable(Request $request, $databox_id)
    {
        $success = false;

        try {
            $databox = $this->findDataboxById($databox_id);
            $indexable = !!$request->request->get('indexable', false);
            $this->getApplicationBox()->set_databox_indexable($databox, $indexable);
            $success = true;
        } catch (\Exception $e) {

        }

        if ('json' === $request->getRequestFormat()) {
            return $this->app->json([
                'success' => $success,
                'msg'     => $success ? $this->app->trans('Successful update') : $this->app->trans('An error occured'),
                'sbas_id' => $databox_id,
            ]);
        }

        return $this->app->redirectPath('admin_database', [
            'databox_id' => $databox_id,
            'success'    => (int) $success,
        ]);
    }

    /**
     * Update databox CGU's
     *
     * @param  Request          $request    The current HTTP request
     * @param  integer          $databox_id The requested databox
     * @return RedirectResponse
     */
    public function updateDatabaseCGU(Request $request, $databox_id)
    {
        $databox = $this->findDataboxById($databox_id);

        try {
            foreach ($request->request->get('TOU', []) as $loc => $terms) {
                $databox->update_cgus($loc, $terms, !!$request->request->get('valid', false));
            }
        } catch (\Exception $e) {
            return $this->app->redirectPath('admin_database_display_cgus', [
                'databox_id' => $databox_id,
                'success'    => 0,
            ]);
        }

        return $this->app->redirectPath('admin_database_display_cgus', [
            'databox_id' => $databox_id,
            'success'    => 1,
        ]);
    }

    /**
     * Mount a collection on a databox
     *
     * @param  Request $request       The current HTTP request
     * @param  integer $databox_id    The requested databox
     * @param  integer $collection_id The requested collection id
     * @return RedirectResponse
     */
    public function mountCollection(Request $request, $databox_id, $collection_id)
    {
        $connection = $this->getApplicationBox()->get_connection();
        $connection->beginTransaction();
        try {
            /** @var Authenticator $authenticator */
            $authenticator = $this->app->getAuthenticator();
            $baseId = \collection::mount_collection(
                $this->app,
                $this->findDataboxById($databox_id),
                $collection_id,
                $authenticator->getUser()
            );

            $othCollSel = (int) $request->request->get("othcollsel") ?: null;

            if (null !== $othCollSel) {
                $query = $this->createUserQuery();
                $n = 0;

                /** @var ACLProvider $aclProvider */
                $aclProvider = $this->app['acl'];
                while ($n < $query->on_base_ids([$othCollSel])->get_total()) {
                    $results = $query->limit($n, 50)->execute()->get_results();

                    foreach ($results as $user) {
                        $aclProvider->get($user)->duplicate_right_from_bas($othCollSel, $baseId);
                    }

                    $n += 50;
                }
            }

            $connection->commit();

            return $this->app->redirectPath('admin_database', [
                'databox_id' => $databox_id,
                'mount'      => 'ok',
            ]);
        } catch (\Exception $e) {
            $connection->rollBack();

            return $this->app->redirectPath('admin_database', [
                'databox_id' => $databox_id,
                'mount'      => 'ko',
            ]);
        }
    }

    /**
     * Set a new logo for a databox
     *
     * @param  Request $request    The current HTTP request
     * @param  integer $databox_id The requested databox
     * @return RedirectResponse
     */
    public function sendLogoPdf(Request $request, $databox_id)
    {
        try {
            if (null !== ($file = $request->files->get('newLogoPdf')) && $file->isValid()) {

                if ($file->getClientSize() < 65536) {
                    $databox = $this->findDataboxById($databox_id);
                    $this->getApplicationBox()->write_databox_pic(
                        $this->app['media-alchemyst'],
                        $this->app['filesystem'],
                        $databox,
                        $file,
                        \databox::PIC_PDF
                    );
                    unlink($file->getPathname());

                    return $this->app->redirectPath('admin_database', [
                        'databox_id' => $databox_id,
                        'success'    => '1',
                    ]);
                } else {
                    return $this->app->redirectPath('admin_database', [
                        'databox_id' => $databox_id,
                        'success'    => '0',
                        'error'      => 'file-too-big',
                    ]);
                }
            } else {
                return $this->app->redirectPath('admin_database', [
                    'databox_id' => $databox_id,
                    'success'    => '0',
                    'error'      => 'file-invalid',
                ]);
            }
        } catch (\Exception $e) {
            return $this->app->redirectPath('admin_database', [
                'databox_id' => $databox_id,
                'success'    => '0',
                'error'      => 'file-error',
            ]);
        }
    }

    /**
     * Delete an existing logo for a databox
     *
     * @param  Request $request    The current HTTP request
     * @param  integer $databox_id The requested databox
     * @return JsonResponse|RedirectResponse
     */
    public function deleteLogoPdf(Request $request, $databox_id)
    {
        $success = false;

        try {
            $this->getApplicationBox()->write_databox_pic(
                $this->app['media-alchemyst'],
                $this->app['filesystem'],
                $this->findDataboxById($databox_id),
                null,
                \databox::PIC_PDF
            );
            $success = true;
        } catch (\Exception $e) {

        }

        if ('json' === $request->getRequestFormat()) {
            return $this->app->json([
                'success' => $success,
                'msg'     => $success ? $this->app->trans('Successful removal') : $this->app->trans('An error occured'),
                'sbas_id' => $databox_id,
            ]);
        }

        // TODO: Check whether html call is still valid
        return $this->app->redirectPath('admin_database', [
            'databox_id' => $databox_id,
            'error'      => 'file-too-big',
        ]);
    }

    /**
     * Clear databox logs
     *
     * @param  Request $request    The current HTTP request
     * @param  integer $databox_id The requested databox
     * @return JsonResponse|RedirectResponse
     */
    public function clearLogs(Request $request, $databox_id)
    {
        $success = false;

        try {
            $this->findDataboxById($databox_id)->clear_logs();
            $success = true;
        } catch (\Exception $e) {

        }

        if ('json' === $request->getRequestFormat()) {
            return $this->app->json([
                'success' => $success,
                'msg'     => $success ? $this->app->trans('Successful update') : $this->app->trans('An error occured'),
                'sbas_id' => $databox_id,
            ]);
        }

        // TODO: Check whether html call is still valid
        return $this->app->redirectPath('admin_database', [
            'databox_id' => $databox_id,
            'error'      => 'file-too-big',
        ]);
    }

    /**
     * Change the name of a databox
     *
     * @param  Request $request    The current HTTP request
     * @param  integer $databox_id The requested databox
     * @return JsonResponse|RedirectResponse
     */
    public function changeViewName(Request $request, $databox_id)
    {
        if (null === $viewName = $request->request->get('viewname')) {
            $this->app->abort(400, $this->app->trans('Missing view name parameter'));
        }

        $success = false;

        try {
            $this->findDataboxById($databox_id)->set_viewname($viewName);
            $success = true;
        } catch (\Exception $e) {

        }

        if ('json' === $request->getRequestFormat()) {
            return $this->app->json([
                'success' => $success,
                'msg'     => $success ? $this->app->trans('Successful update') : $this->app->trans('An error occured'),
                'sbas_id' => $databox_id,
            ]);
        }

        // TODO: Check whether html call is still valid
        return $this->app->redirectPath('admin_database', [
            'databox_id' => $databox_id,
            'error'      => 'file-too-big',
        ]);
    }

    /**
     * Unmount a databox
     *
     * @param  Request $request    The current HTTP request
     * @param  integer $databox_id The requested databox
     * @return JsonResponse|RedirectResponse
     */
    public function unmountDatabase(Request $request, $databox_id)
    {
        $success = false;

        try {
            $databox = $this->findDataboxById($databox_id);
            $databox->unmount_databox();

            $success = true;
        } catch (\Exception $e) {

        }

        if ('json' === $request->getRequestFormat()) {
            $msg = $success
                ? $this->app->trans('The publication has been stopped')
                : $this->app->trans('An error occured');
            return $this->app->json([
                'success' => $success,
                'msg'     => $msg,
                'sbas_id' => $databox_id
            ]);
        }

        return $this->app->redirectPath('admin_databases', [
            'reload-tree' => 1,
        ]);
    }

    /**
     * Empty a databox
     *
     * @param  Request $request    The current HTTP request
     * @param  integer $databox_id The requested databox
     * @return JsonResponse|RedirectResponse
     */
    public function emptyDatabase(Request $request, $databox_id)
    {
        $msg = $this->app->trans('An error occurred');
        $success = false;
        $taskCreated = false;

        try {
            $databox = $this->findDataboxById($databox_id);

//            foreach ($databox->get_collections() as $collection) {
//                if ($collection->get_record_amount() <= 500) {
//                    $collection->empty_collection(500);
//                } else {
//                    /** @var TaskManipulator $taskManipulator */
//                    $taskManipulator = $this->app['manipulator.task'];
//                    $taskManipulator->createEmptyCollectionJob($collection);
//                }
//            }
//
//            $msg = $this->app->trans('Base empty successful');
            $success = true;


//            if ($taskCreated) {
//                $msg = $this->app->trans('A task has been created, please run it to complete empty collection');
//            }
        } catch (\Exception $e) {

        }

        if ('json' === $request->getRequestFormat()) {
            return $this->app->json([
                'success' => $success,
                'msg'     => $msg,
                'sbas_id' => $databox_id,
            ]);
        }

        // TODO: Can this method be called as HTML?
        return $this->app->redirectPath('admin_database', [
            'databox_id' => $databox_id,
            'error'      => 'file-too-big',
        ]);
    }

    /**
     * Get number of indexed items for a databox
     *
     * @param  Request $request    The current HTTP request
     * @param  integer $databox_id The requested databox
     * @return JsonResponse
     */
    public function progressBarInfos(Request $request, $databox_id)
    {
        if (!$request->isXmlHttpRequest() || 'json' !== $request->getRequestFormat()) {
            $this->app->abort(400, $this->app->trans('Bad request format, only JSON is allowed'));
        }

        $appbox = $this->getApplicationBox();

        $ret = [
            'success'           => false,
            'sbas_id'           => null,
            'msg'               => $this->app->trans('An error occured'),
            'indexable'         => false,
            'viewname'          => null,
            'printLogoURL'      => null,
            'counts'            => null,
        ];

        try {
            $databox = $this->findDataboxById($databox_id);

            $ret['sbas_id'] = $databox_id;
            $ret['indexable'] = $appbox->is_databox_indexable($databox);
            $ret['viewname'] = (($databox->get_dbname() == $databox->get_viewname())
                ? $this->app->trans('admin::base: aucun alias')
                : $databox->get_viewname());
            $ret['counts'] = $databox->get_counts();
            if ($this->app['filesystem']->exists($this->app['root.path'] . '/config/minilogos/logopdf_' . $databox_id . '.jpg')) {
                $ret['printLogoURL'] = '/custom/minilogos/logopdf_' . $databox_id . '.jpg';
            }

            $ret['success'] = true;
            $ret['msg'] = $this->app->trans('Successful update');
        } catch (\Exception $e) {

        }

        return $this->app->json($ret);
    }

    /**
     * Display page for reorder collections on a databox
     *
     * @param  integer $databox_id The requested databox
     * @return Response
     */
    public function getReorder($databox_id)
    {
        $acl = $this->getAclForUser();

        return $this->render('admin/collection/reorder.html.twig', [
            'collections' => $acl->get_granted_base([], [$databox_id]),
        ]);
    }

    /**
     * Apply collection reorder changes
     *
     * @param  Request $request    The current HTTP request
     * @param  integer $databox_id The requested databox
     * @return JsonResponse|RedirectResponse
     */
    public function setReorder(Request $request, $databox_id)
    {
        try {
            foreach ($request->request->get('order', []) as $data) {
                $collection = \collection::getByBaseId($this->app, $data['id']);
                $collection->set_ord($data['offset']);
            }
            $success = true;
        } catch (\Exception $e) {
            $success = false;
        }

        if ('json' === $request->getRequestFormat()) {
            return $this->app->json([
                'success' => $success,
                'msg'     => $success ? $this->app->trans('Successful update') : $this->app->trans('An error occured'),
                'sbas_id' => $databox_id,
            ]);
        }

        return $this->app->redirectPath('admin_database_display_collections_order', [
            'databox_id' => $databox_id,
            'success'    => (int) $success,
        ]);
    }

    /**
     * Display page to create a new collection
     */
    public function getNewCollection()
    {
        return $this->render('admin/collection/create.html.twig', [
            'collections'   => $this->getGrantedCollections($this->getAclForUser()),
        ]);
    }

    /**
     * Create a new collection
     *
     * @param  Request $request    The current HTTP request
     * @param  integer $databox_id The requested databox
     * @return Response
     */
    public function createCollection(Request $request, $databox_id)
    {
        if (($name = trim($request->request->get('name', ''))) === '') {
            return $this->app->redirectPath('admin_database_display_new_collection_form', [
                'databox_id' => $databox_id,
                'error'      => 'name',
            ]);
        }

        try {
            $databox = $this->findDataboxById($databox_id);
            $collection = \collection::create(
                $this->app, $databox,
                $this->getApplicationBox(),
                $name,
                $this->getAuthenticator()->getUser()
            );

            if (($request->request->get('ccusrothercoll') === "on")
                && (null !== $othcollsel = $request->request->get('othcollsel'))) {
                $query = $this->createUserQuery();
                $total = $query->on_base_ids([$othcollsel])->get_total();
                $n = 0;
                while ($n < $total) {
                    $results = $query->limit($n, 20)->execute()->get_results();
                    foreach ($results as $user) {
                        $this->getAclForUser($user)->duplicate_right_from_bas($othcollsel, $collection->get_base_id());
                    }
                    $n += 20;
                }
            }

            return $this->app->redirectPath('admin_display_collection', [
                'bas_id' => $collection->get_base_id(),
                'success' => 1,
                'reload-tree' => 1,
            ]);
        } catch (\Exception $e) {
            return $this->app->redirectPath('admin_database_submit_collection', [
                'databox_id' => $databox_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Display page to get some details on a appbox
     *
     * @param  Request $request    The current HTTP request
     * @param  integer $databox_id The requested databox
     * @return Response
     */
    public function getDetails(Request $request, $databox_id)
    {
        $databox = $this->findDataboxById($databox_id);

        $details = [];
        $total = ['total_subdefs' => 0, 'total_size' => 0];

        foreach ($databox->get_record_details($request->query->get('sort')) as $collName => $colDetails) {
            $details[$collName] = [
                'total_subdefs' => 0,
                'total_size' => 0,
                'medias' => []
            ];

            foreach ($colDetails as $subdefName => $subdefDetails) {
                $details[$collName]['total_subdefs'] += $subdefDetails['n'];
                $total['total_subdefs'] += $subdefDetails['n'];
                $details[$collName]['total_size'] += $subdefDetails['siz'];
                $total['total_size'] += $subdefDetails['siz'];

                $details[$collName]['medias'][] = [
                    'subdef_name' => $subdefName,
                    'total_subdefs' => $subdefDetails['n'],
                    'total_size' => $subdefDetails['siz'],
                ];
            }
        }

        return $this->render('admin/databox/details.html.twig', [
            'databox' => $databox,
            'table'   => $details,
            'total'   => $total
        ]);
    }

    private function getGrantedCollections(\ACL $acl)
    {
        $collections = [];

        foreach ($this->getApplicationBox()->get_databoxes() as $databox) {
            $sbasId = $databox->get_sbas_id();
            foreach ($acl->get_granted_base([\ACL::CANADMIN], [$sbasId]) as $collection) {
                $databox = $collection->get_databox();
                if (!isset($collections[$sbasId])) {
                    $collections[$databox->get_sbas_id()] = [
                        'databox'             => $databox,
                        'databox_collections' => []
                    ];
                }
                $collections[$databox->get_sbas_id()]['databox_collections'][] = $collection;
                /** @var DisplaySettingService $settings */
                $settings = $this->app['settings'];
                $userOrderSetting = $settings->getUserSetting($this->app->getAuthenticatedUser(), 'order_collection_by');
                // a temporary array to sort the collections
                $aName = [];
                list($ukey, $uorder) = ["order", SORT_ASC];     // default ORDER_BY_ADMIN
                switch ($userOrderSetting) {
                    case $settings::ORDER_ALPHA_ASC :
                        list($ukey, $uorder) = ["name", SORT_ASC];
                        break;
                    case $settings::ORDER_ALPHA_DESC :
                        list($ukey, $uorder) = ["name", SORT_DESC];
                        break;
                }
                foreach ($collections[$databox->get_sbas_id()]['databox_collections'] as $key => $row) {
                    if ($ukey == "order") {
                        $aName[$key] = $row->get_ord();
                    }
                    else {
                        $aName[$key] = $row->get_name();
                    }
                }
                // sort the collections
                array_multisort($aName, $uorder, SORT_REGULAR, $collections[$databox->get_sbas_id()]['databox_collections']);
            }
        }


        return $collections;
    }

    /**
     * @return EventDispatcherInterface
     */
    private function getDispatcher()
    {
        return $this->app['dispatcher'];
    }

    /**
     * @return ElasticsearchOptions
     */
    private function getElasticsearchOptions()
    {
        return $this->app['elasticsearch.options'];
    }
}
