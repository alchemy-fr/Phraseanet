<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
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
        $mediaSubdefinition = $record->get_subdef($subdef);

        $filename = $request->get("filename") ?: $mediaSubdefinition->get_file();

        $pathOut = $this->tamperProofSubDefinition($mediaSubdefinition, $watermark, $stamp);

        $disposition = $request->query->get('download') ? DeliverDataInterface::DISPOSITION_ATTACHMENT : DeliverDataInterface::DISPOSITION_INLINE;

        // nb: $filename will be sanitized, no need to do it here
        $response = $this->deliverFile($pathOut,  $filename, $disposition, $mediaSubdefinition->get_mime());

        if (in_array($subdef, array('document', 'preview'))) {
            $response->setPrivate();
            $this->logView($record, $request);
        } elseif ($subdef !== 'thumbnail') {
            try {
                if ($mediaSubdefinition->getDataboxSubdef()->get_class() != \databox_subdef::CLASS_THUMBNAIL) {
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
            $logger = $this->getDataboxLogger($record->getDatabox());
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

    /**
     * @param \media_subdef $mediaSubdefinition
     * @param bool $watermark
     * @param bool $stamp
     * @return string
     */
    private function tamperProofSubDefinition(\media_subdef $mediaSubdefinition, $watermark, $stamp)
    {
        $pathOut = $mediaSubdefinition->getRealPath();

        if ($watermark === true && $mediaSubdefinition->get_type() === \media_subdef::TYPE_IMAGE) {
            $pathOut = \recordutils_image::watermark($this->app, $mediaSubdefinition);
        } elseif ($stamp === true && $mediaSubdefinition->get_type() === \media_subdef::TYPE_IMAGE) {
            if( !is_null($newPath = \recordutils_image::stamp($this->app, $mediaSubdefinition)) ) {
                $pathOut = $newPath;
            }
        }

        return $pathOut;
    }
}
