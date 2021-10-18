<?php

namespace Alchemy\Phrasea\Controller\Api\V3;


use ACL;
use Alchemy\Phrasea\Application\Helper\DispatcherAware;
use Alchemy\Phrasea\Application\Helper\JsonBodyAware;
use Alchemy\Phrasea\Border\Manager;
use Alchemy\Phrasea\Controller\Api\Result;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Filesystem\FilesystemService;
use Alchemy\Phrasea\Media\SubdefGenerator;
use collection;
use Exception;
use Guzzle\Http\Client as Guzzle;
use Neutron\TemporaryFilesystem\TemporaryFilesystemInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class V3SubdefsServiceController extends Controller
{
    use JsonBodyAware;
    use DispatcherAware;


    /**
     * POST file
     *
     * @param Request $request
     * @param int $base_id
     *
     * @return Response
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

        $newPathname = null;    // will be set if a file is uploaded

        if (count($request->files->get('file')) == 0) {
            if(count($request->get('url')) == 1) {
                // upload by url
                $url = $request->get('url');
                $pi = pathinfo($url);   // filename, extension

                $tempfile = $this->getTmpFilesystem()->createTemporaryFile('download_', null, $pi['extension']);

                try {
                    $guzzle = new Guzzle($url);
                    $res = $guzzle->get("", [], ['save_to' => $tempfile])->send();
                }
                catch (Exception $e) {
                    return Result::createBadRequest($request, sprintf('Error "%s" downloading "%s"', $e->getMessage(), $url));
                }

                if($res->getStatusCode() !== 200) {
                    return Result::createBadRequest($request, sprintf('Error %s downloading "%s"', $res->getStatusCode(), $url));
                }
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

            $newPathname = $file->getPathname() . '.' . $file->getClientOriginalExtension();

            if (false === rename($file->getPathname(), $newPathname)) {
                return Result::createError($request, 403, 'Error while renaming file')->createResponse();
            }
        }

        if($newPathname) {
            // a file is included
            $media = $this->app->getMediaFromUri($newPathname);

            $type = $media->getType();   // 'document', 'audio', 'video', 'image', 'flash', 'map'
            $subdefs = $collection->get_databox()->get_subdef_structure()->getSubdefGroup($type);

            foreach ($subdefs as $subdef) {

                $ext = $this->getFilesystemService()->getExtensionFromSpec($subdef->getSpecs());

                $destFile = $this->getTmpFilesystem()->createTemporaryFile(null, '_'.$subdef->get_name(), $ext);

                $this->getSubdefGenerator()->generateSubdefFromFile($newPathname, $subdef, $destFile);
            }
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

}
