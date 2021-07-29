<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\SigmaRaw;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class LensType extends AbstractTag
{

    protected $Id = 'LENSMODEL';

    protected $Name = 'LensType';

    protected $FullName = 'SigmaRaw::Properties';

    protected $GroupName = 'SigmaRaw';

    protected $g0 = 'SigmaRaw';

    protected $g1 = 'SigmaRaw';

    protected $g2 = 'Camera';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Lens Type';

    protected $Values = array(
        16 => array(
            'Id' => 16,
            'Label' => 'Sigma 50mm F2.8 EX DG MACRO',
        ),
        '16.1' => array(
            'Id' => '16.1',
            'Label' => 'Sigma 70mm F2.8 EX DG Macro',
        ),
        '16.2' => array(
            'Id' => '16.2',
            'Label' => 'Sigma 105mm F2.8 EX DG Macro',
        ),
        22 => array(
            'Id' => 22,
            'Label' => 'Sigma 18-50mm F3.5-5.6 DC',
        ),
        259 => array(
            'Id' => 259,
            'Label' => 'Sigma 180mm F3.5 EX IF HSM APO Macro',
        ),
        260 => array(
            'Id' => 260,
            'Label' => 'Sigma 150mm F2.8 EX DG HSM APO Macro',
        ),
        261 => array(
            'Id' => 261,
            'Label' => 'Sigma 180mm F3.5 EX DG HSM APO Macro',
        ),
        262 => array(
            'Id' => 262,
            'Label' => 'Sigma 150mm F2.8 EX DG OS HSM APO Macro',
        ),
        263 => array(
            'Id' => 263,
            'Label' => 'Sigma 180mm F2.8 EX DG OS HSM APO Macro',
        ),
        297 => array(
            'Id' => 297,
            'Label' => 'Sigma Lens (0x129)',
        ),
        '297.1' => array(
            'Id' => '297.1',
            'Label' => 'Sigma 14mm F2.8 EX Aspherical',
        ),
        '297.2' => array(
            'Id' => '297.2',
            'Label' => 'Sigma 30mm F1.4',
        ),
        305 => array(
            'Id' => 305,
            'Label' => 'Sigma Lens (0x131)',
        ),
        '305.1' => array(
            'Id' => '305.1',
            'Label' => 'Sigma 17-70mm F2.8-4.5 DC Macro',
        ),
        '305.2' => array(
            'Id' => '305.2',
            'Label' => 'Sigma 70-200mm F2.8 APO EX HSM',
        ),
        '305.3' => array(
            'Id' => '305.3',
            'Label' => 'Sigma 120-300mm F2.8 APO EX IF HSM',
        ),
        308 => array(
            'Id' => 308,
            'Label' => 'Sigma 100-300mm F4 EX DG HSM APO',
        ),
        309 => array(
            'Id' => 309,
            'Label' => 'Sigma 120-300mm F2.8 EX DG HSM APO',
        ),
        310 => array(
            'Id' => 310,
            'Label' => 'Sigma 120-300mm F2.8 EX DG OS HSM APO',
        ),
        311 => array(
            'Id' => 311,
            'Label' => 'Sigma 120-300mm F2.8 DG OS HSM | S',
        ),
        323 => array(
            'Id' => 323,
            'Label' => 'Sigma 600mm F8 Mirror',
        ),
        325 => array(
            'Id' => 325,
            'Label' => 'Sigma Lens (0x145)',
        ),
        '325.1' => array(
            'Id' => '325.1',
            'Label' => 'Sigma 15-30mm F3.5-4.5 EX DG Aspherical',
        ),
        '325.2' => array(
            'Id' => '325.2',
            'Label' => 'Sigma 18-50mm F2.8 EX DG',
        ),
        '325.3' => array(
            'Id' => '325.3',
            'Label' => 'Sigma 20-40mm F2.8 EX DG',
        ),
        336 => array(
            'Id' => 336,
            'Label' => 'Sigma 30mm F1.4 DC HSM',
        ),
        338 => array(
            'Id' => 338,
            'Label' => 'Sigma Lens (0x152)',
        ),
        '338.1' => array(
            'Id' => '338.1',
            'Label' => 'Sigma APO 800mm F5.6 EX DG HSM',
        ),
        '338.2' => array(
            'Id' => '338.2',
            'Label' => 'Sigma 12-24mm F4.5-5.6 EX DG ASP HSM',
        ),
        '338.3' => array(
            'Id' => '338.3',
            'Label' => 'Sigma 10-20mm F4-5.6 EX DC HSM',
        ),
        357 => array(
            'Id' => 357,
            'Label' => 'Sigma 70-200mm F2.8 EX',
        ),
        361 => array(
            'Id' => 361,
            'Label' => 'Sigma 18-50mm F2.8 EX DC',
        ),
        387 => array(
            'Id' => 387,
            'Label' => 'Sigma 500mm F4.5 EX HSM APO',
        ),
        388 => array(
            'Id' => 388,
            'Label' => 'Sigma 500mm F4.5 EX DG HSM APO',
        ),
        389 => array(
            'Id' => 389,
            'Label' => 'Sigma 500mm F4 DG OS HSM | S',
        ),
        404 => array(
            'Id' => 404,
            'Label' => 'Sigma 300mm F2.8 EX HSM APO',
        ),
        405 => array(
            'Id' => 405,
            'Label' => 'Sigma 300mm F2.8 EX DG HSM APO',
        ),
        512 => array(
            'Id' => 512,
            'Label' => 'Sigma 12-24mm F4.5-5.6 EX DG ASP HSM',
        ),
        513 => array(
            'Id' => 513,
            'Label' => 'Sigma 10-20mm F4-5.6 EX DC HSM',
        ),
        514 => array(
            'Id' => 514,
            'Label' => 'Sigma 10-20mm F3.5 EX DC HSM',
        ),
        515 => array(
            'Id' => 515,
            'Label' => 'Sigma 8-16mm F4.5-5.6 DC HSM',
        ),
        516 => array(
            'Id' => 516,
            'Label' => 'Sigma 12-24mm F4.5-5.6 DG HSM II',
        ),
        517 => array(
            'Id' => 517,
            'Label' => 'Sigma 12-24mm F4 DG HSM | A',
        ),
        528 => array(
            'Id' => 528,
            'Label' => 'Sigma 18-35mm F1.8 DC HSM | A',
        ),
        576 => array(
            'Id' => 576,
            'Label' => 'Sigma 135mm F1.8 DG HSM | A',
        ),
        598 => array(
            'Id' => 598,
            'Label' => 'Sigma 105mm F2.8 EX Macro',
        ),
        599 => array(
            'Id' => 599,
            'Label' => 'Sigma 105mm F2.8 EX DG Macro',
        ),
        600 => array(
            'Id' => 600,
            'Label' => 'Sigma 105mm F2.8 EX DG OS HSM Macro',
        ),
        601 => array(
            'Id' => 601,
            'Label' => 'Sigma 105mm F1.4 DG HSM | A',
        ),
        624 => array(
            'Id' => 624,
            'Label' => 'Sigma 70mm F2.8 EX DG Macro',
        ),
        625 => array(
            'Id' => 625,
            'Label' => 'Sigma 70mm F2.8 DG Macro | A',
        ),
        768 => array(
            'Id' => 768,
            'Label' => 'Sigma 30mm F1.4 EX DC HSM',
        ),
        769 => array(
            'Id' => 769,
            'Label' => 'Sigma 30mm F1.4 DC HSM | A',
        ),
        770 => array(
            'Id' => 770,
            'Label' => 'Sigma 30mm F1.4 DC DN | C',
        ),
        784 => array(
            'Id' => 784,
            'Label' => 'Sigma 50mm F1.4 EX DG HSM',
        ),
        785 => array(
            'Id' => 785,
            'Label' => 'Sigma 50mm F1.4 DG HSM | A',
        ),
        800 => array(
            'Id' => 800,
            'Label' => 'Sigma 85mm F1.4 EX DG HSM',
        ),
        801 => array(
            'Id' => 801,
            'Label' => 'Sigma 85mm F1.4 DG HSM | A',
        ),
        816 => array(
            'Id' => 816,
            'Label' => 'Sigma 30mm F2.8 EX DN',
        ),
        832 => array(
            'Id' => 832,
            'Label' => 'Sigma 35mm F1.4 DG HSM',
        ),
        837 => array(
            'Id' => 837,
            'Label' => 'Sigma 50mm F2.8 EX Macro',
        ),
        838 => array(
            'Id' => 838,
            'Label' => 'Sigma 50mm F2.8 EX DG Macro',
        ),
        848 => array(
            'Id' => 848,
            'Label' => 'Sigma 60mm F2.8 DN | A',
        ),
        1024 => array(
            'Id' => 1024,
            'Label' => 'Sigma 19mm F2.8 EX DN',
        ),
        1025 => array(
            'Id' => 1025,
            'Label' => 'Sigma 24mm F1.4 DG HSM | A',
        ),
        1041 => array(
            'Id' => 1041,
            'Label' => 'Sigma 20mm F1.8 EX DG ASP RF',
        ),
        1042 => array(
            'Id' => 1042,
            'Label' => 'Sigma 20mm F1.4 DG HSM | A',
        ),
        1074 => array(
            'Id' => 1074,
            'Label' => 'Sigma 24mm F1.8 EX DG ASP Macro',
        ),
        1088 => array(
            'Id' => 1088,
            'Label' => 'Sigma 28mm F1.8 EX DG ASP Macro',
        ),
        1104 => array(
            'Id' => 1104,
            'Label' => 'Sigma 14mm F1.8 DH HSM | A',
        ),
        1121 => array(
            'Id' => 1121,
            'Label' => 'Sigma 14mm F2.8 EX ASP HSM',
        ),
        1141 => array(
            'Id' => 1141,
            'Label' => 'Sigma 15mm F2.8 EX Diagonal FishEye',
        ),
        1142 => array(
            'Id' => 1142,
            'Label' => 'Sigma 15mm F2.8 EX DG Diagonal Fisheye',
        ),
        1143 => array(
            'Id' => 1143,
            'Label' => 'Sigma 10mm F2.8 EX DC HSM Fisheye',
        ),
        1155 => array(
            'Id' => 1155,
            'Label' => 'Sigma 8mm F4 EX Circular Fisheye',
        ),
        1156 => array(
            'Id' => 1156,
            'Label' => 'Sigma 8mm F4 EX DG Circular Fisheye',
        ),
        1157 => array(
            'Id' => 1157,
            'Label' => 'Sigma 8mm F3.5 EX DG Circular Fisheye',
        ),
        1158 => array(
            'Id' => 1158,
            'Label' => 'Sigma 4.5mm F2.8 EX DC HSM Circular Fisheye',
        ),
        1284 => array(
            'Id' => 1284,
            'Label' => 'Sigma 70-300mm F4-5.6 Macro Super',
        ),
        1285 => array(
            'Id' => 1285,
            'Label' => 'Sigma APO 70-300mm F4-5.6 Macro Super',
        ),
        1286 => array(
            'Id' => 1286,
            'Label' => 'Sigma 70-300mm F4-5.6 APO Macro Super II',
        ),
        1287 => array(
            'Id' => 1287,
            'Label' => 'Sigma 70-300mm F4-5.6 DL Macro Super II',
        ),
        1288 => array(
            'Id' => 1288,
            'Label' => 'Sigma 70-300mm F4-5.6 DG APO Macro',
        ),
        1289 => array(
            'Id' => 1289,
            'Label' => 'Sigma 70-300mm F4-5.6 DG Macro',
        ),
        1296 => array(
            'Id' => 1296,
            'Label' => 'Sigma 17-35 F2.8-4 EX DG ASP',
        ),
        1298 => array(
            'Id' => 1298,
            'Label' => 'Sigma 15-30mm F3.5-4.5 EX DG ASP DF',
        ),
        1299 => array(
            'Id' => 1299,
            'Label' => 'Sigma 20-40mm F2.8 EX DG',
        ),
        1305 => array(
            'Id' => 1305,
            'Label' => 'Sigma 17-35 F2.8-4 EX ASP HSM',
        ),
        1312 => array(
            'Id' => 1312,
            'Label' => 'Sigma 100-300mm F4.5-6.7 DL',
        ),
        1313 => array(
            'Id' => 1313,
            'Label' => 'Sigma 18-50mm F3.5-5.6 DC Macro',
        ),
        1319 => array(
            'Id' => 1319,
            'Label' => 'Sigma 100-300mm F4 EX IF HSM',
        ),
        1321 => array(
            'Id' => 1321,
            'Label' => 'Sigma 120-300mm F2.8 EX HSM IF APO',
        ),
        1349 => array(
            'Id' => 1349,
            'Label' => 'Sigma 28-70mm F2.8 EX ASP DF',
        ),
        1351 => array(
            'Id' => 1351,
            'Label' => 'Sigma 24-60mm F2.8 EX DG',
        ),
        1352 => array(
            'Id' => 1352,
            'Label' => 'Sigma 24-70mm F2.8 EX DG Macro',
        ),
        1353 => array(
            'Id' => 1353,
            'Label' => 'Sigma 28-70mm F2.8 EX DG',
        ),
        1382 => array(
            'Id' => 1382,
            'Label' => 'Sigma 70-200mm F2.8 EX IF APO',
        ),
        1383 => array(
            'Id' => 1383,
            'Label' => 'Sigma 70-200mm F2.8 EX IF HSM APO',
        ),
        1384 => array(
            'Id' => 1384,
            'Label' => 'Sigma 70-200mm F2.8 EX DG IF HSM APO',
        ),
        1385 => array(
            'Id' => 1385,
            'Label' => 'Sigma 70-200 F2.8 EX DG HSM APO Macro',
        ),
        1393 => array(
            'Id' => 1393,
            'Label' => 'Sigma 24-70mm F2.8 IF EX DG HSM',
        ),
        1394 => array(
            'Id' => 1394,
            'Label' => 'Sigma 70-300mm F4-5.6 DG OS',
        ),
        1398 => array(
            'Id' => 1398,
            'Label' => 'Sigma 24-70mm F2.8 DG OS HSM | A',
        ),
        1401 => array(
            'Id' => 1401,
            'Label' => 'Sigma 70-200mm F2.8 EX DG HSM APO Macro',
        ),
        1408 => array(
            'Id' => 1408,
            'Label' => 'Sigma 18-50mm F2.8 EX DC',
        ),
        1409 => array(
            'Id' => 1409,
            'Label' => 'Sigma 18-50mm F2.8 EX DC Macro',
        ),
        1410 => array(
            'Id' => 1410,
            'Label' => 'Sigma 18-50mm F2.8 EX DC HSM Macro',
        ),
        1411 => array(
            'Id' => 1411,
            'Label' => 'Sigma 17-50mm F2.8 EX DC OS HSM',
        ),
        1416 => array(
            'Id' => 1416,
            'Label' => 'Sigma 24-35mm F2 DG HSM | A',
        ),
        1417 => array(
            'Id' => 1417,
            'Label' => 'Sigma APO 70-200mm F2.8 EX DG OS HSM',
        ),
        1428 => array(
            'Id' => 1428,
            'Label' => 'Sigma 300-800mm F5.6 EX HSM IF APO',
        ),
        1429 => array(
            'Id' => 1429,
            'Label' => 'Sigma 300-800mm F5.6 EX DG APO HSM',
        ),
        1431 => array(
            'Id' => 1431,
            'Label' => 'Sigma 200-500mm F2.8 APO EX DG',
        ),
        1448 => array(
            'Id' => 1448,
            'Label' => 'Sigma 70-300mm F4-5.6 APO DG Macro (Motorized)',
        ),
        1449 => array(
            'Id' => 1449,
            'Label' => 'Sigma 70-300mm F4-5.6 DG Macro (Motorized)',
        ),
        1541 => array(
            'Id' => 1541,
            'Label' => 'Sigma 24-70mm F3.5-5.6 ASP HF',
        ),
        1587 => array(
            'Id' => 1587,
            'Label' => 'Sigma 28-70mm F2.8-4 HS',
        ),
        1588 => array(
            'Id' => 1588,
            'Label' => 'Sigma 28-70mm F2.8-4 DG',
        ),
        1589 => array(
            'Id' => 1589,
            'Label' => 'Sigma 24-105mm F4 DG OS HSM | A',
        ),
        1604 => array(
            'Id' => 1604,
            'Label' => 'Sigma 28-80mm F3.5-5.6 ASP HF Macro',
        ),
        1625 => array(
            'Id' => 1625,
            'Label' => 'Sigma 28-80mm F3.5-5.6 Mini Zoom Macro II ASP',
        ),
        1633 => array(
            'Id' => 1633,
            'Label' => 'Sigma 28-105mm F2.8-4 IF ASP',
        ),
        1635 => array(
            'Id' => 1635,
            'Label' => 'Sigma 28-105mm F3.8-5.6 IF UC-III ASP',
        ),
        1636 => array(
            'Id' => 1636,
            'Label' => 'Sigma 28-105mm F2.8-4 IF DG ASP',
        ),
        1639 => array(
            'Id' => 1639,
            'Label' => 'Sigma 24-135mm F2.8-4.5 IF ASP',
        ),
        1640 => array(
            'Id' => 1640,
            'Label' => 'Sigma 17-70mm F2.8-4 DC Macro OS HSM',
        ),
        1641 => array(
            'Id' => 1641,
            'Label' => 'Sigma 17-70mm F2.8-4.5 DC HSM Macro',
        ),
        1668 => array(
            'Id' => 1668,
            'Label' => 'Sigma 55-200mm F4-5.6 DC',
        ),
        1670 => array(
            'Id' => 1670,
            'Label' => 'Sigma 50-200mm F4-5.6 DC OS HSM',
        ),
        1673 => array(
            'Id' => 1673,
            'Label' => 'Sigma 17-70mm F2.8-4.5 DC Macro',
        ),
        1680 => array(
            'Id' => 1680,
            'Label' => 'Sigma 50-150mm F2.8 EX DC HSM APO',
        ),
        1681 => array(
            'Id' => 1681,
            'Label' => 'Sigma 50-150mm F2.8 EX DC APO HSM II',
        ),
        1682 => array(
            'Id' => 1682,
            'Label' => 'Sigma APO 50-150mm F2.8 EX DC OS HSM',
        ),
        1683 => array(
            'Id' => 1683,
            'Label' => 'Sigma 50-100mm F1.8 DC HSM | A',
        ),
        1801 => array(
            'Id' => 1801,
            'Label' => 'Sigma 28-135mm F3.8-5.6 IF ASP Macro',
        ),
        1827 => array(
            'Id' => 1827,
            'Label' => 'Sigma 135-400mm F4.5-5.6 ASP APO',
        ),
        1829 => array(
            'Id' => 1829,
            'Label' => 'Sigma 80-400mm F4.5-5.6 EX OS',
        ),
        1830 => array(
            'Id' => 1830,
            'Label' => 'Sigma 80-400mm F4.5-5.6 EX DG OS APO',
        ),
        1831 => array(
            'Id' => 1831,
            'Label' => 'Sigma 135-400mm F4.5-5.6 DG ASP APO',
        ),
        1832 => array(
            'Id' => 1832,
            'Label' => 'Sigma 120-400mm F4.5-5.6 DG APO OS HSM',
        ),
        1833 => array(
            'Id' => 1833,
            'Label' => 'Sigma 100-400mm F5-6.3 DG OS HSM | C',
        ),
        1840 => array(
            'Id' => 1840,
            'Label' => 'Sigma 60-600mm F4.5-6.3 DG OS HSM | S',
        ),
        1843 => array(
            'Id' => 1843,
            'Label' => 'Sigma 170-500mm F5-6.3 ASP APO',
        ),
        1844 => array(
            'Id' => 1844,
            'Label' => 'Sigma 170-500mm F5-6.3 DG ASP APO',
        ),
        1845 => array(
            'Id' => 1845,
            'Label' => 'Sigma 50-500mm F4-6.3 EX RF HSM APO',
        ),
        1846 => array(
            'Id' => 1846,
            'Label' => 'Sigma 50-500mm F4-6.3 EX DG HSM APO',
        ),
        1847 => array(
            'Id' => 1847,
            'Label' => 'Sigma 150-500mm F5-6.3 APO DG OS HSM',
        ),
        1848 => array(
            'Id' => 1848,
            'Label' => 'Sigma 50-500mm F4.5-6.3 APO DG OS HSM',
        ),
        1856 => array(
            'Id' => 1856,
            'Label' => 'Sigma 150-600mm F5-6.3 DG OS HSM | S',
        ),
        1861 => array(
            'Id' => 1861,
            'Label' => 'Sigma 150-600mm F5-6.3 DG OS HSM | C',
        ),
        1911 => array(
            'Id' => 1911,
            'Label' => 'Sigma 18-200mm F3.5-6.3 DC',
        ),
        1917 => array(
            'Id' => 1917,
            'Label' => 'Sigma 18-200mm F3.5-6.3 DC (Motorized)',
        ),
        1925 => array(
            'Id' => 1925,
            'Label' => 'Sigma 28-200mm F3.5-5.6 DL ASP IF HZM Macro',
        ),
        1927 => array(
            'Id' => 1927,
            'Label' => 'Sigma 28-200mm F3.5-5.6 Compact ASP HZ Macro',
        ),
        1929 => array(
            'Id' => 1929,
            'Label' => 'Sigma 18-125mm F3.5-5.6 DC',
        ),
        1936 => array(
            'Id' => 1936,
            'Label' => 'Sigma 28-300mm F3.5-6.3 DL ASP IF HZM',
        ),
        1939 => array(
            'Id' => 1939,
            'Label' => 'Sigma 28-300mm F3.5-6.3 Macro',
        ),
        1940 => array(
            'Id' => 1940,
            'Label' => 'Sigma 28-200mm F3.5-5.6 DG Compact ASP HZ Macro',
        ),
        1941 => array(
            'Id' => 1941,
            'Label' => 'Sigma 28-300mm F3.5-6.3 DG Macro',
        ),
        2083 => array(
            'Id' => 2083,
            'Label' => 'Sigma 1.4X TC EX APO',
        ),
        2084 => array(
            'Id' => 2084,
            'Label' => 'Sigma 1.4X Teleconverter EX APO DG',
        ),
        2131 => array(
            'Id' => 2131,
            'Label' => 'Sigma 18-125mm F3.8-5.6 DC OS HSM',
        ),
        2145 => array(
            'Id' => 2145,
            'Label' => 'Sigma 18-50mm F2.8-4.5 DC OS HSM',
        ),
        2160 => array(
            'Id' => 2160,
            'Label' => 'Sigma 2.0X Teleconverter TC-2001',
        ),
        2165 => array(
            'Id' => 2165,
            'Label' => 'Sigma 2.0X TC EX APO',
        ),
        2166 => array(
            'Id' => 2166,
            'Label' => 'Sigma 2.0X Teleconverter EX APO DG',
        ),
        2169 => array(
            'Id' => 2169,
            'Label' => 'Sigma 1.4X Teleconverter TC-1401',
        ),
        2176 => array(
            'Id' => 2176,
            'Label' => 'Sigma 18-250mm F3.5-6.3 DC OS HSM',
        ),
        2178 => array(
            'Id' => 2178,
            'Label' => 'Sigma 18-200mm F3.5-6.3 II DC OS HSM',
        ),
        2179 => array(
            'Id' => 2179,
            'Label' => 'Sigma 18-250mm F3.5-6.3 DC Macro OS HSM',
        ),
        2180 => array(
            'Id' => 2180,
            'Label' => 'Sigma 17-70mm F2.8-4 DC OS HSM Macro | C',
        ),
        2181 => array(
            'Id' => 2181,
            'Label' => 'Sigma 18-200mm F3.5-6.3 DC OS HSM Macro | C',
        ),
        2182 => array(
            'Id' => 2182,
            'Label' => 'Sigma 18-300mm F3.5-6.3 DC OS HSM Macro | C',
        ),
        2184 => array(
            'Id' => 2184,
            'Label' => 'Sigma 18-200mm F3.5-6.3 DC OS',
        ),
        2192 => array(
            'Id' => 2192,
            'Label' => 'Sigma Mount Converter MC-11',
        ),
        2345 => array(
            'Id' => 2345,
            'Label' => 'Sigma 60mm F2.8 DN | A',
        ),
        4099 => array(
            'Id' => 4099,
            'Label' => 'Sigma 19mm F2.8',
        ),
        4100 => array(
            'Id' => 4100,
            'Label' => 'Sigma 30mm F2.8',
        ),
        4101 => array(
            'Id' => 4101,
            'Label' => 'Sigma 50mm F2.8 Macro',
        ),
        4102 => array(
            'Id' => 4102,
            'Label' => 'Sigma 19mm F2.8',
        ),
        4103 => array(
            'Id' => 4103,
            'Label' => 'Sigma 30mm F2.8',
        ),
        4104 => array(
            'Id' => 4104,
            'Label' => 'Sigma 50mm F2.8 Macro',
        ),
        4105 => array(
            'Id' => 4105,
            'Label' => 'Sigma 14mm F4',
        ),
        24577 => array(
            'Id' => 24577,
            'Label' => 'Sigma 150-600mm F5-6.3 DG OS HSM | S',
        ),
        24579 => array(
            'Id' => 24579,
            'Label' => 'Sigma 45mm F2.8 DG DN | C',
        ),
        24582 => array(
            'Id' => 24582,
            'Label' => 'Sigma 50mm F1.4 DG HSM | A',
        ),
        32773 => array(
            'Id' => 32773,
            'Label' => 'Sigma 35mm F1.4 DG HSM | A',
        ),
        32777 => array(
            'Id' => 32777,
            'Label' => 'Sigma 18-35mm F1.8 DC HSM | A',
        ),
        35072 => array(
            'Id' => 35072,
            'Label' => 'Sigma 70-300mm F4-5.6 DG OS',
        ),
        41216 => array(
            'Id' => 41216,
            'Label' => 'Sigma 24-70mm F2.8 DG Macro',
        ),
    );

}
