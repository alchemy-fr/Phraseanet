<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Admin;

use Alchemy\Phrasea\SearchEngine\ConfigurationPanelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SearchEngineController
{
    /**
     * @var ConfigurationPanelInterface
     */
    private $configurationPanel;

    public function __construct(ConfigurationPanelInterface $configurationPanel)
    {
        $this->configurationPanel = $configurationPanel;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function getConfigurationPanelAction(Request $request)
    {
        return $this->configurationPanel->get($request);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function postConfigurationPanelAction(Request $request)
    {
        return $this->configurationPanel->post($request);
    }
}
