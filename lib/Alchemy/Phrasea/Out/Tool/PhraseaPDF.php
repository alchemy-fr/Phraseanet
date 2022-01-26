<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Out\Tool;

use Alchemy\Phrasea\Model\Entities\User;

class PhraseaPDF extends \TCPDF
{
    const FONT = 'freesans';

    /** @var User */
    private $printOwnerUser;

    public function Header()
    {

    }

    public function Footer()
    {
        $owner = ' Phraseanet';
        $unNeededTextLength = '';
        if (!empty($this->printOwnerUser)) {
            $owner = sprintf('Printed with Phraseanet by <a href="mailto:%s">%s</a> on %s', $this->printOwnerUser->getEmail(), $this->printOwnerUser->getDisplayName(), (new \DateTime())->format("Y/m/d"));
            $unNeededTextLength = sprintf('<a href="%s"></a>', $this->printOwnerUser->getEmail());
        }

        $this->SetLeftMargin(0);
        $mr = $this->SetRightMargin(0);

        $this->SetY(-15);

        $this->SetFont(self::FONT, 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');

        if (empty($this->printOwnerUser)) {
            $this->SetFont(self::FONT, '', 8);
            $w = $this->GetStringWidth('Printed by');

            $this->SetFont(self::FONT, 'B', 8);
            $w += $this->GetStringWidth($owner);

            $this->SetXY(-$w - $mr - 5, -15);

            $this->SetFont(self::FONT, '', 8);
            $this->Write(8, 'Printed by');

            $this->SetFont(self::FONT, 'B', 8);
            $this->Write(8, ' Phraseanet');
        } else {
            $this->SetFont(self::FONT, '', 8);
            $w = $this->GetStringWidth($owner) - $this->GetStringWidth($unNeededTextLength);

            $this->SetXY(-$w - $mr -5, -15);

            $this->SetFont(self::FONT, '', 8);
            $this->writeHTMLCell($w,8, '', '', $owner);
        }
    }

    public function setPrintOwnerUser($user)
    {
        $this->printOwnerUser = $user;
    }
}
