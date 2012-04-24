<?php

namespace Alchemy\Phrasea\Media\Subdef\OptionType;

interface OptionType
{
    const TYPE_RANGE = 'Range';
    const TYPE_ENUM = 'Enum';
    const TYPE_BOOLEAN = 'Boolean';

    public function getType();
    public function getName();
    public function getValue();
}
