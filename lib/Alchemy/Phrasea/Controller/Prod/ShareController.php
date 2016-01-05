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
        $outputVars = [
            'isAvailable' => false,
            'preview' => [
              'permalinkUrl' => '',
              'permaviewUrl' => '',
              'embedUrl' => '',
              'width' => '',
              'height' => ''
            ]
        ];
        $record = new \record_adapter($this->app, \phrasea::sbasFromBas($this->app, $base_id), $record_id);

        if (!$this->getAclForUser()->has_access_to_subdef($record, 'preview')) {
            $this->app->abort(403);
        }

        $preview = $record->get_preview();

        if ($preview->get_permalink() !== null) {


            $subdefName = $preview->get_name();
            $subdef = $record->get_subdef($subdefName);

            switch ($record->getType()) {

                case 'flexpaper':
                case 'document':
                case 'audio':
                case 'video':
                default:
                    $token = $preview->get_permalink()->get_token();
                    $permalinkUrl = $preview->get_permalink()->get_url();
                    $permaviewUrl = $preview->get_permalink()->get_page();
                    $previewWidth = $preview->get_width();
                    $previewHeight = $preview->get_height();
                    break;
            }


            $sbas_id = $record->getDataboxId();
            $embedUrl = $this->app->url('alchemy_embed_view', [
                'sbas_id' => $sbas_id,
                'record_id' => $record_id,
                'subdefName' => $subdefName,
                'token' => $token,
            ]);

            $outputVars = [
                'isAvailable' => true,
                'preview' => [
                    'permalinkUrl' => $permalinkUrl,
                    'permaviewUrl' => $permaviewUrl,
                    'embedUrl' => $embedUrl,
                    'width' => $previewWidth,
                    'height' => $previewHeight
                ]
            ];
        }

        return $this->renderResponse('prod/Share/record.html.twig', $outputVars);
    }
}
