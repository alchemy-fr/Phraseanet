<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Core\Configuration;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class task_period_apibridge extends task_appboxAbstract
{

    /**
     * Return the name of the task
     * @return string
     */
    public function getName()
    {
        return 'API bridge uploader';
    }

    /**
     * Get help
     * @return string
     */
    public function help()
    {
        return '';
    }

    /**
     *
     * @param  appbox $appbox
     * @return Array
     */
    protected function retrieveContent(appbox $appbox)
    {
        $status = array(Bridge_Element::STATUS_PENDING, Bridge_Element::STATUS_PROCESSING, Bridge_Element::STATUS_PROCESSING_SERVER);

        $params = array();
        $n = 1;

        foreach ($status as $stat) {
            $params[':status' . $n] = $stat;
            $n ++;
        }

        $sql = 'SELECT id, account_id FROM bridge_elements'
            . ' WHERE (status = ' . implode(' OR status = ', array_keys($params)) . ')';

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        return $rs;
    }

    /**
     *
     * @param  appbox                $appbox
     * @param  array                 $row
     * @return task_period_apibridge
     */
    protected function processOneContent(appbox $appbox, Array $row)
    {
        try {
            $account = Bridge_Account::load_account($this->dependencyContainer, $row['account_id']);
            $element = new Bridge_Element($this->dependencyContainer, $account, $row['id']);

            $this->log("process " . $element->get_id() . " with status " . $element->get_status());

            if ($element->get_status() == Bridge_Element::STATUS_PENDING) {
                $this->upload_element($element);
            } else {
                $this->update_element($element);
            }
        } catch (Exception $e) {
            $sql = 'UPDATE bridge_elements SET status = :status WHERE id = :id';

            $params = array(
                ':status' => Bridge_Element::STATUS_ERROR
                , ':id'     => $row['id']
            );

            $stmt = $appbox->get_connection()->prepare($sql);
            $stmt->execute($params);
            $stmt->closeCursor();
        }

        return $this;
    }

    /**
     *
     * @param  appbox                $appbox
     * @param  array                 $row
     * @return task_period_apibridge
     */
    protected function postProcessOneContent(appbox $appbox, Array $row)
    {
        return $this;
    }

    /**
     *
     * @param  Bridge_Element        $element
     * @return task_period_apibridge
     */
    private function upload_element(Bridge_Element $element)
    {
        $account = $element->get_account();
        $element->set_status(Bridge_Element::STATUS_PROCESSING);
        $dist_id = null;
        try {
            $dist_id = $account->get_api()->upload($element->get_record(), $element->get_datas());
            $element->set_uploaded_on(new DateTime());
        } catch (Exception $e) {
            $this->log('Error while uploading : ' . $e->getMessage());
            $element->set_status(Bridge_Element::STATUS_ERROR);
        }
        $element->set_dist_id($dist_id);

        return $this;
    }

    /**
     *
     * @param  Bridge_Element        $element
     * @return task_period_apibridge
     */
    protected function update_element(Bridge_Element $element)
    {
        $account = $element->get_account();
        $connector_status = $account->get_api()->get_element_status($element);

        $status = $element->get_account()->get_api()->map_connector_to_element_status($connector_status);
        $error_message = $element->get_account()->get_api()->get_error_message_from_status($connector_status);

        $previous_status = $element->get_status();

        if ($status) {
            $element->set_status($status);
            $this->log('updating status for : ' . $element->get_id() . " to " . $status);
        }
        $element->set_connector_status($connector_status);

        if ($status === $previous_status) {
            return;
        }

        switch ($status) {
            case Bridge_Element::STATUS_ERROR:

                $params = array(
                    'usr_id'     => $account->get_user()->get_id()
                    , 'reason'     => $error_message
                    , 'account_id' => $account->get_id()
                    , 'sbas_id'    => $element->get_record()->get_sbas_id()
                    , 'record_id'  => $element->get_record()->get_record_id()
                );
                $events_mngr = $this->dependencyContainer['events-manager'];
                $events_mngr->trigger('__BRIDGE_UPLOAD_FAIL__', $params);

                break;
            default:
            case Bridge_Element::STATUS_DONE:
            case Bridge_Element::STATUS_PENDING:
            case Bridge_Element::STATUS_PROCESSING_SERVER:
            case Bridge_Element::STATUS_PROCESSING:

                break;
        }

        return $this;
    }
}
