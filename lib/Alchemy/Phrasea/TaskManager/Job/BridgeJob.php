<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Event\BridgeUploadFailureEvent;
use Alchemy\Phrasea\TaskManager\Editor\DefaultEditor;

class BridgeJob extends AbstractJob
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->translator->trans('Bridge uploader');
    }

    /**
     * {@inheritdoc}
     */
    public function getJobId()
    {
        return 'Bridge';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->translator->trans('Keep synchronization between bridge and client APIs.');
    }

    /**
     * {@inheritdoc}
     */
    public function getEditor()
    {
        return new DefaultEditor($this->translator);
    }

    /**
     * {@inheritdoc}
     */
    protected function doJob(JobData $data)
    {
        $app = $data->getApplication();

        $status = [
            \Bridge_Element::STATUS_PENDING,
            \Bridge_Element::STATUS_PROCESSING,
            \Bridge_Element::STATUS_PROCESSING_SERVER
        ];

        $params = [];
        $n = 1;

        foreach ($status as $stat) {
            $params[':status' . $n] = $stat;
            $n ++;
        }

        $sql = 'SELECT id, account_id FROM bridge_elements'
            . ' WHERE (status = ' . implode(' OR status = ', array_keys($params)) . ')';

        $stmt = $app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            if (!$this->isStarted()) {
                break;
            }
            try {
                $account = \Bridge_Account::load_account($app, $row['account_id']);
                $element = new \Bridge_Element($app, $account, $row['id']);

                $this->log('debug', "process " . $element->get_id() . " with status " . $element->get_status());

                if ($element->get_status() == \Bridge_Element::STATUS_PENDING) {
                    $this->upload_element($element);
                } else {
                    $this->update_element($app, $element);
                }
            } catch (\Exception $e) {
                $this->log('error', sprintf("An error occured : %s", $e->getMessage()));

                $sql = 'UPDATE bridge_elements SET status = :status WHERE id = :id';
                $params = [':status' => \Bridge_Element::STATUS_ERROR, ':id' => $row['id']];
                $stmt = $app->getApplicationBox()->get_connection()->prepare($sql);
                $stmt->execute($params);
                $stmt->closeCursor();
            }
        }
    }

    /**
     * @param Bridge_Element $element
     *
     * @return BridgeJob
     */
    private function upload_element(\Bridge_Element $element)
    {
        $account = $element->get_account();
        $element->set_status(\Bridge_Element::STATUS_PROCESSING);
        $dist_id = null;
        try {
            $dist_id = $account->get_api()->upload($element->get_record(), $element->get_datas());
            $element->set_uploaded_on(new \DateTime());
            $element->set_status(\Bridge_Element::STATUS_DONE);
        } catch (\Exception $e) {
            $this->log('debug', 'Error while uploading : ' . $e->getMessage());
            $element->set_status(\Bridge_Element::STATUS_ERROR);
        }
        $element->set_dist_id($dist_id);

        return $this;
    }

    /**
     * @param Bridge_Element $element
     *
     * @return BridgeJob
     */
    protected function update_element(Application $app, \Bridge_Element $element)
    {
        $account = $element->get_account();
        $connector_status = $account->get_api()->get_element_status($element);

        $status = $element->get_account()->get_api()->map_connector_to_element_status($connector_status);
        $error_message = $element->get_account()->get_api()->get_error_message_from_status($connector_status);

        $previous_status = $element->get_status();

        if ($status) {
            $element->set_status($status);
            $this->log('debug', 'updating status for : ' . $element->get_id() . " to " . $status);
        }
        $element->set_connector_status($connector_status);

        if ($status === $previous_status) {
            return;
        }

        switch ($status) {
            case \Bridge_Element::STATUS_ERROR:
                $app['dispatcher']->dispatch(new BridgeUploadFailureEvent($element, $error_message));
                break;
            default:
            case \Bridge_Element::STATUS_DONE:
            case \Bridge_Element::STATUS_PENDING:
            case \Bridge_Element::STATUS_PROCESSING_SERVER:
            case \Bridge_Element::STATUS_PROCESSING:

                break;
        }

        return $this;
    }
}
