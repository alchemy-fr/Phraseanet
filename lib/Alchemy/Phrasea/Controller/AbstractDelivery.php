<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Application\Helper\DataboxLoggerAware;
use Alchemy\Phrasea\Application\Helper\DelivererAware;
use Alchemy\Phrasea\Http\DeliverDataInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractDelivery
{
    use DataboxLoggerAware;
    use DelivererAware;

    /** @var Application */
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function deliverContent(Request $request, \record_adapter $record, $subdef, $watermark, $stamp)
    {
        $file = $record->get_subdef($subdef);
        $pathOut = $file->get_pathfile();

        if ($watermark === true && $file->get_type() === \media_subdef::TYPE_IMAGE) {
            $pathOut = \recordutils_image::watermark($this->app, $file);
        } elseif ($stamp === true && $file->get_type() === \media_subdef::TYPE_IMAGE) {
            $pathOut = \recordutils_image::stamp($this->app, $file);
        }

        $disposition = $request->query->get('download') ? DeliverDataInterface::DISPOSITION_ATTACHMENT : DeliverDataInterface::DISPOSITION_INLINE;

        /** @var Response $response */
        $response = $this->deliverFile($pathOut, $file->get_file(), $disposition, $file->get_mime());

        if (in_array($subdef, array('document', 'preview'))) {
            $response->setPrivate();
            $this->logView($record, $request);
        } elseif ($subdef !== 'thumbnail') {
            try {
                if ($file->getDataboxSubdef()->get_class() != \databox_subdef::CLASS_THUMBNAIL) {
                    $response->setPrivate();
                    $this->logView($record, $request);
                }
            } catch (\Exception $e) {
                // Ignore exception
            }
        }

        $response->isNotModified($request);

        return $response;
    }

    private function logView(\record_adapter $record, Request $request)
    {
        try {
            $logger = $this->getDataboxLogger($record->get_databox());
            $log_id = $logger->get_id();
            $record->log_view(
                $log_id,
                $request->headers->get('referer', 'NO REFERRER'),
                $this->app['phraseanet.configuration']['main']['key']
            )
            ;
        } catch (\Exception $e) {
            // Ignore exception
        }
    }
}
