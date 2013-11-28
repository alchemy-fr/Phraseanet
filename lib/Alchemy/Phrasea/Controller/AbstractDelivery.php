<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
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

        $pathIn = $pathOut = $file->get_pathfile();

        if ($watermark === true && $file->get_type() === \media_subdef::TYPE_IMAGE) {
            $pathOut = \recordutils_image::watermark($app, $file);
        } elseif ($stamp === true && $file->get_type() === \media_subdef::TYPE_IMAGE) {
            $pathOut = \recordutils_image::stamp($app, $file);
        }

        $log_id = null;
        try {
            $logger = $app['phraseanet.logger']($record->get_databox());
            $log_id = $logger->get_id();

            $referrer = 'NO REFERRER';

            if (isset($_SERVER['HTTP_REFERER'])) {
                $referrer = $_SERVER['HTTP_REFERER'];
            }

            $record->log_view($log_id, $referrer, $app['phraseanet.configuration']['main']['key']);
        } catch (\Exception $e) {

        }

        $disposition = $request->query->get('download') ? DeliverDataInterface::DISPOSITION_ATTACHMENT : DeliverDataInterface::DISPOSITION_INLINE;

        $response = $app['phraseanet.file-serve']->deliverFile($pathOut, $file->get_file(), $disposition, $file->get_mime());
        $response->setPrivate();

        /* @var $response \Symfony\Component\HttpFoundation\Response */
        if ($file->getEtag()) {
            $response->setEtag($file->getEtag());
            $response->setLastModified($file->get_modification_date());
        }

        if (false === $record->is_grouping() && $subdef !== 'document') {
            try {
                if ($file->getDataboxSubdef()->get_class() == \databox_subdef::CLASS_THUMBNAIL) {
                    // default expiration is 5 days
                    $expiration = 60 * 60 * 24 * 5;
                    $response->setExpires(new \DateTime(sprintf('+%d seconds', $expiration)));

                    $response->setMaxAge($expiration);
                    $response->setSharedMaxAge($expiration);
                    $response->setPublic();
                }
            } catch (\Exception $e) {

            }
        }

        $response->isNotModified($request);

        return $response;
    }
}
