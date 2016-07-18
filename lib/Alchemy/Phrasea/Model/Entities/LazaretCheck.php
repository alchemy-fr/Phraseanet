<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model\Entities;

use Alchemy\Phrasea\Application;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @ORM\Table(name="LazaretChecks")
 * @ORM\Entity
 */
class LazaretCheck
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=512)
     */
    private $checkClassname;

    /**
     * @ORM\ManyToOne(targetEntity="LazaretFile", inversedBy="checks", cascade={"persist"})
     * @ORM\JoinColumn(name="lazaret_file_id", referencedColumnName="id")
     */
    private $lazaretFile;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set checkClassname
     *
     * @param  string       $checkClassname
     * @return LazaretCheck
     */
    public function setCheckClassname($checkClassname)
    {
        $this->checkClassname = $checkClassname;

        return $this;
    }

    /**
     * Get checkClassname
     *
     * @return string
     */
    public function getCheckClassname()
    {
        return $this->checkClassname;
    }

    /**
     * Set lazaretFile
     *
     * @param  LazaretFile  $lazaretFile
     * @return LazaretCheck
     */
    public function setLazaretFile(LazaretFile $lazaretFile = null)
    {
        $this->lazaretFile = $lazaretFile;

        return $this;
    }

    /**
     * Get lazaretFile
     *
     * @return LazaretFile
     */
    public function getLazaretFile()
    {
        return $this->lazaretFile;
    }

    /**
     * @param TranslatorInterface $translator
     * @return string   the reason why a record is in lazaret
     */
    public function getReason(TranslatorInterface $translator)
    {
        $className = $this->getCheckClassname();
        if (method_exists($className, "getReason")) {
            return $className::getReason($translator);
        } else {
            return '';
        }
    }

    /**
     * @param Application $app
     * @return \record_adapter[]  the records conflicting with this check
     */
    public function listConflicts(Application $app)
    {
        $className = $this->getCheckClassname();
        if (method_exists($className, "listConflicts")) {
            return $className::listConflicts($app, $this->lazaretFile);
        } else {
            return [];
        }
    }

}
