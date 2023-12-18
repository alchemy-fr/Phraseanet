<?php

namespace Alchemy\Phrasea\Controller\Api\V3;


use ACL;
use Alchemy\Phrasea\Application\Helper\DispatcherAware;
use Alchemy\Phrasea\Application\Helper\JsonBodyAware;
use Alchemy\Phrasea\Border\Attribute\Status;
use Alchemy\Phrasea\Border\Checker\Response as CheckerResponse;
use Alchemy\Phrasea\Border\File;
use Alchemy\Phrasea\Border\Manager;
use Alchemy\Phrasea\Border\Visa;
use Alchemy\Phrasea\Controller\Api\Result;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Core\Event\RecordEdit;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Model\Entities\LazaretAttribute;
use Alchemy\Phrasea\Model\Entities\LazaretFile;
use Alchemy\Phrasea\Model\Entities\LazaretSession;
use collection;
use Doctrine\DBAL\DBALException;
use Exception;
use GuzzleHttp\Client as Guzzle;
use Neutron\TemporaryFilesystem\TemporaryFilesystemInterface;
use p4field;
use record_adapter;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class V3RecordController extends Controller
{
    use JsonBodyAware;
    use DispatcherAware;


    /**
     * GET record
     *
     * @param Request $request
     * @param int $databox_id
     * @param int $record_id
     * @param bool $must_be_story
     *
     * @return Response
     */
    public function indexAction_GET(Request $request, $databox_id, $record_id, $must_be_story = false)
    {
        try {
            $record = $this->findDataboxById($databox_id)->get_record($record_id);
            if ($must_be_story && !$record->isStory()) {
                throw new NotFoundHttpException();
            }

            $r = $this->getResultHelpers()->listRecord($request, $record, $this->getAclForUser());

            return Result::create($request, $r)->createResponse();
        }
        catch (NotFoundHttpException $e) {
            return Result::createError($request, 404, 'record not found')->createResponse();
        }
        catch (Exception $e) {
            return Result::createBadRequest($request, $e->getMessage());
        }
    }

    /**
     * POST record
     *
     * @param Request $request
     * @param int $base_id
     *
     * @return Response
     * @throws DBALException
     * @throws Exception
     */
    public function indexAction_POST(Request $request, $base_id)
    {
        $body = $this->decodeJsonBody($request);

        $collection = collection::getByBaseId($this->app, $base_id);

        if (!$this->getAclForUser()->has_right_on_base($base_id, ACL::CANADDRECORD)) {
            return Result::createError($request, 403, sprintf(
                'You do not have access to collection %s', $collection->get_label($this->app['locale'])
            ))->createResponse();
        }

        $ret = [];

        $uploadedFilename = $originalName = $newPathname = null;    // will be set if a file is uploaded

        if (count($request->files->get('file')) == 0) {
            if(count($request->get('url')) == 1) {
                // upload by url
                $url = $request->get('url');
                $pi = pathinfo($url);   // filename, extension

                /** @var TemporaryFilesystemInterface $tmpFs */
                $tmpFs = $this->app['temporary-filesystem'];
                $tempfile = $tmpFs->createTemporaryFile('download_', null, $pi['extension']);

                try {
                    $guzzle = new Guzzle(['base_uri' => $url]);
                    $res = $guzzle->get("", ['save_to' => $tempfile]);
                }
                catch (Exception $e) {
                    return Result::createBadRequest($request, sprintf('Error "%s" downloading "%s"', $e->getMessage(), $url));
                }

                if($res->getStatusCode() !== 200) {
                    return Result::createBadRequest($request, sprintf('Error %s downloading "%s"', $res->getStatusCode(), $url));
                }

                $originalName = $pi['filename'] . '.' . $pi['extension'];
                $uploadedFilename = $newPathname = $tempfile;
            }
        }
        else {
            // upload by file
            $file = $request->files->get('file');
            if (!$file instanceof UploadedFile) {
                return Result::createBadRequest($request, 'You can upload one file at time');
            }
            if (!$file->isValid()) {
                return Result::createBadRequest($request, 'Data corrupted, please try again');
            }

            $uploadedFilename = $file->getPathname();
            $originalName = $file->getClientOriginalName();
            $newPathname = $file->getPathname() . '.' . $file->getClientOriginalExtension();

            if (false === rename($file->getPathname(), $newPathname)) {
                return Result::createError($request, 403, 'Error while renaming file')->createResponse();
            }
        }

        if($newPathname) {
            // a file is included
            $media = $this->app->getMediaFromUri($newPathname);

            $Package = new File($this->app, $media, $collection, $originalName);

            if ($request->get('status')) {
                $Package->addAttribute(new Status($this->app, $request->get('status')));
            }

            $session = new LazaretSession();
            $session->setUser($this->getAuthenticatedUser());

            $entityManager = $this->app['orm.em'];
            $entityManager->persist($session);
            $entityManager->flush();

            $reasons = $output = null;

            $translator = $this->app['translator'];

             $callback = function ($element, Visa $visa) use ($translator, &$reasons, &$output) {
                if (!$visa->isValid()) {
                    $reasons = array_map(function (CheckerResponse $response) use ($translator) {
                        return $response->getMessage($translator);
                    }, $visa->getResponses());
                }

                $output = $element;
            };

            switch ($request->get('forceBehavior')) {
                case '0' :
                    $behavior = Manager::FORCE_RECORD;
                    break;
                case '1' :
                    $behavior = Manager::FORCE_LAZARET;
                    break;
                case null:
                    $behavior = null;
                    break;
                default:
                    return Result::createBadRequest($request, sprintf(
                        'Invalid forceBehavior value `%s`', $request->get('forceBehavior')
                    ));
            }

            $nosubdef = $request->get('nosubdefs') === '' || p4field::isyes($request->get('nosubdefs'));
            $this->getBorderManager()->process($session, $Package, $callback, $behavior, $nosubdef);

            // remove $newPathname on temporary directory
            if ($newPathname !== $uploadedFilename) {
                @rename($newPathname, $uploadedFilename);
            }

            $ret = ['entity' => null];

            if ($output instanceof record_adapter) {
                /** @var record_adapter $output */
                try {
                    $output->setMetadatasByActions($body);
                }
                catch (Exception $e) {
                    return Result::createBadRequest($request, $e->getMessage());
                }

                $ret['url'] = '/records/' . $output->getDataboxId() . '/' . $output->getRecordId() . '/';
                $this->dispatch(PhraseaEvents::RECORD_UPLOAD, new RecordEdit($output));
            }
            elseif ($output instanceof LazaretFile) {

                // keep the json body as an attribute of lazaret file
                /** @var LazaretFile $output */
                $attribute = new LazaretAttribute();
                $attribute->setName('_patch_')
                    ->setValue(json_encode($body))
                    ->setLazaretFile($output);
                $output->addAttribute($attribute);
                $this->app['orm.em']->persist($attribute);

                $ret['url'] = '/quarantine/item/' . $output->getId() . '/';
            }
        }
        else {
            // no file was included, just create a record
            $output = record_adapter::create($collection, $this->app);
            try {
                $output->setMetadatasByActions($body);
                $ret['url'] = '/records/' . $output->getDataboxId() . '/' . $output->getRecordId() . '/';
            }
            catch (Exception $e) {
                return Result::createBadRequest($request, $e->getMessage());
            }
        }

        return Result::create($request, $ret)->createResponse();
    }


    /**
     * PATCH record
     *
     * @param Request $request
     * @param int $databox_id
     * @param int $record_id
     *
     * @return Response
     */
    public function indexAction_PATCH(Request $request, $databox_id, $record_id)
    {
        $record = $this->findDataboxById($databox_id)->get_record($record_id);
        $previousDescription = $record->getRecordDescriptionAsArray();

        try {
            $body = $this->decodeJsonBody($request);
        }
        catch (Exception $e) {
            return Result::createBadRequest($request, 'Bad JSON');
        }

        try {
            $record->setMetadatasByActions($body);
        }
        catch (Exception $e) {
            return Result::createBadRequest($request, $e->getMessage());
        }

        // @todo Move event dispatch inside record_adapter class (keeps things encapsulated)
        $this->dispatch(PhraseaEvents::RECORD_EDIT, new RecordEdit($record, $previousDescription));

        $ret = $this->getResultHelpers()->listRecord($request, $record, $this->getAclForUser());

        return Result::create($request, $ret)->createResponse();
    }


    /**
     * @return V3ResultHelpers
     */
    protected function getResultHelpers()
    {
        return $this->app['controller.api.v3.resulthelpers'];
    }

    /**
     * @return Manager
     */
    private function getBorderManager()
    {
        return $this->app['border-manager'];
    }

}
