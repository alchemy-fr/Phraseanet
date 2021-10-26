<?php

namespace Alchemy\Phrasea\Controller\Api\V3;


use Alchemy\Phrasea\Application\Helper\DispatcherAware;
use Alchemy\Phrasea\Application\Helper\JsonBodyAware;
use Alchemy\Phrasea\Border\Manager;
use Alchemy\Phrasea\Controller\Api\Result;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Filesystem\FilesystemService;
use Alchemy\Phrasea\Media\SubdefGenerator;
use Exception;
use Guzzle\Http\Client;
use Neutron\TemporaryFilesystem\TemporaryFilesystemInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatorInterface;


class V3SubdefsServiceController extends Controller
{
    use JsonBodyAware;
    use DispatcherAware;


    public function callbackAction_POST(Request $request)
    {
        /** @var UploadedFile $file */
        $file = $request->files->get('file');
        /** @var string $body  json supplemental infos */
        $body = $request->get('body');

        file_put_contents(dirname(__FILE__).'/../../../../../../logs/subdefgenerator.txt', sprintf("\n%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf(
                "into callbackAction_POST with\n - file: \"%s\"\n - filesize: %d\n - body: \"%s\"" ,
                $file->getRealPath(),
                $file->getSize(),
                $body
            )
        ), FILE_APPEND | LOCK_EX);

        $ret = [
            'message' => "subdef service callback was called",
            'file' => $file->getRealPath(),
            'filesize' => $file->getSize(),
            'body' => $body
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
        $body = $this->decodeJsonBody($request);

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
            'failed' => []
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
            }
            $sourceFile = $this->getTmpFilesystem()->createTemporaryFile('download_', null, $ext);

            try {
                $guzzle = new Client();
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

            $type = $media->getType();   // 'document', 'audio', 'video', 'image', 'flash', 'map'
            $subdefs = $databox->get_subdef_structure()->getSubdefGroup($type);

            // $subdef = new \databox_subdef($type, $sxSettings, $this->getTranslator());

            $guzzle = new Client();
            $postFilenameRoot = $body->destination->filename ?: "subdef";

            foreach ($subdefs as $subdef) {

                if(is_array($body->destination->subdefs) && !in_array($subdef->get_name(), $body->destination->subdefs)) {
                    continue;
                }

                $destFile = null;       // set if subdef generated
                $postFilename = $postFilenameRoot . '_' . $subdef->get_name();

                try {       // to make a subef
                    $start = microtime(true);
                    $ext = $this->getFilesystemService()->getExtensionFromSpec($subdef->getSpecs());

                    /** @var string $destFile */
                    $destFile = $this->getTmpFilesystem()->createTemporaryFile(null, '_' . $subdef->get_name(), $ext);

                    $this->getSubdefGenerator()->generateSubdefFromFile($sourceFile, $subdef, $destFile);

                    $duration = microtime(true) - $start;

                    $postFilename .= ('.' . $ext);

                    $data = [
                        'filename'  => $postFilename,
                        'extension' => $ext,
                        'name'      => $subdef->get_name(),
                        'class'     => $subdef->get_class(),
                        'filesize'  => filesize($destFile),
                        'build_duration'  => $duration
                    ];
                }
                catch (Exception $e) {
                    // failed to generate subdef
                    $data = [
                        'name'      => $subdef->get_name(),
                        'error'     => sprintf("failed to generate subdef \"%s\": %s", $subdef->get_name(), $e->getMessage())
                    ];
                    $ret['failed'][$subdef->get_name()] = $data;
                    $destFile = null;
                }

                // post the subdef
                if($destFile) {
                    try {       // to post result
                        $start = microtime(true);
                        $r = $guzzle->post($destination_url)
                            ->addPostFile('file', $destFile, null, $postFilename)
                            ->addPostFields([
                                'body' => json_encode($data)
                            ]);

                        $r->send();

                        $data['post_duration'] = microtime(true) - $start;

                        $ret['sent'][$subdef->get_name()] = $data;
                    }
                    catch (Exception $e) {
                        $data = [
                            'name'      => $subdef->get_name(),
                            'error'     => sprintf("failed to post subdef \"%s\": %s", $subdef->get_name(), $e->getMessage())
                        ];
                        $ret['failed'][$subdef->get_name()] = $data;
                    }

                    unlink($destFile);
                }
            }

            unlink($sourceFile);

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
