<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller;

use Alchemy\Phrasea\Application\Helper\DispatcherAware;
use Alchemy\Phrasea\Core\Event\ValidationEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Exception\SessionNotFound;
use Alchemy\Phrasea\Model\Entities\Basket;
use Alchemy\Phrasea\Model\Entities\FeedEntry;
use Alchemy\Phrasea\Model\Entities\Token;
use Alchemy\Phrasea\Model\Entities\ValidationData;
use Alchemy\Phrasea\Model\Repositories\BasketElementRepository;
use Alchemy\Phrasea\Model\Repositories\BasketRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LightboxController extends Controller
{
    use DispatcherAware;

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
            $repository->findActiveByUser($this->getAuthenticatedUser()),
            $repository->findActiveValidationByUser($this->getAuthenticatedUser())
        );

        return $this->renderResponse('lightbox/index.html.twig', [
            'baskets_collection' => $basket_collection,
            'module_name'        => 'Lightbox',
            'module'             => 'lightbox',
        ]);
    }

    /**
     * @param int $sselcont_id
     * @return Response
     */
    public function ajaxNoteFormAction($sselcont_id)
    {
        if (!$this->app['browser']->isMobile()) {
            return new Response('');
        }

        /** @var BasketElementRepository $basketElementRepository */
        $basketElementRepository = $this->app['repo.basket-elements'];
        $basketElement = $basketElementRepository
            ->findUserElement($sselcont_id, $this->getAuthenticatedUser());

        return $this->renderResponse('lightbox/note_form.html.twig', [
            'basket_element' => $basketElement,
            'module_name'    => '',
        ]);
    }

    /**
     * @param int $sselcont_id
     * @return Response
     */
    public function ajaxLoadBasketElementAction($sselcont_id)
    {
        /** @var BasketElementRepository $repository */
        $repository = $this->app['repo.basket-elements'];
        $basketElement = $repository->findUserElement($sselcont_id, $this->getAuthenticatedUser());

        $basket = $basketElement->getBasket();

        $elements = $basket->getElements();
        for ($i = 0; $i < count($elements); ++$i) {
            if ($sselcont_id == $elements[$i]->getId()) {
                $nextKey = $i + 1;
                $prevKey = $i - 1;
                if ($nextKey < count($elements)) {
                    $nextId = $elements[$nextKey]->getId();
                }
                else {
                    $nextId = null;
                }
                if ($prevKey >= 0) {
                    $prevId = $elements[$prevKey]->getId();
                }
                else {
                    $prevId = null;
                }
            }
        }

        if ($this->app['browser']->isMobile()) {
            return $this->renderResponse('lightbox/basket_element.html.twig', [
                'basket_element' => $basketElement,
                'module_name'    => $basketElement->getRecord($this->app)->get_title(),
                'nextId'         => $nextId,
                'prevId'         => $prevId
            ]);
        }


        $ret = [];
        $ret['number'] = $basketElement->getRecord($this->app)->getNumber();
        $ret['title'] = $basketElement->getRecord($this->app)->get_title();

        $ret['preview'] = $this->render(
            'common/preview.html.twig',
            ['record' => $basketElement->getRecord($this->app), 'not_wrapped' => true]
        );
        $ret['options_html'] = $this->render(
            'lightbox/sc_options_box.html.twig',
            ['basket_element' => $basketElement]
        );
        $ret['agreement_html'] = $this->render(
            'lightbox/agreement_box.html.twig',
            ['basket' => $basket, 'basket_element' => $basketElement]
        );
        $ret['selector_html'] = $this->render('lightbox/selector_box.html.twig', ['basket_element' => $basketElement]);
        $ret['note_html'] = $this->render('lightbox/sc_note.html.twig', ['basket_element' => $basketElement]);
        $ret['caption'] = $this->render(
            'common/caption.html.twig',
            ['view' => 'preview', 'record' => $basketElement->getRecord($this->app)]
        );

        return $this->app->json($ret);
    }

    /**
     * @param int $entry_id
     * @param int $item_id
     * @return Response
     */
    public function ajaxLoadFeedItemAction($entry_id, $item_id) {
        /** @var FeedEntry $entry */
        $entry = $this->app['repo.feed-entries']->find($entry_id);
        $item = $entry->getItem($item_id);

        $record = $item->getRecord($this->app);

        /** @var \Browser $browser */
        $browser = $this->app['browser'];
        if ($browser->isMobile()) {
            return $this->renderResponse('lightbox/feed_element.html.twig', [
                'feed_element' => $item,
                'module_name'  => $record->get_title()
            ]);
        }

        $ret = [];
        $ret['number'] = $record->getNumber();
        $ret['title'] = $record->get_title();
        $ret['preview'] = $this->render('common/preview.html.twig', [
            'record' => $record,
            'not_wrapped' => true,
        ]);
        $ret['options_html'] = $this->render('lightbox/feed_options_box.html.twig', ['feed_element' => $item]);
        $ret['caption'] = $this->render(
            'common/caption.html.twig', [
            'view'   => 'preview',
            'record' => $record,
        ]);
        $ret['agreement_html'] = $ret['selector_html'] = $ret['note_html'] = '';

        return $this->app->json($ret);
    }

    /**
     * @param Basket $basket
     * @return Response
     */
    public function validationAction(Basket $basket) {
        try {
            \Session_Logger::updateClientInfos($this->app, 6);
        } catch (SessionNotFound $e) {
            return $this->app->redirectPath('logout');
        }

        /** @var BasketRepository $repository */
        $repository = $this->app['repo.baskets'];

        $basket_collection = $repository->findActiveValidationAndBasketByUser($this->getAuthenticatedUser());

        $basket = $this->markBasketRead($basket);
        $basket = $this->markBasketUserAwareOfValidation($basket);

        $response = $this->renderResponse(
            $this->getValidationTemplate(), [
            'baskets_collection' => $basket_collection,
            'basket'             => $basket,
            'local_title'        => strip_tags($basket->getName()),
            'module'             => 'lightbox',
            'module_name'        => $this->app->trans('admin::monitor: module validation'),
        ]);
        $response->setCharset('UTF-8');

        return $response;
    }

    /**
     * @param Basket $basket
     * @return Response
     */
    public function compareAction(Basket $basket) {
        try {
            \Session_Logger::updateClientInfos($this->app, 6);
        } catch (SessionNotFound $e) {
            return $this->app->redirectPath('logout');
        }

        /** @var BasketRepository $repository */
        $repository = $this->app['repo.baskets'];

        $basket_collection = $repository->findActiveValidationAndBasketByUser($this->getAuthenticatedUser());

        $basket = $this->markBasketRead($basket);
        $basket = $this->markBasketUserAwareOfValidation($basket);

        $response = $this->renderResponse($this->getValidationTemplate(), [
            'baskets_collection' => $basket_collection,
            'basket'             => $basket,
            'local_title'        => strip_tags($basket->getName()),
            'module'             => 'lightbox',
            'module_name'        => $this->app->trans('admin::monitor: module validation'),
        ]);
        $response->setCharset('UTF-8');

        return $response;
    }

    /**
     * @param Basket $basket
     * @return Basket
     */
    private function markBasketRead(Basket $basket)
    {
        if ($basket->isRead() === false) {
            /** @var Basket $basket */
            $basket = $this->app['orm.em']->merge($basket);
            $basket->markRead();
            $this->app['orm.em']->flush();
        }

        return $basket;
    }

    /**
     * @return string
     */
    private function getValidationTemplate()
    {
        return 'lightbox/validate.html.twig';
    }

    /**
     * @param Basket $basket
     * @return Basket
     */
    private function markBasketUserAwareOfValidation(Basket $basket)
    {
        if ($basket->getValidation() && $basket->getValidation()
                ->getParticipant($this->getAuthenticatedUser())
                ->getIsAware() === false
        ) {
            /** @var Basket $basket */
            $basket = $this->app['orm.em']->merge($basket);
            $basket->getValidation()
                ->getParticipant($this->getAuthenticatedUser())
                ->setIsAware(true)
            ;
            $this->app['orm.em']->flush();
        }

        return $basket;
    }

    /**
     * @param int $entry_id
     * @return Response
     */
    public function getFeedEntryAction($entry_id)
    {
        $app = $this->app;
        try {
            \Session_Logger::updateClientInfos($app, 6);
        } catch (SessionNotFound $e) {
            return $app->redirectPath('logout');
        }

        /** @var FeedEntry $feed_entry */
        $feed_entry = $app['repo.feed-entries']->find($entry_id);

        $content = $feed_entry->getItems();
        $first = $content->first();

        $response = $this->renderResponse('lightbox/feed.html.twig', [
            'feed_entry'  => $feed_entry,
            'first_item'  => $first,
            'local_title' => $feed_entry->getTitle(),
            'module'      => 'lightbox',
            'module_name' => $app->trans('admin::monitor: module validation')
        ]);
        $response->setCharset('UTF-8');

        return $response;
    }

    /**
     * @param Basket $basket
     * @return Response
     */
    public function ajaxReportAction(Basket $basket)
    {
        return $this->renderResponse('lightbox/basket_content_report.html.twig', [
            'basket' => $basket,
        ]);
    }

    /**
     * @param Request $request
     * @param int     $sselcont_id
     * @return Response
     */
    public function ajaxSetNoteAction(Request $request, $sselcont_id)
    {
        $note = $request->request->get('note');

        if (is_null($note)) {
            return new Response('You must provide a note value', 400);
        }

        /** @var BasketElementRepository $repository */
        $repository = $this->app['repo.basket-elements'];

        $basket_element = $repository->findUserElement($sselcont_id, $this->getAuthenticatedUser());

        $validationData = $basket_element->getUserValidationDatas($this->getAuthenticatedUser());
        /** @var ValidationData $validationData */
        $validationData = $this->app['orm.em']->merge($validationData);
        $validationData->setNote($note);
        $this->app['orm.em']->flush();

        $data = $this->render('lightbox/sc_note.html.twig', ['basket_element' => $basket_element]);
        $output = ['error' => false, 'datas' => $data];

        return $this->app->json($output);
    }

    public function ajaxSetElementAgreementAction(Request $request, $sselcont_id)
    {
        $agreement = $request->request->get('agreement');

        if (is_null($agreement)) {
            return new Response('You must provide an agreement value', 400);
        }

        $agreement = $agreement > 0;

        try {
            $ret = [
                'error'      => true,
                'releasable' => false,
                'datas'      => $this->app->trans('Erreur lors de la mise a jour des donnes')
            ];

            /** @var BasketElementRepository $repository */
            $repository = $this->app['repo.basket-elements'];

            $basketElement = $repository->findUserElement($sselcont_id, $this->getAuthenticatedUser());
            $validationData = $basketElement->getUserValidationDatas($this->getAuthenticatedUser());

            if (!$basketElement->getBasket()
                ->getValidation()
                ->getParticipant($this->getAuthenticatedUser())->getCanAgree()
            ) {
                throw new Exception('You can not agree on this');
            }

            $validationData->setAgreement($agreement);

            $participant = $basketElement->getBasket()
                ->getValidation()
                ->getParticipant($this->getAuthenticatedUser());

            $this->app['orm.em']->merge($basketElement);
            $this->app['orm.em']->flush();

            $releasable = ($participant->isReleasable())
                ? $releasable = $this->app->trans('Do you want to send your report ?')
                : false;

            $ret = [
                'error'      => false,
                'datas'      => '',
                'releasable' => $releasable,
            ];
        } catch (Exception $e) {
            $ret['datas'] = $e->getMessage();
        }

        return $this->app->json($ret);
    }

    /**
     * @param Basket $basket
     * @return Response
     */
    public function ajaxSetReleaseAction(Basket $basket)
    {
        try {
            if (!$basket->getValidation()) {
                throw new Exception('There is no validation session attached to this basket');
            }

            if (!$basket->getValidation()->getParticipant($this->getAuthenticatedUser())->getCanAgree()) {
                throw new Exception('You have not right to agree');
            }

            $this->assertAtLeastOneElementAgreed($basket);
            $participant = $basket->getValidation()->getParticipant($this->getAuthenticatedUser());

            /** @var Token $token */
            $token = $this->app['manipulator.token']->createBasketValidationToken($basket);
            $url = $this->app->url('lightbox', ['LOG' => $token->getValue()]);

            $this->dispatch(PhraseaEvents::VALIDATION_DONE, new ValidationEvent($participant, $basket, $url));

            $participant->setIsConfirmed(true);

            $this->app['orm.em']->merge($participant);
            $this->app['orm.em']->flush();

            $data = ['error' => false, 'datas' => $this->app->trans('Envoie avec succes')];
        } catch (Exception $e) {
            $data = ['error' => true, 'datas' => $e->getMessage()];
        }

        return $this->app->json($data);
    }

    /**
     * @param Basket $basket
     * @throws Exception
     */
    private function assertAtLeastOneElementAgreed(Basket $basket)
    {
        foreach ($basket->getElements() as $element) {
            if (null !== $element->getUserValidationDatas($this->getAuthenticatedUser())->getAgreement()) {
                return;
            }
        }

        $message = $this->app->trans('You have to give your feedback at least on one document to send a report');
        throw new Exception($message);
    }
}
