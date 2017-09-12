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

        $databox = $this->app->findDataboxById($record->getDataboxId());
        $subdefGroup = $databox->get_subdef_structure()->getSubdefGroup($record->getType());

        $subdefList = array();
        if ($subdefGroup) {
            foreach ($subdefGroup as $subdefObj) {
                $value = $subdefObj->get_class();
                if (!in_array($value, $subdefList))
                {
                    if (!$this->getAclForUser()->has_access_to_subdef($record, $value)) {
                        return;
                    }
                    $preview = $record->get_subdef($value);
                    if (null !== $previewLink = $preview->get_permalink()) {
                        $permalinkUrl = $previewLink->get_url()->__toString();
                        $permaviewUrl = $previewLink->get_page();
                        $previewWidth = $preview->get_width();
                        $previewHeight = $preview->get_height();

                        $embedUrl = $this->app->url('alchemy_embed_view', ['url' => (string)$permalinkUrl]);
                        $previewData = [
                            'permalinkUrl' => $permalinkUrl,
                            'permaviewUrl' => $permaviewUrl,
                            'embedUrl'     => $embedUrl,
                            'width'        => $previewWidth,
                            'height'       => $previewHeight
                        ];
                        $subdefList[$value] = $previewData;
                    }
                }
            }

            //set default key
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
        }


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
