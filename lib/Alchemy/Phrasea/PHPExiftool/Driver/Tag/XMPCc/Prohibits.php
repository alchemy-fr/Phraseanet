<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\XMPCc;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class Prohibits extends AbstractTag
{

    protected $Id = 'prohibits';

    protected $Name = 'Prohibits';

    protected $FullName = 'XMP::cc';

    protected $GroupName = 'XMP-cc';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-cc';

    protected $g2 = 'Author';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Prohibits';

    protected $flag_List = true;

    protected $flag_Bag = true;

    protected $Values = array(
        'cc:CommercialUse' => array(
            'Id' => 'cc:CommercialUse',
            'Label' => 'Commercial Use',
        ),
        'cc:HighIncomeNationUse' => array(
            'Id' => 'cc:HighIncomeNationUse',
            'Label' => 'High Income Nation Use',
        ),
    );

}
