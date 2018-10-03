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

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Application\Helper\UserQueryAware;
use Alchemy\Phrasea\Collection\CollectionService;
use Alchemy\Phrasea\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CollectionController extends Controller
{
    use UserQueryAware;

    /**
     * @var CollectionService
     */
    private $collectionService;

    public function __construct(Application $application, CollectionService $collectionService)
    {
        parent::__construct($application);

        $this->collectionService = $collectionService;
    }

    /**
     * Display collection information page
     *
     * @param  Request     $request The current request
     * @param  integer     $bas_id  The collection base_id
     * @return Response
     */
    public function getCollection(Request $request, $bas_id)
    {
        $collection = \collection::getByBaseId($this->app, $bas_id);

        $admins = [];

        if ($this->getAclForUser()->has_right_on_base($bas_id, \ACL::COLL_MANAGE)) {
            $query = $this->createUserQuery();
            $admins = $query->on_base_ids([$bas_id])
                ->who_have_right([\ACL::ORDER_MASTER])
                ->execute()
                ->get_results();
        }

        switch ($errorMsg = $request->query->get('error')) {
            case 'file-error':
                $errorMsg = $this->app->trans('Error while sending the file');
                break;
            case 'file-invalid':
                $errorMsg = $this->app->trans('Invalid file format');
                break;
            case 'file-file-too-big':
                $errorMsg = $this->app->trans('The file is too big');
                break;
            case 'collection-not-empty':
                $errorMsg = $this->app->trans('Empty the collection before removing');
                break;
        }

        return $this->render('admin/collection/collection.html.twig', [
            'collection' => $collection,
            'admins'     => $admins,
            'errorMsg'   => $errorMsg,
            'reloadTree' => $request->query->get('reload-tree') === '1'
        ]);
    }

    /**
     * Set new admin to handle orders
     *
     * @param  Request $request The current request
     * @param  integer $bas_id  The collection base_id
     * @return Response
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Exception
     */
    public function setOrderAdmins(Request $request, $bas_id)
    {
        $admins = array_filter(
            array_values($request->request->get('admins', [])),
            function ($value) { return $value != false; }
        );

        if (false && count($admins) === 0) {
            $this->app->abort(400, 'No admins provided.');
        }

        if (!is_array($admins)) {
            $this->app->abort(400, 'Admins must be an array.');
        }

        $collection = $this->getApplicationBox()->get_collection($bas_id);
        $collectionReference = $collection->getReference();

        $this->collectionService->setOrderMasters($collectionReference, $admins);

        return $this->app->redirectPath('admin_display_collection', [
            'bas_id'  => $bas_id,
            'success' => 1,
        ]);
    }

    /**
     * Empty a collection
     *
     * @param  Request                       $request The current request
     * @param  integer                       $bas_id  The collection base_id
     * @return Response
     */
    public function emptyCollection(Request $request, $bas_id)
    {
        $success = false;
        $msg = $this->app->trans('An error occurred');

        $collection = \collection::getByBaseId($this->app, $bas_id);
        try {
            if ($collection->get_record_amount() <= 500) {
                $collection->empty_collection(500);
                $msg = $this->app->trans('Collection empty successful');
            } else {
                $this->app['manipulator.task']->createEmptyCollectionJob($collection);
                $msg = $this->app->trans('A task has been creted, please run it to complete empty collection');
            }

            $success = true;
        } catch (\Exception $e) {

        }

        if ('json' === $request->getRequestFormat()) {
            return $this->app->json([
                'success' => $success,
                'msg'     => $msg,
                'bas_id'  => $collection->get_base_id()
            ]);
        }

        return $this->app->redirectPath('admin_display_collection', [
            'bas_id'  => $collection->get_base_id(),
            'success' => (int) $success,
        ]);
    }

    /**
     * Delete the collection stamp
     *
     * @param  Request                       $request The current request
     * @param  integer                       $bas_id  The collection base_id
     * @return Response
     */
    public function deleteStamp(Request $request, $bas_id)
    {
        $success = false;

        $collection = \collection::getByBaseId($this->app, $bas_id);

        try {
            $this->app->getApplicationBox()->write_collection_pic(
                $this->app['media-alchemyst'],
                $this->app['filesystem'],
                $collection,
                null,
                \collection::PIC_STAMP
            );
            $success = true;
        } catch (\Exception $e) {

        }

        if ('json' === $request->getRequestFormat()) {
            return $this->app->json([
                'success' => $success,
                'msg'     => $success ? $this->app->trans('Successful removal') : $this->app->trans('An error occured'),
                'bas_id'  => $collection->get_base_id()
            ]);
        }

        return $this->app->redirectPath('admin_display_collection', [
            'bas_id'  => $collection->get_base_id(),
            'success' => (int) $success,
        ]);
    }

    /**
     * Delete the collection watermark
     *
     * @param  Request                       $request The current request
     * @param  integer                       $bas_id  The collection base_id
     * @return Response
     */
    public function deleteWatermark(Request $request, $bas_id)
    {
        $success = false;

        $collection = \collection::getByBaseId($this->app, $bas_id);

        try {
            $this->app->getApplicationBox()->write_collection_pic(
                $this->app['media-alchemyst'],
                $this->app['filesystem'],
                $collection,
                null,
                \collection::PIC_WM
            );
            $success = true;
        } catch (\Exception $e) {

        }

        if ('json' === $request->getRequestFormat()) {
            return $this->app->json([
                'success' => $success,
                'msg'     => $success ? $this->app->trans('Successful removal') : $this->app->trans('An error occured'),
                'bas_id'  => $collection->get_base_id(),
            ]);
        }

        return $this->app->redirectPath('admin_display_collection', [
            'bas_id'  => $collection->get_base_id(),
            'success' => (int) $success,
        ]);
    }

    /**
     * Delete the current collection logo
     *
     * @param  Request $request The current request
     * @param  integer $bas_id  The collection base_id
     * @return Response
     */
    public function deleteLogo(Request $request, $bas_id)
    {
        $success = false;

        $collection = \collection::getByBaseId($this->app, $bas_id);

        try {
            $collection->update_logo(null);
            $this->app->getApplicationBox()->write_collection_pic(
                $this->app['media-alchemyst'],
                $this->app['filesystem'],
                $collection,
                null,
                \collection::PIC_LOGO
            );
            $success = true;
        } catch (\Exception $e) {

        }

        if ('json' === $request->getRequestFormat()) {
            return $this->app->json([
                'success' => $success,
                'msg'     => $success ? $this->app->trans('Successful removal') : $this->app->trans('An error occured'),
                'bas_id'  => $collection->get_base_id(),
            ]);
        }

        return $this->app->redirectPath('admin_display_collection', [
            'bas_id'  => $collection->get_base_id(),
            'success' => (int) $success,
        ]);
    }

    /**
     * Set a collection stamp
     *
     * @param  Request          $request The current request
     * @param  integer          $bas_id  The collection base_id
     * @return Response
     */
    public function setStamp(Request $request, $bas_id)
    {
        if (null === $file = $request->files->get('newStamp')) {
            $this->app->abort(400);
        }

        if ($file->getClientSize() > 1024 * 1024) {
            return $this->app->redirectPath('admin_display_collection', [
                'bas_id'  => $bas_id,
                'success' => 0,
                'error'   => 'file-too-big',
            ]);
        }

        if (!$file->isValid()) {
            return $this->app->redirectPath('admin_display_collection', [
                'bas_id'  => $bas_id,
                'success' => 0,
                'error'   => 'file-invalid',
            ]);
        }

        $collection = \collection::getByBaseId($this->app, $bas_id);

        try {
            $this->app->getApplicationBox()->write_collection_pic(
                $this->app['media-alchemyst'],
                $this->app['filesystem'],
                $collection,
                $file,
                \collection::PIC_STAMP
            );

            $this->app['filesystem']->remove($file->getPathname());
        } catch (\Exception $e) {
            return $this->app->redirectPath('admin_display_collection', [
                'bas_id'  => $bas_id,
                'success' => 0,
                'error'   => 'file-error',
            ]);
        }

        return $this->app->redirectPath('admin_display_collection', [
            'bas_id'  => $bas_id,
            'success' => 1,
        ]);
    }

    /**
     * Set a collection watermark
     *
     * @param  Request $request The current request
     * @param  integer $bas_id  The collection base_id
     * @return Response
     */
    public function setWatermark(Request $request, $bas_id)
    {
        if (null === $file = $request->files->get('newWm')) {
            $this->app->abort(400);
        }

        if ($file->getClientSize() > 65535) {
            return $this->app->redirectPath('admin_display_collection', [
                'bas_id'  => $bas_id,
                'success' => 0,
                'error'   => 'file-too-big',
            ]);
        }

        if (!$file->isValid()) {
            return $this->app->redirectPath('admin_display_collection', [
                'bas_id'  => $bas_id,
                'success' => 0,
                'error'   => 'file-invalid',
            ]);
        }

        $collection = \collection::getByBaseId($this->app, $bas_id);

        try {
            $this->app->getApplicationBox()->write_collection_pic(
                $this->app['media-alchemyst'],
                $this->app['filesystem'],
                $collection,
                $file,
                \collection::PIC_WM
            );
            $this->app['filesystem']->remove($file->getPathname());
        } catch (\Exception $e) {
            return $this->app->redirectPath('admin_display_collection', [
                'bas_id'  => $bas_id,
                'success' => 0,
                'error'   => 'file-error',
            ]);
        }

        return $this->app->redirectPath('admin_display_collection', [
            'bas_id'  => $bas_id,
            'success' => 1,
        ]);
    }

    /**
     * Set collection minilogo
     *
     * @param  Request $request The current request
     * @param  integer $bas_id  The collection base_id
     * @return Response
     */
    public function setMiniLogo(Request $request, $bas_id)
    {
        if (null === $file = $request->files->get('newLogo')) {
            $this->app->abort(400);
        }

        if ($file->getClientSize() > 65535) {
            return $this->app->redirectPath('admin_display_collection', [
                'bas_id'  => $bas_id,
                'success' => 0,
                'error'   => 'file-too-big',
            ]);
        }

        if (!$file->isValid()) {
            return $this->app->redirectPath('admin_display_collection', [
                'bas_id'  => $bas_id,
                'success' => 0,
                'error'   => 'file-invalid',
            ]);
        }

        $collection = \collection::getByBaseId($this->app, $bas_id);

        try {
            $this->app->getApplicationBox()->write_collection_pic(
                $this->app['media-alchemyst'],
                $this->app['filesystem'],
                $collection,
                $file,
                \collection::PIC_LOGO);
            $this->app['filesystem']->remove($file->getPathname());
        } catch (\Exception $e) {
            return $this->app->redirectPath('admin_display_collection', [
                'bas_id'  => $bas_id,
                'success' => 0,
                'error'   => 'file-error',
            ]);
        }

        return $this->app->redirectPath('admin_display_collection', [
            'bas_id'  => $bas_id,
            'success' => 1,
        ]);
    }

    /**
     * Delete a Collection
     *
     * @param  Request $request The current request
     * @param  integer $bas_id  The collection base_id
     * @return Response
     */
    public function delete(Request $request, $bas_id)
    {
        $success = false;
        $msg = $this->app->trans('An error occured');

        $collection = \collection::getByBaseId($this->app, $bas_id);

        try {
            if ($collection->get_record_amount() > 0) {
                $msg = $this->app->trans('Empty the collection before removing');
            } else {
                $collection->unmount();
                $collection->delete();
                $success = true;
                $msg = $this->app->trans('Successful removal');
            }
        } catch (\Exception $e) {
        }

        if ('json' === $request->getRequestFormat()) {
            return $this->app->json([
                'success' => $success,
                'msg'     => $msg
            ]);
        }

        if ($collection->get_record_amount() > 0) {
            return $this->app->redirectPath('admin_display_collection', [
                'bas_id'  => $collection->get_sbas_id(),
                'success' => 0,
                'error'   => 'collection-not-empty',
            ]);
        }

        if ($success) {
            return $this->app->redirectPath('admin_display_collection', [
                'bas_id'      => $collection->get_sbas_id(),
                'success'     => 1,
                'reload-tree' => 1,
            ]);
        }

        return $this->app->redirectPath('admin_display_collection', [
            'bas_id'  => $collection->get_sbas_id(),
            'success' => 0,
        ]);
    }

    /**
     * Unmount a collection from application box
     *
     * @param  Request                       $request The current request
     * @param  integer                       $bas_id  The collection base_id
     * @return Response
     */
    public function unmount(Request $request, $bas_id)
    {
        $success = false;

        $collection = \collection::getByBaseId($this->app, $bas_id);

        try {
            $collection->unmount();
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
            ]);
        }

        return $this->app->redirectPath('admin_display_collection', [
            'bas_id'  => $collection->get_sbas_id(),
            'success' => (int) $success,
        ]);
    }

    /**
     * Rename a collection
     *
     * @param  Request $request The current request
     * @param  integer $bas_id  The collection base_id
     * @return Response
     */
    public function rename(Request $request, $bas_id)
    {
        if (trim($name = $request->request->get('name')) === '') {
            $this->app->abort(400, $this->app->trans('Missing name parameter'));
        }

        $success = false;

        $collection = \collection::getByBaseId($this->app, $bas_id);

        try {
            $collection->set_name($name);
            $success = true;
        } catch (\Exception $e) {

        }

        if ('json' === $this->app['request']->getRequestFormat()) {
            return $this->app->json([
                'success' => $success,
                'msg'     => $success ? $this->app->trans('Successful update') : $this->app->trans('An error occured'),
            ]);
        }

        return $this->app->redirectPath('admin_display_collection', [
            'bas_id'      => $collection->get_base_id(),
            'success'     => (int) $success,
            'reload-tree' => 1,
        ]);
    }

    public function labels(Request $request, $bas_id)
    {
        if (null === $labels = $request->request->get('labels')) {
            $this->app->abort(400, $this->app->trans('Missing labels parameter'));
        }
        if (false === is_array($labels)) {
            $this->app->abort(400, $this->app->trans('Invalid labels parameter'));
        }

        $collection = \collection::getByBaseId($this->app, $bas_id);
        $success = true;

        try {
            foreach ($this->app['locales.available'] as $code => $language) {
                if (!isset($labels[$code])) {
                    continue;
                }
                $value = $labels[$code] ?: null;
                $collection->set_label($code, $value);
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

        return $this->app->redirectPath('admin_display_collection', [
            'bas_id'      => $collection->get_base_id(),
            'success'     => (int) $success,
            'reload-tree' => 1,
        ]);
    }

    /**
     * Set public presentation watermark
     *
     * @param  Request $request The current request
     * @param  integer $bas_id  The collection base_id
     * @return Response
     */
    public function setPublicationDisplay(Request $request, $bas_id)
    {
        if (null === $watermark = $request->request->get('pub_wm')) {
            $this->app->abort(400, 'Missing public watermark setting');
        }

        $success = false;

        $collection = \collection::getByBaseId($this->app, $bas_id);

        try {
            $collection->set_public_presentation($watermark);
            $success = true;
        } catch (\Exception $e) {

        }

        if ('json' === $request->getRequestFormat()) {
            return $this->app->json([
                'success' => $success,
                'msg'     => $success ? $this->app->trans('Successful update') : $this->app->trans('An error occured'),
            ]);
        }

        return $this->app->redirectPath('admin_display_collection', [
            'bas_id'      => $collection->get_sbas_id(),
            'success'     => (int) $success,
        ]);
    }

    /**
     * Enable a collection
     *
     * @param  Request $request The current request
     * @param  integer $bas_id  The collection base_id
     * @return Response
     */
    public function enable(Request $request, $bas_id)
    {
        $success = false;

        $collection = \collection::getByBaseId($this->app, $bas_id);

        try {
            $collection->enable($this->app->getApplicationBox());
            $success = true;
        } catch (\Exception $e) {

        }

        if ('json' === $request->getRequestFormat()) {
            return $this->app->json([
                'success' => $success,
                'msg'     => $success ? $this->app->trans('Successful update') : $this->app->trans('An error occured'),
            ]);
        }

        return $this->app->redirectPath('admin_display_collection', [
            'bas_id'      => $collection->get_sbas_id(),
            'success'     => (int) $success,
        ]);
    }

    /**
     * Disable a collection
     *
     * @param  Request $request The current request
     * @param  integer $bas_id  The collection base_id
     * @return Response
     */
    public function disabled(Request $request, $bas_id)
    {
        $success = false;

        $collection = \collection::getByBaseId($this->app, $bas_id);

        try {
            $collection->disable($this->app->getApplicationBox());
            $success = true;
        } catch (\Exception $e) {

        }

        if ('json' === $request->getRequestFormat()) {
            return $this->app->json([
                'success' => $success,
                'msg'     => $success ? $this->app->trans('Successful update') : $this->app->trans('An error occured'),
            ]);
        }

        return $this->app->redirectPath('admin_display_collection', [
            'bas_id'      => $collection->get_sbas_id(),
            'success'     => (int) $success,
        ]);
    }

    /**
     * Display suggested values
     *
     * @param integer $bas_id  The collection base_id
     * @return string
     */
    public function getSuggestedValues($bas_id)
    {
        /** @var \databox $databox */
        $databox = $this->app->findDataboxById(\phrasea::sbasFromBas($this->app, $bas_id));
        $collection = \collection::getByBaseId($this->app, $bas_id);
        $structFields = $suggestedValues = $basePrefs = [];

        /** @var \databox_field $meta */
        foreach ($databox->get_meta_structure() as $meta) {
            if ($meta->is_readonly()) {
                continue;
            }

            $structFields[$meta->get_name()] = $meta;
        }

        if ($sxe = simplexml_load_string($collection->get_prefs())) {
            $z = $sxe->xpath('/baseprefs/sugestedValues');
            if ($z && is_array($z)) {
                $f = 0;
                foreach ($z[0] as $ki => $vi) {
                    if ($vi && isset($structFields[$ki])) {
                        foreach ($vi->value as $oneValue) {
                            $suggestedValues[] = [
                                'key'   => $ki,
                                'value' => $f,
                                'name'  => (string) $oneValue
                            ];
                            $f++;
                        }
                    }
                }
            }

            $z = $sxe->xpath('/baseprefs');
            if ($z && is_array($z)) {
                /**
                 * @var string $ki
                 * @var \SimpleXMLElement $vi
                 */
                foreach ($z[0] as $ki => $vi) {
                    $pref = ['status' => null, 'xml'    => null];

                    if ($ki == 'status') {
                        $pref['status'] = $vi;
                    } elseif ($ki != 'sugestedValues') {
                        $pref['xml'] = $vi->asXML();
                    }

                    $basePrefs[] = $pref;
                }
            }
        }

        return $this->render('admin/collection/suggested_value.html.twig', [
            'collection'      => $collection,
            'databox'         => $databox,
            'suggestedValues' => $suggestedValues,
            'structFields'    => $structFields,
            'basePrefs'       => $basePrefs,
        ]);
    }

    /**
     * Register suggested values
     *
     * @param  Request $request The current request
     * @param  integer $bas_id  The collection base_id
     * @return Response
     */
    public function submitSuggestedValues(Request $request, $bas_id)
    {
        $success = false;

        $collection = \collection::getByBaseId($this->app, $bas_id);
        $prefs = $request->request->get('str');

        try {
            if ('' !== trim($prefs)) {
                $domdoc = new \DOMDocument();
                if (true === @$domdoc->loadXML($prefs)) {
                    $collection->set_prefs($domdoc);
                    $success = true;
                }
            }
        } catch (\Exception $e) {

        }

        if ('json' === $request->getRequestFormat()) {
            return $this->app->json([
                'success' => $success,
                'msg'     => $success ? $this->app->trans('Successful update') : $this->app->trans('An error occured'),
                'bas_id'  => $collection->get_base_id(),
            ]);
        }

        return $this->app->redirectPath('admin_collection_display_suggested_values', [
            'bas_id'      => $collection->get_sbas_id(),
            'success'     => (int) $success,
        ]);
    }

    /**
     * Get document details in the requested collection
     *
     * @param  integer     $bas_id  The collection base_id
     * @return Response
     */
    public function getDetails($bas_id)
    {
        $collection = \collection::getByBaseId($this->app, $bas_id);

        $out = ['total' => ['totobj' => 0, 'totsiz' => 0, 'mega'   => '0', 'giga'   => '0'], 'result' => []];

        foreach ($collection->get_record_details() as $vrow) {

            $last_k1 = $last_k2 = null;
            $outRow = ['midobj' => 0, 'midsiz' => 0];

            if ($vrow['amount'] > 0 || $last_k1 !== $vrow['coll_id']) {
                if (extension_loaded('bcmath')) {
                    $outRow['midsiz'] = bcadd($outRow['midsiz'], $vrow['size'], 0);
                } else {
                    $outRow['midsiz'] += $vrow['size'];
                }

                if ($last_k2 !== $vrow['name']) {
                    $outRow['name'] = $vrow['name'];
                    $last_k2 = $vrow['name'];
                }

                if (extension_loaded('bcmath')) {
                    $mega = bcdiv($vrow['size'], 1024 * 1024, 5);
                } else {
                    $mega = $vrow['size'] / (1024 * 1024);
                }

                if (extension_loaded('bcmath')) {
                    $giga = bcdiv($vrow['size'], 1024 * 1024 * 1024, 5);
                } else {
                    $giga = $vrow['size'] / (1024 * 1024 * 1024);
                }

                $outRow['mega'] = sprintf('%.2f', $mega);
                $outRow['giga'] = sprintf('%.2f', $giga);
                $outRow['amount'] = $vrow['amount'];
            }

            $out['total']['totobj'] += $outRow['amount'];

            if (extension_loaded('bcmath')) {
                $out['total']['totsiz'] = bcadd($out['total']['totsiz'], $outRow['midsiz'], 0);
            } else {
                $out['total']['totsiz'] += $outRow['midsiz'];
            }

            if (extension_loaded('bcmath')) {
                $mega = bcdiv($outRow['midsiz'], 1024 * 1024, 5);
            } else {
                $mega = $outRow['midsiz'] / (1024 * 1024);
            }

            if (extension_loaded('bcmath')) {
                $giga = bcdiv($outRow['midsiz'], 1024 * 1024 * 1024, 5);
            } else {
                $giga = $outRow['midsiz'] / (1024 * 1024 * 1024);
            }

            $outRow['mega_mid_size'] = sprintf('%.2f', $mega);
            $outRow['giga_mid_size'] = sprintf('%.2f', $giga);

            $out['result'][] = $outRow;
        }

        if (extension_loaded('bcmath')) {
            $out['total']['mega'] = bcdiv($out['total']['totsiz'], 1024 * 1024, 5);
        } else {
            $out['total']['mega'] = $out['total']['totsiz'] / (1024 * 1024);
        }

        if (extension_loaded('bcmath')) {
            $out['total']['giga'] = bcdiv($out['total']['totsiz'], 1024 * 1024 * 1024, 5);
        } else {
            $out['total']['giga'] = $out['total']['totsiz'] / (1024 * 1024 * 1024);
        }

        return $this->render('admin/collection/details.html.twig', [
            'collection' => $collection,
            'table'      => $out,
        ]);
    }
}
