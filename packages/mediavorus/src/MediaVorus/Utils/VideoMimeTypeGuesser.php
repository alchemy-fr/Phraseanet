<?php

/*
 * This file is part of MediaVorus.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MediaVorus\Utils;

use Symfony\Component\Mime\MimeTypeGuesserInterface;

/**
 *
 * @author      Romain Neutron - imprec@gmail.com
 * @license     http://opensource.org/licenses/MIT MIT
 */
class VideoMimeTypeGuesser implements MimeTypeGuesserInterface
{
    public static $videoMimeTypes = array(
        'webm' => 'video/webm',
        'ogv'  => 'video/ogg',
        'mts'  => 'video/m2ts',
        'mov'  => 'video/quicktime',
        'm4v'  => 'video/m4v'
    );

    /**
     * {@inheritdoc}
     */
    public function isGuesserSupported(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function guessMimeType(string $path): ?string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (array_key_exists($extension, static::$videoMimeTypes)) {
            return static::$videoMimeTypes[$extension];
        }

        return null;
    }
}
