<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\ControllerProvider\Prod;

use Alchemy\Phrasea\Application\Helper\DataboxLoggerAware;
use Alchemy\Phrasea\Application\Helper\FilesystemAware;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Controller\RecordsRequest;
use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\Media\SubdefSubstituer;
use Alchemy\Phrasea\Metadata\PhraseanetMetadataReader;
use Alchemy\Phrasea\Metadata\PhraseanetMetadataSetter;
use DataURI\Parser;
use MediaAlchemyst\Alchemyst;
use MediaVorus\MediaVorus;
use PHPExiftool\Exception\ExceptionInterface as PHPExiftoolException;
use PHPExiftool\Reader;
use Symfony\Component\HttpFoundation\Request;

class ToolsController extends Controller
{
    use DataboxLoggerAware;
    use FilesystemAware;

    public function indexAction(Request $request)
    {
        $records = RecordsRequest::fromRequest($this->app, $request, false);

        $metadata = false;
        $record = null;

        if (count($records) == 1) {
            $record = $records->first();
            if (!$record->is_grouping()) {
                try {
                    $metadata = $this->getExifToolReader()
                        ->files($record->get_subdef('document')->get_pathfile())
                        ->first()->getMetadatas();
                } catch (PHPExiftoolException $e) {
                    // ignore
                } catch (\Exception_Media_SubdefNotFound $e) {
                    // ignore
                }
            }
        }

        return $this->render('prod/actions/Tools/index.html.twig', [
            'records'   => $records,
            'record'    => $record,
            'metadatas' => $metadata,
        ]);
    }

    public function rotateAction(Request $request)
    {
        $records = RecordsRequest::fromRequest($this->app, $request, false);
        $rotation = (int)$request->request->get('rotation', 90);
        if (!in_array($rotation, [-90, 90, 180], true)) {
            $rotation = 90;
        }

        foreach ($records as $record) {
            foreach ($record->get_subdefs() as $subdef) {
                if ($subdef->get_type() !== \media_subdef::TYPE_IMAGE) {
                    continue;
                }

                try {
                    $subdef->rotate($rotation, $this->getMediaAlchemyst(), $this->getMediaVorus());
                } catch (\Exception $e) {
                }
            }
        }

        return $this->app->json(['success' => true, 'errorMessage' => '']);
    }

    public function imageAction(Request $request)
    {
        $return = ['success' => true];

        $force = $request->request->get('force_substitution') == '1';

        $selection = RecordsRequest::fromRequest($this->app, $request, false, array('canmodifrecord'));

        foreach ($selection as $record) {
            $substituted = false;
            foreach ($record->get_subdefs() as $subdef) {
                if ($subdef->is_substituted()) {
                    $substituted = true;

                    if ($force) {
                        // unset flag
                        $subdef->set_substituted(false);
                    }
                    break;
                }
            }

            if (!$substituted || $force) {
                $record->rebuild_subdefs();
            }
        }


        return $this->app->json($return);
    }

    public function hddocAction(Request $request)
    {
        $success = false;
        $message = $this->app->trans('An error occured');

        if ($file = $request->files->get('newHD')) {

            if ($file->isValid()) {

                $fileName = $file->getClientOriginalName();

                try {

                    $tempoDir = tempnam(sys_get_temp_dir(), 'substit');
                    unlink($tempoDir);
                    mkdir($tempoDir);
                    $tempoFile = $tempoDir . DIRECTORY_SEPARATOR . $fileName;

                    if (false === rename($file->getPathname(), $tempoFile)) {
                        throw new RuntimeException('Error while renaming file');
                    }

                    $record = new \record_adapter($this->app, $request->get('sbas_id'), $request->get('record_id'));

                    $media = $this->app->getMediaFromUri($tempoFile);

                    $this->getSubDefinitionSubstituer()->substitute($record, 'document', $media);
                    $record->insertTechnicalDatas($this->getMediaVorus());
                    $this->getMetadataSetter()->replaceMetadata($this->getMetadataReader() ->read($media), $record);

                    $this->getDataboxLogger($record->get_databox())
                        ->log($record, \Session_Logger::EVENT_SUBSTITUTE, 'HD', '' );

                    if ((int) $request->request->get('ccfilename') === 1) {
                        $record->set_original_name($fileName);
                    }
                    unlink($tempoFile);
                    rmdir($tempoDir);
                    $success = true;
                    $message = $this->app->trans('Document has been successfully substitued');
                } catch (\Exception $e) {
                    $message = $this->app->trans('file is not valid');
                }
            } else {
                $message = $this->app->trans('file is not valid');
            }
        } else {
            $this->app->abort(400, 'Missing file parameter');
        }

        return $this->render('prod/actions/Tools/iframeUpload.html.twig', [
            'success'   => $success,
            'message'   => $message,
        ]);
    }

    public function changeThumbnailAction(Request $request)
    {
        $file = $request->files->get('newThumb');

        if (empty($file)) {
            $this->app->abort(400, 'Missing file parameter');
        }

        if (! $file->isValid()) {
            return $this->render('prod/actions/Tools/iframeUpload.html.twig', [
                'success'   => false,
                'message'   => $this->app->trans('file is not valid'),
            ]);
        }

        try {
            $fileName = $file->getClientOriginalName();
            $tempoDir = tempnam(sys_get_temp_dir(), 'substit');
            unlink($tempoDir);
            mkdir($tempoDir);
            $tempoFile = $tempoDir . DIRECTORY_SEPARATOR . $fileName;

            if (false === rename($file->getPathname(), $tempoFile)) {
                throw new RuntimeException('Error while renaming file');
            }

            $record = new \record_adapter($this->app, $request->get('sbas_id'), $request->get('record_id'));

            $media = $this->app->getMediaFromUri($tempoFile);

            $this->getSubDefinitionSubstituer()->substitute($record, 'thumbnail', $media);
            $this->getDataboxLogger($record->get_databox())
                ->log($record, \Session_Logger::EVENT_SUBSTITUTE, 'thumbnail', '');

            unlink($tempoFile);
            rmdir($tempoDir);
            $success = true;
            $message = $this->app->trans('Thumbnail has been successfully substitued');
        } catch (\Exception $e) {
            $success = false;
            $message = $this->app->trans('file is not valid');
        }

        return $this->render('prod/actions/Tools/iframeUpload.html.twig', [
            'success'   => $success,
            'message'   => $message,
        ]);
    }

    public function submitConfirmBoxAction(Request $request)
    {
        $template = 'prod/actions/Tools/confirm.html.twig';

        try {
            $record = new \record_adapter($this->app, $request->request->get('sbas_id'), $request->request->get('record_id'));
            $var = [
                'video_title' => $record->get_title(),
                'image'       => $request->request->get('image', ''),
            ];
            $return = [
                'error' => false,
                'datas' => $this->render($template, $var),
            ];
        } catch (\Exception $e) {
            $return = [
                'error' => true,
                'datas' => $this->app->trans('an error occured'),
            ];
        }

        return $this->app->json($return);
    }

    public function applyThumbnailExtractionAction(Request $request)
    {
        $return = ['success' => false, 'message' => ''];

        try {
            $record = new \record_adapter($this->app, $request->request->get('sbas_id'), $request->request->get('record_id'));

            $dataUri = Parser::parse($request->request->get('image', ''));

            $name = sprintf('extractor_thumb_%s', $record->get_serialize_key());
            $fileName = sprintf('%s/%s.png',  sys_get_temp_dir(), $name);

            file_put_contents($fileName, $dataUri->getData());

            $media = $this->app->getMediaFromUri($fileName);

            $this->getSubDefinitionSubstituer()->substitute($record, 'thumbnail', $media);
            $this->getDataboxLogger($record->get_databox())
                ->log($record, \Session_Logger::EVENT_SUBSTITUTE, 'thumbnail', '');

            unset($media);
            $this->getFilesystem()->remove($fileName);

            $return['success'] = true;
        } catch (\Exception $e) {
            $return['message'] = $e->getMessage();
        }

        return $this->app->json($return);
    }

    /**
     * @return Reader
     */
    private function getExifToolReader()
    {
        return $this->app['exiftool.reader'];
    }

    /**
     * @return Alchemyst
     */
    private function getMediaAlchemyst()
    {
        return $this->app['media-alchemyst'];
    }

    /**
     * @return MediaVorus
     */
    private function getMediaVorus()
    {
        return $this->app['mediavorus'];
    }

    /**
     * @return SubdefSubstituer
     */
    private function getSubDefinitionSubstituer()
    {
        return $this->app['subdef.substituer'];
    }

    /**
     * @return PhraseanetMetadataSetter
     */
    private function getMetadataSetter()
    {
        return $this->app['phraseanet.metadata-setter'];
    }

    /**
     * @return PhraseanetMetadataReader
     */
    private function getMetadataReader()
    {
        return $this->app['phraseanet.metadata-reader'];
    }
}
