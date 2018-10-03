<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Http;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ServeFileResponseFactory implements DeliverDataInterface
{
    private $unicode;

    public function __construct(\unicode $unicode)
    {
        $this->unicode = $unicode;
    }

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

    private function sanitizeFilename($filename)
    {
        return str_replace(['/', '\\'], '', $filename);
    }

    private function sanitizeFilenameFallback($filename)
    {
        return $this->unicode->remove_nonazAZ09($filename, true, true, true);
    }
}
