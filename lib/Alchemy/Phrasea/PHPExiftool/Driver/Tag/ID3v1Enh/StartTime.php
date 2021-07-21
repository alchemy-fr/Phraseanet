<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\ID3v1Enh;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class StartTime extends AbstractTag
{

    protected $Id = 215;

    protected $Name = 'StartTime';

    protected $FullName = 'ID3::v1_Enh';

    protected $GroupName = 'ID3v1_Enh';

    protected $g0 = 'ID3';

    protected $g1 = 'ID3v1_Enh';

    protected $g2 = 'Audio';

    protected $Type = 'string';

    protected $Writable = false;

    protected $Description = 'Start Time';

    protected $MaxLength = 6;

}
