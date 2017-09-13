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
use Symfony\Component\HttpFoundation\Response;

class ShareController extends Controller
{
    /**
     *  Share a record
     *
     * @param  integer     $base_id
     * @param  integer     $record_id
     * @return Response
     */
    public function shareRecord($base_id, $record_id)
    {
//        $outputVars = [
//            'isAvailable' => false,
//            'subdefList' => [],
//            'defaultKey' => 'preview',
//            'preview' => [
//              'permalinkUrl' => '',
//              'permaviewUrl' => '',
//              'embedUrl' => '',
//              'width' => '',
//              'height' => ''
//            ]
//        ];

        $outputVars = [
            'isAvailable' => false,
            'subdefList' => [],
            'defaultKey' => 'preview',
        ];

        $record = new \record_adapter($this->app, \phrasea::sbasFromBas($this->app, $base_id), $record_id);

        //get list of subdefs
        $subdefs = $record->get_subdefs();

        $databoxSubdefs = $record->getDatabox()->get_subdef_structure()->getSubdefGroup($record->getType());

        $acl = $this->getAclForUser();

        $subdefList = array();

        foreach ($subdefs as $subdef) {
            $label = $subdefName = $subdef->get_name();

            if ('document' == $subdefName) {
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

            $value = $subdef->get_name();
            $preview = $record->get_subdef($value);

            if (null !== $previewLink = $preview->get_permalink()) {
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

        //set default key to preview or thumbnail
        if (array_key_exists("preview",$subdefList)) {
            $defaultKey = 'preview';
        }else if(array_key_exists("thumbnail",$subdefList)) {
            $defaultKey = 'thumbnail';
        }

        $outputVars = [
            'isAvailable' => true,
            'subdefList' => $subdefList,
            'defaultKey' => $defaultKey
        ];

//        if (!$this->getAclForUser()->has_access_to_subdef($record, 'thumbnail')) {
//            $this->app->abort(403);
//        }

//        $preview = $record->get_preview();
//
//        if (null !== $previewLink = $preview->get_permalink()) {
//            $permalinkUrl = $previewLink->get_url();
//            $permaviewUrl = $previewLink->get_page();
//            $previewWidth = $preview->get_width();
//            $previewHeight = $preview->get_height();
//
//            $embedUrl = $this->app->url('alchemy_embed_view', ['url' => (string)$permalinkUrl]);
//
//            $outputVars = [
//                'isAvailable' => true,
//                'subdefList' => $subdefList,
//                'preview' => [
//                    'permalinkUrl' => $permalinkUrl,
//                    'permaviewUrl' => $permaviewUrl,
//                    'embedUrl' => $embedUrl,
//                    'width' => $previewWidth,
//                    'height' => $previewHeight
//                ]
//            ];
//        }

        return $this->renderResponse('prod/Share/record.html.twig', $outputVars);
    }
}
