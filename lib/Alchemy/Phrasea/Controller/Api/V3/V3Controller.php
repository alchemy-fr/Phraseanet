<?php

namespace Alchemy\Phrasea\Controller\Api\V3;

use Alchemy\Phrasea\Controller\Api\Result;
use Alchemy\Phrasea\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class V3Controller extends Controller
{
    public function getDataboxSubdefsAction(Request $request)
    {
        if (!empty($request->attributes->get('databox_id'))) {
            $ret = [
                'databoxes' => $this->listSubdefsStructure([$this->findDataboxById($request->attributes->get('databox_id'))])
            ];
        } else {
            $acl = $this->getAclForUser($this->getAuthenticatedUser());

            // ensure can see databox structure
            $ret = [
                'databoxes' => $this->listSubdefsStructure($acl->get_granted_sbas([\ACL::BAS_MODIFY_STRUCT]))
            ];
        }

        return Result::create($request, $ret)->createResponse();
    }

    /**
     * List the subdef structure of databoxes
     * @param array $databoxes
     * @return array
     * @throws \Exception
     */
    private function listSubdefsStructure(array $databoxes)
    {
        $ret = [];

        /** @var \databox $databox */
        foreach ($databoxes as $databox) {
            $databoxId = $databox->get_sbas_id();
            $subdefStructure = $databox->get_subdef_structure();
            $subdefs = [];
            foreach ($subdefStructure as $subGroup) {
                /** @var \databox_subdef $sub */
                foreach ($subGroup->getIterator() as $sub) {
                    $opt = [];
                    $data = [
                        'name'             => $sub->get_name(),
                        'databox_id'       => $databoxId,
                        'class'            => $sub->get_class(),
                        'preset'           => $sub->get_preset(),
                        'downloadable'     => $sub->isDownloadable(),
                        'tobuild'          => $sub->isTobuild(),
                        'devices'          => $sub->getDevices(),
                        'labels'           => [
                            'fr' => $sub->get_label('fr'),
                            'en' => $sub->get_label('en'),
                            'de' => $sub->get_label('de'),
                            'nl' => $sub->get_label('nl'),
                        ],
                    ];
                    $options = $sub->getOptions();
                    foreach ($options as $option) {
                        $opt[$option->getName()] = $option->getValue();
                    }
                    $data['options'] = $opt;
                    $subdefs[$subGroup->getName()][$sub->get_name()] = $data;
                }
            }
            $ret[$databoxId]['databox_id']  = $databoxId;
            $ret[$databoxId]['subdefs']     = $subdefs;
        }

        return $ret;
    }
}
