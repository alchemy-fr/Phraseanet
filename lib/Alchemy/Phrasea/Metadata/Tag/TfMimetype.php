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

class TfMimetype extends AbstractTag
{
    protected string $id = 'Phraseanet:tf-mimetype';
    protected string $name = 'tf-mimetype';
    protected array $description = [
        'en' => 'The mime type of the archived file',
    ];

}
