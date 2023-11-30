<?php

namespace Alchemy\Phrasea\Command\Feedback\Report;


interface ActionInterface
{
    function addAction(array &$actions, array $context);
}
