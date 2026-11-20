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

class TfArchivedate extends AbstractTag
{
    protected string $id = 'Phraseanet:tf-archivedate';
    protected string $name = 'tf-archivedate';
    protected array $description = [
        'en' => 'The date the document has been archived',
    ];

}
