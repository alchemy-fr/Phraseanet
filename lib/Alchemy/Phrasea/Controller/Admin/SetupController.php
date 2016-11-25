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

use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Core\Configuration\Configuration;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Core\Configuration\RegistryManipulator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class SetupController extends Controller
{
    public function submitGlobalsAction(Request $request)
    {
        /** @var RegistryManipulator $manipulator */
        $manipulator = $this->app['registry.manipulator'];
        /** @var PropertyAccess $config */
        $config = $this->app['conf'];

        $form = $manipulator->createForm($this->app['conf']);

        if ('POST' === $request->getMethod()) {
            $form->submit($request->request->all());
            if ($form->isValid()) {
                $config->set('registry', $this->buildRegistryData($config, $manipulator, $form));

                return $this->app->redirectPath('setup_display_globals');
            }

            // Do not return a 400 status code as not very well handled in calling JS.
        }

        return $this->renderResponse('admin/setup.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @param PropertyAccess $config
     * @param RegistryManipulator $manipulator
     * @param FormInterface $form
     * @return mixed
     */
    protected function buildRegistryData(PropertyAccess $config, RegistryManipulator $manipulator, FormInterface $form)
    {
        $data = $manipulator->getRegistryData($form);

        if ($data['email']['smtp-password'] == null) {
            $data['email']['smtp-password'] = $config->get([ 'registry', 'email', 'smtp-password']);
        }

        return $data;
    }
}
