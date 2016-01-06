<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Media;

final class FloatTechnicalData implements TechnicalData
{
    /** @var string */
    private $name;
    /** @var float */
    private $value;

    public function __construct($name, $value)
    {
        $this->name = $name;
        $this->value = (float)$value;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }
}
