<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Http;

use Alchemy\Phrasea\Http\DeliverDataInterface;
use Alchemy\Phrasea\Application;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ServeFileResponseFactory implements DeliverDataInterface
{
    private $xSendFileEnable = false;
    private $unicode;

    public function __construct($enableXSendFile, \unicode $unicode)
    {
        $this->xSendFileEnable = (Boolean) $enableXSendFile;
        $this->unicode = $unicode;

        if ($this->xSendFileEnable) {
            BinaryFileResponse::trustXSendfileTypeHeader();
        }
    }

    /**
     * @param  Application $app
     * @return self
     */
    public static function create(Application $app)
    {
        return new self(
            $app['phraseanet.configuration']['xsendfile']['enabled'],
            $app['unicode']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function deliverFile($file, $filename = '', $disposition = self::DISPOSITION_INLINE, $mimeType = null ,$cacheDuration = 0)
    {
        $response = new BinaryFileResponse($file);
        $response->setContentDisposition($disposition, $this->sanitizeFilename($filename), $this->sanitizeFilenameFallback($filename));
        $response->setMaxAge($cacheDuration);
        $response->setPrivate();

        if (null !== $mimeType) {
             $response->headers->set('Content-Type', $mimeType);
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function deliverData($data, $filename, $mimeType, $disposition = self::DISPOSITION_INLINE, $cacheDuration = 0)
    {
        $response = new Response($data);
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            $disposition,
            $this->sanitizeFilename($filename),
            $this->sanitizeFilenameFallback($filename
        )));
        $response->headers->set('Content-Type', $mimeType);
        $response->setMaxAge($cacheDuration);

        return $response;
    }

    public function isXSendFileEnable()
    {
        return $this->xSendFileEnable;
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
