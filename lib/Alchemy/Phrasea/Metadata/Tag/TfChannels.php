<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Metadata\Tag;

class TfChannels extends AbstractTag
{
    protected string $id = 'Phraseanet:tf-channels';
    protected string $name = 'tf-channels';
    protected array $description = [
        'en' => 'The number of color channels in the file (if available)',
    ];

}
