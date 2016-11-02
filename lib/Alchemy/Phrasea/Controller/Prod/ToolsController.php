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

use Alchemy\Phrasea\Application\Helper\DataboxLoggerAware;
use Alchemy\Phrasea\Application\Helper\DispatcherAware;
use Alchemy\Phrasea\Application\Helper\FilesystemAware;
use Alchemy\Phrasea\Application\Helper\SubDefinitionSubstituerAware;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Controller\RecordsRequest;
use Alchemy\Phrasea\Core\Event\Record\RecordEvents;
use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\Metadata\PhraseanetMetadataReader;
use Alchemy\Phrasea\Metadata\PhraseanetMetadataSetter;
use Alchemy\Phrasea\Record\RecordWasRotated;
use DataURI\Parser;
use MediaAlchemyst\Alchemyst;
use MediaVorus\MediaVorus;
use PHPExiftool\Exception\ExceptionInterface as PHPExiftoolException;
use PHPExiftool\Reader;
use Symfony\Component\HttpFoundation\Request;

class ToolsController extends Controller
{
    use DataboxLoggerAware;
    use DispatcherAware;
    use FilesystemAware;
    use SubDefinitionSubstituerAware;

    public function indexAction(Request $request)
    {
        $records = RecordsRequest::fromRequest($this->app, $request, false);

        $metadata = false;
        $record = null;
        $recordAccessibleSubdefs = array();

        if (count($records) == 1) {
            /** @var \record_adapter $record */
            $record = $records->first();

            // fetch subdef list:
            $subdefs = $record->get_subdefs();

            $acl = $this->getAclForUser();

            if ($acl->has_right(\ACL::BAS_CHUPUB)
                && $acl->has_right_on_base($record->getBaseId(), \ACL::CANMODIFRECORD)
                && $acl->has_right_on_base($record->getBaseId(), \ACL::IMGTOOLS)
            ) {
                $databoxSubdefs = $record->getDatabox()->get_subdef_structure()->getSubdefGroup($record->getType());

                foreach ($subdefs as $subdef) {
                    $label = $subdefName = $subdef->get_name();
                    if (null === $permalink = $subdef->get_permalink()) {
                        continue;
                    }

                    if ('document' == $subdefName) {
                        if (!$acl->has_right_on_base($record->getBaseId(), \ACL::CANDWNLDHD)) {
                            continue;
                        }
                        $label = $this->app->trans('prod::tools: document');
                    } elseif ($databoxSubdefs->hasSubdef($subdefName)) {
                        if (!$acl->has_access_to_subdef($record, $subdefName)) {
                            continue;
                        }

                        $label = $databoxSubdefs->getSubdef($subdefName)->get_label($this->app['locale']);
                    }
                    $recordAccessibleSubdefs[] = array(
                        'name' => $subdef->get_name(),
                        'state' => $permalink->get_is_activated(),
                        'label' => $label,
                    );
                }
            }

            if (!$record->isStory()) {
                try {
                    $metadata = $this->getExifToolReader()
                        ->files($record->get_subdef('document')->getRealPath())
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
            'recordSubdefs' => $recordAccessibleSubdefs,
            'metadatas' => $metadata,
        ]);
    }

    public function rotateAction(Request $request)
    {
        $records = RecordsRequest::fromRequest($this->app, $request, false);
        $rotation = (int)$request->request->get('rotation', 90);
        $rotation %= 360;

        if ($rotation > 180) {
            $rotation -= 360;
        }

        if ($rotation <= -180) {
            $rotation += 360;
        }

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
                    // ignore exception
                }
            }

            $this->dispatch(RecordEvents::ROTATE, new RecordWasRotated($record, $rotation));
        }

        return $this->app->json(['success' => true, 'errorMessage' => '']);
    }

    public function imageAction(Request $request)
    {
        $return = ['success' => true];

        $force = $request->request->get('force_substitution') == '1';

        $selection = RecordsRequest::fromRequest($this->app, $request, false, [\ACL::CANMODIFRECORD]);

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

                    $this->getDataboxLogger($record->getDatabox())
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
            $this->getDataboxLogger($record->getDatabox())
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
        try {
            $record = new \record_adapter($this->app, $request->request->get('sbas_id'), $request->request->get('record_id'));

            $subDef = $request->request->get('sub_def');

            // legacy handling
            if (!is_array($subDef)) {
                $subDef = ['name' => 'thumbnail', 'src' => $request->request->get('image', '')];
            }

            foreach ($subDef as $def) {
                $this->substituteMedia($record, $def['name'], $def['src']);
            }

            $return = ['success' => true, 'message' => ''];
        } catch (\Exception $e) {
            $return = ['success' => false, 'message' => $e->getMessage()];
        }

        return $this->app->json($return);
    }

    /**
     * Edit a record share state
     * @param Request $request
     * @param $base_id
     * @param $record_id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function editRecordSharing(Request $request, $base_id, $record_id)
    {

        $record = new \record_adapter($this->app, \phrasea::sbasFromBas($this->app, $base_id), $record_id);
        $subdefName = (string)$request->request->get('name');
        $state = $request->request->get('state') == 'true' ? true : false;

        $acl = $this->getAclForUser();
        if (!$acl->has_right(\ACL::BAS_CHUPUB)
            || !$acl->has_right_on_base($record->getBaseId(), \ACL::CANMODIFRECORD)
            || !$acl->has_right_on_base($record->getBaseId(), \ACL::IMGTOOLS)
            || ('document' == $subdefName && !$acl->has_right_on_base($record->getBaseId(), \ACL::CANDWNLDHD))
            || ('document' != $subdefName && !$acl->has_access_to_subdef($record, $subdefName))
        ) {
            $this->app->abort(403);
        }

        $subdef = $record->get_subdef($subdefName);

        if (null === $permalink = $subdef->get_permalink()) {
            return $this->app->json(['success' => false, 'state' => false], 400);
        }

        try {
            $permalink->set_is_activated($state);
            $return = ['success' => true, 'state' => $permalink->get_is_activated()];
        } catch (\Exception $e) {
            $return = ['success' => false, 'state' => $permalink->get_is_activated()];
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

    /**
     * @param \record_adapter $record
     * @param string $subDefName
     * @param string $subDefDataUri
     * @throws \DataURI\Exception\InvalidDataException
     */
    private function substituteMedia(\record_adapter $record, $subDefName, $subDefDataUri)
    {
        $dataUri = Parser::parse($subDefDataUri);

        $name = sprintf('extractor_thumb_%s', $record->getId());
        $fileName = sprintf('%s/%s.png', sys_get_temp_dir(), $name);

        file_put_contents($fileName, $dataUri->getData());

        $media = $this->app->getMediaFromUri($fileName);

        $this->getSubDefinitionSubstituer()->substitute($record, $subDefName, $media);
        $this->getDataboxLogger($record->getDatabox())
          ->log($record, \Session_Logger::EVENT_SUBSTITUTE, $subDefName, '');

        unset($media);
        $this->getFilesystem()->remove($fileName);
    }
}
