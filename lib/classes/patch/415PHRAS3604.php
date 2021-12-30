<?php

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Repositories\FeedItemRepository;
use Alchemy\Phrasea\Model\Entities\FeedItem;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class patch_415PHRAS3604 implements patchInterface
{
    /** @var string */
    private $release = '4.1.5';

    /** @var array */
    private $concern = [base::APPLICATION_BOX];

    /**
     * @inheritDoc
     */
    public function get_release()
    {
        return $this->release;
    }

    /**
     * @inheritDoc
     */
    public function concern()
    {
        return $this->concern;
    }

    /**
     * @inheritDoc
     */
    public function require_all_upgrades()
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getDoctrineMigrations()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function apply(base $base, Application $app)
    {
        if ($base->get_base_type() === base::DATA_BOX) {
            $this->patch_databox($base, $app);
        }
        elseif ($base->get_base_type() === base::APPLICATION_BOX) {
            $this->patch_appbox($base, $app);
        }

        return true;
    }

    private function patch_databox(base $databox, Application $app)
    {
    }

    private function patch_appbox(base $databox, Application $app)
    {
        /** @var FeedItemRepository $feedItemRepository */
        $feedItemRepository = $app['repo.feed-items'];

        /** @var FeedItem $feedItem */
        foreach ($feedItemRepository->findAll() as $feedItem) {
            // if the record is not found, delete the feedItem
            try {
                if ($app->findDataboxById($feedItem->getSbasId())->getRecordRepository()->find($feedItem->getRecordId()) == null) {
                    $app['orm.em']->remove($feedItem);
                }
            } catch (NotFoundHttpException $e) {
                // the referenced sbas_id is not found, so delete also the feedItem
                 $app['orm.em']->remove($feedItem);
            }
        }

        $app['orm.em']->flush();
    }
}
