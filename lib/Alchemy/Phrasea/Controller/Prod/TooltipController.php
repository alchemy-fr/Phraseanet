<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Application\Helper\SearchEngineAware;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Model\Entities\Basket;
use Alchemy\Phrasea\Model\Repositories\UserRepository;
use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;
use Symfony\Component\HttpFoundation\Request;

class TooltipController extends Controller
{
    use SearchEngineAware;

    public function displayBasket(Basket $basket)
    {
        return $this->render('prod/Tooltip/Basket.html.twig', ['basket' => $basket]);
    }

    public function displayStory($sbas_id, $record_id)
    {
        $Story = new \record_adapter($this->app, $sbas_id, $record_id);

        return $this->render('prod/Tooltip/Story.html.twig', ['Story' => $Story]);
    }

    public function displayUserBadge($usr_id)
    {
        $user = $this->getUserRepository()->find($usr_id);

        return $this->render('prod/Tooltip/User.html.twig', ['user' => $user]);
    }

    public function displayPreview($sbas_id, $record_id)
    {
        return $this->render('prod/Tooltip/Preview.html.twig', [
            'record' => new \record_adapter($this->app, $sbas_id, $record_id),
            'autoplay' => true,
            'not_wrapped' => true
        ]);
    }

    public function displayCaption(Request $request, $sbas_id, $record_id, $context)
    {
        $number = (int) $request->get('number');
        $record = new \record_adapter($this->app, $sbas_id, $record_id, $number);

        $search_engine = $search_engine_options = null;

        if ($context == 'answer') {
            try {
                $search_engine_options = SearchEngineOptions::hydrate($this->app, $request->request->get('options_serial'));
                $search_engine = $this->getSearchEngine();
            } catch (\Exception $e) {
                $search_engine = null;
            }
        }

        return $this->render('prod/Tooltip/Caption.html.twig', [
            'record'        => $record,
            'view'          => $context,
            'highlight'     => $request->request->get('query'),
            'searchEngine'  => $search_engine,
            'searchOptions' => $search_engine_options,
        ]);
    }

    public function displayTechnicalDatas($sbas_id, $record_id)
    {
        $record = new \record_adapter($this->app, $sbas_id, $record_id);

        try {
            $document = $record->get_subdef('document');
        } catch (\Exception $e) {
            $document = null;
        }

        return $this->render('prod/Tooltip/TechnicalDatas.html.twig', [
            'record'   => $record,
            'document' => $document,
        ]);
    }

    public function displayFieldInfos($sbas_id, $field_id)
    {
        $field = $this->getDataboxField($sbas_id, $field_id);

        return $this->render('prod/Tooltip/DataboxField.html.twig', ['field' => $field]);
    }

    public function displayDCESInfos($sbas_id, $field_id)
    {
        $field = $this->getDataboxField($sbas_id, $field_id);

        return $this->render('prod/Tooltip/DCESFieldInfo.html.twig', ['field' => $field]);
    }

    public function displayMetaRestrictions($sbas_id, $field_id)
    {
        $field = $this->getDataboxField($sbas_id, $field_id);

        return $this->render('prod/Tooltip/DataboxFieldRestrictions.html.twig', ['field' => $field]);
    }

    /**
     * @return UserRepository
     */
    private function getUserRepository()
    {
        return $this->app['repo.users'];
    }

    /**
     * @param mixed $sbas_id
     * @param mixed $field_id
     * @return \databox_field
     */
    private function getDataboxField($sbas_id, $field_id)
    {
        $databox = $this->findDataboxById((int)$sbas_id);

        return $databox->get_meta_structure()->get_element($field_id);
    }
}
