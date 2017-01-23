<?php

namespace Alchemy\Phrasea\Setup\Action;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Connection\ConnectionSettings;

class CreateDataboxAction implements Action
{
    /**
     * @var Application
     */
    private $application;

    /**
     * @var ConnectionSettings
     */
    private $connectionSettings;

    /**
     * @param Application $application
     * @param ConnectionSettings $connectionSettings
     */
    public function __construct(Application $application, ConnectionSettings $connectionSettings)
    {
        $this->application = $application;
        $this->connectionSettings = $connectionSettings;
    }

    /**
     * @return ActionResult
     */
    public function execute()
    {

    }
}
