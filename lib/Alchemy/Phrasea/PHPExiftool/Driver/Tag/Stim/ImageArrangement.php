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
class ImageArrangement extends AbstractTag
{

    protected $Id = 2;

    protected $Name = 'ImageArrangement';

    protected $FullName = 'Stim::Main';

    protected $GroupName = 'Stim';

    protected $g0 = 'Stim';

    protected $g1 = 'Stim';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Image Arrangement';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Parallel View Alignment',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Cross View Alignment',
        ),
    );

}
