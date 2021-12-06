<?php

namespace Alchemy\Phrasea\Controller\Api\V3;


use Alchemy\Phrasea\Application\Helper\DispatcherAware;
use Alchemy\Phrasea\Application\Helper\JsonBodyAware;
use Alchemy\Phrasea\Border\Manager;
use Alchemy\Phrasea\Controller\Api\Result;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Filesystem\FilesystemService;
use Alchemy\Phrasea\Helper\JsonBodyHelper;
use Alchemy\Phrasea\Media\SubdefGenerator;
use databox_subdef;
use Exception;
use Guzzle\Http\Client;
use Neutron\TemporaryFilesystem\TemporaryFilesystemInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatorInterface;


class V3SubdefsServiceController extends Controller
{
    use JsonBodyAware;
    use DispatcherAware;


    /**
     * the internal route "api/v3/subdefs_service_callback" can be used to explore the data sent to service callback
     * it will save the received file in logs, and log infos into logs/subdefgenerator.txt
     *
     * @param Request $request
     * @return JsonResponse|Response
     */
    public function callbackAction_POST(Request $request)
    {
        $logto = realpath(dirname(__FILE__).'/../../../../../../logs') . '/';

        /** @var UploadedFile $file */
        $file = $request->files->get('file');
        $info = $request->get('file_info');

        // save the received file
        $src = $file->getRealPath();
        $dst = $logto . $info['filename'];
        copy($src, $dst);

        // log
        file_put_contents($logto. 'subdefgenerator.txt', sprintf("\n%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf(
                "into callbackAction_POST with\n - file: \"%s\"\n - filesize: %d\n - saved to: \"%s\"\n - payload: %s" ,
                $file->getRealPath(),
                $file->getSize(),
                $dst,
                var_export($request->request->all(), true)
            )
        ), FILE_APPEND | LOCK_EX);

        // for now the subdef service does not expect any result from the callback
        // so this "ret" is for debug only
        $ret = [
            'message' => "subdef service callback was called",
            'file' => $file->getRealPath(),
            'filesize' => $file->getSize()
        ];

        return Result::create($request, $ret)->createResponse();
    }


    /**
     * POST file
     *
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    public function indexAction_POST(Request $request)
    {
        $body = $this->decodeJsonBody($request, null, JsonBodyHelper::OBJECT);

        $dbox_id = $body->databoxId;
        $databox = $this->app->getApplicationBox()->get_databox($dbox_id);

        /*
        $collection = collection::getByBaseId($this->app, $base_id);

        if (!$this->getAclForUser()- has_right_on_base($base_id, ACL::CANADDRECORD)) {
            return Result::createError($request, 403, sprintf(
                'You do not have access to collection %s', $collection->get_label($this->app['locale'])
            ))->createResponse();
        }
        */
        $ret = [
            'sent' => [],
        ];

        $sourceFile = null;    // will be set if a file is uploaded

        if(is_null($destination_url = $body->destination->url)) {
            return Result::createBadRequest($request, sprintf('Missing destination/url'));
        }

        if(!is_null($src_url = $body->source->url)) {
            // upload by url

            if(is_null($ext = $body->source->extension)) {
                $pi = pathinfo($src_url);   // filename, extension
                $ext = $pi['extension'];
                if (strpos($ext, '?') > 0) {
                    $ext = explode('?', $ext)[0];
                }
            }
            $sourceFile = $this->getTmpFilesystem()->createTemporaryFile('download_', null, $ext);

            try {
                $guzzle = new Client();
                $guzzle->setSslVerification(false);
                $res = $guzzle->get($body->source->url, [], ['save_to' => $sourceFile])->send();
                unset($guzzle);
            }
            catch (Exception $e) {
                return Result::createBadRequest($request, sprintf('Error "%s" downloading "%s"', $e->getMessage(), $src_url));
            }

            if($res->getStatusCode() !== 200) {
                return Result::createBadRequest($request, sprintf('Error %s downloading "%s"', $res->getStatusCode(), $src_url));
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

            $sourceFile = $file->getPathname() . '.' . $file->getClientOriginalExtension();

            if (false === rename($file->getPathname(), $sourceFile)) {
                return Result::createError($request, 403, 'Error while renaming file')->createResponse();
            }
        }

        if($sourceFile) {
            // a file is included

            // check the size if passed
            if(!is_null($body->source->size) && filesize($sourceFile) !== (int)($body->source->size)) {
                return Result::createBadRequest($request, 'Uploaded file size does not match');
            }

            $media = $this->app->getMediaFromUri($sourceFile);


            $phrSubdefs = [];    // array of phr subdefs by name, will give the list of wanted subdefs (aliased by client)
            $allWanted = [];     // in case no wanted subdef was passed in body, prepare a full list
            $type = $media->getType();   // 'document', 'audio', 'video', 'image', 'flash', 'map'
            foreach($databox->get_subdef_structure()->getSubdefGroup($type) as $sd) {
                $phrSubdefs[$sd->get_name()] = ['subdef' => $sd, 'destinations' => []];
                $allWanted[$sd->get_name()] = ['source' => $sd->get_name()];
            }
            // list wanted subdefs
            $wanted = (array)$body->destination->subdefs;
            if(empty($wanted)) {
                $wanted = $allWanted;    // no list of wanted subdefs : send all
            }
            unset($allWanted);

            // map a list of phr sources to a list of alias (wanted) names
            foreach ($wanted as $w => $a) {
                $a = (array) $a;
                if(!array_key_exists($a['source'], $phrSubdefs)) {
                    // the source (phr subdef name) is unknown : ignore
                    continue;
                }
                $k = $a['source'];
                unset($a['source']);
                $phrSubdefs[$k]['destinations'][$w] = (array) $a;
            }


            $guzzle = new Client();
            $guzzle->setSslVerification(false);
            $postFilenameRoot = $body->destination->filename ?: "subdef_";

            $destPayload = $body->destination->payload ?: [];

            try {
                foreach ($phrSubdefs as $sd) {
                    /** @var databox_subdef $subdef */
                    $subdef = $sd['subdef'];
                    foreach ($sd['destinations'] as $destName => $destAttr) {

                        $postFilename = $postFilenameRoot . $destName;

                        $start = microtime(true);
                        $ext = $this->getFilesystemService()->getExtensionFromSpec($subdef->getSpecs());

                        /** @var string $destFile */
                        $destFile = $this->getTmpFilesystem()->createTemporaryFile(null, '_' . $subdef->get_name(), $ext);

                        $this->getSubdefGenerator()->generateSubdefFromFile($sourceFile, $subdef, $destFile);

                        $duration = microtime(true) - $start;

                        $postFilename .= '.' . $ext;

                        $data = [
                            'filename'       => $postFilename,
                            'extension'      => $ext,
                            'name'           => $subdef->get_name(),
                            'class'          => $subdef->get_class(),
                            'filesize'       => filesize($destFile),
                            'build_duration' => $duration,
                        ];

                        $start = microtime(true);

                        $postFields = array_merge((array)$destPayload, [
                            'file_info' => $data,
                        ]);

                        try {
                            $guzzle->post($destination_url)
                                ->addPostFields($postFields)
                                ->addPostFile('file', $destFile, null, $postFilename)
                                ->send();
                        }
                        catch (Exception $e) {
                            throw new Exception(sprintf(
                                'Failed to post subdef "%s" file: %s',
                                $subdef->get_name(),
                                $e->getMessage()
                            ), 0, $e);
                        }
                        finally {
                            unlink($destFile);
                        }

                        $data['post_duration'] = microtime(true) - $start;

                        $ret['sent'][$subdef->get_name()] = $data;
                    }
                }
            } finally {
                unlink($sourceFile);
            }

            unset($guzzle);
        }

        return Result::create($request, $ret)->createResponse();
    }

    /**
     * @return TemporaryFilesystemInterface
     */
    private function getTmpFilesystem()
    {
        return $this->app['temporary-filesystem'];
    }

    /**
     * @return FilesystemService
     */
    private function getFilesystemService()
    {
        return $this->app['phraseanet.filesystem'];
    }

    /**
     * @return SubdefGenerator
     */
    private function getSubdefGenerator()
    {
        return $this->app['subdef.generator'];
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

    /**
     * @return TranslatorInterface
     */
    private function getTranslator()
    {
        return $this->app['translator'];
    }

}
