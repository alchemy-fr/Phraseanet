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
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ServeFileResponseFactory implements DeliverDataInterface
{
    public static $X_SEND_FILE = false;

    private $rootPath;
    private $xAccelRedirectPath = '';
    private $xAccelRedirectMountPoint = '';

    public function __construct($rootPath)
    {
        $this->rootPath = $rootPath;
    }

    /**
     * @param Application $app
     * @return self
     */
    public static function create(Application $app)
    {
        $factory = new self($app['root.path']);

        ServeFileResponseFactory::$X_SEND_FILE = $app['phraseanet.registry']->get('GV_modxsendfile');

        $factory->setXAccelRedirectPath($app['phraseanet.registry']->get('GV_X_Accel_Redirect'));
        $factory->setXAccelRedirectMountPoint($app['phraseanet.registry']->get('GV_X_Accel_Redirect_mount_point'));

        return $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function deliverFile($file, $filename = '', $disposition = ResponseHeaderBag::DISPOSITION_INLINE)
    {
        $response = new BinaryFileResponse($file);
        $response->setContentDisposition($disposition, $this->sanitizeFilename($filename), $this->sanitizeFilenameFallback($filename));

        if (self::$X_SEND_FILE) {
            $response->headers->set('X-Accel-Redirect', $this->xAccelRedirectMapping($file));
        }

        $response->headers->set('Pragma', 'public', true);

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function deliverData($data, $filename, $mimeType, $disposition = ResponseHeaderBag::DISPOSITION_INLINE)
    {
        $response = new Response($data);

        $dispositionHeader = $response->headers->makeDisposition($disposition, $this->sanitizeFilename($filename), $this->sanitizeFilenameFallback($filename));
        $response->headers->set('Content-Disposition', $dispositionHeader);

        $response->headers->set('pragma', 'no-cache');
        $response->headers->set('Content-Type', $mimeType);

        $response->setMaxAge(3600);

        $d = new \DateTime();
        $d->format(\DateTime::RFC822);

        $response->setExpires(new \DateTime('1997-07-26'));
        $response->setLastModified(new \DateTime());
        $response->setPublic();

        return $response;
    }

    public function getXAccelRedirectPath()
    {
        return $this->xAccelRedirectPath;
    }

    public function setXAccelRedirectPath($xAccelRedirectPath)
    {
        $xAccelRedirectPath .= substr($xAccelRedirectPath, -1) === '/' ? '' : '/';
        $this->xAccelRedirectPath = $xAccelRedirectPath;

        return $this;
    }

    public function getXAccelRedirectMountPoint()
    {
        return $this->xAccelRedirectMountPoint;
    }

    public function setXAccelRedirectMountPoint($xAccelRedirectMountPoint)
    {
        $xAccelRedirectMountPoint = (substr($xAccelRedirectMountPoint, 0, 1) === '/' ? '' : '/') . $xAccelRedirectMountPoint;
        $xAccelRedirectMountPoint .= substr($xAccelRedirectMountPoint, -1) === '/' ? '' : '/';

        $this->xAccelRedirectMountPoint = $xAccelRedirectMountPoint;

        return $this;
    }

    private function sanitizeFilename($filename)
    {
        return str_replace(array('/', '\\'), '', $filename);
    }

    private function sanitizeFilenameFallback($filename)
    {
        $unicode = new \unicode();
        return $unicode->remove_nonazAZ09($filename, true, true, true);
    }

    private function xAccelRedirectMapping($file)
    {
        return str_replace(
            array(
                $this->xAccelRedirectPath,
                $this->rootPath . '/tmp/download/',
                $this->rootPath . '/tmp/lazaret/'
            ), array(
                $this->xAccelRedirectMountPoint,
                '/download/',
                '/lazaret/'
            ),
            $file
        );
    }
}
