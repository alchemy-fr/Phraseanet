<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\FlashPix;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class LanguageCode extends AbstractTag
{

    protected $Id = 3;

    protected $Name = 'LanguageCode';

    protected $FullName = 'FlashPix::WordDocument';

    protected $GroupName = 'FlashPix';

    protected $g0 = 'FlashPix';

    protected $g1 = 'FlashPix';

    protected $g2 = 'Other';

    protected $Type = 'int16u';

    protected $Writable = false;

    protected $Description = 'Language Code';

    protected $Values = array(
        1024 => array(
            'Id' => 1024,
            'Label' => 'None',
        ),
        1025 => array(
            'Id' => 1025,
            'Label' => 'Arabic',
        ),
        1026 => array(
            'Id' => 1026,
            'Label' => 'Bulgarian',
        ),
        1027 => array(
            'Id' => 1027,
            'Label' => 'Catalan',
        ),
        1028 => array(
            'Id' => 1028,
            'Label' => 'Traditional Chinese',
        ),
        1029 => array(
            'Id' => 1029,
            'Label' => 'Czech',
        ),
        1030 => array(
            'Id' => 1030,
            'Label' => 'Danish',
        ),
        1031 => array(
            'Id' => 1031,
            'Label' => 'German',
        ),
        1032 => array(
            'Id' => 1032,
            'Label' => 'Greek',
        ),
        1033 => array(
            'Id' => 1033,
            'Label' => 'English (US)',
        ),
        1034 => array(
            'Id' => 1034,
            'Label' => 'Spanish (Castilian)',
        ),
        1035 => array(
            'Id' => 1035,
            'Label' => 'Finnish',
        ),
        1036 => array(
            'Id' => 1036,
            'Label' => 'French',
        ),
        1037 => array(
            'Id' => 1037,
            'Label' => 'Hebrew',
        ),
        1038 => array(
            'Id' => 1038,
            'Label' => 'Hungarian',
        ),
        1039 => array(
            'Id' => 1039,
            'Label' => 'Icelandic',
        ),
        1040 => array(
            'Id' => 1040,
            'Label' => 'Italian',
        ),
        1041 => array(
            'Id' => 1041,
            'Label' => 'Japanese',
        ),
        1042 => array(
            'Id' => 1042,
            'Label' => 'Korean',
        ),
        1043 => array(
            'Id' => 1043,
            'Label' => 'Dutch',
        ),
        1044 => array(
            'Id' => 1044,
            'Label' => 'Norwegian (Bokmal)',
        ),
        1045 => array(
            'Id' => 1045,
            'Label' => 'Polish',
        ),
        1046 => array(
            'Id' => 1046,
            'Label' => 'Portuguese (Brazilian)',
        ),
        1047 => array(
            'Id' => 1047,
            'Label' => 'Rhaeto-Romanic',
        ),
        1048 => array(
            'Id' => 1048,
            'Label' => 'Romanian',
        ),
        1049 => array(
            'Id' => 1049,
            'Label' => 'Russian',
        ),
        1050 => array(
            'Id' => 1050,
            'Label' => 'Croato-Serbian (Latin)',
        ),
        1051 => array(
            'Id' => 1051,
            'Label' => 'Slovak',
        ),
        1052 => array(
            'Id' => 1052,
            'Label' => 'Albanian',
        ),
        1053 => array(
            'Id' => 1053,
            'Label' => 'Swedish',
        ),
        1054 => array(
            'Id' => 1054,
            'Label' => 'Thai',
        ),
        1055 => array(
            'Id' => 1055,
            'Label' => 'Turkish',
        ),
        1056 => array(
            'Id' => 1056,
            'Label' => 'Urdu',
        ),
        1057 => array(
            'Id' => 1057,
            'Label' => 'Bahasa',
        ),
        1058 => array(
            'Id' => 1058,
            'Label' => 'Ukrainian',
        ),
        1059 => array(
            'Id' => 1059,
            'Label' => 'Byelorussian',
        ),
        1060 => array(
            'Id' => 1060,
            'Label' => 'Slovenian',
        ),
        1061 => array(
            'Id' => 1061,
            'Label' => 'Estonian',
        ),
        1062 => array(
            'Id' => 1062,
            'Label' => 'Latvian',
        ),
        1063 => array(
            'Id' => 1063,
            'Label' => 'Lithuanian',
        ),
        1065 => array(
            'Id' => 1065,
            'Label' => 'Farsi',
        ),
        1069 => array(
            'Id' => 1069,
            'Label' => 'Basque',
        ),
        1071 => array(
            'Id' => 1071,
            'Label' => 'Macedonian',
        ),
        1078 => array(
            'Id' => 1078,
            'Label' => 'Afrikaans',
        ),
        1086 => array(
            'Id' => 1086,
            'Label' => 'Malaysian',
        ),
        2052 => array(
            'Id' => 2052,
            'Label' => 'Simplified Chinese',
        ),
        2055 => array(
            'Id' => 2055,
            'Label' => 'German (Swiss)',
        ),
        2057 => array(
            'Id' => 2057,
            'Label' => 'English (British)',
        ),
        2058 => array(
            'Id' => 2058,
            'Label' => 'Spanish (Mexican)',
        ),
        2060 => array(
            'Id' => 2060,
            'Label' => 'French (Belgian)',
        ),
        2064 => array(
            'Id' => 2064,
            'Label' => 'Italian (Swiss)',
        ),
        2067 => array(
            'Id' => 2067,
            'Label' => 'Dutch (Belgian)',
        ),
        2068 => array(
            'Id' => 2068,
            'Label' => 'Norwegian (Nynorsk)',
        ),
        2070 => array(
            'Id' => 2070,
            'Label' => 'Portuguese',
        ),
        2074 => array(
            'Id' => 2074,
            'Label' => 'Serbo-Croatian (Cyrillic)',
        ),
        3081 => array(
            'Id' => 3081,
            'Label' => 'English (Australian)',
        ),
        3084 => array(
            'Id' => 3084,
            'Label' => 'French (Canadian)',
        ),
        4108 => array(
            'Id' => 4108,
            'Label' => 'French (Swiss)',
        ),
    );

}
