<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\HP;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class ISO extends AbstractTag
{

    protected $Id = 52;

    protected $Name = 'ISO';

    protected $FullName = 'mixed';

    protected $GroupName = 'HP';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'HP';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = false;

    protected $Description = 'ISO';

    protected $flag_Permanent = true;

}
