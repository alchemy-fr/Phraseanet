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

class TfBits extends AbstractTag
{
    protected string $id = 'Phraseanet:tf-bits';
    protected string $name = 'tf-bits';
    protected array $description = [
        'en' => 'The color depth of the file (if available)',
    ];

}
