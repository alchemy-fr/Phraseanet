<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\FlashPix;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Identification extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'Identification';

    protected $FullName = 'FlashPix::WordDocument';

    protected $GroupName = 'FlashPix';

    protected $g0 = 'FlashPix';

    protected $g1 = 'FlashPix';

    protected $g2 = 'Other';

    protected $Type = 'int16u';

    protected $Writable = false;

    protected $Description = 'Identification';

    protected $Values = array(
        25194 => array(
            'Id' => 25194,
            'Label' => 'Word 98 Mac',
        ),
        27234 => array(
            'Id' => 27234,
            'Label' => 'MS Word 97',
        ),
        42460 => array(
            'Id' => 42460,
            'Label' => 'Word 6.0/7.0',
        ),
        42476 => array(
            'Id' => 42476,
            'Label' => 'Word 8.0',
        ),
    );

}
