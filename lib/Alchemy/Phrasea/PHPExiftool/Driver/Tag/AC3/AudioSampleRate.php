<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\AC3;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class AudioSampleRate extends AbstractTag
{

    protected $Id = 'AudioSampleRate';

    protected $Name = 'AudioSampleRate';

    protected $FullName = 'M2TS::AC3';

    protected $GroupName = 'AC3';

    protected $g0 = 'M2TS';

    protected $g1 = 'AC3';

    protected $g2 = 'Audio';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Audio Sample Rate';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 48000,
        ),
        1 => array(
            'Id' => 1,
            'Label' => 44100,
        ),
        2 => array(
            'Id' => 2,
            'Label' => 32000,
        ),
    );

}
