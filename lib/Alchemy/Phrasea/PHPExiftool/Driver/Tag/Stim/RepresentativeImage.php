<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\Stim;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class RepresentativeImage extends AbstractTag
{

    protected $Id = 10;

    protected $Name = 'RepresentativeImage';

    protected $FullName = 'Stim::Main';

    protected $GroupName = 'Stim';

    protected $g0 = 'Stim';

    protected $g1 = 'Stim';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Representative Image';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Left Viewpoint',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Right Viewpoint',
        ),
    );

}
