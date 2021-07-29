<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Photoshop;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class LayerBlendModes extends AbstractTag
{

    protected $Id = '_xbnd';

    protected $Name = 'LayerBlendModes';

    protected $FullName = 'Photoshop::Layers';

    protected $GroupName = 'Photoshop';

    protected $g0 = 'Photoshop';

    protected $g1 = 'Photoshop';

    protected $g2 = 'Image';

    protected $Type = 'undef';

    protected $Writable = false;

    protected $Description = 'Layer Blend Modes';

    protected $flag_List = true;

    protected $Values = array(
        'colr' => array(
            'Id' => 'colr',
            'Label' => 'Color',
        ),
        'dark' => array(
            'Id' => 'dark',
            'Label' => 'Darken',
        ),
        'diff' => array(
            'Id' => 'diff',
            'Label' => 'Difference',
        ),
        'diss' => array(
            'Id' => 'diss',
            'Label' => 'Dissolve',
        ),
        'div ' => array(
            'Id' => 'div ',
            'Label' => 'Color Dodge',
        ),
        'dkCl' => array(
            'Id' => 'dkCl',
            'Label' => 'Darker Color',
        ),
        'fdiv' => array(
            'Id' => 'fdiv',
            'Label' => 'Divide',
        ),
        'fsub' => array(
            'Id' => 'fsub',
            'Label' => 'Subtract',
        ),
        'hLit' => array(
            'Id' => 'hLit',
            'Label' => 'Hard Light',
        ),
        'hMix' => array(
            'Id' => 'hMix',
            'Label' => 'Hard Mix',
        ),
        'hue ' => array(
            'Id' => 'hue ',
            'Label' => 'Hue',
        ),
        'idiv' => array(
            'Id' => 'idiv',
            'Label' => 'Color Burn',
        ),
        'lLit' => array(
            'Id' => 'lLit',
            'Label' => 'Linear Light',
        ),
        'lbrn' => array(
            'Id' => 'lbrn',
            'Label' => 'Linear Burn',
        ),
        'lddg' => array(
            'Id' => 'lddg',
            'Label' => 'Linear Dodge',
        ),
        'lgCl' => array(
            'Id' => 'lgCl',
            'Label' => 'Lighter Color',
        ),
        'lite' => array(
            'Id' => 'lite',
            'Label' => 'Lighten',
        ),
        'lum ' => array(
            'Id' => 'lum ',
            'Label' => 'Luminosity',
        ),
        'mul ' => array(
            'Id' => 'mul ',
            'Label' => 'Multiply',
        ),
        'norm' => array(
            'Id' => 'norm',
            'Label' => 'Normal',
        ),
        'over' => array(
            'Id' => 'over',
            'Label' => 'Overlay',
        ),
        'pLit' => array(
            'Id' => 'pLit',
            'Label' => 'Pin Light',
        ),
        'pass' => array(
            'Id' => 'pass',
            'Label' => 'Pass Through',
        ),
        'sLit' => array(
            'Id' => 'sLit',
            'Label' => 'Soft Light',
        ),
        'sat ' => array(
            'Id' => 'sat ',
            'Label' => 'Saturation',
        ),
        'scrn' => array(
            'Id' => 'scrn',
            'Label' => 'Screen',
        ),
        'smud' => array(
            'Id' => 'smud',
            'Label' => 'Exclusion',
        ),
        'vLit' => array(
            'Id' => 'vLit',
            'Label' => 'Vivid Light',
        ),
    );

}
