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

class TfWidth extends AbstractTag
{
    protected string $id = 'Phraseanet:tf-width';
    protected string $name = 'tf-width';
    protected array $description = [
        'en' => 'The width of the file (if available)',
    ];

}
