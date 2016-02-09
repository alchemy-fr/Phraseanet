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

        if (null !== $previewLink = $preview->get_permalink()) {
            $permalinkUrl = $previewLink->get_url();
            $permaviewUrl = $previewLink->get_page();
            $previewWidth = $preview->get_width();
            $previewHeight = $preview->get_height();

            $embedUrl = $this->app->url('alchemy_embed_view', ['url' => (string)$permalinkUrl]);

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
