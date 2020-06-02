<?php

namespace Alchemy\Phrasea\Controller\Api;

use Alchemy\Phrasea\Application\Helper\JsonBodyAware;
use Alchemy\Phrasea\Collection\Reference\CollectionReference;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Databox\DataboxGroupable;
use Alchemy\Phrasea\Fractal\CallbackTransformer;
use Alchemy\Phrasea\Fractal\IncludeResolver;
use Alchemy\Phrasea\Fractal\SearchResultTransformerResolver;
use Alchemy\Phrasea\Fractal\TraceableArraySerializer;
use Alchemy\Phrasea\Model\Manipulator\UserManipulator;
use Alchemy\Phrasea\Model\RecordReferenceInterface;
use Alchemy\Phrasea\Record\RecordCollection;
use Alchemy\Phrasea\Record\RecordReferenceCollection;
use Alchemy\Phrasea\Search\CaptionView;
use Alchemy\Phrasea\Search\PermalinkTransformer;
use Alchemy\Phrasea\Search\PermalinkView;
use Alchemy\Phrasea\Search\RecordTransformer;
use Alchemy\Phrasea\Search\RecordView;
use Alchemy\Phrasea\Search\SearchResultView;
use Alchemy\Phrasea\Search\StoryTransformer;
use Alchemy\Phrasea\Search\StoryView;
use Alchemy\Phrasea\Search\SubdefTransformer;
use Alchemy\Phrasea\Search\SubdefView;
use Alchemy\Phrasea\Search\TechnicalDataTransformer;
use Alchemy\Phrasea\Search\TechnicalDataView;
use Alchemy\Phrasea\Search\V1SearchCompositeResultTransformer;
use Alchemy\Phrasea\Search\V1SearchResultTransformer;
use Alchemy\Phrasea\SearchEngine\SearchEngineInterface;
use Alchemy\Phrasea\SearchEngine\SearchEngineLogger;
use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;
use Alchemy\Phrasea\SearchEngine\SearchEngineResult;
use League\Fractal\Resource\Item;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use databox_field;


class V3Controller extends Controller
{
    use JsonBodyAware;

    /**
     * Return detailed information about one story
     *
     * @param  Request $request
     * @param  int     $databox_id
     * @param  int     $record_id
     *
     * @return Response
     */
    public function setmetadatasAction(Request $request, $databox_id, $record_id)
    {
        $struct = $this->findDataboxById($databox_id)->get_meta_structure();
        $record = $this->findDataboxById($databox_id)->get_record($record_id);

        //$record->set_metadatas()

        $structByKey = [];
        $nameToStrucId = [];
        foreach($struct as $f) {
            $nameToStrucId[$f->get_name()] = $f->get_id();
            $structByKey[$f->get_id()] = $f;
            $structByKey[$f->get_name()] = &$structByKey[$f->get_id()];
        }

        try {
            $b = $this->decodeJsonBody($request);
        }
        catch(\Exception $e) {
            return $this->app['controller.api.v1']->getBadRequestAction($request, 'Bad JSON');
        }

        $metadatas_ops = [];
        foreach ($b->metadatas as $_m) {
            // sanity
            if($_m->meta_struct_id && $_m->field_name) {
                return $this->app['controller.api.v1']->getBadRequestAction(
                    $request,
                    "define meta_struct_id OR field_name, not both."
                );
            }
            // select fields that match meta_struct_id or field_name (can be arrays)
            $fields_list = null;    // to filter caption_fields from record, default all
            $struct_fields = [];    // struct fields that match meta_struct_id or field_name
            if(($field_keys = $_m->meta_struct_id ? $_m->meta_struct_id : $_m->field_name) !== null) {  // can be null if none defined (=match all)
                if (!is_array($field_keys)) {
                    $field_keys = [$field_keys];
                }
                $fields_list = [];
                foreach ($field_keys as $k) {
                    if(array_key_exists($k, $structByKey)) {
                        $fields_list[] = $structByKey[$k]->get_name();
                        $struct_fields[$structByKey[$k]->get_id()] = $structByKey[$k];
                    }
                }
            }
            $caption_fields = $record->get_caption()->get_fields($fields_list, true);

            $meta_id = is_null($_m->meta_id) ? null : (int)($_m->meta_id);

            if(!($match_method = (string)($_m->match_method))) {
                $match_method = 'ignore_case';
            }
            if(!in_array($match_method, ['strict', 'ignore_case', 'regexp'])) {
                return $this->app['controller.api.v1']->getBadRequestAction(
                    $request,
                    sprintf("bad match_method (%s).", $match_method)
                );
            }

            $values = [];
            if(is_array($_m->value)) {
                foreach ($_m->value as $v) {
                    $values[] = is_null($v) ? null : (string)$v;
                }
            }
            else {
                $values = is_null($_m->value) ? [] : [(string)($_m->value)];
            }

            if(!($action = (string)($_m->action))) {
                $action = 'set';
            }
            switch($_m->action) {
                case 'set':
                    $metadatas_ops = array_merge(
                        $metadatas_ops,
                        $this->setmetadatasAction_set($struct_fields, $caption_fields, $meta_id, $values)
                    );
                    break;
                case 'add':
                    $metadatas_ops = array_merge(
                        $metadatas_ops,
                        $this->setmetadatasAction_add($struct_fields, $values)
                    );
                    break;
                case 'delete':
                    $metadatas_ops = array_merge(
                        $metadatas_ops,
                        $this->setmetadatasAction_replace($caption_fields, $meta_id, $match_method, $values, null)
                    );
                    break;
                case 'replace':
                    if(!is_string($_m->replace_with) && !is_null($_m->replace_with)) {
                        return $this->app['controller.api.v1']->getBadRequestAction(
                            $request,
                            "bad \"replace_with\" for action \"replace\"."
                        );
                    }
                    $metadatas_ops = array_merge(
                        $metadatas_ops,
                        $this->setmetadatasAction_replace($caption_fields, $meta_id, $match_method, $values, $_m->replace_with)
                    );
                    break;
                default:
                    return $this->app['controller.api.v1']->getBadRequestAction(
                        $request,
                        sprintf("bad action (%s).", $action)
                    );
            }
        }

        return Result::create($request, $metadatas_ops)->createResponse();
    }

    private function match($pattern, $method, $value)
    {
        switch ($method) {
            case 'strict':
                return $value === $pattern;
            case 'ignore_case':
                return strtolower($value) === strtolower($pattern);
            case 'regexp':
                return preg_match($pattern, $value) == 1;
        }
    }

    /**
     * @param databox_field[] $struct_fields    struct-fields (from struct) matching meta_struct_id or field_name
     * @param \caption_field[] $caption_fields  caption-fields (from record) matching meta_struct_id or field_name (or all if not set)
     * @param int|null $meta_id
     * @param string[] $values
     *
     * @return array                            ops to execute
     */
    private function setmetadatasAction_set($struct_fields, $caption_fields, $meta_id, $values)
    {
        $ops = [];

        // if one field was multi-valued and no meta_id was set, we must delete all values
        foreach ($caption_fields as $cf) {
            if ($cf->is_multi() && is_null($meta_id)) {
                foreach ($cf->get_values() as $field_value) {
                    $a[] = [
                        'meta_struct_id' => $cf->get_meta_struct_id(),
                        'meta_id'        => $field_value->getId(),
                        'value'          => null
                    ];
                }
            }
        }
        // now set values to matching struct_fields
        foreach ($struct_fields as $sf) {
            if($sf->is_multi()) {
                //  add the non-null value(s)
                foreach ($values as $value) {
                    if (!is_null($value)) {
                        $ops[] = [
                            'meta_struct_id' => $sf->get_id(),
                            'meta_id'        => $meta_id,  // can be null
                            'value'          => $value
                        ];
                    }
                }
            }
            else {
                // mono-valued
                $ops[] = [
                    'meta_struct_id' => $sf->get_id(),
                    'meta_id'        => $meta_id,  // probably null,
                    'value'          => $values[0]
                ];
            }
        }

        return $ops;
    }

    /**
     * @param databox_field[] $struct_fields    struct-fields (from struct) matching meta_struct_id or field_name
     * @param string[] $values
     *
     * @return array                            ops to execute
     */
    private function setmetadatasAction_add($struct_fields, $values)
    {
        $ops = [];

        // now set values to matching struct_fields
        foreach ($struct_fields as $sf) {
            if(!$sf->is_multi()) {
                // todo : return error "cant add to mono-valued"
                continue;
            }
            //  add the non-null value(s)
            foreach ($values as $value) {
                if (!is_null($value)) {
                    $ops[] = [
                        'meta_struct_id' => $sf->get_id(),
                        'meta_id'        => null,
                        'value'          => $value
                    ];
                }
            }
        }

        return $ops;
    }

    /**
     * @param \caption_field[] $caption_fields  caption-fields (from record) matching meta_struct_id or field_name (or all if not set)
     * @param int|null $meta_id
     * @param string $match_method              "strict" | "ignore_case" | "regexp"
     * @param string[] $values
     * @param string|null $replace_with
     *
     * @return array                            ops to execute
     */
    private function setmetadatasAction_replace($caption_fields, $meta_id, $match_method, $values, $replace_with)
    {
        $ops = [];

        foreach ($caption_fields as $cf) {
            // match all ?
            if(is_null($meta_id) && count($values) == 0) {
                foreach ($cf->get_values() as $field_value) {
                    $ops[] = [
                        'meta_struct_id' => $cf->get_meta_struct_id(),
                        'meta_id'        => $field_value->getId(),
                        'value'          => $replace_with
                    ];
                }
            }
            // match by meta-id ?
            if (!is_null($meta_id)) {
                foreach ($cf->get_values() as $field_value) {
                    if ($field_value->getId() === $meta_id) {
                        $a[] = [
                            'meta_struct_id' => $cf->get_meta_struct_id(),
                            'meta_id'        => $field_value->getId(),
                            'value'          => $replace_with
                        ];
                    }
                }
            }
            // match by value(s) ?
            foreach ($values as $value) {
                foreach ($cf->get_values() as $field_value) {
                    if ($this->match($value, $match_method, $field_value->getValue())) {
                        $ops[] = [
                            'meta_struct_id' => $cf->get_meta_struct_id(),
                            'meta_id'        => $field_value->getId(),
                            'value'          => $match_method=='regexp' ? preg_replace($value, $replace_with, $field_value->getValue()): $replace_with
                        ];
                    }
                }
            }
        }

        return $ops;
    }


    /**
     * Return detailed information about one story
     *
     * @param  Request $request
     * @param  int     $databox_id
     * @param  int     $record_id
     *
     * @return Response
     */
    public function getStoryAction(Request $request, $databox_id, $record_id)
    {
        try {
            $story = $this->findDataboxById($databox_id)->get_record($record_id);

            return Result::create($request, ['story' => $this->listStory($request, $story)])->createResponse();
        } catch (NotFoundHttpException $e) {
            return Result::createError($request, 404, $this->app->trans('Story Not Found'))->createResponse();
        } catch (\Exception $e) {
            return $this->app['controller.api.v1']->getBadRequestAction($request, $this->app->trans('An error occurred'));
        }
    }

    /**
     * Search for results
     *
     * @param  Request $request
     *
     * @return Response
     */
    public function searchAction(Request $request)
    {
        $subdefTransformer = new SubdefTransformer($this->app['acl'], $this->getAuthenticatedUser(), new PermalinkTransformer());
        $technicalDataTransformer = new TechnicalDataTransformer();
        $recordTransformer = new RecordTransformer($subdefTransformer, $technicalDataTransformer);
        $storyTransformer = new StoryTransformer($subdefTransformer, $recordTransformer);
        $compositeTransformer = new V1SearchCompositeResultTransformer($recordTransformer, $storyTransformer);
        $searchTransformer = new V1SearchResultTransformer($compositeTransformer);

        $transformerResolver = new SearchResultTransformerResolver([
            '' => $searchTransformer,
            'results' => $compositeTransformer,
            'results.stories' => $storyTransformer,
            'results.stories.thumbnail' => $subdefTransformer,
            'results.stories.metadatas' => new CallbackTransformer(),
            'results.stories.caption' => new CallbackTransformer(),
            'results.stories.records' => $recordTransformer,
            'results.stories.records.thumbnail' => $subdefTransformer,
            'results.stories.records.technical_informations' => $technicalDataTransformer,
            'results.stories.records.subdefs' => $subdefTransformer,
            'results.stories.records.metadata' => new CallbackTransformer(),
            'results.stories.records.status' => new CallbackTransformer(),
            'results.stories.records.caption' => new CallbackTransformer(),
            'results.records' => $recordTransformer,
            'results.records.thumbnail' => $subdefTransformer,
            'results.records.technical_informations' => $technicalDataTransformer,
            'results.records.subdefs' => $subdefTransformer,
            'results.records.metadata' => new CallbackTransformer(),
            'results.records.status' => new CallbackTransformer(),
            'results.records.caption' => new CallbackTransformer(),
        ]);

        $includeResolver = new IncludeResolver($transformerResolver);

        $fractal = new \League\Fractal\Manager();
        $fractal->setSerializer(new TraceableArraySerializer($this->app['dispatcher']));
        $fractal->parseIncludes($this->resolveSearchIncludes($request));

        $result = $this->doSearch($request);

        $story_max_records = null;
        // if search on story
        if ($request->get('search_type') == 1) {
            $story_max_records = (int)$request->get('story_max_records') ?: 10;
        }

        $searchView = $this->buildSearchView(
            $result,
            $includeResolver->resolve($fractal),
            $this->resolveSubdefUrlTTL($request),
            $story_max_records
        );

        $ret = $fractal->createData(new Item($searchView, $searchTransformer))->toArray();

        return Result::create($request, $ret)->createResponse();
    }

    /**
     * Retrieve detailed information about one story
     *
     * @param Request         $request
     * @param \record_adapter $story
     * @return array
     * @throws \Exception
     */
    private function listStory(Request $request, \record_adapter $story)
    {
        if (!$story->isStory()) {
            return Result::createError($request, 404, 'Story not found')->createResponse();
        }

        $per_page = (int)$request->get('per_page')?:10;
        $page = (int)$request->get('page')?:1;
        $offset = ($per_page * ($page - 1)) + 1;

        $caption = $story->get_caption();

        $format = function (\caption_record $caption, $dcField) {

            $field = $caption->get_dc_field($dcField);

            if (!$field) {
                return null;
            }

            return $field->get_serialized_values();
        };

        return [
            '@entity@'      => V1Controller::OBJECT_TYPE_STORY,
            'databox_id'    => $story->getDataboxId(),
            'story_id'      => $story->getRecordId(),
            'updated_on'    => $story->getUpdated()->format(DATE_ATOM),
            'created_on'    => $story->getCreated()->format(DATE_ATOM),
            'collection_id' => $story->getCollectionId(),
            'base_id'       => $story->getBaseId(),
            'thumbnail'     => $this->listEmbeddableMedia($request, $story, $story->get_thumbnail()),
            'uuid'          => $story->getUuid(),
            'metadatas'     => [
                '@entity@'       => V1Controller::OBJECT_TYPE_STORY_METADATA_BAG,
                'dc:contributor' => $format($caption, \databox_Field_DCESAbstract::Contributor),
                'dc:coverage'    => $format($caption, \databox_Field_DCESAbstract::Coverage),
                'dc:creator'     => $format($caption, \databox_Field_DCESAbstract::Creator),
                'dc:date'        => $format($caption, \databox_Field_DCESAbstract::Date),
                'dc:description' => $format($caption, \databox_Field_DCESAbstract::Description),
                'dc:format'      => $format($caption, \databox_Field_DCESAbstract::Format),
                'dc:identifier'  => $format($caption, \databox_Field_DCESAbstract::Identifier),
                'dc:language'    => $format($caption, \databox_Field_DCESAbstract::Language),
                'dc:publisher'   => $format($caption, \databox_Field_DCESAbstract::Publisher),
                'dc:relation'    => $format($caption, \databox_Field_DCESAbstract::Relation),
                'dc:rights'      => $format($caption, \databox_Field_DCESAbstract::Rights),
                'dc:source'      => $format($caption, \databox_Field_DCESAbstract::Source),
                'dc:subject'     => $format($caption, \databox_Field_DCESAbstract::Subject),
                'dc:title'       => $format($caption, \databox_Field_DCESAbstract::Title),
                'dc:type'        => $format($caption, \databox_Field_DCESAbstract::Type),
            ],
            'records'       => $this->listRecords($request, array_values($story->getChildren($offset, $per_page)->get_elements())),
        ];
    }

    private function listEmbeddableMedia(Request $request, \record_adapter $record, \media_subdef $media)
    {
        if (!$media->is_physically_present()) {
            return null;
        }

        if ($this->getAuthenticator()->isAuthenticated()) {
            $acl = $this->getAclForUser();
            if ($media->get_name() !== 'document'
                && false === $acl->has_access_to_subdef($record, $media->get_name())
            ) {
                return null;
            }
            if ($media->get_name() === 'document'
                && !$acl->has_right_on_base($record->getBaseId(), \ACL::CANDWNLDHD)
                && !$acl->has_hd_grant($record)
            ) {
                return null;
            }
        }

        if ($media->get_permalink() instanceof \media_Permalink_Adapter) {
            $permalink = $this->listPermalink($media->get_permalink());
        } else {
            $permalink = null;
        }

        $urlTTL = (int) $request->get(
            'subdef_url_ttl',
            $this->getConf()->get(['registry', 'general', 'default-subdef-url-ttl'])
        );
        if ($urlTTL < 0) {
            $urlTTL = -1;
        }
        $issuer = $this->getAuthenticatedUser();

        return [
            'name' => $media->get_name(),
            'permalink' => $permalink,
            'height' => $media->get_height(),
            'width' => $media->get_width(),
            'filesize' => $media->get_size(),
            'devices' => $media->getDevices(),
            'player_type' => $media->get_type(),
            'mime_type' => $media->get_mime(),
            'substituted' => $media->is_substituted(),
            'created_on'  => $media->get_creation_date()->format(DATE_ATOM),
            'updated_on'  => $media->get_modification_date()->format(DATE_ATOM),
            'url' => $this->app['media_accessor.subdef_url_generator']->generate($issuer, $media, $urlTTL),
            'url_ttl' => $urlTTL,
        ];
    }

    private function listPermalink(\media_Permalink_Adapter $permalink)
    {
        $downloadUrl = $permalink->get_url();
        $downloadUrl->getQuery()->set('download', '1');

        return [
            'created_on'   => $permalink->get_created_on()->format(DATE_ATOM),
            'id'           => $permalink->get_id(),
            'is_activated' => $permalink->get_is_activated(),
            /** @Ignore */
            'label'        => $permalink->get_label(),
            'updated_on'   => $permalink->get_last_modified()->format(DATE_ATOM),
            'page_url'     => $permalink->get_page(),
            'download_url' => (string)$downloadUrl,
            'url'          => (string)$permalink->get_url(),
        ];
    }

    /**
     * @param Request $request
     * @param RecordReferenceInterface[]|RecordReferenceCollection $records
     * @return array
     */
    private function listRecords(Request $request, $records)
    {
        if (!$records instanceof RecordReferenceCollection) {
            $records = new RecordReferenceCollection($records);
        }

        $technicalData = $this->app['service.technical_data']->fetchRecordsTechnicalData($records);

        $data = [];

        foreach ($records->toRecords($this->getApplicationBox()) as $index => $record) {
            $record->setTechnicalDataSet($technicalData[$index]);

            $data[$index] = $this->listRecord($request, $record);
        }

        return $data;
    }

    /**
     * Retrieve detailed information about one record
     *
     * @param Request          $request
     * @param \record_adapter $record
     * @return array
     */
    private function listRecord(Request $request, \record_adapter $record)
    {
        $technicalInformation = [];
        foreach ($record->get_technical_infos()->getValues() as $name => $value) {
            $technicalInformation[] = ['name' => $name, 'value' => $value];
        }

        $data = [
            'databox_id'             => $record->getDataboxId(),
            'record_id'              => $record->getRecordId(),
            'mime_type'              => $record->getMimeType(),
            'title'                  => $record->get_title(),
            'original_name'          => $record->get_original_name(),
            'updated_on'             => $record->getUpdated()->format(DATE_ATOM),
            'created_on'             => $record->getCreated()->format(DATE_ATOM),
            'collection_id'          => $record->getCollectionId(),
            'base_id'                => $record->getBaseId(),
            'sha256'                 => $record->getSha256(),
            'thumbnail'              => $this->listEmbeddableMedia($request, $record, $record->get_thumbnail()),
            'technical_informations' => $technicalInformation,
            'phrasea_type'           => $record->getType(),
            'uuid'                   => $record->getUuid(),
        ];

        if ($request->attributes->get('_extended', false)) {
            $data = array_merge($data, [
                'subdefs' => $this->listRecordEmbeddableMedias($request, $record),
                'metadata' => $this->listRecordMetadata($record),
                'status' => $this->listRecordStatus($record),
                'caption' => $this->listRecordCaption($record),
            ]);
        }

        return $data;
    }

    /**
     * @param Request $request
     * @param \record_adapter $record
     * @return array
     */
    private function listRecordEmbeddableMedias(Request $request, \record_adapter $record)
    {
        $subdefs = [];

        foreach ($record->get_embedable_medias([], []) as $name => $media) {
            if (null !== $subdef = $this->listEmbeddableMedia($request, $record, $media)) {
                $subdefs[] = $subdef;
            }
        }

        return $subdefs;
    }

    /**
     * List all fields of given record
     *
     * @param \record_adapter $record
     * @return array
     */
    private function listRecordMetadata(\record_adapter $record)
    {
        $includeBusiness = $this->getAclForUser()->can_see_business_fields($record->getDatabox());

        return $this->listRecordCaptionFields($record->get_caption()->get_fields(null, $includeBusiness));
    }

    /**
     * @param \caption_field[] $fields
     * @return array
     */
    private function listRecordCaptionFields($fields)
    {
        $ret = [];

        foreach ($fields as $field) {
            $databox_field = $field->get_databox_field();

            $fieldData = [
                'meta_structure_id' => $field->get_meta_struct_id(),
                'name' => $field->get_name(),
                'labels' => [
                    'fr' => $databox_field->get_label('fr'),
                    'en' => $databox_field->get_label('en'),
                    'de' => $databox_field->get_label('de'),
                    'nl' => $databox_field->get_label('nl'),
                ],
            ];

            foreach ($field->get_values() as $value) {
                $data = [
                    'meta_id' => $value->getId(),
                    'value' => $value->getValue(),
                ];

                $ret[] = $fieldData + $data;
            }
        }

        return $ret;
    }

    /**
     * Retrieve detailed information about one status
     *
     * @param \record_adapter $record
     * @return array
     */
    private function listRecordStatus(\record_adapter $record)
    {
        $ret = [];
        foreach ($record->getStatusStructure() as $bit => $status) {
            $ret[] = [
                'bit'   => $bit,
                'state' => \databox_status::bitIsSet($record->getStatusBitField(), $bit),
            ];
        }

        return $ret;
    }

    /**
     * @param \record_adapter $record
     * @return array
     */
    private function listRecordCaption(\record_adapter $record)
    {
        $includeBusiness = $this->getAclForUser()->can_see_business_fields($record->getDatabox());

        $caption = [];

        foreach ($record->get_caption()->get_fields(null, $includeBusiness) as $field) {
            $caption[] = [
                'meta_structure_id' => $field->get_meta_struct_id(),
                'name' => $field->get_name(),
                'value' => $field->get_serialized_values(';'),
            ];
        }

        return $caption;
    }

    /**
     * Returns requested includes
     *
     * @param Request $request
     * @return string[]
     */
    private function resolveSearchIncludes(Request $request)
    {
        $includes = [
            'results.stories.records'
        ];

        if ($request->attributes->get('_extended', false)) {
            if ($request->get('search_type') != SearchEngineOptions::RECORD_STORY) {
                $includes = array_merge($includes, [
                    'results.stories.records.subdefs',
                    'results.stories.records.metadata',
                    'results.stories.records.caption',
                    'results.stories.records.status'
                ]);
            }
            else {
                $includes = [ 'results.stories.caption' ];
            }

            $includes = array_merge($includes, [
                'results.records.subdefs',
                'results.records.metadata',
                'results.records.caption',
                'results.records.status'
            ]);
        }

        return $includes;
    }

    /**
     * @param SearchEngineResult $result
     * @param string[] $includes
     * @param int $urlTTL
     * @param int|null $story_max_records
     * @return SearchResultView
     */
    private function buildSearchView(SearchEngineResult $result, array $includes, $urlTTL, $story_max_records = null)
    {
        $references = new RecordReferenceCollection($result->getResults());

        $records = new RecordCollection();
        $stories = new RecordCollection();

        foreach ($references->toRecords($this->getApplicationBox()) as $record) {
            if ($record->isStory()) {
                $stories[$record->getId()] = $record;
            } else {
                $records[$record->getId()] = $record;
            }
        }

        $resultView = new SearchResultView($result);

        if ($stories->count() > 0) {
            $user = $this->getAuthenticatedUser();
            $children = [];

            foreach ($stories->getDataboxIds() as $databoxId) {
                $storyIds = $stories->getDataboxRecordIds($databoxId);

                $selections = $this->findDataboxById($databoxId)
                    ->getRecordRepository()
                    ->findChildren($storyIds, $user,1, $story_max_records);
                $children[$databoxId] = array_combine($storyIds, $selections);
            }

            /** @var StoryView[] $storyViews */
            $storyViews = [];
            /** @var RecordView[] $childrenViews */
            $childrenViews = [];

            foreach ($stories as $index => $story) {
                $storyView = new StoryView($story);

                $selection = $children[$story->getDataboxId()][$story->getRecordId()];

                $childrenView = $this->buildRecordViews($selection);

                foreach ($childrenView as $view) {
                    $childrenViews[spl_object_hash($view)] = $view;
                }

                $storyView->setChildren($childrenView);

                $storyViews[$index] = $storyView;
            }

            if (in_array('results.stories.thumbnail', $includes, true)) {
                $subdefViews = $this->buildSubdefsViews($stories, ['thumbnail'], $urlTTL);

                foreach ($storyViews as $index => $storyView) {
                    $storyView->setSubdefs($subdefViews[$index]);
                }
            }

            if (in_array('results.stories.metadatas', $includes, true) ||
                in_array('results.stories.caption', $includes, true)) {
                $captions = $this->app['service.caption']->findByReferenceCollection($stories);
                $canSeeBusiness = $this->retrieveSeeBusinessPerDatabox($stories);

                $this->buildCaptionViews($storyViews, $captions, $canSeeBusiness);
            }

            $allChildren = new RecordCollection();
            foreach ($childrenViews as $index => $childrenView) {
                $allChildren[$index] = $childrenView->getRecord();
            }

            $names = in_array('results.stories.records.subdefs', $includes, true) ? null : ['thumbnail'];
            $subdefViews = $this->buildSubdefsViews($allChildren, $names, $urlTTL);
            $technicalDatasets = $this->app['service.technical_data']->fetchRecordsTechnicalData($allChildren);

            foreach ($childrenViews as $index => $recordView) {
                $recordView->setSubdefs($subdefViews[$index]);
                $recordView->setTechnicalDataView(new TechnicalDataView($technicalDatasets[$index]));
            }

            if (array_intersect($includes, ['results.stories.records.metadata', 'results.stories.records.caption'])) {
                $captions = $this->app['service.caption']->findByReferenceCollection($allChildren);
                $canSeeBusiness = $this->retrieveSeeBusinessPerDatabox($allChildren);

                $this->buildCaptionViews($childrenViews, $captions, $canSeeBusiness);
            }

            $resultView->setStories($storyViews);
        }

        if ($records->count() > 0) {
            $names = in_array('results.records.subdefs', $includes, true) ? null : ['thumbnail'];
            $recordViews = $this->buildRecordViews($records);
            $subdefViews = $this->buildSubdefsViews($records, $names, $urlTTL);

            $technicalDatasets = $this->app['service.technical_data']->fetchRecordsTechnicalData($records);

            foreach ($recordViews as $index => $recordView) {
                $recordView->setSubdefs($subdefViews[$index]);
                $recordView->setTechnicalDataView(new TechnicalDataView($technicalDatasets[$index]));
            }

            if (array_intersect($includes, ['results.records.metadata', 'results.records.caption'])) {
                $captions = $this->app['service.caption']->findByReferenceCollection($records);
                $canSeeBusiness = $this->retrieveSeeBusinessPerDatabox($records);

                $this->buildCaptionViews($recordViews, $captions, $canSeeBusiness);
            }

            $resultView->setRecords($recordViews);
        }

        return $resultView;
    }

    /**
     * @param Request $request
     * @return SearchEngineResult
     */
    private function doSearch(Request $request)
    {
        $options = SearchEngineOptions::fromRequest($this->app, $request);
        $options->setFirstResult((int)($request->get('offset_start') ?: 0));
        $options->setMaxResults((int)$request->get('per_page') ?: 10);

        $this->getSearchEngine()->resetCache();

        $search_result = $this->getSearchEngine()->query((string)$request->get('query'), $options);

        $this->getUserManipulator()->logQuery($this->getAuthenticatedUser(), $search_result->getQueryText());

        // log array of collectionIds (from $options) for each databox
        $collectionsReferencesByDatabox = $options->getCollectionsReferencesByDatabox();
        foreach ($collectionsReferencesByDatabox as $sbid => $references) {
            $databox = $this->findDataboxById($sbid);
            $collectionsIds = array_map(function(CollectionReference $ref){return $ref->getCollectionId();}, $references);
            $this->getSearchEngineLogger()->log($databox, $search_result->getQueryText(), $search_result->getTotal(), $collectionsIds);
        }

        $this->getSearchEngine()->clearCache();

        return $search_result;
    }

    /**
     * @return SearchEngineInterface
     */
    private function getSearchEngine()
    {
        return $this->app['phraseanet.SE'];
    }

    /**
     * @return UserManipulator
     */
    private function getUserManipulator()
    {
        return $this->app['manipulator.user'];
    }

    /**
     * @return SearchEngineLogger
     */
    private function getSearchEngineLogger()
    {
        return $this->app['phraseanet.SE.logger'];
    }

    /**
     * @param Request $request
     * @return int
     */
    private function resolveSubdefUrlTTL(Request $request)
    {
        $urlTTL = $request->query->get('subdef_url_ttl');

        if (null !== $urlTTL) {
            return (int)$urlTTL;
        }

        return $this->getConf()->get(['registry', 'general', 'default-subdef-url-ttl']);
    }

    /**
     * @param RecordCollection|\record_adapter[] $references
     * @return RecordView[]
     */
    private function buildRecordViews($references)
    {
        if (!$references instanceof RecordCollection) {
            $references = new RecordCollection($references);
        }

        $recordViews = [];

        foreach ($references as $index => $record) {
            $recordViews[$index] = new RecordView($record);
        }

        return $recordViews;
    }

    /**
     * @param RecordReferenceInterface[]|RecordReferenceCollection|DataboxGroupable $references
     * @param array|null $names
     * @param int $urlTTL
     * @return SubdefView[][]
     */
    private function buildSubdefsViews($references, array $names = null, $urlTTL)
    {
        $subdefGroups = $this->app['service.media_subdef']
            ->findSubdefsByRecordReferenceFromCollection($references, $names);

        $fakeSubdefs = [];

        foreach ($subdefGroups as $index => $subdefGroup) {
            if (!isset($subdefGroup['thumbnail'])) {
                $fakeSubdef = new \media_subdef($this->app, $references[$index], 'thumbnail', true, []);
                $fakeSubdefs[spl_object_hash($fakeSubdef)] = $fakeSubdef;

                $subdefGroups[$index]['thumbnail'] = $fakeSubdef;
            }
        }

        $allSubdefs = $this->mergeGroupsIntoOneList($subdefGroups);
        $allPermalinks = \media_Permalink_Adapter::getMany(
            $this->app,
            array_filter($allSubdefs, function (\media_subdef $subdef) use ($fakeSubdefs) {
                return !isset($fakeSubdefs[spl_object_hash($subdef)]);
            })
        );
        $urls = $this->app['media_accessor.subdef_url_generator']
            ->generateMany($this->getAuthenticatedUser(), $allSubdefs, $urlTTL);

        $subdefViews = [];

        /** @var \media_subdef $subdef */
        foreach ($allSubdefs as $index => $subdef) {
            $subdefView = new SubdefView($subdef);

            if (isset($allPermalinks[$index])) {
                $subdefView->setPermalinkView(new PermalinkView($allPermalinks[$index]));
            }

            $subdefView->setUrl($urls[$index]);
            $subdefView->setUrlTTL($urlTTL);

            $subdefViews[spl_object_hash($subdef)] = $subdefView;
        }

        $reorderedGroups = [];

        /** @var \media_subdef[] $subdefGroup */
        foreach ($subdefGroups as $index => $subdefGroup) {
            $reordered = [];

            foreach ($subdefGroup as $subdef) {
                $reordered[] = $subdefViews[spl_object_hash($subdef)];
            }

            $reorderedGroups[$index] = $reordered;
        }

        return $reorderedGroups;
    }

    /**
     * @param array $groups
     * @return array|mixed
     */
    private function mergeGroupsIntoOneList(array $groups)
    {
        // Strips keys from the internal array
        array_walk($groups, function (array &$group) {
            $group = array_values($group);
        });

        if ($groups) {
            return call_user_func_array('array_merge', $groups);
        }

        return [];
    }

    /**
     * @param RecordReferenceInterface[]|DataboxGroupable $references
     * @return array<int, bool>
     */
    private function retrieveSeeBusinessPerDatabox($references)
    {
        if (!$references instanceof DataboxGroupable) {
            $references = new RecordReferenceCollection($references);
        }

        $acl = $this->getAclForUser();

        $canSeeBusiness = [];

        foreach ($references->getDataboxIds() as $databoxId) {
            $canSeeBusiness[$databoxId] = $acl->can_see_business_fields($this->findDataboxById($databoxId));
        }

        $rights = [];

        foreach ($references as $index => $reference) {
            $rights[$index] = $canSeeBusiness[$reference->getDataboxId()];
        }

        return $rights;
    }

    /**
     * @param RecordView[] $recordViews
     * @param \caption_record[] $captions
     * @param bool[] $canSeeBusiness
     */
    private function buildCaptionViews($recordViews, $captions, $canSeeBusiness)
    {
        foreach ($recordViews as $index => $recordView) {
            $caption = $captions[$index];

            $captionView = new CaptionView($caption);

            $captionView->setFields($caption->get_fields(null, isset($canSeeBusiness[$index]) && (bool)$canSeeBusiness[$index]));

            $recordView->setCaption($captionView);
        }
    }
}
