<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Controller\RecordsRequest;
use Entities\Basket as BasketEntity;
use Entities\BasketElement;
use Entities\ValidationData;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Basket implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->before(function(Request $request) use ($app) {
            $app['firewall']->requireAuthentication();
        });

        /**
         * Get a basket
         */
        $controllers->get('/{basket_id}/', $this->call('displayBasket'))
            ->bind('prod_baskets_basket')
            ->assert('basket_id', '\d+');

        /**
         * This route is used to create a Basket
         *
         * @params name : title (mandatory)
         * @params desc : description (optionnal)
         * @params lst  : Phraseanet serialized record list (optionnal)
         *
         * @accept JSON / YAML
         *
         */
        $controllers->post('/', $this->call('createBasket'))
            ->bind('prod_baskets');

        /**
         * This route is used to delete a basket
         *
         * @accept JSON / HTML
         *
         */
        $controllers->post('/{basket_id}/delete/', $this->call('deleteBasket'))
            ->assert('basket_id', '\d+')
            ->bind('basket_delete');

        /**
         * Removes a BasketElement
         */
        $controllers->post('/{basket_id}/delete/{basket_element_id}/', $this->call('removeBasketElement'))
            ->bind('prod_baskets_basket_element_remove')
            ->assert('basket_id', '\d+')
            ->assert('basket_element_id', '\d+');

        /**
         * Update name and description of a basket
         *
         * @param name string mandatory
         * @param description string optionnal
         *
         */
        $controllers->post('/{basket_id}/update/', $this->call('updateBasket'))
            ->bind('prod_baskets_basket_update')
            ->assert('basket_id', '\d+');

        /**
         * Get the form to update the Basket attributes (name and description)
         */
        $controllers->get('/{basket_id}/update/', $this->call('displayUpdateForm'))
            ->assert('basket_id', '\d+');

        /**
         * Get the Basket reorder form
         */
        $controllers->get('/{basket_id}/reorder/', $this->call('displayReorderForm'))
            ->assert('basket_id', '\d+')
            ->bind('prod_baskets_basket_reorder');

        $controllers->post('/{basket_id}/reorder/', $this->call('reorder'))
            ->assert('basket_id', '\d+');

        /**
         * Toggle the status of a Basket
         *
         * @param acrhive : 0|1 (mandatory)
         *
         * @returns JSON / HTML
         */
        $controllers->post('/{basket_id}/archive/', $this->call('archiveBasket'))
            ->bind('prod_baskets_basket_archive')
            ->assert('basket_id', '\d+');

        /**
         * Add a BasketElement to a basket
         */
        $controllers->post('/{basket_id}/addElements/', $this->call('addElements'))
            ->assert('basket_id', '\d+');

        /**
         *
         * Move Basket element from a basket to another
         *
         * @params elements Array : list of basket element id
         *
         */
        $controllers->post('/{basket_id}/stealElements/', $this->call('stealElements'))
            ->assert('basket_id', '\d+');

        /**
         * Get basket creation form
         */
        $controllers->get('/create/', $this->call('displayCreateForm'))->bind('prod_baskets_create');

        return $controllers;
    }

    public function displayBasket(Application $app, Request $request, $basket_id)
    {
        $basket = $app['EM']->getRepository('\Entities\Basket')
            ->findUserBasket($app, $basket_id, $app['authentication']->getUser(), false);

        if ($basket->getIsRead() === false) {
            $basket->setIsRead(true);
            $app['EM']->flush();
        }

        if ($basket->getValidation()) {
            if ($basket->getValidation()->getParticipant($app['authentication']->getUser(), $app)->getIsAware() === false) {
                $basket->getValidation()->getParticipant($app['authentication']->getUser(), $app)->setIsAware(true);
                $app['EM']->flush();
            }
        }

        $params = array(
            'basket' => $basket,
            'ordre'  => $request->query->get('order')
        );

        return $app['twig']->render('prod/WorkZone/Basket.html.twig', $params);
    }

    public function createBasket(Application $app, Request $request)
    {
        $request = $app['request'];
        /* @var $request \Symfony\Component\HttpFoundation\Request */

        $Basket = new BasketEntity();

        $Basket->setName($request->request->get('name', ''));
        $Basket->setOwner($app['authentication']->getUser());
        $Basket->setDescription($request->request->get('desc'));

        $app['EM']->persist($Basket);

        $n = 0;

        $records = RecordsRequest::fromRequest($app, $request, true);

        foreach ($records as $record) {
            if ($Basket->hasRecord($app, $record)) {
                continue;
            }

            $basket_element = new BasketElement();
            $basket_element->setRecord($record);
            $basket_element->setBasket($Basket);

            $app['EM']->persist($basket_element);

            $Basket->addBasketElement($basket_element);

            $n++;
        }

        $app['EM']->flush();

        if ($request->getRequestFormat() == 'json') {
            $data = array(
                'success' => true
                , 'message' => _('Basket created')
                , 'basket'  => array(
                    'id' => $Basket->getId()
                )
            );

            return $app->json($data);
        } else {
            return $app->redirectPath('prod_baskets_basket', array('basket_id' => $Basket->getId()));
        }
    }

    public function deleteBasket(Application $app, Request $request, $basket_id)
    {
        $basket = $app['EM']->getRepository('\Entities\Basket')
            ->findUserBasket($app, $basket_id, $app['authentication']->getUser(), true);

        $app['EM']->remove($basket);
        $app['EM']->flush();

        $data = array(
            'success' => true
            , 'message' => _('Basket has been deleted')
        );

        if ($request->getRequestFormat() == 'json') {
            return $app->json($data);
        } else {
            return $app->redirectPath('prod_workzone_show');
        }
    }

    public function removeBasketElement(Application $app, Request $request, $basket_id, $basket_element_id)
    {
        $basket = $app['EM']->getRepository('\Entities\Basket')
            ->findUserBasket($app, $basket_id, $app['authentication']->getUser(), true);

        foreach ($basket->getElements() as $basket_element) {
            /* @var $basket_element \Entities\BasketElement */
            if ($basket_element->getId() == $basket_element_id) {
                $app['EM']->remove($basket_element);
            }
        }

        $app['EM']->flush();

        $data = array(
            'success' => true
            , 'message' => _('Record removed from basket')
        );

        if ($request->getRequestFormat() == 'json') {
            return $app->json($data);
        } else {
            return $app->redirectPath('prod_workzone_show');
        }
    }

    public function updateBasket(Application $app, Request $request, $basket_id)
    {
        $success = false;

        try {
            $basket = $app['EM']->getRepository('\Entities\Basket')
                ->findUserBasket($app, $basket_id, $app['authentication']->getUser(), true);

            $basket->setName($request->request->get('name', ''));
            $basket->setDescription($request->request->get('description'));

            $app['EM']->merge($basket);
            $app['EM']->flush();

            $success = true;
            $msg = _('Basket has been updated');
        } catch (\Exception_NotFound $e) {
            $msg = _('The requested basket does not exist');
        } catch (\Exception_Forbidden $e) {
            $msg = _('You do not have access to this basket');
        } catch (\Exception $e) {
            $msg = _('An error occurred');
        }

        $data = array(
            'success' => $success
            , 'message' => $msg
            , 'basket'  => array('id' => $basket_id)
        );

        if ($request->getRequestFormat() == 'json') {
            return $app->json($data);
        } else {
            return $app->redirectPath('prod_workzone_show');
        }
    }

    public function displayUpdateForm(Application $app, $basket_id)
    {
        $basket = $app['EM']
            ->getRepository('\Entities\Basket')
            ->findUserBasket($app, $basket_id, $app['authentication']->getUser(), true);

        return $app['twig']->render('prod/Baskets/Update.html.twig', array('basket' => $basket));
    }

    public function displayReorderForm(Application $app, $basket_id)
    {
        $basket = $app['EM']
            ->getRepository('\Entities\Basket')
            ->findUserBasket($app, $basket_id, $app['authentication']->getUser(), true);

        return $app['twig']->render('prod/Baskets/Reorder.html.twig', array('basket' => $basket));
    }

    public function reorder(Application $app, $basket_id)
    {
        $ret = array('success' => false, 'message' => _('An error occured'));
        try {
            $basket = $app['EM']->getRepository('\Entities\Basket')
                ->findUserBasket($app, $basket_id, $app['authentication']->getUser(), true);

            $order = $app['request']->request->get('element');

            /* @var $basket \Entities\Basket */
            foreach ($basket->getElements() as $basketElement) {
                if (isset($order[$basketElement->getId()])) {
                    $basketElement->setOrd($order[$basketElement->getId()]);

                    $app['EM']->merge($basketElement);
                }
            }

            $app['EM']->flush();
            $ret = array('success' => true, 'message' => _('Basket updated'));
        } catch (\Exception $e) {

        }

        return $app->json($ret);
    }

    public function archiveBasket(Application $app, Request $request, $basket_id)
    {
        $basket = $app['EM']->getRepository('\Entities\Basket')
            ->findUserBasket($app, $basket_id, $app['authentication']->getUser(), true);

        $archive_status = !!$request->request->get('archive');

        $basket->setArchived($archive_status);

        $app['EM']->merge($basket);
        $app['EM']->flush();

        if ($archive_status) {
            $message = _('Basket has been archived');
        } else {
            $message = _('Basket has been unarchived');
        }

        $data = array(
            'success' => true
            , 'archive' => $archive_status
            , 'message' => $message
        );

        if ($request->getRequestFormat() == 'json') {
            return $app->json($data);
        } else {
            return $app->redirectPath('prod_workzone_show');
        }
    }

    public function addElements(Application $app, Request $request, $basket_id)
    {
        $basket = $app['EM']->getRepository('\Entities\Basket')
            ->findUserBasket($app, $basket_id, $app['authentication']->getUser(), true);

        $n = 0;

        $records = RecordsRequest::fromRequest($app, $request, true);

        foreach ($records as $record) {
            if ($basket->hasRecord($app, $record))
                continue;

            $basket_element = new BasketElement();
            $basket_element->setRecord($record);
            $basket_element->setBasket($basket);

            $app['EM']->persist($basket_element);

            $basket->addBasketElement($basket_element);

            if (null !== $validationSession = $basket->getValidation()) {

                $participants = $validationSession->getParticipants();

                foreach ($participants as $participant) {
                    $validationData = new ValidationData();
                    $validationData->setParticipant($participant);
                    $validationData->setBasketElement($basket_element);

                    $app['EM']->persist($validationData);
                }
            }

            $n++;
        }

        $app['EM']->flush();

        $data = array(
            'success' => true
            , 'message' => sprintf(_('%d records added'), $n)
        );

        if ($request->getRequestFormat() == 'json') {
            return $app->json($data);
        } else {
            return $app->redirectPath('prod_workzone_show');
        }
    }

    public function stealElements(Application $app, Request $request, $basket_id)
    {
        $basket = $app['EM']->getRepository('\Entities\Basket')
            ->findUserBasket($app, $basket_id, $app['authentication']->getUser(), true);

        $n = 0;

        foreach ($request->request->get('elements') as $bask_element_id) {
            try {
                $basket_element = $app['EM']->getRepository('\Entities\BasketElement')
                    ->findUserElement($bask_element_id, $app['authentication']->getUser());
            } catch (\Exception $e) {
                continue;
            }

            $basket_element->setBasket($basket);
            $basket->addBasketElement($basket_element);
            $n++;
        }

        $app['EM']->flush();

        $data = array(
            'success' => true
            , 'message' => sprintf(_('%d records moved'), $n)
        );

        if ($request->getRequestFormat() == 'json') {
            return $app->json($data);
        } else {
            return $app->redirectPath('prod_workzone_show');
        }
    }

    public function displayCreateForm(Application $app)
    {
        return $app['twig']->render('prod/Baskets/Create.html.twig');
    }

    /**
     * Prefix the method to call with the controller class name
     *
     * @param  string $method The method to call
     * @return string
     */
    private function call($method)
    {
        return sprintf('%s::%s', __CLASS__, $method);
    }
}
