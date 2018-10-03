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

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Authentication\ACLProvider;
use Alchemy\Phrasea\Authentication\Authenticator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DatafileController extends AbstractDelivery
{
    /** @var ACLProvider */
    private $acl;
    /** @var \appbox */
    private $appbox;
    /** @var Authenticator */
    private $authentication;

    public function __construct(Application $app, \appbox $appbox, ACLProvider $acl, Authenticator $authenticator)
    {
        parent::__construct($app);

        $this->appbox = $appbox;
        $this->acl = $acl;
        $this->authentication = $authenticator;
    }

    public function getAction(Request $request, $sbas_id, $record_id, $subdef)
    {
        $databox = $this->appbox->get_databox((int) $sbas_id);
        $record = new \record_adapter($this->app, $sbas_id, $record_id);

        $stamp = $watermark = false;

        if ($subdef != 'thumbnail') {
            $all_access = false;
            $subdefGroup = $databox->get_subdef_structure()->getSubdefGroup($record->getType());
            if ($subdefGroup) {
                foreach ($subdefGroup as $subdefObj) {
                    if ($subdefObj->get_name() == $subdef) {
                        if ($subdefObj->get_class() == 'thumbnail') {
                            $all_access = true;
                        }
                        break;
                    }
                }
            }

            if (!$record->has_subdef($subdef) || !$record->get_subdef($subdef)->is_physically_present()) {
                throw new NotFoundHttpException;
            }

            if (!$this->acl->get($this->authentication->getUser())->has_access_to_subdef($record, $subdef)) {
                throw new AccessDeniedHttpException(sprintf('User has not access to subdef %s', $subdef));
            }

            $stamp = false;
            $watermark = !$this->acl->get($this->authentication->getUser())
                ->has_right_on_base($record->getBaseId(), \ACL::NOWATERMARK);

            if ($watermark && !$all_access) {
                $subdef_class = null;
                try {
                    $subdef_class = $databox
                        ->get_subdef_structure()
                        ->get_subdef($record->getType(), $subdef)
                        ->get_class();
                } catch (\Exception_Databox_SubdefNotFound $e) {

                }

                if ($subdef_class == \databox_subdef::CLASS_PREVIEW && $this->acl->get($this->authentication->getUser())->has_preview_grant($record)) {
                    $watermark = false;
                } elseif ($subdef_class == \databox_subdef::CLASS_DOCUMENT && $this->acl->get(
                        $this->authentication->getUser())->has_hd_grant($record)) {
                    $watermark = false;
                }
            }

            if ($watermark && !$all_access) {
                $repository = $this->app['repo.basket-elements'];

                $ValidationByRecord = $repository->findReceivedValidationElementsByRecord($record, $this->authentication->getUser());
                $ReceptionByRecord = $repository->findReceivedElementsByRecord($record, $this->authentication->getUser());

                if ($ValidationByRecord && count($ValidationByRecord) > 0) {
                    $watermark = false;
                } elseif ($ReceptionByRecord && count($ReceptionByRecord) > 0) {
                    $watermark = false;
                }
            }
        }

        return $this->deliverContent($request, $record, $subdef, $watermark, $stamp);
    }
}
