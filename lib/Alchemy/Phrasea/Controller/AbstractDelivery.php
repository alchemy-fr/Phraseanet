<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller;

use Alchemy\Phrasea\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

            $record->log_view($log_id, $referrer, $app['phraseanet.registry']->get('GV_sit'));
        } catch (\Exception $e) {

        }

        $response = \set_export::stream_file($app['phraseanet.registry'], $pathOut, $file->get_file(), $file->get_mime(), 'inline');
        $response->setPrivate();

        /* @var $response \Symfony\Component\HttpFoundation\Response */
        if ($file->getEtag()) {
            $response->setEtag($file->getEtag());
            $response->setLastModified($file->get_modification_date());
        }

        $response->headers->addCacheControlDirective('must-revalidate', true);
        $response->isNotModified($request);

        return $response;
    }
}
