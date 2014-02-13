<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Border;

use Alchemy\Phrasea\Core\Configuration\Configuration;
use MediaVorus\Utils\AudioMimeTypeGuesser;
use MediaVorus\Utils\PostScriptMimeTypeGuesser;
use MediaVorus\Utils\RawImageMimeTypeGuesser;
use MediaVorus\Utils\VideoMimeTypeGuesser;
use Symfony\Component\HttpFoundation\File\MimeType\FileBinaryMimeTypeGuesser;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;

class MimeGuesserConfiguration
{
    private $conf;

    public function __construct(Configuration $conf)
    {
        $this->conf = $conf;
    }

    /**
     * Registers mime type guessers given the configuration
     */
    public function register()
    {
        $guesser = MimeTypeGuesser::getInstance();

        $guesser->register(new FileBinaryMimeTypeGuesser());
        $guesser->register(new RawImageMimeTypeGuesser());
        $guesser->register(new PostScriptMimeTypeGuesser());
        $guesser->register(new AudioMimeTypeGuesser());
        $guesser->register(new VideoMimeTypeGuesser());

        if ($this->conf->isSetup()) {
            $conf = $this->conf->getConfig();

            if (isset($conf['border-manager']['extension-mapping']) && is_array($conf['border-manager']['extension-mapping'])) {
                $guesser->register(new CustomExtensionGuesser($conf['border-manager']['extension-mapping']));
            }
        }
    }
}
