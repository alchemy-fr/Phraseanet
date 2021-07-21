<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\ICCProfile;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class PerceptualRenderingIntentGamut extends AbstractTag
{

    protected $Id = 'rig0';

    protected $Name = 'PerceptualRenderingIntentGamut';

    protected $FullName = 'ICC_Profile::Main';

    protected $GroupName = 'ICC_Profile';

    protected $g0 = 'ICC_Profile';

    protected $g1 = 'ICC_Profile';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Perceptual Rendering Intent Gamut';

    protected $Values = array(
        'prmg' => array(
            'Id' => 'prmg',
            'Label' => 'Perceptual Reference Medium Gamut',
        ),
    );

}
