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
use Alchemy\Phrasea\Core\Event\Record\RecordAutoSubtitleEvent;
use Alchemy\Phrasea\Core\Event\Record\RecordEvents;
use Alchemy\Phrasea\Core\Event\Record\SubdefinitionCreateEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\Metadata\PhraseanetMetadataReader;
use Alchemy\Phrasea\Metadata\PhraseanetMetadataSetter;
use Alchemy\Phrasea\Record\RecordWasRotated;
use Alchemy\Phrasea\WorkerManager\Event\RecordsWriteMetaEvent;
use Alchemy\Phrasea\WorkerManager\Event\WorkerEvents;
use DataURI\Parser;
use MediaAlchemyst\Alchemyst;
use MediaVorus\MediaVorus;
use record_adapter;
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

        $metadatas = false;
        $record = null;
        $recordAccessibleSubdefs = [];
        $listsubdef = null;
        if (count($records) == 1) {
            /** @var record_adapter $record */
            $record = $records->first();

            /**Array list of subdefs**/
            $listsubdef = array_keys($record->get_subdefs());
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
                    }
                    elseif ($databoxSubdefs !== null && $databoxSubdefs->hasSubdef($subdefName)) {
                        if (!$acl->has_access_to_subdef($record, $subdefName)) {
                            continue;
                        }

                        $label = $databoxSubdefs->getSubdef($subdefName)->get_label($this->app['locale']);
                    }
                    $recordAccessibleSubdefs[] = [
                        'name'  => $subdef->get_name(),
                        'state' => $permalink->get_is_activated(),
                        'label' => $label,
                    ];
                }
            }
            if (!$record->isStory()) {
                $metadatas = true;
            }
        }

        $availableSubdefLabel = [];
        $countSubdefTodo = [];

        $substituables = [];
        if ($this->getConf()->get(['registry', 'modules', 'doc-substitution'])) {
            $substituables[] = 'document';
        }
        /** @var record_adapter $rec */
        foreach ($records as $rec) {

            $databoxSubdefs = $rec->getDatabox()->get_subdef_structure()->getSubdefGroup($rec->getType());
            if ($databoxSubdefs !== null) {
                foreach ($databoxSubdefs as $sub) {
                    if ($sub->isTobuild()) {
                        $label = trim($sub->get_label($this->app['locale']));
                        $availableSubdefLabel[] = $label;
                        if (isset($countSubdefTodo[$label])) {
                            $countSubdefTodo[$label]++;
                        }
                        else {
                            $countSubdefTodo[$label] = 1;
                        }
                    }
                    if ($sub->isSubstituable()) {
                        $substituables[] = $sub->get_name();
                    }
                }
            }
        }

        if (count($records) > 1) {
            $substituables = [];
        }

        $this->setSessionFormToken('prodToolsSubdef');
        $this->setSessionFormToken('prodToolsRotate');
        $this->setSessionFormToken('prodToolsHDSubstitution');
        $this->setSessionFormToken('prodToolsThumbSubstitution');

        return $this->render('prod/actions/Tools/index.html.twig', [
            'records'              => $records,
            'record'               => $record,
            'recordSubdefs'        => $recordAccessibleSubdefs,
            'metadatas'            => $metadatas,
            'listsubdef'           => $listsubdef,
            'availableSubdefLabel' => array_unique($availableSubdefLabel),
            'nbRecords'            => count($records),
            'countSubdefTodo'      => $countSubdefTodo,
            'substituables'        => $substituables,
        ]);
    }

    public function rotateAction(Request $request)
    {
        if (!$this->isCrsfValid($request, 'prodToolsRotate')) {
            return $this->app->json(['success' => false , 'message' => 'invalid rotate form'], 403);
        }

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
            /** @var  \media_subdef $subdef */
            foreach ($record->get_subdefs() as $subdef) {
                if ($subdef->get_type() !== \media_subdef::TYPE_IMAGE) {
                    continue;
                }

                try {
                    $subdef->rotate($rotation, $this->getMediaAlchemyst(), $this->getMediaVorus());
                }
                catch (\Exception $e) {
                    // ignore exception
                }
            }

            $this->dispatch(RecordEvents::ROTATE, new RecordWasRotated($record, $rotation));
        }

        return $this->app->json(['success' => true, 'errorMessage' => '']);
    }

    public function imageAction(Request $request)
    {
        if (!$this->isCrsfValid($request, 'prodToolsSubdef')) {
            return $this->app->json(['success' => false , 'message' => 'invalid create subview form'], 403);
        }

        $return = ['success' => true];

        $force = $request->request->get('force_substitution') == '1';
        $subdefsLabel = $request->request->get('subdefsLabel', []);

        $selection = RecordsRequest::fromRequest($this->app, $request, false, [\ACL::CANMODIFRECORD]);

        /** @var record_adapter $record */
        foreach ($selection as $record) {
            $substituted = false;
            /** @var  \media_subdef $subdef */
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
                $subdefsName = [];

                // get subdefinition name from selected subdefinition label
                $databoxSubdefs = $record->getDatabox()->get_subdef_structure()->getSubdefGroup($record->getType());
                if ($databoxSubdefs !== null) {
                    foreach ($databoxSubdefs as $sub) {
                        if (in_array(trim($sub->get_label($this->app['locale'])), $subdefsLabel)) {
                            $subdefsName[] = $sub->get_name();
                        }
                    }
                }

                $this->dispatch(RecordEvents::SUBDEFINITION_CREATE, new SubdefinitionCreateEvent($record, false, $subdefsName));
            }
        }


        return $this->app->json($return);
    }

    public function hddocAction(Request $request)
    {
        if (!$this->isCrsfValid($request, 'prodToolsHDSubstitution')) {
            return $this->app->json(['success' => false , 'message' => 'invalid document substitution form'], 403);
        }

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

                    $record = new record_adapter($this->app, $request->get('sbas_id'), $request->get('record_id'));

                    $media = $this->app->getMediaFromUri($tempoFile);

                    $this->getSubDefinitionSubstituer()->substituteDocument($record, $media);
                    $record->insertTechnicalDatas($this->getMediaVorus());
                    $this->getMetadataSetter()->replaceMetadata($this->getMetadataReader()->read($media), $record);

                    $this->getDataboxLogger($record->getDatabox())
                        ->log($record, \Session_Logger::EVENT_SUBSTITUTE, 'HD', '');

                    if ((int)$request->request->get('ccfilename') === 1) {
                        $record->set_original_name($fileName);
                    }
                    unlink($tempoFile);
                    rmdir($tempoDir);
                    $success = true;
                    $message = $this->app->trans('Document has been successfully substitued');
                }
                catch (\Exception $e) {
                    $message = $this->app->trans('file is not valid');
                }
            }
            else {
                $message = $this->app->trans('file is not valid');
            }
        }
        else {
            $this->app->abort(400, 'Missing file parameter');
        }

        return $this->render('prod/actions/Tools/iframeUpload.html.twig', [
            'success' => $success,
            'message' => $message,
        ]);
    }

    public function changeThumbnailAction(Request $request)
    {
        if (!$this->isCrsfValid($request, 'prodToolsThumbSubstitution')) {
            return $this->app->json(['success' => false , 'message' => 'invalid thumbnail substitution form'], 403);
        }

        $file = $request->files->get('newThumb');

        if (empty($file)) {
            $this->app->abort(400, 'Missing file parameter');
        }

        if (!$file->isValid()) {
            return $this->render('prod/actions/Tools/iframeUpload.html.twig', [
                'success' => false,
                'message' => $this->app->trans('file is not valid'),
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

            $record = new record_adapter($this->app, $request->get('sbas_id'), $request->get('record_id'));

            $media = $this->app->getMediaFromUri($tempoFile);

            // no BC break before PHRAS-3918 when only "thumbnail" was substituable
            if(($subdef = $request->get('subdef')) === null) {
                $subdef = 'thumbnail';
            }

            $this->getSubDefinitionSubstituer()->substituteSubdef($record, $subdef, $media);
            $this->getDataboxLogger($record->getDatabox())
                ->log($record, \Session_Logger::EVENT_SUBSTITUTE, $subdef, '');

            unlink($tempoFile);
            rmdir($tempoDir);
            $success = true;
            $message = sprintf($this->app->trans('Subdef "%s" has been successfully substitued'), $subdef);
        }
        catch (\Exception $e) {
            $success = false;
            $message = $this->app->trans('file is not valid');
        }

        return $this->render('prod/actions/Tools/iframeUpload.html.twig', [
            'success' => $success,
            'message' => $message,
        ]);
    }

    public function submitConfirmBoxAction(Request $request)
    {
        $template = 'prod/actions/Tools/confirm.html.twig';

        try {
            $record = new record_adapter($this->app, $request->request->get('sbas_id'), $request->request->get('record_id'));
            $var = [
                'video_title' => $record->get_title(['encode' => record_adapter::ENCODE_NONE]),
                'image'       => $request->request->get('image', ''),
            ];
            $return = [
                'error' => false,
                'datas' => $this->render($template, $var),
            ];
        }
        catch (\Exception $e) {
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
            $record = new record_adapter($this->app, $request->request->get('sbas_id'), $request->request->get('record_id'));

            $subDef = $request->request->get('sub_def');

            // legacy handling
            if (!is_array($subDef)) {
                $subDef = ['name' => 'thumbnail', 'src' => $request->request->get('image', '')];
            }

            foreach ($subDef as $def) {
                $this->substituteMedia($record, $def['name'], $def['src']);
            }

            $return = ['success' => true, 'message' => ''];
        }
        catch (\Exception $e) {
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

        $record = new record_adapter($this->app, \phrasea::sbasFromBas($this->app, $base_id), $record_id);
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
        }
        catch (\Exception $e) {
            $return = ['success' => false, 'state' => $permalink->get_is_activated()];
        }

        return $this->app->json($return);
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
     * @param record_adapter $record
     * @param string $subDefName
     * @param string $subDefDataUri
     * @throws \DataURI\Exception\InvalidDataException
     */
    private function substituteMedia(record_adapter $record, $subDefName, $subDefDataUri)
    {
        $dataUri = Parser::parse($subDefDataUri);

        $name = sprintf('extractor_thumb_%s', $record->getId());
        $fileName = sprintf('%s/%s.png', sys_get_temp_dir(), $name);

        file_put_contents($fileName, $dataUri->getData());

        $media = $this->app->getMediaFromUri($fileName);

        if ($subDefName == 'document') {
            $this->getSubDefinitionSubstituer()->substituteDocument($record, $media);
        }
        else {
            $this->getSubDefinitionSubstituer()->substituteSubdef($record, $subDefName, $media);
        }
        $this->getDataboxLogger($record->getDatabox())
            ->log($record, \Session_Logger::EVENT_SUBSTITUTE, $subDefName, '');

        unset($media);
        $this->getFilesystem()->remove($fileName);
    }

    /**
     * @param $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function saveMetasAction(Request $request)
    {
        $record = new record_adapter($this->app,
            (int)$request->request->get("databox_id"),
            (int)$request->request->get("record_id"));

        $metadatas[0] = [
            'meta_struct_id' => (int)$request->request->get("meta_struct_id"),
            'meta_id'        => '',
            'value'          => $request->request->get("value")
        ];
        try {
            $record->set_metadatas($metadatas);

            // order to write meta in file
            $this->app['dispatcher']->dispatch(WorkerEvents::RECORDS_WRITE_META,
                new RecordsWriteMetaEvent([$record->getRecordId()], $record->getDataboxId()));

        }
        catch (\Exception $e) {
            return $this->app->json(['success' => false, 'errorMessage' => $e->getMessage()]);
        }

        return $this->app->json(['success' => true, 'errorMessage' => '']);
    }

    public function autoSubtitleAction(Request $request)
    {
        $record = new record_adapter($this->app,
            (int)$request->request->get("databox_id"),
            (int)$request->request->get("record_id")
        );


        $this->dispatch(
            PhraseaEvents::RECORD_AUTO_SUBTITLE,
            new RecordAutoSubtitleEvent(
                $record,
                $request->request->get("subtitle_language_source"),
                json_decode($request->request->get("subtitle_destination"), true),
                $this->getAuthenticatedUser()->getId()
            )
        );

        return $this->app->json(["status" => "dispatch"]);
    }

    public function videoEditorAction(Request $request)
    {
        $records = RecordsRequest::fromRequest($this->app, $request, false);

        $metadatas = false;
        $record = null;
        $JSFields = [];
        $videoTextTrackFields = [];

        if (count($records) == 1) {
            /** @var record_adapter $record */
            $record = $records->first();
            $databox = $record->getDatabox();


            foreach ($databox->get_meta_structure() as $meta) {
                /** @var \databox_field $meta */
                $fields[] = $meta;

                /** @Ignore */
                $JSFields[$meta->get_id()] = [
                    'id'     => $meta->get_id(),
                    'name'   => $meta->get_name(),
                    '_value' => $record->getCaption([$meta->get_name()]),
                ];

                if (preg_match('/^VideoTextTrack(.*)$/iu', $meta->get_name(), $matches) && !empty($matches[1]) && strlen($matches[1]) == 2) {
                    $field['label'] = $matches[1];
                    $field['meta_struct_id'] = $meta->get_id();
                    $field['value'] = '';
                    if ($record->get_caption()->has_field($meta->get_name())) {
                        $fieldValues = $record->get_caption()->get_field($meta->get_name())->get_values();
                        $fieldValue = array_pop($fieldValues);
                        $field['value'] = $fieldValue->getValue();
                    }
                    $videoTextTrackFields[$meta->get_id()] = $field;
                    unset($field);
                }
            }

            if (!$record->isStory()) {
                $metadatas = true;
            }
        }
        $conf = $this->getConf();

        return $this->render('prod/actions/Tools/videoEditor.html.twig', [
            'records'              => $records,
            'record'               => $record,
            'videoEditorConfig'    => $conf->get(['video-editor']),
            'metadatas'            => $metadatas,
            'JSonFields'           => json_encode($JSFields),
            'videoTextTrackFields' => $videoTextTrackFields,
            'languages'            => $this->languageList()
        ]);
    }

    private function isPhysicallyPresent(record_adapter $record, $subdefName)
    {
        try {
            return $record->get_subdef($subdefName)->is_physically_present();
        }
        catch (\Exception $e) {
            unset($e);
        }

        return false;
    }

    private function languageList()
    {
        return [
            'af-ZA' => 'af-ZA Afrikaans (South Africa)', 'am-ET' => 'am-ET Amharic (Ethiopia)',
            'ar-DZ' => 'ar-DZ Arabic (Algeria)', 'ar-BH' => 'ar-BH Arabic (Bahrain)',
            'ar-EG' => 'ar-EG Arabic (Egypt)', 'ar-IQ' => 'ar-IQ Arabic (Iraq)',
            'ar-IL' => 'ar-IL Arabic (Israel)', 'ar-YE' => 'ar-YE Arabic (Yemen)',
            'eu-ES' => 'eu-ES Basque (Spain)', 'bn-BD' => 'bn-BD Bengali (Bangladesh)',
            'bn-IN' => 'bn-IN Bengali (India)', 'bg-BG' => 'bg-BG Bulgarian (Bulgaria)',
            'ca-ES' => 'ca-ES Catalan (Spain)', 'yue-Hant-HK' => 'yue-Hant-HK Chinese, Cantonese (Traditional, Hong Kong)',
            'cmn-Hans-CN' => 'cmn-Hans-CN Chinese, Mandarin (Simplified, China)', 'hr-HR' => 'hr-HR Croatian (Croatia)',
            'cs-CZ' => 'cs-CZ Czech (Czech Republic)', 'da-DK' => 'da-DK Danish (Denmark)',
            'nl-NL' => 'nl-NL Dutch (Netherlands)', 'nl-BE' => 'nl-BE Dutch (Belgium)',
            'en-AU' => 'en-AU English (Australia)', 'en-CA' => 'en-CA English (Canada)',
            'en-GB' => 'en-GB English (United Kingdom)', 'en-US' => 'en-US	English (United States)',
            'fr-CA' => 'fr-CA French (Canada)', 'fr-FR' => 'fr-FR French (France)',
            'fr-BE' => 'fr-BE French (Belgium)', 'fr-CH' => 'fr-CH French (Switzerland)',
            'ka-GE' => 'ka-GE Georgian (Georgia)', 'de-DE' => 'de-DE German (Germany)',
            'el-GR' => 'el-GR Greek (Greece)', 'he-IL' => 'he-IL Hebrew (Israel)',
            'hi-IN' => 'hi-IN Hindi (India)', 'hu-HU' => 'hu-HU Hungarian (Hungary)',
            'is-IS' => 'is-IS Icelandic (Iceland)', 'id-ID' => 'id-ID Indonesian (Indonesia)',
            'it-IT' => 'it-IT Italian (Italy)', 'ja-JP' => 'ja-JP Japanese (Japan)',
            'ko-KR' => 'ko-KR Korean (South Korea)', 'lo-LA' => 'lo-LA Lao (Laos)',
            'lt-LT' => 'lt-LT Lithuanian (Lithuania)', 'ms-MY' => 'ms-MY Malay (Malaysia)',
            'ne-NP' => 'ne-NP Nepali (Nepal)', 'nb-NO' => 'nb-NO Norwegian Bokmål (Norway)',
            'pl-PL' => 'pl-PL Polish (Poland)', 'pt-BR' => 'pt-BR Portuguese (Brazil)',
            'pt-PT' => 'pt-PT Portuguese (Portugal)', 'ro-RO' => 'ro-RO	Romanian (Romania)',
            'ru-RU' => 'ru-RU Russian (Russia)', 'sr-RS' => 'sr-RS Serbian (Serbia)',
            'sk-SK' => 'sk-SK Slovak (Slovakia)', 'sl-SI' => 'sl-SI	Slovenian (Slovenia)',
            'es-ES' => 'es-ES Spanish (Spain)', 'sv-SE' => 'sv-SE Swedish (Sweden)',
            'th-TH' => 'th-TH Thai (Thailand)', 'tr-TR' => 'tr-TR Turkish (Turkey)',
            'uk-UA' => 'uk-UA Ukrainian (Ukraine)', 'vi-VN' => 'vi-VN Vietnamese (Vietnam)',
            'et-EE' => 'et-EE Estonian (Estonia)', 'mn-MN' => 'mn-MN Mongolian (Mongolia)',
            'uz-UZ' => 'uz-UZ Uzbek (Uzbekistan)'

        ];
    }
}
