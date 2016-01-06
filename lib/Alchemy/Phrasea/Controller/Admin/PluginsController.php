<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Controller\Admin;

use Alchemy\Phrasea\Controller\BaseController;
use Alchemy\Phrasea\Plugin\PluginMetadataInterface;
use Symfony\Component\HttpFoundation\Response;

class PluginsController extends BaseController
{
    public function indexAction()
    {
        return $this->render('admin/plugins/index.html.twig', [
            'plugins' => $this->app['plugins'],
        ]);
    }

    /**
     * @param string $pluginName
     * @return Response
     */
    public function showAction($pluginName)
    {
        if (!isset($this->app['plugins'][$pluginName])) {
            throw new \InvalidArgumentException('Expects a valid plugin name.');
        }

        /** @var PluginMetadataInterface $plugin */
        $plugin = $this->app['plugins'][$pluginName];

        $configurationTabs = [];

        foreach ($plugin->getConfigurationTabServiceIds() as $tabName => $serviceId) {
            $configurationTab = $this->app[$serviceId];

            if ($this->isGranted('VIEW', $configurationTab)) {
                $configurationTabs[$tabName] = $configurationTab;
            }
        }

        return $this->render('admin/plugins/show.html.twig', [
            'plugin' => $this->app['plugins'][$pluginName],
            'configurationTabs' => $configurationTabs,
        ]);
    }
}
