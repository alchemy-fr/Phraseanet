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

use Alchemy\Phrasea\Model\Entities\LazaretFile;
use Alchemy\Phrasea\Border;
use Alchemy\Phrasea\Border\Attribute\AttributeInterface;
use Alchemy\Phrasea\Http\DeliverDataInterface;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Filesystem\Exception\IOException;

class Lazaret implements ControllerProviderInterface
{
    /**
     * Connect the ControllerCollection to the Silex Application
     *
     * @param  Application                 $app A silex application
     * @return \Silex\ControllerCollection
     */
    public function connect(Application $app)
    {
        $app['controller.prod.lazaret'] = $this;

        $controllers = $app['controllers_factory'];

        $controllers->before(function (Request $request) use ($app) {
            $app['firewall']->requireAuthentication()
                ->requireRight('addrecord');
        });

        $controllers->get('/', 'controller.prod.lazaret:listElement')
            ->bind('lazaret_elements');

        $controllers->get('/{file_id}/', 'controller.prod.lazaret:getElement')
            ->assert('file_id', '\d+')
            ->bind('lazaret_element');

        $controllers->post('/{file_id}/force-add/', 'controller.prod.lazaret:addElement')
            ->assert('file_id', '\d+')
            ->bind('lazaret_force_add');

        $controllers->post('/{file_id}/deny/', 'controller.prod.lazaret:denyElement')
            ->assert('file_id', '\d+')
            ->bind('lazaret_deny_element');

        $controllers->post('/empty/', 'controller.prod.lazaret:emptyLazaret')
            ->bind('lazaret_empty');

        $controllers->post('/{file_id}/accept/', 'controller.prod.lazaret:acceptElement')
            ->assert('file_id', '\d+')
            ->bind('lazaret_accept');

        $controllers->get('/{file_id}/thumbnail/', 'controller.prod.lazaret:thumbnailElement')
            ->assert('file_id', '\d+')
            ->bind('lazaret_thumbnail');

        return $controllers;
    }

    /**
     * List all elements in lazaret
     *
     * @param Application $app     A Silex application
     * @param Request     $request The current request
     *
     * @return Response
     */
    public function listElement(Application $app, Request $request)
    {
        $baseIds = array_keys($app['acl']->get($app['authentication']->getUser())->get_granted_base(['canaddrecord']));

        $lazaretFiles = null;

        if (count($baseIds) > 0) {
            $lazaretRepository = $app['EM']->getRepository('Alchemy\Phrasea\Model\Entities\LazaretFile');

            $lazaretFiles = $lazaretRepository->findPerPage(
                $baseIds, $request->query->get('offset', 0), $request->query->get('limit', 10)
            );
        }

        return $app['twig']->render(
            'prod/upload/lazaret.html.twig', ['lazaretFiles' => $lazaretFiles]
        );
    }

    /**
     * Get one lazaret Element
     *
     * @param Application $app     A Silex application
     * @param Request     $request The current request
     * @param int         $file_id A lazaret element id
     *
     * @return Response
     */
    public function getElement(Application $app, Request $request, $file_id)
    {
        $ret = ['success' => false, 'message' => '', 'result'  => []];

        $lazaretFile = $app['EM']->find('Alchemy\Phrasea\Model\Entities\LazaretFile', $file_id);

        /* @var $lazaretFile LazaretFile */
        if (null === $lazaretFile) {
            $ret['message'] = $app->trans('File is not present in quarantine anymore, please refresh');

            return $app->json($ret);
        }

        $file = [
            'filename' => $lazaretFile->getOriginalName(),
            'base_id'  => $lazaretFile->getBaseId(),
            'created'  => $lazaretFile->getCreated()->format(\DateTime::ATOM),
            'updated'  => $lazaretFile->getUpdated()->format(\DateTime::ATOM),
            'pathname' => $app['root.path'] . '/tmp/lazaret/' . $lazaretFile->getFilename(),
            'sha256'   => $lazaretFile->getSha256(),
            'uuid'     => $lazaretFile->getUuid(),
        ];

        $ret['result'] = $file;
        $ret['success'] = true;

        return $app->json($ret);
    }

    /**
     * Add an element to phraseanet
     *
     * @param Application $app     A Silex application
     * @param Request     $request The current request
     * @param int         $file_id A lazaret element id
     *
     * parameters   : 'bas_id'            int     (mandatory) : The id of the destination collection
     *                'keep_attributes'   boolean (optional)  : Keep all attributes attached to the lazaret element
     *                'attributes'        array   (optional)  : Attributes id's to attach to the lazaret element
     *
     * @return Response
     */
    public function addElement(Application $app, Request $request, $file_id)
    {
        $ret = ['success' => false, 'message' => '', 'result'  => []];

        //Optional parameter
        $keepAttributes = !!$request->request->get('keep_attributes', false);
        $attributesToKeep = $request->request->get('attributes', []);

        //Mandatory parameter
        if (null === $request->request->get('bas_id')) {
            $ret['message'] = $app->trans('You must give a destination collection');

            return $app->json($ret);
        }

        $lazaretFile = $app['EM']->find('Alchemy\Phrasea\Model\Entities\LazaretFile', $file_id);

        /* @var $lazaretFile LazaretFile */
        if (null === $lazaretFile) {
            $ret['message'] = $app->trans('File is not present in quarantine anymore, please refresh');

            return $app->json($ret);
        }

        $lazaretFileName = $app['root.path'] . '/tmp/lazaret/' . $lazaretFile->getFilename();
        $lazaretThumbFileName = $app['root.path'] . '/tmp/lazaret/' . $lazaretFile->getThumbFilename();

        try {
            $borderFile = Border\File::buildFromPathfile(
                    $lazaretFileName, $lazaretFile->getCollection($app), $app, $lazaretFile->getOriginalName()
            );

            $record = null;
            /* @var $record \record_adapter */

            //Post record creation
            $callBack = function ($element, $visa, $code) use (&$record) {
                    $record = $element;
                };

            //Force creation record
            $app['border-manager']->process(
                $lazaretFile->getSession(), $borderFile, $callBack, Border\Manager::FORCE_RECORD
            );

            $app['phraseanet.SE']->addRecord($record);

            if ($keepAttributes) {
                //add attribute

                $metaFields = new Border\MetaFieldsBag();
                $metadataBag = new Border\MetadataBag();

                foreach ($lazaretFile->getAttributes() as $attr) {
                    //Check which ones to keep
                    if (!!count($attributesToKeep)) {
                        if (!in_array($attr->getId(), $attributesToKeep)) {
                            continue;
                        }
                    }

                    try {
                        $attribute = Border\Attribute\Factory::getFileAttribute($app, $attr->getName(), $attr->getValue());
                    } catch (\InvalidArgumentException $e) {
                        continue;
                    }

                    /* @var $attribute AttributeInterface */

                    switch ($attribute->getName()) {
                        case AttributeInterface::NAME_METADATA:
                            $value = $attribute->getValue();
                            $metadataBag->set($value->getTag()->getTagname(), new \PHPExiftool\Driver\Metadata\Metadata($value->getTag(), $value->getValue()));
                            break;
                        case AttributeInterface::NAME_STORY:
                            $attribute->getValue()->appendChild($record);
                            break;
                        case AttributeInterface::NAME_STATUS:
                            $record->set_binary_status($attribute->getValue());
                            break;
                        case AttributeInterface::NAME_METAFIELD:
                            $metaFields->set($attribute->getField()->get_name(), $attribute->getValue());
                            break;
                    }
                }

                $datas = $metadataBag->toMetadataArray($record->get_databox()->get_meta_structure());
                $record->set_metadatas($datas);

                $fields = $metaFields->toMetadataArray($record->get_databox()->get_meta_structure());
                $record->set_metadatas($fields);
            }

            //Delete lazaret file
            $app['EM']->remove($lazaretFile);
            $app['EM']->flush();

            $ret['success'] = true;
        } catch (\Exception $e) {
            $ret['message'] = $app->trans('An error occured');
        }

        try {
            $app['filesystem']->remove([$lazaretFileName, $lazaretThumbFileName]);
        } catch (IOException $e) {

        }

        return $app->json($ret);
    }

    /**
     * Delete a lazaret element
     *
     * @param Application $app     A Silex application where the controller is mounted on
     * @param Request     $request The current request
     * @param int         $file_id A lazaret element id
     *
     * @return Response
     */
    public function denyElement(Application $app, Request $request, $file_id)
    {
        $ret = ['success' => false, 'message' => '', 'result'  => []];

        $lazaretFile = $app['EM']->find('Alchemy\Phrasea\Model\Entities\LazaretFile', $file_id);
        /* @var $lazaretFile LazaretFile */
        if (null === $lazaretFile) {
            $ret['message'] = $app->trans('File is not present in quarantine anymore, please refresh');

            return $app->json($ret);
        }

        try {
            $this->denyLazaretFile($app, $lazaretFile);
            $ret['success'] = true;
        } catch (\Exception $e) {

        }

        return $app->json($ret);
    }

    protected function denyLazaretFile(Application $app, LazaretFile $lazaretFile)
    {
        $lazaretFileName = $app['root.path'] . '/tmp/lazaret/' . $lazaretFile->getFilename();
        $lazaretThumbFileName = $app['root.path'] . '/tmp/lazaret/' . $lazaretFile->getThumbFilename();

        $app['EM']->remove($lazaretFile);
        $app['EM']->flush();

        try {
            $app['filesystem']->remove([$lazaretFileName, $lazaretThumbFileName]);
        } catch (IOException $e) {

        }

        return $this;
    }

    /**
     * Empty lazaret
     *
     * @param Application $app
     * @param Request     $request
     *
     * @return Response
     */
    public function emptyLazaret(Application $app, Request $request)
    {
        $ret = ['success' => false, 'message' => '', 'result'  => []];

        $lazaretFiles = $app['EM']->getRepository('Alchemy\Phrasea\Model\Entities\LazaretFile')->findAll();

        $app['EM']->beginTransaction();

        try {
            foreach ($lazaretFiles as $lazaretFile) {
                $this->denyLazaretFile($app, $lazaretFile);
            }
            $app['EM']->commit();
            $ret['success'] = true;
        } catch (\Exception $e) {
            $app['EM']->rollback();
            $ret['message'] = $app->trans('An error occured');
        }

        return $app->json($ret);
    }

    /**
     * Substitute a record element by a lazaret element
     *
     * @param Application $app     A Silex application where the controller is mounted on
     * @param Request     $request The current request
     * @param int         $file_id A lazaret element id
     *
     * @return Response
     */
    public function acceptElement(Application $app, Request $request, $file_id)
    {
        $ret = ['success' => false, 'message' => '', 'result'  => []];

        //Mandatory parameter
        if (null === $recordId = $request->request->get('record_id')) {
            $ret['message'] = $app->trans('You must give a destination record');

            return $app->json($ret);
        }

        $lazaretFile = $app['EM']->find('Alchemy\Phrasea\Model\Entities\LazaretFile', $file_id);

        /* @var $lazaretFile LazaretFile */
        if (null === $lazaretFile) {
            $ret['message'] = $app->trans('File is not present in quarantine anymore, please refresh');

            return $app->json($ret);
        }

        $found = false;

        //Check if the choosen record is eligible to the substitution
        foreach ($lazaretFile->getRecordsToSubstitute($app) as $record) {
            if ($record->get_record_id() !== (int) $recordId) {
                continue;
            }

            $found = true;
            break;
        }

        if (!$found) {
            $ret['message'] = $app->trans('The destination record provided is not allowed');

            return $app->json($ret);
        }

        $lazaretFileName = $app['root.path'] . '/tmp/lazaret/' . $lazaretFile->getFilename();
        $lazaretThumbFileName = $app['root.path'] . '/tmp/lazaret/' . $lazaretFile->getThumbFilename();

        try {
            $media = $app['mediavorus']->guess($lazaretFileName);

            $record = $lazaretFile->getCollection($app)->get_databox()->get_record($recordId);
            $record->substitute_subdef('document', $media, $app);
            $app['phraseanet.logger']($record->get_databox())->log(
                $record,
                \Session_Logger::EVENT_SUBSTITUTE,
                'HD',
                ''
            );

            //Delete lazaret file
            $app['EM']->remove($lazaretFile);
            $app['EM']->flush();

            $ret['success'] = true;
        } catch (\Exception $e) {
            $ret['message'] = $app->trans('An error occured');
        }

        try {
            $app['filesystem']->remove([$lazaretFileName, $lazaretThumbFileName]);
        } catch (IOException $e) {

        }

        return $app->json($ret);
    }

    /**
     * Get the associated lazaret element thumbnail
     *
     * @param Application $app     A Silex application where the controller is mounted on
     * @param Request     $request The current request
     * @param int         $file_id A lazaret element id
     *
     * @return Response
     */
    public function thumbnailElement(Application $app, Request $request, $file_id)
    {
        $lazaretFile = $app['EM']->find('Alchemy\Phrasea\Model\Entities\LazaretFile', $file_id);

        /* @var $lazaretFile LazaretFile */
        if (null === $lazaretFile) {
            return new Response(null, 404);
        }

        $lazaretThumbFileName = $app['root.path'] . '/tmp/lazaret/' . $lazaretFile->getThumbFilename();

        return $app['phraseanet.file-serve']->deliverFile($lazaretThumbFileName, $lazaretFile->getOriginalName(), DeliverDataInterface::DISPOSITION_INLINE, 'image/jpeg', 3600);
    }
}
