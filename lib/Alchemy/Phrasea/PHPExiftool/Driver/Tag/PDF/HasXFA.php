<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\PDF;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class HasXFA extends AbstractTag
{

    protected $Id = '_has_xfa';

    protected $Name = 'HasXFA';

    protected $FullName = 'PDF::AcroForm';

    protected $GroupName = 'PDF';

    protected $g0 = 'PDF';

    protected $g1 = 'PDF';

    protected $g2 = 'Other';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Has XFA';

    protected $Values = array(
        false => array(
            'Id' => false,
            'Label' => 'No',
        ),
        true => array(
            'Id' => true,
            'Label' => 'Yes',
        ),
    );

}
