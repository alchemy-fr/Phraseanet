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
use Symfony\Component\Routing\Generator\UrlGenerator;


class V3StoriesController extends V3RecordController
{
    use JsonBodyAware;
    use DispatcherAware;

    /**
     * Return children of a story
     *
     * @param  Request $request
     * @param  int     $databox_id
     * @param  int     $record_id
     *
     * @return Response
     */
    public function childrenAction_GET(Request $request, $databox_id, $record_id)
    {
        try {
            $story = $this->findDataboxById($databox_id)->get_record($record_id);
            if (!$story->isStory()) {
                throw new NotFoundHttpException();
            }

            list($offset, $limit) = V3ResultHelpers::paginationFromRequest($request);

            $ret = $this->listRecords($request, array_values($story->getChildren($offset, $limit)->get_elements()));

            return Result::create($request, $ret)->createResponse();
        }
        catch (NotFoundHttpException $e) {
            return Result::createError($request, 404, 'Story Not Found')->createResponse();
        }
        catch (Exception $e) {
            return $this->app['controller.api.v1']->getBadRequestAction($request, 'An error occurred');
        }
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

        $data = [];
        foreach ($records->toRecords($this->getApplicationBox()) as $index => $record) {
            // $data[$index] = $this->getResultHelpers()->listRecord($request, $record, $this->getAclForUser());
            $data[$index] = $this->getUrlGenerator()->generate(
                'api.v3.records:indexAction_GET',
                [
                    'databox_id' => $record->getDataboxId(),
                    'record_id' => $record->getRecordId(),
                    //'oauth_token' => $request->get('oauth_token')
                ]
            );
        }

        return $data;
    }


    /**
     * @return UrlGenerator
     */
    private function getUrlGenerator()
    {
        return $this->app['url_generator'];
    }

}
