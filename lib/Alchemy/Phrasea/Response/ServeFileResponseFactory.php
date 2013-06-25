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
    private $mappings;
    private $unicode;

    public function __construct($enableXSendFile, $xAccelMappings, \unicode $unicode)
    {
        $this->xSendFileEnable = $enableXSendFile;

        $mappings = array();

        foreach ($xAccelMappings as $path => $mountPoint) {
            if (is_dir($path) && '' !== $mountPoint) {
                $mappings[$this->sanitizeXAccelPath($path)] = $this->sanitizeXAccelMountPoint($mountPoint);
            }
        }

        $this->mappings = $mappings;
        $this->unicode = $unicode;
    }

    /**
     * @param Application $app
     * @return self
     */
    public static function create(Application $app)
    {
        return new self(
            $app['phraseanet.registry']->get('GV_modxsendfile'),
            array(
                $app['phraseanet.registry']->get('GV_X_Accel_Redirect') => $app['phraseanet.registry']->get('GV_X_Accel_Redirect_mount_point'),
                $app['root.path'] . '/tmp/download/'                    => '/download/',
                $app['root.path'] . '/tmp/lazaret/'                     => '/lazaret/'
        ), new \unicode());
    }

    /**
     * {@inheritdoc}
     */
    public function deliverFile($file, $filename = '', $disposition = self::DISPOSITION_INLINE, $mimeType = null ,$cacheDuration = 3600)
    {
        $response = new BinaryFileResponse($file);
        $response->setContentDisposition($disposition, $this->sanitizeFilename($filename), $this->sanitizeFilenameFallback($filename));

        if ($this->isXSendFileEnable() && $this->isMapped($file)) {
            $response->headers->set('X-Accel-Redirect', $this->xAccelRedirectMapping($file));
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

    private function sanitizeXAccelPath($path)
    {
        return sprintf('%s/', rtrim($path, '/'));
    }

    private function sanitizeXAccelMountPoint($mountPoint)
    {
        return sprintf('/%s/', rtrim(ltrim($mountPoint, '/'), '/'));
    }

    private function sanitizeFilename($filename)
    {
        return str_replace(array('/', '\\'), '', $filename);
    }

    private function sanitizeFilenameFallback($filename)
    {
        return $this->unicode->remove_nonazAZ09($filename, true, true, true);
    }

    private function xAccelRedirectMapping($file)
    {
        return str_replace(array_keys($this->mappings), array_values($this->mappings), $file);
    }

    private function isMapped($file)
    {
        foreach (array_keys($this->mappings) as $path) {
            if (false !== strpos($file, $path)) {
                 return true;
            }
        }

        return false;
    }
}
