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
class DocFlags extends AbstractTag
{

    protected $Id = 5;

    protected $Name = 'DocFlags';

    protected $FullName = 'FlashPix::WordDocument';

    protected $GroupName = 'FlashPix';

    protected $g0 = 'FlashPix';

    protected $g1 = 'FlashPix';

    protected $g2 = 'Other';

    protected $Type = 'int16u';

    protected $Writable = false;

    protected $Description = 'Doc Flags';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Template',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'AutoText only',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Complex',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Has picture',
        ),
        256 => array(
            'Id' => 256,
            'Label' => 'Encrypted',
        ),
        512 => array(
            'Id' => 512,
            'Label' => '1Table',
        ),
        1024 => array(
            'Id' => 1024,
            'Label' => 'Read only',
        ),
        2048 => array(
            'Id' => 2048,
            'Label' => 'Passworded',
        ),
        4096 => array(
            'Id' => 4096,
            'Label' => 'ExtChar',
        ),
        8192 => array(
            'Id' => 8192,
            'Label' => 'Load override',
        ),
        16384 => array(
            'Id' => 16384,
            'Label' => 'Far east',
        ),
        32768 => array(
            'Id' => 32768,
            'Label' => 'Obfuscated',
        ),
    );

}
