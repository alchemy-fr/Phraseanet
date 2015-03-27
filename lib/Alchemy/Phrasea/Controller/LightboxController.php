<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Exception\SessionNotFound;
use Alchemy\Phrasea\Model\Repositories\BasketElementRepository;
use Alchemy\Phrasea\Model\Repositories\BasketRepository;
use Symfony\Component\HttpFoundation\Response;

class LightboxController
{
    /** @var Application */
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function rootAction()
    {
        try {
            \Session_Logger::updateClientInfos($this->app, 6);
        } catch (SessionNotFound $e) {
            return $this->app->redirectPath('logout');
        }

        /** @var BasketRepository $repository */
        $repository = $this->app['repo.baskets'];
        $basket_collection = array_merge(
            $repository->findActiveByUser($this->app['authentication']->getUser()),
            $repository->findActiveValidationByUser($this->app['authentication']->getUser())
        );

        $template = 'lightbox/index.html.twig';
        if (!$this->app['browser']->isNewGeneration() && !$this->app['browser']->isMobile()) {
            $template = 'lightbox/IE6/index.html.twig';
        }

        return new Response($this->app['twig']->render($template, [
                'baskets_collection' => $basket_collection,
                'module_name'        => 'Lightbox',
                'module'             => 'lightbox'
            ]
        ));
    }

    public function ajaxNoteFormAction($sselcont_id)
    {
        if (!$this->app['browser']->isMobile()) {
            return new Response('');
        }

        /** @var BasketElementRepository $basketElementRepository */
        $basketElementRepository = $this->app['repo.basket-elements'];
        $basketElement = $basketElementRepository
            ->findUserElement($sselcont_id, $this->app['authentication']->getUser());

        return $this->app['twig']->render('lightbox/note_form.html.twig', [
            'basket_element' => $basketElement,
            'module_name'    => '',
        ]);
    }

    public function ajaxLoadBasketElementAction($sselcont_id)
    {
        /** @var BasketElementRepository $repository */
        $repository = $this->app['repo.basket-elements'];

        $basketElement = $repository->findUserElement($sselcont_id, $this->app['authentication']->getUser());

        /** @var \Twig_Environment $twig */
        $twig = $this->app['twig'];
        if ($this->app['browser']->isMobile()) {
            $output = $twig->render('lightbox/basket_element.html.twig', [
                    'basket_element' => $basketElement,
                    'module_name'    => $basketElement->getRecord($this->app)->get_title()
                ]
            );

            return new Response($output);
        }

        $isNewGenerationBrowser = $this->app['browser']->isNewGeneration();
        $basket = $basketElement->getBasket();

        $ret = [];
        $ret['number'] = $basketElement->getRecord($this->app)->get_number();
        $ret['title'] = $basketElement->getRecord($this->app)->get_title();

        $ret['preview'] = $twig->render(
            'common/preview.html.twig',
            ['record' => $basketElement->getRecord($this->app), 'not_wrapped' => true]
        );
        $ret['options_html'] = $twig->render(
            $isNewGenerationBrowser ? 'lightbox/sc_options_box.html.twig' : 'lightbox/IE6/sc_options_box.html.twig',
            ['basket_element' => $basketElement]
        );
        $ret['agreement_html'] = $twig->render(
            $isNewGenerationBrowser ? 'lightbox/agreement_box.html.twig' : 'lightbox/IE6/agreement_box.html.twig',
            ['basket' => $basket, 'basket_element' => $basketElement]
        );
        $ret['selector_html'] = $twig->render('lightbox/selector_box.html.twig', ['basket_element' => $basketElement]);
        $ret['note_html'] = $twig->render('lightbox/sc_note.html.twig', ['basket_element' => $basketElement]);
        $ret['caption'] = $twig->render(
            'common/caption.html.twig',
            ['view' => 'preview', 'record' => $basketElement->getRecord($this->app)]
        );

        return $this->app->json($ret);
    }
}
