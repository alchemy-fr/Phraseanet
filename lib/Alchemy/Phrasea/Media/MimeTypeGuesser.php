<?php

namespace Alchemy\Phrasea\Media;

use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;

class MimeTypeGuesser implements MimeTypeGuesserInterface
{
    public static $mimeTypes = array(
        'mpeg' => 'video/mpeg',
        'mpg'  => 'video/mpeg',
        'mov'  => 'video/quicktime',
        'dv'   => 'video/x-dv',
    );

    /**
     * {@inheritdoc}
     */
    public function guess($path)
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (array_key_exists($extension, static::$mimeTypes)) {
            return static::$mimeTypes[$extension];
        }

        return null;
    }
}
