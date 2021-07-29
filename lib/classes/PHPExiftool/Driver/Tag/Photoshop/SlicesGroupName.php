<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Photoshop;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class SlicesGroupName extends AbstractTag
{

    protected $Id = 20;

    protected $Name = 'SlicesGroupName';

    protected $FullName = 'Photoshop::SliceInfo';

    protected $GroupName = 'Photoshop';

    protected $g0 = 'Photoshop';

    protected $g1 = 'Photoshop';

    protected $g2 = 'Other';

    protected $Type = 'var_ustr32';

    protected $Writable = false;

    protected $Description = 'Slices Group Name';

}
