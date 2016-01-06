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
use Alchemy\Phrasea\Core\Configuration\RegistryManipulator;
use Symfony\Component\HttpFoundation\Request;

class SetupController extends Controller
{
    public function submitGlobalsAction(Request $request)
    {
        /** @var RegistryManipulator $manipulator */
        $manipulator = $this->app['registry.manipulator'];
        $form = $manipulator->createForm($this->app['conf']);

        if ('POST' === $request->getMethod()) {
            $form->submit($request->request->all());
            if ($form->isValid()) {
                $this->app['conf']->set('registry', $manipulator->getRegistryData($form));

                return $this->app->redirectPath('setup_display_globals');
            }

            // Do not return a 400 status code as not very well handled in calling JS.
        }

        return $this->renderResponse('admin/setup.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
