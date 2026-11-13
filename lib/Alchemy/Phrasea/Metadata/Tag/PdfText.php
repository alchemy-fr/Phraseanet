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

class PdfText extends AbstractTag
{
    protected string $id = 'Phraseanet:pdf-text';
    protected string $name = 'pdf-text';
    protected array $description = [
        'en' => 'The content text of the PDF file',
    ];

}
