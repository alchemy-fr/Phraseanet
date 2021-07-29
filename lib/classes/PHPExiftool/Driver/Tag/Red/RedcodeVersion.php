<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Red;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class RedcodeVersion extends AbstractTag
{

    protected $Id = 7;

    protected $Name = 'RedcodeVersion';

    protected $FullName = 'mixed';

    protected $GroupName = 'Red';

    protected $g0 = 'Red';

    protected $g1 = 'Red';

    protected $g2 = 'Video';

    protected $Type = 'string';

    protected $Writable = false;

    protected $Description = 'Redcode Version';

    protected $MaxLength = 1;

}
