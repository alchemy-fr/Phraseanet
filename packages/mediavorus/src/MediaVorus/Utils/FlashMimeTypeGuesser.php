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
class FlashMimeTypeGuesser implements MimeTypeGuesserInterface
{
    public static $flashMimeTypes = array(
        'swf' => 'application/x-shockwave-flash',
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

        if (array_key_exists($extension, static::$flashMimeTypes)) {
            return static::$flashMimeTypes[$extension];
        }

        return null;
    }
}
