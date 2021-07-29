<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\EXE;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ImageFileCharacteristics extends AbstractTag
{

    protected $Id = 9;

    protected $Name = 'ImageFileCharacteristics';

    protected $FullName = 'EXE::Main';

    protected $GroupName = 'EXE';

    protected $g0 = 'EXE';

    protected $g1 = 'EXE';

    protected $g2 = 'Other';

    protected $Type = 'int16u';

    protected $Writable = false;

    protected $Description = 'Image File Characteristics';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'No relocs',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Executable',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'No line numbers',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'No symbols',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Aggressive working-set trim',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'Large address aware',
        ),
        128 => array(
            'Id' => 128,
            'Label' => 'Bytes reversed lo',
        ),
        256 => array(
            'Id' => 256,
            'Label' => '32-bit',
        ),
        512 => array(
            'Id' => 512,
            'Label' => 'No debug',
        ),
        1024 => array(
            'Id' => 1024,
            'Label' => 'Removable run from swap',
        ),
        2048 => array(
            'Id' => 2048,
            'Label' => 'Net run from swap',
        ),
        4096 => array(
            'Id' => 4096,
            'Label' => 'System file',
        ),
        8192 => array(
            'Id' => 8192,
            'Label' => 'DLL',
        ),
        16384 => array(
            'Id' => 16384,
            'Label' => 'Uniprocessor only',
        ),
        32768 => array(
            'Id' => 32768,
            'Label' => 'Bytes reversed hi',
        ),
    );

}
