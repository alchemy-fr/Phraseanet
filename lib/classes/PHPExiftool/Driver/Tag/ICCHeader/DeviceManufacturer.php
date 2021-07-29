<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\ICCHeader;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class DeviceManufacturer extends AbstractTag
{

    protected $Id = 48;

    protected $Name = 'DeviceManufacturer';

    protected $FullName = 'ICC_Profile::Header';

    protected $GroupName = 'ICC-header';

    protected $g0 = 'ICC_Profile';

    protected $g1 = 'ICC-header';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = false;

    protected $Description = 'Device Manufacturer';

    protected $MaxLength = 4;

    protected $Values = array(
        '' => array(
            'Id' => '',
            'Label' => '',
        ),
        '4d2p' => array(
            'Id' => '4d2p',
            'Label' => 'Erdt Systems GmbH & Co KG',
        ),
        'AAMA' => array(
            'Id' => 'AAMA',
            'Label' => 'Aamazing Technologies, Inc.',
        ),
        'ACER' => array(
            'Id' => 'ACER',
            'Label' => 'Acer Peripherals',
        ),
        'ACLT' => array(
            'Id' => 'ACLT',
            'Label' => 'Acolyte Color Research',
        ),
        'ACTI' => array(
            'Id' => 'ACTI',
            'Label' => 'Actix Sytems, Inc.',
        ),
        'ADAR' => array(
            'Id' => 'ADAR',
            'Label' => 'Adara Technology, Inc.',
        ),
        'ADBE' => array(
            'Id' => 'ADBE',
            'Label' => 'Adobe Systems Inc.',
        ),
        'ADI ' => array(
            'Id' => 'ADI ',
            'Label' => 'ADI Systems, Inc.',
        ),
        'AGFA' => array(
            'Id' => 'AGFA',
            'Label' => 'Agfa Graphics N.V.',
        ),
        'ALMD' => array(
            'Id' => 'ALMD',
            'Label' => 'Alps Electric USA, Inc.',
        ),
        'ALPS' => array(
            'Id' => 'ALPS',
            'Label' => 'Alps Electric USA, Inc.',
        ),
        'ALWN' => array(
            'Id' => 'ALWN',
            'Label' => 'Alwan Color Expertise',
        ),
        'AMTI' => array(
            'Id' => 'AMTI',
            'Label' => 'Amiable Technologies, Inc.',
        ),
        'AOC ' => array(
            'Id' => 'AOC ',
            'Label' => 'AOC International (U.S.A), Ltd.',
        ),
        'APAG' => array(
            'Id' => 'APAG',
            'Label' => 'Apago',
        ),
        'APPL' => array(
            'Id' => 'APPL',
            'Label' => 'Apple Computer Inc.',
        ),
        'AST ' => array(
            'Id' => 'AST ',
            'Label' => 'AST',
        ),
        'AT&T' => array(
            'Id' => 'AT&T',
            'Label' => 'AT&T Computer Systems',
        ),
        'BAEL' => array(
            'Id' => 'BAEL',
            'Label' => 'BARBIERI electronic',
        ),
        'BRCO' => array(
            'Id' => 'BRCO',
            'Label' => 'Barco NV',
        ),
        'BRKP' => array(
            'Id' => 'BRKP',
            'Label' => 'Breakpoint Pty Limited',
        ),
        'BROT' => array(
            'Id' => 'BROT',
            'Label' => 'Brother Industries, LTD',
        ),
        'BULL' => array(
            'Id' => 'BULL',
            'Label' => 'Bull',
        ),
        'BUS ' => array(
            'Id' => 'BUS ',
            'Label' => 'Bus Computer Systems',
        ),
        'C-IT' => array(
            'Id' => 'C-IT',
            'Label' => 'C-Itoh',
        ),
        'CAMR' => array(
            'Id' => 'CAMR',
            'Label' => 'Intel Corporation',
        ),
        'CANO' => array(
            'Id' => 'CANO',
            'Label' => 'Canon, Inc. (Canon Development Americas, Inc.)',
        ),
        'CARR' => array(
            'Id' => 'CARR',
            'Label' => 'Carroll Touch',
        ),
        'CASI' => array(
            'Id' => 'CASI',
            'Label' => 'Casio Computer Co., Ltd.',
        ),
        'CBUS' => array(
            'Id' => 'CBUS',
            'Label' => 'Colorbus PL',
        ),
        'CEL ' => array(
            'Id' => 'CEL ',
            'Label' => 'Crossfield',
        ),
        'CELx' => array(
            'Id' => 'CELx',
            'Label' => 'Crossfield',
        ),
        'CGS ' => array(
            'Id' => 'CGS ',
            'Label' => 'CGS Publishing Technologies International GmbH',
        ),
        'CHM ' => array(
            'Id' => 'CHM ',
            'Label' => 'Rochester Robotics',
        ),
        'CIGL' => array(
            'Id' => 'CIGL',
            'Label' => 'Colour Imaging Group, London',
        ),
        'CITI' => array(
            'Id' => 'CITI',
            'Label' => 'Citizen',
        ),
        'CL00' => array(
            'Id' => 'CL00',
            'Label' => 'Candela, Ltd.',
        ),
        'CLIQ' => array(
            'Id' => 'CLIQ',
            'Label' => 'Color IQ',
        ),
        'CMCO' => array(
            'Id' => 'CMCO',
            'Label' => 'Chromaco, Inc.',
        ),
        'CMiX' => array(
            'Id' => 'CMiX',
            'Label' => 'CHROMiX',
        ),
        'COLO' => array(
            'Id' => 'COLO',
            'Label' => 'Colorgraphic Communications Corporation',
        ),
        'COMP' => array(
            'Id' => 'COMP',
            'Label' => 'COMPAQ Computer Corporation',
        ),
        'COMp' => array(
            'Id' => 'COMp',
            'Label' => 'Compeq USA/Focus Technology',
        ),
        'CONR' => array(
            'Id' => 'CONR',
            'Label' => 'Conrac Display Products',
        ),
        'CORD' => array(
            'Id' => 'CORD',
            'Label' => 'Cordata Technologies, Inc.',
        ),
        'CPQ ' => array(
            'Id' => 'CPQ ',
            'Label' => 'Compaq Computer Corporation',
        ),
        'CPRO' => array(
            'Id' => 'CPRO',
            'Label' => 'ColorPro',
        ),
        'CRN ' => array(
            'Id' => 'CRN ',
            'Label' => 'Cornerstone',
        ),
        'CTX ' => array(
            'Id' => 'CTX ',
            'Label' => 'CTX International, Inc.',
        ),
        'CVIS' => array(
            'Id' => 'CVIS',
            'Label' => 'ColorVision',
        ),
        'CWC ' => array(
            'Id' => 'CWC ',
            'Label' => 'Fujitsu Laboratories, Ltd.',
        ),
        'DARI' => array(
            'Id' => 'DARI',
            'Label' => 'Darius Technology, Ltd.',
        ),
        'DATA' => array(
            'Id' => 'DATA',
            'Label' => 'Dataproducts',
        ),
        'DCP ' => array(
            'Id' => 'DCP ',
            'Label' => 'Dry Creek Photo',
        ),
        'DCRC' => array(
            'Id' => 'DCRC',
            'Label' => 'Digital Contents Resource Center, Chung-Ang University',
        ),
        'DELL' => array(
            'Id' => 'DELL',
            'Label' => 'Dell Computer Corporation',
        ),
        'DIC ' => array(
            'Id' => 'DIC ',
            'Label' => 'Dainippon Ink and Chemicals',
        ),
        'DICO' => array(
            'Id' => 'DICO',
            'Label' => 'Diconix',
        ),
        'DIGI' => array(
            'Id' => 'DIGI',
            'Label' => 'Digital',
        ),
        'DL&C' => array(
            'Id' => 'DL&C',
            'Label' => 'Digital Light & Color',
        ),
        'DPLG' => array(
            'Id' => 'DPLG',
            'Label' => 'Doppelganger, LLC',
        ),
        'DS  ' => array(
            'Id' => 'DS  ',
            'Label' => 'Dainippon Screen',
        ),
        'DSOL' => array(
            'Id' => 'DSOL',
            'Label' => 'DOOSOL',
        ),
        'DUPN' => array(
            'Id' => 'DUPN',
            'Label' => 'DuPont',
        ),
        'EPSO' => array(
            'Id' => 'EPSO',
            'Label' => 'Epson',
        ),
        'ESKO' => array(
            'Id' => 'ESKO',
            'Label' => 'Esko-Graphics',
        ),
        'ETRI' => array(
            'Id' => 'ETRI',
            'Label' => 'Electronics and Telecommunications Research Institute',
        ),
        'EVER' => array(
            'Id' => 'EVER',
            'Label' => 'Everex Systems, Inc.',
        ),
        'EXAC' => array(
            'Id' => 'EXAC',
            'Label' => 'ExactCODE GmbH',
        ),
        'Eizo' => array(
            'Id' => 'Eizo',
            'Label' => 'EIZO NANAO CORPORATION',
        ),
        'FALC' => array(
            'Id' => 'FALC',
            'Label' => 'Falco Data Products, Inc.',
        ),
        'FF  ' => array(
            'Id' => 'FF  ',
            'Label' => 'Fuji Photo Film Co.,LTD',
        ),
        'FFEI' => array(
            'Id' => 'FFEI',
            'Label' => 'FujiFilm Electronic Imaging, Ltd.',
        ),
        'FNRD' => array(
            'Id' => 'FNRD',
            'Label' => 'fnord software',
        ),
        'FORA' => array(
            'Id' => 'FORA',
            'Label' => 'Fora, Inc.',
        ),
        'FORE' => array(
            'Id' => 'FORE',
            'Label' => 'Forefront Technology Corporation',
        ),
        'FP  ' => array(
            'Id' => 'FP  ',
            'Label' => 'Fujitsu',
        ),
        'FPA ' => array(
            'Id' => 'FPA ',
            'Label' => 'WayTech Development, Inc.',
        ),
        'FUJI' => array(
            'Id' => 'FUJI',
            'Label' => 'Fujitsu',
        ),
        'FX  ' => array(
            'Id' => 'FX  ',
            'Label' => 'Fuji Xerox Co., Ltd.',
        ),
        'GCC ' => array(
            'Id' => 'GCC ',
            'Label' => 'GCC Technologies, Inc.',
        ),
        'GGSL' => array(
            'Id' => 'GGSL',
            'Label' => 'Global Graphics Software Limited',
        ),
        'GMB ' => array(
            'Id' => 'GMB ',
            'Label' => 'Gretagmacbeth',
        ),
        'GMG ' => array(
            'Id' => 'GMG ',
            'Label' => 'GMG GmbH & Co. KG',
        ),
        'GOLD' => array(
            'Id' => 'GOLD',
            'Label' => 'GoldStar Technology, Inc.',
        ),
        'GOOG' => array(
            'Id' => 'GOOG',
            'Label' => 'Google',
        ),
        'GPRT' => array(
            'Id' => 'GPRT',
            'Label' => 'Giantprint Pty Ltd',
        ),
        'GTMB' => array(
            'Id' => 'GTMB',
            'Label' => 'Gretagmacbeth',
        ),
        'GVC ' => array(
            'Id' => 'GVC ',
            'Label' => 'WayTech Development, Inc.',
        ),
        'GW2K' => array(
            'Id' => 'GW2K',
            'Label' => 'Sony Corporation',
        ),
        'HCI ' => array(
            'Id' => 'HCI ',
            'Label' => 'HCI',
        ),
        'HDM ' => array(
            'Id' => 'HDM ',
            'Label' => 'Heidelberger Druckmaschinen AG',
        ),
        'HERM' => array(
            'Id' => 'HERM',
            'Label' => 'Hermes',
        ),
        'HITA' => array(
            'Id' => 'HITA',
            'Label' => 'Hitachi America, Ltd.',
        ),
        'HP  ' => array(
            'Id' => 'HP  ',
            'Label' => 'Hewlett-Packard',
        ),
        'HTC ' => array(
            'Id' => 'HTC ',
            'Label' => 'Hitachi, Ltd.',
        ),
        'HiTi' => array(
            'Id' => 'HiTi',
            'Label' => 'HiTi Digital, Inc.',
        ),
        'IBM ' => array(
            'Id' => 'IBM ',
            'Label' => 'IBM Corporation',
        ),
        'IDNT' => array(
            'Id' => 'IDNT',
            'Label' => 'Scitex Corporation, Ltd.',
        ),
        'IEC ' => array(
            'Id' => 'IEC ',
            'Label' => 'Hewlett-Packard',
        ),
        'IIYA' => array(
            'Id' => 'IIYA',
            'Label' => 'Iiyama North America, Inc.',
        ),
        'IKEG' => array(
            'Id' => 'IKEG',
            'Label' => 'Ikegami Electronics, Inc.',
        ),
        'IMAG' => array(
            'Id' => 'IMAG',
            'Label' => 'Image Systems Corporation',
        ),
        'IMI ' => array(
            'Id' => 'IMI ',
            'Label' => 'Ingram Micro, Inc.',
        ),
        'INTC' => array(
            'Id' => 'INTC',
            'Label' => 'Intel Corporation',
        ),
        'INTL' => array(
            'Id' => 'INTL',
            'Label' => 'N/A (INTL)',
        ),
        'INTR' => array(
            'Id' => 'INTR',
            'Label' => 'Intra Electronics USA, Inc.',
        ),
        'IOCO' => array(
            'Id' => 'IOCO',
            'Label' => 'Iocomm International Technology Corporation',
        ),
        'IPS ' => array(
            'Id' => 'IPS ',
            'Label' => 'InfoPrint Solutions Company',
        ),
        'IRIS' => array(
            'Id' => 'IRIS',
            'Label' => 'Scitex Corporation, Ltd.',
        ),
        'ISL ' => array(
            'Id' => 'ISL ',
            'Label' => 'Ichikawa Soft Laboratory',
        ),
        'ITNL' => array(
            'Id' => 'ITNL',
            'Label' => 'N/A (ITNL)',
        ),
        'IVM ' => array(
            'Id' => 'IVM ',
            'Label' => 'IVM',
        ),
        'IWAT' => array(
            'Id' => 'IWAT',
            'Label' => 'Iwatsu Electric Co., Ltd.',
        ),
        'Idnt' => array(
            'Id' => 'Idnt',
            'Label' => 'Scitex Corporation, Ltd.',
        ),
        'Inca' => array(
            'Id' => 'Inca',
            'Label' => 'Inca Digital Printers Ltd.',
        ),
        'Iris' => array(
            'Id' => 'Iris',
            'Label' => 'Scitex Corporation, Ltd.',
        ),
        'JPEG' => array(
            'Id' => 'JPEG',
            'Label' => 'Joint Photographic Experts Group',
        ),
        'JSFT' => array(
            'Id' => 'JSFT',
            'Label' => 'Jetsoft Development',
        ),
        'JVC ' => array(
            'Id' => 'JVC ',
            'Label' => 'JVC Information Products Co.',
        ),
        'KART' => array(
            'Id' => 'KART',
            'Label' => 'Scitex Corporation, Ltd.',
        ),
        'KFC ' => array(
            'Id' => 'KFC ',
            'Label' => 'KFC Computek Components Corporation',
        ),
        'KLH ' => array(
            'Id' => 'KLH ',
            'Label' => 'KLH Computers',
        ),
        'KMHD' => array(
            'Id' => 'KMHD',
            'Label' => 'Konica Minolta Holdings, Inc.',
        ),
        'KNCA' => array(
            'Id' => 'KNCA',
            'Label' => 'Konica Corporation',
        ),
        'KODA' => array(
            'Id' => 'KODA',
            'Label' => 'Kodak',
        ),
        'KYOC' => array(
            'Id' => 'KYOC',
            'Label' => 'Kyocera',
        ),
        'Kart' => array(
            'Id' => 'Kart',
            'Label' => 'Scitex Corporation, Ltd.',
        ),
        'LCAG' => array(
            'Id' => 'LCAG',
            'Label' => 'Leica Camera AG',
        ),
        'LCCD' => array(
            'Id' => 'LCCD',
            'Label' => 'Leeds Colour',
        ),
        'LDAK' => array(
            'Id' => 'LDAK',
            'Label' => 'Left Dakota',
        ),
        'LEAD' => array(
            'Id' => 'LEAD',
            'Label' => 'Leading Technology, Inc.',
        ),
        'LEXM' => array(
            'Id' => 'LEXM',
            'Label' => 'Lexmark International, Inc.',
        ),
        'LINK' => array(
            'Id' => 'LINK',
            'Label' => 'Link Computer, Inc.',
        ),
        'LINO' => array(
            'Id' => 'LINO',
            'Label' => 'Linotronic',
        ),
        'LITE' => array(
            'Id' => 'LITE',
            'Label' => 'Lite-On, Inc.',
        ),
        'Leaf' => array(
            'Id' => 'Leaf',
            'Label' => 'Leaf',
        ),
        'Lino' => array(
            'Id' => 'Lino',
            'Label' => 'Linotronic',
        ),
        'MAGC' => array(
            'Id' => 'MAGC',
            'Label' => 'Mag Computronic (USA) Inc.',
        ),
        'MAGI' => array(
            'Id' => 'MAGI',
            'Label' => 'MAG Innovision, Inc.',
        ),
        'MANN' => array(
            'Id' => 'MANN',
            'Label' => 'Mannesmann',
        ),
        'MICN' => array(
            'Id' => 'MICN',
            'Label' => 'Micron Technology, Inc.',
        ),
        'MICR' => array(
            'Id' => 'MICR',
            'Label' => 'Microtek',
        ),
        'MICV' => array(
            'Id' => 'MICV',
            'Label' => 'Microvitec, Inc.',
        ),
        'MINO' => array(
            'Id' => 'MINO',
            'Label' => 'Minolta',
        ),
        'MITS' => array(
            'Id' => 'MITS',
            'Label' => 'Mitsubishi Electronics America, Inc.',
        ),
        'MITs' => array(
            'Id' => 'MITs',
            'Label' => 'Mitsuba Corporation',
        ),
        'MNLT' => array(
            'Id' => 'MNLT',
            'Label' => 'Minolta',
        ),
        'MODG' => array(
            'Id' => 'MODG',
            'Label' => 'Modgraph, Inc.',
        ),
        'MONI' => array(
            'Id' => 'MONI',
            'Label' => 'Monitronix, Inc.',
        ),
        'MONS' => array(
            'Id' => 'MONS',
            'Label' => 'Monaco Systems Inc.',
        ),
        'MORS' => array(
            'Id' => 'MORS',
            'Label' => 'Morse Technology, Inc.',
        ),
        'MOTI' => array(
            'Id' => 'MOTI',
            'Label' => 'Motive Systems',
        ),
        'MSFT' => array(
            'Id' => 'MSFT',
            'Label' => 'Microsoft Corporation',
        ),
        'MUTO' => array(
            'Id' => 'MUTO',
            'Label' => 'MUTOH INDUSTRIES LTD.',
        ),
        'Mits' => array(
            'Id' => 'Mits',
            'Label' => 'Mitsubishi Electric Corporation Kyoto Works',
        ),
        'NANA' => array(
            'Id' => 'NANA',
            'Label' => 'NANAO USA Corporation',
        ),
        'NEC ' => array(
            'Id' => 'NEC ',
            'Label' => 'NEC Corporation',
        ),
        'NEXP' => array(
            'Id' => 'NEXP',
            'Label' => 'NexPress Solutions LLC',
        ),
        'NISS' => array(
            'Id' => 'NISS',
            'Label' => 'Nissei Sangyo America, Ltd.',
        ),
        'NKON' => array(
            'Id' => 'NKON',
            'Label' => 'Nikon Corporation',
        ),
        'NONE' => array(
            'Id' => 'NONE',
            'Label' => 'none',
        ),
        'OCE ' => array(
            'Id' => 'OCE ',
            'Label' => 'Oce Technologies B.V.',
        ),
        'OCEC' => array(
            'Id' => 'OCEC',
            'Label' => 'OceColor',
        ),
        'OKI ' => array(
            'Id' => 'OKI ',
            'Label' => 'Oki',
        ),
        'OKID' => array(
            'Id' => 'OKID',
            'Label' => 'Okidata',
        ),
        'OKIP' => array(
            'Id' => 'OKIP',
            'Label' => 'Okidata',
        ),
        'OLIV' => array(
            'Id' => 'OLIV',
            'Label' => 'Olivetti',
        ),
        'OLYM' => array(
            'Id' => 'OLYM',
            'Label' => 'OLYMPUS OPTICAL CO., LTD',
        ),
        'ONYX' => array(
            'Id' => 'ONYX',
            'Label' => 'Onyx Graphics',
        ),
        'OPTI' => array(
            'Id' => 'OPTI',
            'Label' => 'Optiquest',
        ),
        'PACK' => array(
            'Id' => 'PACK',
            'Label' => 'Packard Bell',
        ),
        'PANA' => array(
            'Id' => 'PANA',
            'Label' => 'Matsushita Electric Industrial Co., Ltd.',
        ),
        'PANT' => array(
            'Id' => 'PANT',
            'Label' => 'Pantone, Inc.',
        ),
        'PBN ' => array(
            'Id' => 'PBN ',
            'Label' => 'Packard Bell',
        ),
        'PFU ' => array(
            'Id' => 'PFU ',
            'Label' => 'PFU Limited',
        ),
        'PHIL' => array(
            'Id' => 'PHIL',
            'Label' => 'Philips Consumer Electronics Co.',
        ),
        'PNTX' => array(
            'Id' => 'PNTX',
            'Label' => 'HOYA Corporation PENTAX Imaging Systems Division',
        ),
        'POne' => array(
            'Id' => 'POne',
            'Label' => 'Phase One A/S',
        ),
        'PREM' => array(
            'Id' => 'PREM',
            'Label' => 'Premier Computer Innovations',
        ),
        'PRIN' => array(
            'Id' => 'PRIN',
            'Label' => 'Princeton Graphic Systems',
        ),
        'PRIP' => array(
            'Id' => 'PRIP',
            'Label' => 'Princeton Publishing Labs',
        ),
        'QLUX' => array(
            'Id' => 'QLUX',
            'Label' => 'Hong Kong',
        ),
        'QMS ' => array(
            'Id' => 'QMS ',
            'Label' => 'QMS, Inc.',
        ),
        'QPCD' => array(
            'Id' => 'QPCD',
            'Label' => 'QPcard AB',
        ),
        'QUAD' => array(
            'Id' => 'QUAD',
            'Label' => 'QuadLaser',
        ),
        'QUME' => array(
            'Id' => 'QUME',
            'Label' => 'Qume Corporation',
        ),
        'RADI' => array(
            'Id' => 'RADI',
            'Label' => 'Radius, Inc.',
        ),
        'RDDx' => array(
            'Id' => 'RDDx',
            'Label' => 'Integrated Color Solutions, Inc.',
        ),
        'RDG ' => array(
            'Id' => 'RDG ',
            'Label' => 'Roland DG Corporation',
        ),
        'REDM' => array(
            'Id' => 'REDM',
            'Label' => 'REDMS Group, Inc.',
        ),
        'RELI' => array(
            'Id' => 'RELI',
            'Label' => 'Relisys',
        ),
        'RGMS' => array(
            'Id' => 'RGMS',
            'Label' => 'Rolf Gierling Multitools',
        ),
        'RICO' => array(
            'Id' => 'RICO',
            'Label' => 'Ricoh Corporation',
        ),
        'RNLD' => array(
            'Id' => 'RNLD',
            'Label' => 'Edmund Ronald',
        ),
        'ROYA' => array(
            'Id' => 'ROYA',
            'Label' => 'Royal',
        ),
        'RPC ' => array(
            'Id' => 'RPC ',
            'Label' => 'Ricoh Printing Systems,Ltd.',
        ),
        'RTL ' => array(
            'Id' => 'RTL ',
            'Label' => 'Royal Information Electronics Co., Ltd.',
        ),
        'SAMP' => array(
            'Id' => 'SAMP',
            'Label' => 'Sampo Corporation of America',
        ),
        'SAMS' => array(
            'Id' => 'SAMS',
            'Label' => 'Samsung, Inc.',
        ),
        'SANT' => array(
            'Id' => 'SANT',
            'Label' => 'Jaime Santana Pomares',
        ),
        'SCIT' => array(
            'Id' => 'SCIT',
            'Label' => 'Scitex Corporation, Ltd.',
        ),
        'SCRN' => array(
            'Id' => 'SCRN',
            'Label' => 'Dainippon Screen',
        ),
        'SDP ' => array(
            'Id' => 'SDP ',
            'Label' => 'Scitex Corporation, Ltd.',
        ),
        'SEC ' => array(
            'Id' => 'SEC ',
            'Label' => 'SAMSUNG ELECTRONICS CO.,LTD',
        ),
        'SEIK' => array(
            'Id' => 'SEIK',
            'Label' => 'Seiko Instruments U.S.A., Inc.',
        ),
        'SEIk' => array(
            'Id' => 'SEIk',
            'Label' => 'Seikosha',
        ),
        'SGUY' => array(
            'Id' => 'SGUY',
            'Label' => 'ScanGuy.com',
        ),
        'SHAR' => array(
            'Id' => 'SHAR',
            'Label' => 'Sharp Laboratories',
        ),
        'SICC' => array(
            'Id' => 'SICC',
            'Label' => 'International Color Consortium',
        ),
        'SONY' => array(
            'Id' => 'SONY',
            'Label' => 'SONY Corporation',
        ),
        'SPCL' => array(
            'Id' => 'SPCL',
            'Label' => 'SpectraCal',
        ),
        'STAR' => array(
            'Id' => 'STAR',
            'Label' => 'Star',
        ),
        'STC ' => array(
            'Id' => 'STC ',
            'Label' => 'Sampo Technology Corporation',
        ),
        'Scit' => array(
            'Id' => 'Scit',
            'Label' => 'Scitex Corporation, Ltd.',
        ),
        'Sdp ' => array(
            'Id' => 'Sdp ',
            'Label' => 'Scitex Corporation, Ltd.',
        ),
        'Sony' => array(
            'Id' => 'Sony',
            'Label' => 'Sony Corporation',
        ),
        'TALO' => array(
            'Id' => 'TALO',
            'Label' => 'Talon Technology Corporation',
        ),
        'TAND' => array(
            'Id' => 'TAND',
            'Label' => 'Tandy',
        ),
        'TATU' => array(
            'Id' => 'TATU',
            'Label' => 'Tatung Co. of America, Inc.',
        ),
        'TAXA' => array(
            'Id' => 'TAXA',
            'Label' => 'TAXAN America, Inc.',
        ),
        'TDS ' => array(
            'Id' => 'TDS ',
            'Label' => 'Tokyo Denshi Sekei K.K.',
        ),
        'TECO' => array(
            'Id' => 'TECO',
            'Label' => 'TECO Information Systems, Inc.',
        ),
        'TEGR' => array(
            'Id' => 'TEGR',
            'Label' => 'Tegra',
        ),
        'TEKT' => array(
            'Id' => 'TEKT',
            'Label' => 'Tektronix, Inc.',
        ),
        'TI  ' => array(
            'Id' => 'TI  ',
            'Label' => 'Texas Instruments',
        ),
        'TMKR' => array(
            'Id' => 'TMKR',
            'Label' => 'TypeMaker Ltd.',
        ),
        'TOSB' => array(
            'Id' => 'TOSB',
            'Label' => 'TOSHIBA corp.',
        ),
        'TOSH' => array(
            'Id' => 'TOSH',
            'Label' => 'Toshiba, Inc.',
        ),
        'TOTK' => array(
            'Id' => 'TOTK',
            'Label' => 'TOTOKU ELECTRIC Co., LTD',
        ),
        'TRIU' => array(
            'Id' => 'TRIU',
            'Label' => 'Triumph',
        ),
        'TSBT' => array(
            'Id' => 'TSBT',
            'Label' => 'TOSHIBA TEC CORPORATION',
        ),
        'TTX ' => array(
            'Id' => 'TTX ',
            'Label' => 'TTX Computer Products, Inc.',
        ),
        'TVM ' => array(
            'Id' => 'TVM ',
            'Label' => 'TVM Professional Monitor Corporation',
        ),
        'TW  ' => array(
            'Id' => 'TW  ',
            'Label' => 'TW Casper Corporation',
        ),
        'ULSX' => array(
            'Id' => 'ULSX',
            'Label' => 'Ulead Systems',
        ),
        'UNIS' => array(
            'Id' => 'UNIS',
            'Label' => 'Unisys',
        ),
        'UTZF' => array(
            'Id' => 'UTZF',
            'Label' => 'Utz Fehlau & Sohn',
        ),
        'VARI' => array(
            'Id' => 'VARI',
            'Label' => 'Varityper',
        ),
        'VIEW' => array(
            'Id' => 'VIEW',
            'Label' => 'Viewsonic',
        ),
        'VISL' => array(
            'Id' => 'VISL',
            'Label' => 'Visual communication',
        ),
        'VIVO' => array(
            'Id' => 'VIVO',
            'Label' => 'Vivo Mobile Communication Co., Ltd',
        ),
        'WANG' => array(
            'Id' => 'WANG',
            'Label' => 'Wang',
        ),
        'WLBR' => array(
            'Id' => 'WLBR',
            'Label' => 'Wilbur Imaging',
        ),
        'WTG2' => array(
            'Id' => 'WTG2',
            'Label' => 'Ware To Go',
        ),
        'WYSE' => array(
            'Id' => 'WYSE',
            'Label' => 'WYSE Technology',
        ),
        'XERX' => array(
            'Id' => 'XERX',
            'Label' => 'Xerox Corporation',
        ),
        'XRIT' => array(
            'Id' => 'XRIT',
            'Label' => 'X-Rite',
        ),
        'Z123' => array(
            'Id' => 'Z123',
            'Label' => 'Lavanya\'s test Company',
        ),
        'ZRAN' => array(
            'Id' => 'ZRAN',
            'Label' => 'Zoran Corporation',
        ),
        'Zebr' => array(
            'Id' => 'Zebr',
            'Label' => 'Zebra Technologies Inc',
        ),
        'appl' => array(
            'Id' => 'appl',
            'Label' => 'Apple Computer Inc.',
        ),
        'bICC' => array(
            'Id' => 'bICC',
            'Label' => 'basICColor GmbH',
        ),
        'berg' => array(
            'Id' => 'berg',
            'Label' => 'bergdesign incorporated',
        ),
        'ceyd' => array(
            'Id' => 'ceyd',
            'Label' => 'Integrated Color Solutions, Inc.',
        ),
        'clsp' => array(
            'Id' => 'clsp',
            'Label' => 'MacDermid ColorSpan, Inc.',
        ),
        'ds  ' => array(
            'Id' => 'ds  ',
            'Label' => 'Dainippon Screen',
        ),
        'dupn' => array(
            'Id' => 'dupn',
            'Label' => 'DuPont',
        ),
        'ffei' => array(
            'Id' => 'ffei',
            'Label' => 'FujiFilm Electronic Imaging, Ltd.',
        ),
        'flux' => array(
            'Id' => 'flux',
            'Label' => 'FluxData Corporation',
        ),
        'iris' => array(
            'Id' => 'iris',
            'Label' => 'Scitex Corporation, Ltd.',
        ),
        'kart' => array(
            'Id' => 'kart',
            'Label' => 'Scitex Corporation, Ltd.',
        ),
        'lcms' => array(
            'Id' => 'lcms',
            'Label' => 'Little CMS',
        ),
        'lino' => array(
            'Id' => 'lino',
            'Label' => 'Linotronic',
        ),
        'none' => array(
            'Id' => 'none',
            'Label' => 'none',
        ),
        'ob4d' => array(
            'Id' => 'ob4d',
            'Label' => 'Erdt Systems GmbH & Co KG',
        ),
        'obic' => array(
            'Id' => 'obic',
            'Label' => 'Medigraph GmbH',
        ),
        'quby' => array(
            'Id' => 'quby',
            'Label' => 'Qubyx Sarl',
        ),
        'scit' => array(
            'Id' => 'scit',
            'Label' => 'Scitex Corporation, Ltd.',
        ),
        'scrn' => array(
            'Id' => 'scrn',
            'Label' => 'Dainippon Screen',
        ),
        'sdp ' => array(
            'Id' => 'sdp ',
            'Label' => 'Scitex Corporation, Ltd.',
        ),
        'siwi' => array(
            'Id' => 'siwi',
            'Label' => 'SIWI GRAFIKA CORPORATION',
        ),
        'yxym' => array(
            'Id' => 'yxym',
            'Label' => 'YxyMaster GmbH',
        ),
    );

}
