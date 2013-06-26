<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Response;

use Alchemy\Phrasea\Response\DeliverDataInterface;
use Alchemy\Phrasea\Application;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ServeFileResponseFactory implements DeliverDataInterface
{
    private $xSendFileEnable = false;
    private $unicode;

    public function __construct($enableXSendFile, \unicode $unicode)
    {
        $this->xSendFileEnable = $enableXSendFile;
        $this->unicode = $unicode;
    }

    /**
     * @param Application $app
     * @return self
     */
    public static function create(Application $app)
    {
        return new self(
            $app['phraseanet.configuration']['xsendfile']['enable'],
            $app['unicode']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function deliverFile($file, $filename = '', $disposition = self::DISPOSITION_INLINE, $mimeType = null ,$cacheDuration = 3600)
    {
        $response = new BinaryFileResponse($file);
        $response->setContentDisposition($disposition, $this->sanitizeFilename($filename), $this->sanitizeFilenameFallback($filename));

        if ($this->isXSendFileEnable()) {
            BinaryFileResponse::trustXSendfileTypeHeader();
        }

        if (null !== $mimeType) {
             $response->headers->set('Content-Type', $mimeType);
        }

        $response->setMaxAge($cacheDuration);

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function deliverData($data, $filename, $mimeType, $disposition = self::DISPOSITION_INLINE, $cacheDuration = 3600)
    {
        $response = new Response($data);

        $dispositionHeader = $response->headers->makeDisposition($disposition, $this->sanitizeFilename($filename), $this->sanitizeFilenameFallback($filename));
        $response->headers->set('Content-Disposition', $dispositionHeader);

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
