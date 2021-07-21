<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\MNG;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class IterationEndAction extends AbstractTag
{

    protected $Id = 1;

    protected $Name = 'IterationEndAction';

    protected $FullName = 'MNG::TerminationAction';

    protected $GroupName = 'MNG';

    protected $g0 = 'MNG';

    protected $g1 = 'MNG';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Iteration End Action';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Show Last Frame',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Display Nothing',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Show First Frame',
        ),
    );

}
