<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\XMPPlus;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class ImageAlterationConstraints extends AbstractTag
{

    protected $Id = 'ImageAlterationConstraints';

    protected $Name = 'ImageAlterationConstraints';

    protected $FullName = 'PLUS::XMP';

    protected $GroupName = 'XMP-plus';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-plus';

    protected $g2 = 'Author';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Image Alteration Constraints';

    protected $flag_List = true;

    protected $flag_Bag = true;

    protected $Values = array(
        'AL-CLR' => array(
            'Id' => 'AL-CLR',
            'Label' => 'No Colorization',
        ),
        'AL-CRP' => array(
            'Id' => 'AL-CRP',
            'Label' => 'No Cropping',
        ),
        'AL-DCL' => array(
            'Id' => 'AL-DCL',
            'Label' => 'No De-Colorization',
        ),
        'AL-FLP' => array(
            'Id' => 'AL-FLP',
            'Label' => 'No Flipping',
        ),
        'AL-MRG' => array(
            'Id' => 'AL-MRG',
            'Label' => 'No Merging',
        ),
        'AL-RET' => array(
            'Id' => 'AL-RET',
            'Label' => 'No Retouching',
        ),
    );

}
