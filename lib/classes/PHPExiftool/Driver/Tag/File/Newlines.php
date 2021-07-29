<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\File;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Newlines extends AbstractTag
{

    protected $Id = 'Newlines';

    protected $Name = 'Newlines';

    protected $FullName = 'Text::Main';

    protected $GroupName = 'File';

    protected $g0 = 'File';

    protected $g1 = 'File';

    protected $g2 = 'Document';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Newlines';

    protected $Values = array(
        '' => array(
            'Id' => '',
            'Label' => '(none)',
        ),
        '\\x0a' => array(
            'Id' => '\\x0a',
            'Label' => 'Unix LF',
        ),
        '\\x0d' => array(
            'Id' => '\\x0d',
            'Label' => 'Macintosh CR',
        ),
        '\\x0d\\x0a' => array(
            'Id' => '\\x0d\\x0a',
            'Label' => 'Windows CRLF',
        ),
    );

}
