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

class TfBasename extends AbstractTag
{
    protected string $id = 'Phraseanet:tf-basename';
    protected string $name = 'tf-basename';
    protected array $description = [
        'en' => 'The original filename with extension',
    ];

}
