<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Http\DeliverDataInterface;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
abstract class AbstractDelivery implements ControllerProviderInterface
{
    public function deliverContent(Request $request, \record_adapter $record, $subdef, $watermark, $stamp, Application $app)
    {
        $file = $record->get_subdef($subdef);
        $pathOut = $file->get_pathfile();

        if ($watermark === true && $file->get_type() === \media_subdef::TYPE_IMAGE) {
            $pathOut = \recordutils_image::watermark($app, $file);
        } elseif ($stamp === true && $file->get_type() === \media_subdef::TYPE_IMAGE) {
            $pathOut = \recordutils_image::stamp($app, $file);
        }

        $disposition = $request->query->get('download') ? DeliverDataInterface::DISPOSITION_ATTACHMENT : DeliverDataInterface::DISPOSITION_INLINE;

        $response = $app['phraseanet.file-serve']->deliverFile($pathOut, $file->get_file(), $disposition, $file->get_mime());

        if (in_array($subdef, array('document', 'preview'))) {
            $response->setPrivate();
            $this->logView($app, $record, $request);
        } elseif ($subdef !== 'thumbnail') {
            try {
                if ($file->getDataboxSubdef()->get_class() != \databox_subdef::CLASS_THUMBNAIL) {
                    $response->setPrivate();
                    $this->logView($app, $record, $request);
                }
            } catch (\Exception $e) {

            }
        }

        $response->isNotModified($request);

        return $response;
    }

    private function logView(Application $app, \record_adapter $record, Request $request)
    {
        try {
            $logger = $app['phraseanet.logger']($record->get_databox());
            $log_id = $logger->get_id();
            $record->log_view($log_id, $request->headers->get('referer', 'NO REFERRER'), $app['phraseanet.configuration']['main']['key']);
        } catch (\Exception $e) {

        }
    }
}
