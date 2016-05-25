<?php

/*
 * This file is part of alchemy/pipeline-component.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Account;

class RestrictedStatusExtractor 
{
    /**
     * @var \ACL
     */
    private $acl;

    /**
     * @var \appbox
     */
    private $applicationBox;

    /**
     * @param \ACL $acl
     * @param \appbox $applicationBox
     */
    public function __construct(\ACL $acl, \appbox $applicationBox)
    {
        $this->acl = $acl;
        $this->applicationBox = $applicationBox;
    }

    public function getRestrictedStatuses($baseId)
    {
        $restrictions = [];

        $andMask = $this->acl->get_mask_and($baseId);
        $xorMask = $this->acl->get_mask_xor($baseId);

        $structure = $this->getStatusStructure($baseId);

        for ($position = 0; $position < 32; $position++) {
            $andBit = (1 << $position) & $andMask;
            $xorBit = (1 << $position) & $xorMask;

            $status = $structure->getStatus($position);

            if (! $andBit && $status['labels_on_i18n'] == null && $status['labels_off_i18n'] == null) {
                // Ignore unrestricted statuses with null label arrays (label is not configured, hence not used)
                continue;
            }

            $restrictions[] = [
                'position' => $position,
                'labels' => [
                    'on' => $status['labels_on_i18n'],
                    'off' => $status['labels_off_i18n']
                ],
                'restricted' => (bool) $andBit,
                'restriction_flag' => (bool) $xorBit
            ];
        }

        return $restrictions;
    }

    /**
     * @param $baseId
     * @return \Alchemy\Phrasea\Status\StatusStructure
     */
    private function getStatusStructure($baseId)
    {
        $databoxId = $this->applicationBox->get_collection($baseId)->get_sbas_id();
        $databox = $this->applicationBox->get_databox($databoxId);

        return $databox->getStatusStructure();
    }
}
