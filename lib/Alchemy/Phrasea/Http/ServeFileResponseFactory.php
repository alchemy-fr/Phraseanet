<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Http;

use Alchemy\Phrasea\Application;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ServeFileResponseFactory implements DeliverDataInterface
{
    private $unicode;

    public function __construct(\unicode $unicode)
    {
        $this->unicode = $unicode;
    }

    /**
     * @param  Application $app
     * @return self
     */
    public static function create(Application $app)
    {
        return new self(
            $app['unicode']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function deliverFile($file, $filename = '', $disposition = self::DISPOSITION_INLINE, $mimeType = null, $cacheDuration = null)
    {
        $response = new BinaryFileResponse($file);
        $response->setContentDisposition($disposition, $this->sanitizeFilename($filename), $this->sanitizeFilenameFallback($filename));
        if (null !== $cacheDuration) {
            $response->setMaxAge($cacheDuration);
        }

        if (null !== $mimeType) {
             $response->headers->set('Content-Type', $mimeType);
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function deliverData($data, $filename, $mimeType, $disposition = self::DISPOSITION_INLINE, $cacheDuration = null)
    {
        $response = new Response($data);
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            $disposition,
            $this->sanitizeFilename($filename),
            $this->sanitizeFilenameFallback($filename
        )));
        $response->headers->set('Content-Type', $mimeType);
        if (null !== $cacheDuration) {
            $response->setMaxAge($cacheDuration);
        }

        return $response;
    }

    private function sanitizeFilename($filename)
    {
        return str_replace(array('/', '\\'), '', $filename);
    }

    private function sanitizeFilenameFallback($filename)
    {
        return $this->unicode->remove_nonazAZ09($filename, true, true, true);
    }
}
