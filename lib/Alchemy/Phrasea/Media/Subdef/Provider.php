<?php

namespace Alchemy\Phrasea\Media\Subdef;

abstract class Provider implements Subdef
{

    protected $options = array();
    protected $spec;

    protected function registerOption(OptionType\OptionType $option)
    {
        $this->options[$option->getName()] = $option;

        return $this;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function getOption($name)
    {
        return $this->options[$name];
    }

    public function setOptionValue($name, $value)
    {
        $this->options[$name]->setValue($value);

        return $this;
    }

}
