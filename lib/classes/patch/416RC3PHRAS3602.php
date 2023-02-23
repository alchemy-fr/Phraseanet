<?php

use Alchemy\Phrasea\Application;

class patch_416RC3PHRAS3602 implements patchInterface
{
    /** @var string */
    private $release = '4.1.6-rc3';

    /** @var array */
    private $concern = [base::APPLICATION_BOX];

    /**
     * {@inheritdoc}
     */
    public function get_release()
    {
        return $this->release;
    }

    /**
     * {@inheritdoc}
     */
    public function getDoctrineMigrations()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function require_all_upgrades()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function concern()
    {
        return $this->concern;
    }

    /**
     * {@inheritdoc}
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

    private function patch_appbox(base $appbox, Application $app)
    {
        $cnx = $appbox->get_connection();
        $sqls = [
            /**
             * the sessions go directly to baskets, let's call this a "vote session" to not mispatch with former structure
             */
            "update Baskets b inner join ValidationSessions v on v.basket_id=b.id set
b.vote_initiator_id=v.initiator_id,
b.vote_created=v.created,
b.vote_updated=v.updated,
b.vote_expires=v.expires",

            /**
             * the participants are now in relation with a basket
             * nb: now a participant is kept unique to a basket, it was not the case before (very rare doubles ? bug ?)
             *     we keep only the _most recent_ occurence ( max(p.id) )
             * nb: we let the participant id unchanged because it's easier when copying "validationdatas"
             */
            "insert into BasketParticipants (id, user_id, basket_id, can_modify, 
                                is_aware, is_confirmed, can_agree, can_see_others, reminded)
select vp_id as id, user_id, basket_id, 0 as can_modify, is_aware, is_confirmed, can_agree, can_see_others, reminded from 
(
 SELECT concat(user_id, '-', basket_id) as u, 
 group_concat(p.id) as vp_ids,
 max(p.id) as vp_id,
 basket_id,    
 sum(1) as n
 FROM ValidationParticipants p inner join ValidationSessions s on s.id=p.validation_session_id
 group by u
) as a
inner join ValidationParticipants p on p.id=a.vp_id order by vp_id asc",

            /**
             * the "datas" are now "votes"
             * we don't copy orphan data
             * nb: we let the id unchanged
             */
            "insert into BasketElementVotes (id, participant_id, basket_element_id, agreement, note, updated)
SELECT d.id, d.participant_id, d.basket_element_id, d.agreement, d.note, d.updated
FROM ValidationDatas d inner join BasketParticipants p on p.id=d.participant_id
inner join BasketElements e on e.id=d.basket_element_id
order by d.id asc",

            "UPDATE `Baskets` SET `share_expires`=`vote_expires`
WHERE `share_expires` IS NULL AND `vote_expires` IS NOT NULL AND `vote_expires` < NOW();"
        ];

        foreach($sqls as $sql) {
            $cnx->exec($sql);
        }
    }
}
