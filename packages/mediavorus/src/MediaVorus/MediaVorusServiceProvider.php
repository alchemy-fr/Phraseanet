<?php

/*
 * This file is part of MediaVorus.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MediaVorus;

use FFMpeg\Exception\ExecutableNotFoundException;
use MediaVorus\Exception\RuntimeException;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class MediaVorusServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Container $app)
    {
        $app['mediavorus'] = function (Container $app) {
            if (!isset($app['exiftool.reader']) || !isset($app['exiftool.writer'])) {
                throw new RuntimeException('MediaVorus Service Provider requires Exiftool Service Provider');
            }

            $ffprobe = null;
            if (isset($app['ffmpeg.ffprobe'])) {
                try {
                    $ffprobe = $app['ffmpeg.ffprobe'];
                } catch (ExecutableNotFoundException $e) {

                }
            }

            return new MediaVorus($app['exiftool.reader'], $app['exiftool.writer'], $ffprobe);
        };
    }
}
