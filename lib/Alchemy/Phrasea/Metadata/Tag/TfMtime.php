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

class TfMtime extends AbstractTag
{
    protected string $id = 'Phraseanet:tf-mtime';
    protected string $name = 'tf-mtime';
    protected array $description = [
        'en' => 'The mtime of the archived file',
    ];

}
