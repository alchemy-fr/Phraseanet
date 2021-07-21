<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\XMPPlus;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class LicensorTelephoneType2 extends AbstractTag
{

    protected $Id = 'LicensorLicensorTelephoneType2';

    protected $Name = 'LicensorTelephoneType2';

    protected $FullName = 'PLUS::XMP';

    protected $GroupName = 'XMP-plus';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-plus';

    protected $g2 = 'Author';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Licensor Telephone Type 2';

    protected $flag_List = true;

    protected $Values = array(
        'cell' => array(
            'Id' => 'cell',
            'Label' => 'Cell',
        ),
        'fax' => array(
            'Id' => 'fax',
            'Label' => 'FAX',
        ),
        'home' => array(
            'Id' => 'home',
            'Label' => 'Home',
        ),
        'pager' => array(
            'Id' => 'pager',
            'Label' => 'Pager',
        ),
        'work' => array(
            'Id' => 'work',
            'Label' => 'Work',
        ),
    );

}
