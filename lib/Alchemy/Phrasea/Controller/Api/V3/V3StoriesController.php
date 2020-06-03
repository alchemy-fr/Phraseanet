<?php

namespace Alchemy\Phrasea\Controller\Api\V3;

use ACL;
use Alchemy\Phrasea\Application\Helper\DispatcherAware;
use Alchemy\Phrasea\Application\Helper\JsonBodyAware;
use Alchemy\Phrasea\Controller\Api\Result;
use Alchemy\Phrasea\Controller\Api\V1Controller;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Model\RecordReferenceInterface;
use Alchemy\Phrasea\Record\RecordReferenceCollection;
use caption_field;
use caption_record;
use databox_Field_DCESAbstract;
use Exception;
use media_Permalink_Adapter;
use media_subdef;
use record_adapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class V3StoriesController extends Controller
{
    use JsonBodyAware;
    use DispatcherAware;

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
        }
        catch (NotFoundHttpException $e) {
            return Result::createError($request, 404, 'Story Not Found')->createResponse();
        }
        catch (Exception $e) {
            return $this->app['controller.api.v1']->getBadRequestAction($request, 'An error occurred');
        }
    }

    /**
     * @return V3ResultHelpers
     */
    private function getResultHelpers()
    {
        static $rh = null;

        if(is_null($rh)) {
            $rh = new V3ResultHelpers(
                $this,
                $this->getConf(),
                $this->app['media_accessor.subdef_url_generator']
            );
        }
        return $rh;
    }

    /**
     * Retrieve detailed information about one story
     *
     * @param Request         $request
     * @param record_adapter $story
     * @return array
     * @throws Exception
     */
    private function listStory(Request $request, record_adapter $story)
    {
        if (!$story->isStory()) {
            return Result::createError($request, 404, 'Story not found')->createResponse();
        }

        $per_page = (int)$request->get('per_page')?:10;
        $page = (int)$request->get('page')?:1;
        $offset = ($per_page * ($page - 1)) + 1;

        $caption = $story->get_caption();

        $format = function (caption_record $caption, $dcField) {

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
                'dc:contributor' => $format($caption, databox_Field_DCESAbstract::Contributor),
                'dc:coverage'    => $format($caption, databox_Field_DCESAbstract::Coverage),
                'dc:creator'     => $format($caption, databox_Field_DCESAbstract::Creator),
                'dc:date'        => $format($caption, databox_Field_DCESAbstract::Date),
                'dc:description' => $format($caption, databox_Field_DCESAbstract::Description),
                'dc:format'      => $format($caption, databox_Field_DCESAbstract::Format),
                'dc:identifier'  => $format($caption, databox_Field_DCESAbstract::Identifier),
                'dc:language'    => $format($caption, databox_Field_DCESAbstract::Language),
                'dc:publisher'   => $format($caption, databox_Field_DCESAbstract::Publisher),
                'dc:relation'    => $format($caption, databox_Field_DCESAbstract::Relation),
                'dc:rights'      => $format($caption, databox_Field_DCESAbstract::Rights),
                'dc:source'      => $format($caption, databox_Field_DCESAbstract::Source),
                'dc:subject'     => $format($caption, databox_Field_DCESAbstract::Subject),
                'dc:title'       => $format($caption, databox_Field_DCESAbstract::Title),
                'dc:type'        => $format($caption, databox_Field_DCESAbstract::Type),
            ],
            'records'       => $this->listRecords($request, array_values($story->getChildren($offset, $per_page)->get_elements())),
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
    public function listEmbeddableMedia(Request $request, record_adapter $record, media_subdef $media)
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
                && !$acl->has_right_on_base($record->getBaseId(), ACL::CANDWNLDHD)
                && !$acl->has_hd_grant($record)
            ) {
                return null;
            }
        }

        if ($media->get_permalink() instanceof media_Permalink_Adapter) {
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

    private function listPermalink(media_Permalink_Adapter $permalink)
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
     * Retrieve detailed information about one record
     *
     * @param Request          $request
     * @param record_adapter $record
     * @return array
     */
    public function listRecord(Request $request, record_adapter $record)
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
                'status' => $this->getResultHelpers()->listRecordStatus($record),
                'caption' => $this->listRecordCaption($record),
            ]);
        }

        return $data;
    }

    /**
     * @param Request $request
     * @param record_adapter $record
     * @return array
     */
    private function listRecordEmbeddableMedias(Request $request, record_adapter $record)
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
     * @param record_adapter $record
     * @return array
     */
    private function listRecordMetadata(record_adapter $record)
    {
        $includeBusiness = $this->getAclForUser()->can_see_business_fields($record->getDatabox());

        return $this->listRecordCaptionFields($record->get_caption()->get_fields(null, $includeBusiness));
    }

    /**
     * @param caption_field[] $fields
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
     * @param record_adapter $record
     * @return array
     */
    private function listRecordCaption(record_adapter $record)
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

}
