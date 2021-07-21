<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\KodakEffectsIFD;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class DigitalEffectsName extends AbstractTag
{

    protected $Id = 1;

    protected $Name = 'DigitalEffectsName';

    protected $FullName = 'Kodak::SpecialEffects';

    protected $GroupName = 'KodakEffectsIFD';

    protected $g0 = 'Meta';

    protected $g1 = 'KodakEffectsIFD';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Digital Effects Name';

}
