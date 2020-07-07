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
            if (!$story->isStory()) {
                throw new NotFoundHttpException();
            }

            return Result::create($request, $this->listStory($request, $story))->createResponse();
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

        $per_page = (int)$request->get('per_page')?:10;
        $page = (int)$request->get('page')?:1;
        $offset = ($per_page * ($page - 1)) + 1;

        $ret = $this->getResultHelpers()->listRecord($request, $story, $this->getAclForUser());
        $ret['records'] = $this->listRecords($request, array_values($story->getChildren($offset, $per_page)->get_elements()));

        return $ret;
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
     * @return V3ResultHelpers
     */
    private function getResultHelpers()
    {
        return $this->app['controller.api.v3.resulthelpers'];
    }

    /**
     * @return UrlGenerator
     */
    private function getUrlGenerator()
    {
        return $this->app['url_generator'];
    }

}
