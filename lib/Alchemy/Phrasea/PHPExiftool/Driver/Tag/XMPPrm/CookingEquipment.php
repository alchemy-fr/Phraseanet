<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\XMPPrm;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class CookingEquipment extends AbstractTag
{

    protected $Id = 'cookingEquipment';

    protected $Name = 'CookingEquipment';

    protected $FullName = 'XMP::prm';

    protected $GroupName = 'XMP-prm';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-prm';

    protected $g2 = 'Document';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Cooking Equipment';

    protected $flag_Avoid = true;

}
