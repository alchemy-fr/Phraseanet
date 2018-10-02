<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Search;

use Alchemy\Phrasea\Authentication\ACLProvider;
use Alchemy\Phrasea\Model\Entities\User;
use League\Fractal\TransformerAbstract;

class SubdefTransformer extends TransformerAbstract
{
    /**
     * @var ACLProvider
     */
    private $aclProvider;

    /**
     * @var User
     */
    private $user;

    /**
     * @var PermalinkTransformer
     */
    private $permalinkTransformer;

    public function __construct(ACLProvider $aclProvider, User $user, PermalinkTransformer $permalinkTransformer)
    {
        $this->aclProvider = $aclProvider;
        $this->user = $user;
        $this->permalinkTransformer = $permalinkTransformer;
    }

    public function transform(SubdefView $subdefView)
    {
        $media = $subdefView->getSubdef();

        if (!$media->is_physically_present()) {
            return null;
        }

        $acl = $this->aclProvider->get($this->user);
        $record = $media->get_record();

        if ($media->get_name() !== 'document' && false === $acl->has_access_to_subdef($record, $media->get_name())) {
            return null;
        }
        if ($media->get_name() === 'document'
            && !$acl->has_right_on_base($record->getBaseId(), \ACL::CANDWNLDHD)
            && !$acl->has_hd_grant($record)
        ) {
            return null;
        }

        $permalink = $subdefView->getPermalinkView()
            ? $this->permalinkTransformer->transform($subdefView->getPermalinkView())
            : null;

        return [
            'name' => $media->get_name(),
            'permalink' => $permalink,
            'height' => $media->get_height(),
            'width' => $media->get_width(),
            'filesize' => $media->get_size(),
            'devices' => $media->getDevices(),
            'player_type' => $media->get_type(),
            'mime_type' => $media->get_mime(),
            'substituted' => $media->is_substituted(),
            'created_on'  => $media->get_creation_date()->format(DATE_ATOM),
            'updated_on'  => $media->get_modification_date()->format(DATE_ATOM),
            'url' => $subdefView->getUrl(),
            'url_ttl' => $subdefView->getUrlTTL(),
        ];
    }
}
