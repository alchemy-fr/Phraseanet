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

class TfEditdate extends AbstractTag
{
    protected string $id = 'Phraseanet:tf-editdate';
    protected string $name = 'tf-editdate';
    protected array $description = [
        'en' => 'The last date of editing',
    ];

}
