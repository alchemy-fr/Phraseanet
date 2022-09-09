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

use Alchemy\Phrasea\Application\Helper\EntityManagerAware;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Model\Entities\Basket;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ShareController extends Controller
{
    use EntityManagerAware;

    /**
     *  Share a record
     *
     * @param  integer     $base_id
     * @param  integer     $record_id
     * @return Response
     */
    public function shareRecord($base_id, $record_id)
    {
        $record = new \record_adapter($this->app, \phrasea::sbasFromBas($this->app, $base_id), $record_id);

        //get list of subdefs
        $subdefs = $record->get_subdefs();

        $databoxSubdefs = $record->getDatabox()->get_subdef_structure()->getSubdefGroup($record->getType());
        $acl = $this->getAclForUser();
        $subdefList = [];
        $defaultKey = null;
        foreach ($subdefs as $subdef) {
            $subdefName = $subdef->get_name();
            if ($subdefName == 'document') {
                if (!$acl->has_right_on_base($record->getBaseId(), \ACL::CANDWNLDHD)) {
                    continue;
                }
                $label = $this->app->trans('prod::tools: document');
            }
            elseif ($databoxSubdefs->hasSubdef($subdefName)) {
                if (!$acl->has_access_to_subdef($record, $subdefName)) {
                    continue;
                }
                $label = $databoxSubdefs->getSubdef($subdefName)->get_label($this->app['locale']);
            }
            else {
                // this subdef does no exists anymore in databox structure ?
                continue;   // don't publish it
            }
            $value = $subdef->get_name();
            $preview = $record->get_subdef($value);
            $defaultKey = $value;   // will set a default option if neither preview,thumbnail or document is present


            if (($previewLink = $preview->get_permalink()) !== null) {
                $permalinkUrl = $previewLink->get_url()->__toString();
                $permaviewUrl = $previewLink->get_page();
                $previewWidth = $preview->get_width();
                $previewHeight = $preview->get_height();
                $embedUrl = $this->app->url('alchemy_embed_view', ['url' => (string)$permalinkUrl]);
                $previewData = [
                    'label'        => $label,
                    'permalinkUrl' => $permalinkUrl,
                    'permaviewUrl' => $permaviewUrl,
                    'embedUrl'     => $embedUrl,
                    'width'        => $previewWidth,
                    'height'       => $previewHeight
                ];
                $subdefList[$value] = $previewData;
            }
        }

        // candidates as best default selected option
        foreach (["preview", "thumbnail", "document"] as $k) {
            if (array_key_exists($k, $subdefList)) {
                $defaultKey = $k;
                break;
            }
        }
        // if no subdef was sharable, subdefList is empty and defaultKey is null
        // the twig MUST handle that
        $outputVars = [
            'isAvailable' => !empty($subdefList),
            'subdefList'  => $subdefList,
            'defaultKey'  => $defaultKey
        ];

        return $this->renderResponse('prod/Share/record.html.twig', $outputVars);
    }

    public function quitshareAction(Request $request, Basket $basket)
    {
        $ret = [
            'success' => false,
            'message' => ""
        ];

        $user = $this->getAuthenticatedUser();
        if( !is_null($participant = $basket->getParticipant($user))) {
            $manager = $this->getEntityManager();
            $manager->beginTransaction();

            $basket->removeParticipant($participant);
            $manager->remove($participant);
            $manager->persist($basket);
            $manager->flush();

            $manager->commit();
            $ret['success'] = true;
        }

        return $this->app->json($ret);
    }
}
