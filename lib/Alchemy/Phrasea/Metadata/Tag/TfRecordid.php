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

class TfRecordid extends AbstractTag
{
    protected string $id = 'Phraseanet:tf-recordid';
    protected string $name = 'tf-recordid';
    protected array $description = [
        'en' => 'The recordid in Phraseanet',
    ];

}
