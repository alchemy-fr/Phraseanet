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

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Core\Configuration\RegistryFormManipulator;
use Symfony\Component\HttpFoundation\Request;

class SetupController extends Controller
{
    /**
     * @var RegistryFormManipulator
     */
    private $registryFormManipulator;

    /**
     * @var PropertyAccess
     */
    private $configuration;

    public function __construct(Application $app, RegistryFormManipulator $registryFormManipulator, PropertyAccess $configuration)
    {
        parent::__construct($app);

        $this->registryFormManipulator = $registryFormManipulator;
        $this->configuration = $configuration;
    }

    public function submitGlobalsAction(Request $request)
    {
        $form = $this->registryFormManipulator->createForm();

        if ('POST' === $request->getMethod()) {
            $form->submit($request->request->all());

            if ($form->isValid()) {
                $registryData = $this->registryFormManipulator->getRegistryData($form, $this->configuration);

                $this->configuration->set('registry', $registryData);
            }

            // Do not return a 400 status code as not very well handled in calling JS.
        }

        return $this->renderResponse('admin/setup.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
