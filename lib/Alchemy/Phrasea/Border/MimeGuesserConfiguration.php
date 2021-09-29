<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Border;

use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use MediaVorus\Utils\AudioMimeTypeGuesser;
use MediaVorus\Utils\ImageMimeTypeGuesser;
use MediaVorus\Utils\PostScriptMimeTypeGuesser;
use MediaVorus\Utils\RawImageMimeTypeGuesser;
use MediaVorus\Utils\VideoMimeTypeGuesser;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;

class MimeGuesserConfiguration
{
    /** @var PropertyAccess  */
    private $conf;

    public function __construct(PropertyAccess $conf)
    {
        $this->conf = $conf;
    }

    /**
     * Registers mime type guessers given the configuration
     */
    public function register()
    {
        $guesser = MimeTypeGuesser::getInstance();

        $guesser->register(new RawImageMimeTypeGuesser());
        $guesser->register(new PostScriptMimeTypeGuesser());
        $guesser->register(new AudioMimeTypeGuesser());
        $guesser->register(new VideoMimeTypeGuesser());
        $guesser->register(new ImageMimeTypeGuesser());

        $guesser->register(new CustomExtensionGuesser($this->conf->get(['border-manager', 'extension-mapping'], [])));
    }
}
