<?php

namespace Stfalcon\Bundle\EventBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Application\Bundle\UserBundle\Entity\User;
use Stfalcon\Bundle\EventBundle\Entity\Event;

/**
 * EventRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class TicketRepository extends EntityRepository
{
    /**
     * Find tickets of active events for some user
     *
     * @param User $user
     *
     * @return array
     */
    public function findTicketsOfActiveEventsForUser(User $user)
    {
        return $this->getEntityManager()
            ->createQuery('
                SELECT t
                FROM StfalconEventBundle:Ticket t
                JOIN t.event e
                WHERE e.active = TRUE
                    AND t.user = :user
            ')
            ->setParameter('user', $user)
            ->getResult();
    }


    /**
     * @param Event $event  Event
     * @param null  $status Status
     *
     * @return array
     */
    public function findUsersByEventAndStatus(Event $event = null, $status = null)
    {
        $query = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('u', 't', 'p')
            ->from('StfalconEventBundle:Ticket', 't')
            ->join('t.user', 'u')
            ->join('t.event', 'e')
            ->join('t.payment', 'p')
            ->andWhere('e.active = 1');

        if ($event != null) {
            $query->andWhere('t.event = :event')
                ->setParameter(':event', $event);
        }
        if ($status != null) {
            $query->andWhere('p.status = :status')
                ->setParameter(':status', $status);
        }

        $query = $query->getQuery();

        $users = array();
        foreach ($query->execute() as $result) {
            $users[] = $result->getUser();
        }

        return $users;
    }

    /**
     * Find users by event and status
     *
     * @param Array $events  Events
     * @param null  $status Status
     *
     * @return array
     */
    public function findUsersByEventsAndStatus($events = null, $status = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('u')
            ->addSelect('t')
            ->from('StfalconEventBundle:Ticket', 't')
            ->join('t.user', 'u')
            ->join('t.event', 'e')
            ->join('t.payment', 'p')
            ->andWhere('e.active = :eventStatus')
            ->setParameter(':eventStatus', true)
            ->groupBy('u');

        if ($events != null) {
            $qb->andWhere($qb->expr()->in('t.event', ':events'))
                ->setParameter(':events', $events->toArray());
        }
        if ($status != null) {
            $qb->andWhere('p.status = :status')
                ->setParameter(':status', $status);
        }

        $qb = $qb->getQuery();

        $users = array();
        foreach ($qb->execute() as $result) {
            $users[] = $result->getUser();
        }

        return $users;
    }

    /**
     * Find tickets by event
     *
     * @param Event $event
     *
     * @return array
     */
    public function findTicketsByEvent(Event $event)
    {
        return $this->getEntityManager()
            ->createQuery('
                SELECT t
                FROM StfalconEventBundle:Ticket t
                JOIN t.event e
                WHERE e.active = TRUE
                    AND t.event = :event
                GROUP BY t.user
            ')
            ->setParameter('event', $event)
            ->getResult();
    }

    /**
     * Find tickets by event group by user
     *
     * @param Event $event
     * @param int   $count
     *
     * @return array
     */
    public function findTicketsByEventGroupByUser(Event $event, $count = null)
    {
        $qb = $this->createQueryBuilder('t')
            ->select('t')
            ->join('t.event', 'e')
            ->where('e.active = true')
            ->andWhere('t.event = :event')
            ->groupBy('t.user')
            ->setParameter('event', $event);

        if (isset($count) && $count > 0) {
            $qb->setMaxResults($count);
        }

        return $qb->getQuery() ->getResult();
    }
}
