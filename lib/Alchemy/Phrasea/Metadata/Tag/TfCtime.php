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

class TfCtime extends AbstractTag
{
    protected string $id = 'Phraseanet:tf-ctime';
    protected string $name = 'tf-ctime';
    protected array $description = [
        'en' => 'The ctime of the archived file',
    ];

}
