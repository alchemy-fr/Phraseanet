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

class TfQuarantine extends AbstractTag
{
    protected string $id = 'Phraseanet:tf-quarantine';
    protected string $name = 'tf-quarantine';
    protected array $description = [
        'en' => 'The date when document is added to quarantine',
    ];

}
