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

class TfAtime extends AbstractTag
{
    protected string $id = 'Phraseanet:tf-atime';
    protected string $name = 'tf-atime';
    protected array $description = [
        'en' => 'The a-time of the file when archiving',
    ];

}
