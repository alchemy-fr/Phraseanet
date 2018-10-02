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

final class StringTechnicalData implements TechnicalData
{
    /** @var string */
    private $name;
    /** @var string */
    private $value;

    public function __construct($name, $value)
    {
        $this->name = $name;
        $this->value = (string)$value;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }
}
