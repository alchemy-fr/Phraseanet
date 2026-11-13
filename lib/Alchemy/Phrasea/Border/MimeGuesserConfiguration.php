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
use Symfony\Component\Mime\MimeTypes;

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
        $guesser = MimeTypes::getDefault();

        $guesser->registerGuesser(new RawImageMimeTypeGuesser());
        $guesser->registerGuesser(new PostScriptMimeTypeGuesser());
        $guesser->registerGuesser(new AudioMimeTypeGuesser());
        $guesser->registerGuesser(new VideoMimeTypeGuesser());
        $guesser->registerGuesser(new ImageMimeTypeGuesser());

        $guesser->registerGuesser(new CustomExtensionGuesser($this->conf->get(['border-manager', 'extension-mapping'], [])));
    }
}
