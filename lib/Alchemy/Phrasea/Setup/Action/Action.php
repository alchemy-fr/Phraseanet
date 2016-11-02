<?php

namespace Alchemy\Phrasea\Setup\Action;

interface Action
{
    /**
     * @return ActionResult
     */
    public function execute();
}
