<?php

namespace Alchemy\Phrasea\PhraseanetService\Controller;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\PhraseanetService\Form\PSExposeConfigurationType;
use Symfony\Component\HttpFoundation\Request;

class PSAdminController extends Controller
{
    public function indexAction(PhraseaApplication $app)
    {
        return $this->render('admin/phraseanet-service/index.html.twig');
    }

    public function authAction()
    {
        return $this->render('admin/phraseanet-service/auth.html.twig');
    }

    public function exposeAction(PhraseaApplication $app, Request $request)
    {
        $exposeConfiguration = $app['conf']->get(['phraseanet-service', 'expose-service'], null);

        $form = $app->form(new PSExposeConfigurationType(), $exposeConfiguration);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $app['conf']->set(['phraseanet-service', 'expose-service'], $form->getData());

            return $app->redirectPath('ps_admin');
        }

        return $this->render('admin/phraseanet-service/expose.html.twig', [
            'form' => $form->createView()
        ]);
    }

    public function notifyAction()
    {
        return $this->render('admin/phraseanet-service/notify.html.twig');
    }

    public function reportAction()
    {
        return $this->render('admin/phraseanet-service/report.html.twig');
    }

    public function uploaderAction()
    {
        return $this->render('admin/phraseanet-service/uploader.html.twig');
    }
}
