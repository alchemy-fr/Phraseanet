<?php

namespace Alchemy\Phrasea\Controller\Api\V3;

use Alchemy\Phrasea\Application\Helper\DispatcherAware;
use Alchemy\Phrasea\Application\Helper\JsonBodyAware;
use Alchemy\Phrasea\Controller\Api\Result;
use Alchemy\Phrasea\Controller\Api\V1Controller;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Model\RecordReferenceInterface;
use Alchemy\Phrasea\Record\RecordReferenceCollection;
use caption_record;
use databox_Field_DCESAbstract;
use Exception;
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
            'thumbnail'     => $this->getResultHelpers()->listEmbeddableMedia($request, $story, $story->get_thumbnail(), $this->getAclForUser()),
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

            $data[$index] = $this->getResultHelpers()->listRecord($request, $record, $this->getAclForUser());
        }

        return $data;
    }


    /**
     * @return V3ResultHelpers
     */
    private function getResultHelpers()
    {
        return $this->app['controller.api.v3.resulthelpers'];
    }

}
