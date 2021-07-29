<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Sony;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class LensType3 extends AbstractTag
{

    protected $Id = 9;

    protected $Name = 'LensType3';

    protected $FullName = 'Sony::Tag940c';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Image';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Lens Type 3';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Unknown E-mount lens or other lens',
        ),
        '0.1' => array(
            'Id' => '0.1',
            'Label' => 'Sigma 19mm F2.8 [EX] DN',
        ),
        '0.2' => array(
            'Id' => '0.2',
            'Label' => 'Sigma 30mm F2.8 [EX] DN',
        ),
        '0.3' => array(
            'Id' => '0.3',
            'Label' => 'Sigma 60mm F2.8 DN',
        ),
        '0.4' => array(
            'Id' => '0.4',
            'Label' => 'Sony E 18-200mm F3.5-6.3 OSS LE',
        ),
        '0.5' => array(
            'Id' => '0.5',
            'Label' => 'Tamron 18-200mm F3.5-6.3 Di III VC',
        ),
        '0.6' => array(
            'Id' => '0.6',
            'Label' => 'Tokina FiRIN 20mm F2 FE AF',
        ),
        '0.7' => array(
            'Id' => '0.7',
            'Label' => 'Tokina FiRIN 20mm F2 FE MF',
        ),
        '0.8' => array(
            'Id' => '0.8',
            'Label' => 'Zeiss Touit 12mm F2.8',
        ),
        '0.9' => array(
            'Id' => '0.9',
            'Label' => 'Zeiss Touit 32mm F1.8',
        ),
        '0.10' => array(
            'Id' => '0.10',
            'Label' => 'Zeiss Touit 50mm F2.8 Macro',
        ),
        '0.11' => array(
            'Id' => '0.11',
            'Label' => 'Zeiss Loxia 50mm F2',
        ),
        '0.12' => array(
            'Id' => '0.12',
            'Label' => 'Zeiss Loxia 35mm F2',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Sony LA-EA1 or Sigma MC-11 Adapter',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Sony LA-EA2 Adapter',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Sony LA-EA3 Adapter',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Sony LA-EA4 Adapter',
        ),
        44 => array(
            'Id' => 44,
            'Label' => 'Metabones Canon EF Smart Adapter',
        ),
        78 => array(
            'Id' => 78,
            'Label' => 'Metabones Canon EF Smart Adapter Mark III or Other Adapter',
        ),
        184 => array(
            'Id' => 184,
            'Label' => 'Metabones Canon EF Speed Booster Ultra',
        ),
        234 => array(
            'Id' => 234,
            'Label' => 'Metabones Canon EF Smart Adapter Mark IV',
        ),
        239 => array(
            'Id' => 239,
            'Label' => 'Metabones Canon EF Speed Booster',
        ),
        32784 => array(
            'Id' => 32784,
            'Label' => 'Sony E 16mm F2.8',
        ),
        32785 => array(
            'Id' => 32785,
            'Label' => 'Sony E 18-55mm F3.5-5.6 OSS',
        ),
        32786 => array(
            'Id' => 32786,
            'Label' => 'Sony E 55-210mm F4.5-6.3 OSS',
        ),
        32787 => array(
            'Id' => 32787,
            'Label' => 'Sony E 18-200mm F3.5-6.3 OSS',
        ),
        32788 => array(
            'Id' => 32788,
            'Label' => 'Sony E 30mm F3.5 Macro',
        ),
        32789 => array(
            'Id' => 32789,
            'Label' => 'Sony E 24mm F1.8 ZA or Samyang AF 50mm F1.4',
        ),
        '32789.1' => array(
            'Id' => '32789.1',
            'Label' => 'Samyang AF 50mm F1.4',
        ),
        32790 => array(
            'Id' => 32790,
            'Label' => 'Sony E 50mm F1.8 OSS or Samyang AF 14mm F2.8',
        ),
        '32790.1' => array(
            'Id' => '32790.1',
            'Label' => 'Samyang AF 14mm F2.8',
        ),
        32791 => array(
            'Id' => 32791,
            'Label' => 'Sony E 16-70mm F4 ZA OSS',
        ),
        32792 => array(
            'Id' => 32792,
            'Label' => 'Sony E 10-18mm F4 OSS',
        ),
        32793 => array(
            'Id' => 32793,
            'Label' => 'Sony E PZ 16-50mm F3.5-5.6 OSS',
        ),
        32794 => array(
            'Id' => 32794,
            'Label' => 'Sony FE 35mm F2.8 ZA or Samyang Lens',
        ),
        '32794.1' => array(
            'Id' => '32794.1',
            'Label' => 'Samyang AF 24mm F2.8',
        ),
        '32794.2' => array(
            'Id' => '32794.2',
            'Label' => 'Samyang AF 35mm F2.8',
        ),
        32795 => array(
            'Id' => 32795,
            'Label' => 'Sony FE 24-70mm F4 ZA OSS',
        ),
        32796 => array(
            'Id' => 32796,
            'Label' => 'Sony FE 85mm F1.8 or Viltrox PFU RBMH 85mm F1.8',
        ),
        '32796.1' => array(
            'Id' => '32796.1',
            'Label' => 'Viltrox PFU RBMH 85mm F1.8',
        ),
        32797 => array(
            'Id' => 32797,
            'Label' => 'Sony E 18-200mm F3.5-6.3 OSS LE',
        ),
        32798 => array(
            'Id' => 32798,
            'Label' => 'Sony E 20mm F2.8',
        ),
        32799 => array(
            'Id' => 32799,
            'Label' => 'Sony E 35mm F1.8 OSS',
        ),
        32800 => array(
            'Id' => 32800,
            'Label' => 'Sony E PZ 18-105mm F4 G OSS',
        ),
        32801 => array(
            'Id' => 32801,
            'Label' => 'Sony FE 12-24mm F4 G',
        ),
        32802 => array(
            'Id' => 32802,
            'Label' => 'Sony FE 90mm F2.8 Macro G OSS',
        ),
        32803 => array(
            'Id' => 32803,
            'Label' => 'Sony E 18-50mm F4-5.6',
        ),
        32804 => array(
            'Id' => 32804,
            'Label' => 'Sony FE 24mm F1.4 GM',
        ),
        32805 => array(
            'Id' => 32805,
            'Label' => 'Sony FE 24-105mm F4 G OSS',
        ),
        32807 => array(
            'Id' => 32807,
            'Label' => 'Sony E PZ 18-200mm F3.5-6.3 OSS',
        ),
        32808 => array(
            'Id' => 32808,
            'Label' => 'Sony FE 55mm F1.8 ZA',
        ),
        32810 => array(
            'Id' => 32810,
            'Label' => 'Sony FE 70-200mm F4 G OSS',
        ),
        32811 => array(
            'Id' => 32811,
            'Label' => 'Sony FE 16-35mm F4 ZA OSS',
        ),
        32812 => array(
            'Id' => 32812,
            'Label' => 'Sony FE 50mm F2.8 Macro',
        ),
        32813 => array(
            'Id' => 32813,
            'Label' => 'Sony FE 28-70mm F3.5-5.6 OSS',
        ),
        32814 => array(
            'Id' => 32814,
            'Label' => 'Sony FE 35mm F1.4 ZA',
        ),
        32815 => array(
            'Id' => 32815,
            'Label' => 'Sony FE 24-240mm F3.5-6.3 OSS',
        ),
        32816 => array(
            'Id' => 32816,
            'Label' => 'Sony FE 28mm F2',
        ),
        32817 => array(
            'Id' => 32817,
            'Label' => 'Sony FE PZ 28-135mm F4 G OSS',
        ),
        32819 => array(
            'Id' => 32819,
            'Label' => 'Sony FE 100mm F2.8 STF GM OSS',
        ),
        32820 => array(
            'Id' => 32820,
            'Label' => 'Sony E PZ 18-110mm F4 G OSS',
        ),
        32821 => array(
            'Id' => 32821,
            'Label' => 'Sony FE 24-70mm F2.8 GM',
        ),
        32822 => array(
            'Id' => 32822,
            'Label' => 'Sony FE 50mm F1.4 ZA',
        ),
        32823 => array(
            'Id' => 32823,
            'Label' => 'Sony FE 85mm F1.4 GM or Samyang AF 85mm F1.4',
        ),
        '32823.1' => array(
            'Id' => '32823.1',
            'Label' => 'Samyang AF 85mm F1.4',
        ),
        32824 => array(
            'Id' => 32824,
            'Label' => 'Sony FE 50mm F1.8',
        ),
        32826 => array(
            'Id' => 32826,
            'Label' => 'Sony FE 21mm F2.8 (SEL28F20 + SEL075UWC)',
        ),
        32827 => array(
            'Id' => 32827,
            'Label' => 'Sony FE 16mm F3.5 Fisheye (SEL28F20 + SEL057FEC)',
        ),
        32828 => array(
            'Id' => 32828,
            'Label' => 'Sony FE 70-300mm F4.5-5.6 G OSS',
        ),
        32829 => array(
            'Id' => 32829,
            'Label' => 'Sony FE 100-400mm F4.5-5.6 GM OSS',
        ),
        32830 => array(
            'Id' => 32830,
            'Label' => 'Sony FE 70-200mm F2.8 GM OSS',
        ),
        32831 => array(
            'Id' => 32831,
            'Label' => 'Sony FE 16-35mm F2.8 GM',
        ),
        32848 => array(
            'Id' => 32848,
            'Label' => 'Sony FE 400mm F2.8 GM OSS',
        ),
        32849 => array(
            'Id' => 32849,
            'Label' => 'Sony E 18-135mm F3.5-5.6 OSS',
        ),
        32850 => array(
            'Id' => 32850,
            'Label' => 'Sony FE 135mm F1.8 GM',
        ),
        32851 => array(
            'Id' => 32851,
            'Label' => 'Sony FE 200-600mm F5.6-6.3 G OSS',
        ),
        32852 => array(
            'Id' => 32852,
            'Label' => 'Sony FE 600mm F4 GM OSS',
        ),
        32853 => array(
            'Id' => 32853,
            'Label' => 'Sony E 16-55mm F2.8 G',
        ),
        32854 => array(
            'Id' => 32854,
            'Label' => 'Sony E 70-350mm F4.5-6.3 G OSS',
        ),
        32858 => array(
            'Id' => 32858,
            'Label' => 'Sony FE 35mm F1.8',
        ),
        33072 => array(
            'Id' => 33072,
            'Label' => 'Sony FE 70-200mm F2.8 GM OSS + 1.4X Teleconverter',
        ),
        33073 => array(
            'Id' => 33073,
            'Label' => 'Sony FE 70-200mm F2.8 GM OSS + 2X Teleconverter',
        ),
        33076 => array(
            'Id' => 33076,
            'Label' => 'Sony FE 100mm F2.8 STF GM OSS (macro mode)',
        ),
        33077 => array(
            'Id' => 33077,
            'Label' => 'Sony FE 100-400mm F4.5-5.6 GM OSS + 1.4X Teleconverter',
        ),
        33078 => array(
            'Id' => 33078,
            'Label' => 'Sony FE 100-400mm F4.5-5.6 GM OSS + 2X Teleconverter',
        ),
        33079 => array(
            'Id' => 33079,
            'Label' => 'Sony FE 400mm F2.8 GM OSS + 1.4X Teleconverter',
        ),
        33080 => array(
            'Id' => 33080,
            'Label' => 'Sony FE 400mm F2.8 GM OSS + 2X Teleconverter',
        ),
        33081 => array(
            'Id' => 33081,
            'Label' => 'Sony FE 200-600mm F5.6-6.3 G OSS + 1.4X Teleconverter',
        ),
        33082 => array(
            'Id' => 33082,
            'Label' => 'Sony FE 200-600mm F5.6-6.3 G OSS + 2X Teleconverter',
        ),
        33083 => array(
            'Id' => 33083,
            'Label' => 'Sony FE 600mm F4 GM OSS + 1.4X Teleconverter',
        ),
        33084 => array(
            'Id' => 33084,
            'Label' => 'Sony FE 600mm F4 GM OSS + 2X Teleconverter',
        ),
        49201 => array(
            'Id' => 49201,
            'Label' => 'Zeiss Touit 12mm F2.8',
        ),
        49202 => array(
            'Id' => 49202,
            'Label' => 'Zeiss Touit 32mm F1.8',
        ),
        49203 => array(
            'Id' => 49203,
            'Label' => 'Zeiss Touit 50mm F2.8 Macro',
        ),
        49216 => array(
            'Id' => 49216,
            'Label' => 'Zeiss Batis 25mm F2',
        ),
        49217 => array(
            'Id' => 49217,
            'Label' => 'Zeiss Batis 85mm F1.8',
        ),
        49218 => array(
            'Id' => 49218,
            'Label' => 'Zeiss Batis 18mm F2.8',
        ),
        49219 => array(
            'Id' => 49219,
            'Label' => 'Zeiss Batis 135mm F2.8',
        ),
        49220 => array(
            'Id' => 49220,
            'Label' => 'Zeiss Batis 40mm F2 CF',
        ),
        49232 => array(
            'Id' => 49232,
            'Label' => 'Zeiss Loxia 50mm F2',
        ),
        49233 => array(
            'Id' => 49233,
            'Label' => 'Zeiss Loxia 35mm F2',
        ),
        49234 => array(
            'Id' => 49234,
            'Label' => 'Zeiss Loxia 21mm F2.8',
        ),
        49235 => array(
            'Id' => 49235,
            'Label' => 'Zeiss Loxia 85mm F2.4',
        ),
        49236 => array(
            'Id' => 49236,
            'Label' => 'Zeiss Loxia 25mm F2.4',
        ),
        49457 => array(
            'Id' => 49457,
            'Label' => 'Tamron 28-75mm F2.8 Di III RXD',
        ),
        49458 => array(
            'Id' => 49458,
            'Label' => 'Tamron 17-28mm F2.8 Di III RXD',
        ),
        49459 => array(
            'Id' => 49459,
            'Label' => 'Tamron 35mm F2.8 Di III OSD M1:2',
        ),
        49460 => array(
            'Id' => 49460,
            'Label' => 'Tamron 24mm F2.8 Di III OSD M1:2',
        ),
        49712 => array(
            'Id' => 49712,
            'Label' => 'Tokina FiRIN 20mm F2 FE AF',
        ),
        49713 => array(
            'Id' => 49713,
            'Label' => 'Tokina FiRIN 100mm F2.8 FE MACRO',
        ),
        50480 => array(
            'Id' => 50480,
            'Label' => 'Sigma 30mm F1.4 DC DN | C',
        ),
        50481 => array(
            'Id' => 50481,
            'Label' => 'Sigma 50mm F1.4 DG HSM | A',
        ),
        50482 => array(
            'Id' => 50482,
            'Label' => 'Sigma 18-300mm F3.5-6.3 DC MACRO OS HSM | C + MC-11',
        ),
        50483 => array(
            'Id' => 50483,
            'Label' => 'Sigma 18-35mm F1.8 DC HSM | A + MC-11',
        ),
        50484 => array(
            'Id' => 50484,
            'Label' => 'Sigma 24-35mm F2 DG HSM | A + MC-11',
        ),
        50485 => array(
            'Id' => 50485,
            'Label' => 'Sigma 24mm F1.4 DG HSM | A + MC-11',
        ),
        50486 => array(
            'Id' => 50486,
            'Label' => 'Sigma 150-600mm F5-6.3 DG OS HSM | C + MC-11',
        ),
        50487 => array(
            'Id' => 50487,
            'Label' => 'Sigma 20mm F1.4 DG HSM | A + MC-11',
        ),
        50488 => array(
            'Id' => 50488,
            'Label' => 'Sigma 35mm F1.4 DG HSM | A',
        ),
        50489 => array(
            'Id' => 50489,
            'Label' => 'Sigma 150-600mm F5-6.3 DG OS HSM | S + MC-11',
        ),
        50490 => array(
            'Id' => 50490,
            'Label' => 'Sigma 120-300mm F2.8 DG OS HSM | S + MC-11',
        ),
        50492 => array(
            'Id' => 50492,
            'Label' => 'Sigma 24-105mm F4 DG OS HSM | A + MC-11',
        ),
        50493 => array(
            'Id' => 50493,
            'Label' => 'Sigma 17-70mm F2.8-4 DC MACRO OS HSM | C + MC-11',
        ),
        50495 => array(
            'Id' => 50495,
            'Label' => 'Sigma 50-100mm F1.8 DC HSM | A + MC-11',
        ),
        50499 => array(
            'Id' => 50499,
            'Label' => 'Sigma 85mm F1.4 DG HSM | A',
        ),
        50501 => array(
            'Id' => 50501,
            'Label' => 'Sigma 100-400mm F5-6.3 DG OS HSM | C + MC-11',
        ),
        50503 => array(
            'Id' => 50503,
            'Label' => 'Sigma 16mm F1.4 DC DN | C',
        ),
        50507 => array(
            'Id' => 50507,
            'Label' => 'Sigma 105mm F1.4 DG HSM | A',
        ),
        50508 => array(
            'Id' => 50508,
            'Label' => 'Sigma 56mm F1.4 DC DN | C',
        ),
        50512 => array(
            'Id' => 50512,
            'Label' => 'Sigma 70-200mm F2.8 DG OS HSM | S + MC-11',
        ),
        50513 => array(
            'Id' => 50513,
            'Label' => 'Sigma 70mm F2.8 DG MACRO | A',
        ),
        50514 => array(
            'Id' => 50514,
            'Label' => 'Sigma 45mm F2.8 DG DN | C',
        ),
        50515 => array(
            'Id' => 50515,
            'Label' => 'Sigma 35mm F1.2 DG DN | A',
        ),
        50516 => array(
            'Id' => 50516,
            'Label' => 'Sigma 14-24mm F2.8 DG DN | A',
        ),
        50992 => array(
            'Id' => 50992,
            'Label' => 'Voigtlander SUPER WIDE-HELIAR 15mm F4.5 III',
        ),
        50993 => array(
            'Id' => 50993,
            'Label' => 'Voigtlander HELIAR-HYPER WIDE 10mm F5.6',
        ),
        50994 => array(
            'Id' => 50994,
            'Label' => 'Voigtlander ULTRA WIDE-HELIAR 12mm F5.6 III',
        ),
        50995 => array(
            'Id' => 50995,
            'Label' => 'Voigtlander MACRO APO-LANTHAR 65mm F2 Aspherical',
        ),
        50996 => array(
            'Id' => 50996,
            'Label' => 'Voigtlander NOKTON 40mm F1.2 Aspherical',
        ),
        50997 => array(
            'Id' => 50997,
            'Label' => 'Voigtlander NOKTON classic 35mm F1.4',
        ),
        50998 => array(
            'Id' => 50998,
            'Label' => 'Voigtlander MACRO APO-LANTHAR 110mm F2.5',
        ),
        50999 => array(
            'Id' => 50999,
            'Label' => 'Voigtlander COLOR-SKOPAR 21mm F3.5 Aspherical',
        ),
        51000 => array(
            'Id' => 51000,
            'Label' => 'Voigtlander NOKTON 50mm F1.2 Aspherical',
        ),
        51001 => array(
            'Id' => 51001,
            'Label' => 'Voigtlander NOKTON 21mm F1.4 Aspherical',
        ),
        51002 => array(
            'Id' => 51002,
            'Label' => 'Voigtlander APO-LANTHAR 50mm F2 Aspherical',
        ),
        51504 => array(
            'Id' => 51504,
            'Label' => 'Samyang AF 50mm F1.4',
        ),
        51505 => array(
            'Id' => 51505,
            'Label' => 'Samyang AF 14mm F2.8 or Samyang AF 35mm F2.8',
        ),
        '51505.1' => array(
            'Id' => '51505.1',
            'Label' => 'Samyang AF 35mm F2.8',
        ),
        51507 => array(
            'Id' => 51507,
            'Label' => 'Samyang AF 35mm F1.4',
        ),
        51508 => array(
            'Id' => 51508,
            'Label' => 'Samyang AF 45mm F1.8',
        ),
        51510 => array(
            'Id' => 51510,
            'Label' => 'Samyang AF 18mm F2.8',
        ),
    );

}
