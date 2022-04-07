<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model\Repositories;

use Alchemy\Phrasea\Cache\Exception;
use Alchemy\Phrasea\Model\Entities\BasketParticipant;
use DateTime;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\ResultSetMappingBuilder;

class BasketParticipantRepository extends EntityRepository
{
    /**
     * Retrieve all not reminded participants where the validation has not expired
     *
     * @param $timeLeftPercent              float        Percent of the time left before the validation expires.
     * @param $today DateTime               fake "today" to allow to get past/future events
     *                                      (used by SendValidationRemindersCommand.php to debug with --dry)
     * @return BasketParticipant[]
     * @throws \Exception
     */
    public function findNotConfirmedAndNotRemindedParticipantsByTimeLeftPercent(float $timeLeftPercent, DateTime $today=null)
    {
        $rsm = new ResultSetMappingBuilder($this->_em);
        $rsm->addRootEntityFromClassMetadata('Alchemy\Phrasea\Model\Entities\BasketParticipant', 'p');
        $selectClause = $rsm->generateSelectClause();

        switch($this->_em->getConnection()->getDriver()->getName()) {
            case 'pdo_mysql':
                $sql = '
                    SELECT ' . $selectClause . '
                    FROM BasketParticipants p
                    INNER JOIN Baskets b on b.id = p.basket_id
                    WHERE p.is_confirmed = 0
                    AND p.reminded IS NULL
                    AND b.vote_expires > '. ($today===null ? 'CURRENT_TIMESTAMP()' : ':today') . '
                    AND DATE_SUB(b.vote_expires, INTERVAL FLOOR((TO_SECONDS(b.vote_expires) -  TO_SECONDS(b.vote_created)) * :percent) SECOND) <= '. ($today===null ? 'CURRENT_TIMESTAMP()' : ':today')
                ;

                break;
            case 'pdo_sqlite':
                $sql = '
                    SELECT ' . $selectClause . '
                    FROM BasketParticipants p
                    INNER JOIN Baskets b on b.id = p.basket_id
                    WHERE p.is_confirmed = 0
                    AND p.reminded IS NULL
                    AND b.vote_expires > '. ($today===null ? 'strftime("%s","now")' : 'strftime("%s", :today)') . '
                    AND (strftime("%s", b.vote_expires) - ((strftime("%s", b.vote_expires) -  strftime("%s", b.vote_created)) * :percent)  )<= '. ($today===null ? 'strftime("%s","now")' : 'strftime("%s", :today)')
                ;

                break;
            default:
                throw new Exception('Unused PDO!, if necessary define the query for this PDO');

        }

        $q = $this->_em->createNativeQuery($sql, $rsm);
        $q->setParameter('percent', (float)($timeLeftPercent/100));

        if($today !== null) {
            $q->setParameter('today', $today, Type::DATETIME);
        }

        return $q->getResult();
    }
}

