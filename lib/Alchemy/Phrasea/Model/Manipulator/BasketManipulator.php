<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Model\Manipulator;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Entities\Basket;
use Alchemy\Phrasea\Model\Entities\BasketElement;
use Alchemy\Phrasea\Model\Repositories\BasketRepository;
use Assert\Assertion;
use Doctrine\Common\Persistence\ObjectManager;
use record_adapter;

class BasketManipulator
{
    /** @var Application */
    private $app;
    /** @var BasketRepository */
    private $repository;
    /** @var ObjectManager */
    private $manager;

    public function __construct(Application $app, BasketRepository $repository, ObjectManager $manager)
    {
        $this->app = $app;
        $this->repository = $repository;
        $this->manager = $manager;
    }

    /**
     * @param Basket            $basket
     * @param record_adapter[] $records
     * @return BasketElement[]
     */
    public function addRecords(Basket $basket, $records)
    {
        Assertion::allIsInstanceOf($records, record_adapter::class);

        $elements = [];

        foreach ($records as $record) {
            if ($basket->hasRecord($this->app, $record)) {
                continue;
            }

            $basket_element = new BasketElement();
            $basket_element->setRecord($record);
            $basket->addElement($basket_element);

            $this->manager->persist($basket_element);

            $elements[] = $basket_element;
/* !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! adding vote (former data) for a participant should be dynamic
            if (null !== $validationSession = $basket->getValidation()) {

                $participants = $validationSession->getParticipants();

                foreach ($participants as $participant) {
                    $validationData = new ValidationData();
                    $validationData->setParticipant($participant);
                    $validationData->setBasketElement($basket_element);

                    $this->manager->persist($validationData);
                }
            }
*/
        }

        $this->manager->flush();

        return $elements;
    }

    /**
     * @param Basket          $basket
     * @param BasketElement[] $elements
     */
    public function removeElements(Basket $basket, $elements)
    {
        Assertion::allIsInstanceOf($elements, BasketElement::class);

        foreach ($elements as $element) {
            $ord = $element->getOrd();
            $elementId = $element->getId();
            if ($element->getBasket() !== $basket) {
                continue;
            }

            foreach ($basket->getElements() as $basket_element) {
                if ($basket_element->getOrd() > $ord) {
                    $basket_element->setOrd($basket_element->getOrd() - 1);
                    $this->manager->persist($basket_element);
                }
                if ($basket_element->getId() === (int) $elementId) {
                    $basket->removeElement($basket_element);
                    $this->manager->remove($basket_element);
                }
            }
        }
        $this->manager->persist($basket);

        $this->manager->flush();
    }

    public function saveBasket(Basket $basket)
    {
        $this->manager->persist($basket);
        $this->manager->flush();
    }

    public function removeBasket(Basket $basket)
    {
        $this->manager->remove($basket);
        $this->manager->flush();
    }

    public function removeBaskets(array $baskets)
    {
        foreach ($baskets as $basket) {
            $this->manager->remove($basket);
        }
        $this->manager->flush();
    }
}
