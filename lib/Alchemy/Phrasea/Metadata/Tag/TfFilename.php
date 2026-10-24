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

class TfFilename extends AbstractTag
{
    protected string $id = 'Phraseanet:tf-filename';
    protected string $name = 'tf-filename';
    protected array $description = [
        'en' => 'The original filename without extension',
    ];

}
