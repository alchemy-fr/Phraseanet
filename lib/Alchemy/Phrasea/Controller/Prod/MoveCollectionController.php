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

use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Controller\RecordsRequest;
use Symfony\Component\HttpFoundation\Request;

class MoveCollectionController extends Controller
{
    public function displayForm(Request $request)
    {
        $records = RecordsRequest::fromRequest($this->app, $request, false, [\ACL::CANDELETERECORD]);

        $sbas_ids = array_map(function (\databox $databox) {
            return $databox->get_sbas_id();
        }, $records->databoxes());

        $collections = $this->getAclForUser()->get_granted_base([\ACL::CANADDRECORD], $sbas_ids);

        $parameters = [
            'records'     => $records,
            'message'     => '',
            'collections' => $collections,
        ];

        return $this->render('prod/actions/collection_default.html.twig', $parameters);
    }

    public function apply(Request $request)
    {
        /** @var \record_adapter[] $records */
        $records = RecordsRequest::fromRequest($this->app, $request, false, [\ACL::CANDELETERECORD]);

        $datas = [
            'success' => false,
            'message' => '',
        ];

        try {
            if (null === $request->request->get('base_id')) {
                $datas['message'] = $this->app->trans('Missing target collection');

                return $this->app->json($datas);
            }

            if (!$this->getAclForUser()->has_right_on_base($request->request->get('base_id'), \ACL::CANADDRECORD)) {
                $datas['message'] = $this->app->trans("You do not have the permission to move records to %collection%", ['%collection%', \phrasea::bas_labels($request->request->get('base_id'), $this->app)]);

                return $this->app->json($datas);
            }

            try {
                $collection = \collection::getByBaseId($this->app, $request->request->get('base_id'));
            } catch (\Exception_Databox_CollectionNotFound $e) {
                $datas['message'] = $this->app->trans('Invalid target collection');

                return $this->app->json($datas);
            }

            foreach ($records as $record) {
                $record->move_to_collection($collection, $this->getApplicationBox());

                if ($request->request->get("chg_coll_son") == "1") {
                    /** @var \record_adapter $child */
                    foreach ($record->getChildren() as $child) {
                        if ($this->getAclForUser()->has_right_on_base($child->getBaseId(), \ACL::CANDELETERECORD)) {
                            $child->move_to_collection($collection, $this->getApplicationBox());
                        }
                    }
                }
            }

            $ret = [
                'success' => true,
                'message' => $this->app->trans('Records have been successfuly moved'),
            ];
        } catch (\Exception $e) {
            $ret = [
                'success' => false,
                'message' => $this->app->trans('An error occured'),
            ];
        }

        return $this->app->json($ret);
    }
}
