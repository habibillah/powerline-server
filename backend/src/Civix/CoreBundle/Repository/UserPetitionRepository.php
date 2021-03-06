<?php

namespace Civix\CoreBundle\Repository;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Micropetitions\Petition;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserGroup;
use Civix\CoreBundle\Entity\UserPetition;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

class UserPetitionRepository extends EntityRepository
{
    public function getMyGroupsUserPetitions(User $user)
    {
        $entityManager = $this->getEntityManager();

        $activeGroups = $entityManager
            ->getRepository('CivixCoreBundle:UserGroup')
            ->getSubQueryGroupByJoinStatus()
            ->setParameter('user', $user)
            ->setParameter('joinSubqueryStatus', UserGroup::STATUS_ACTIVE)
            ->getQuery()
            ->getResult();

        return $entityManager->createQueryBuilder()
                ->select('p, u, g')
                ->from(UserPetition::class, 'p')
                ->leftJoin('p.user', 'u')
                ->leftJoin('p.group', 'g')
                ->where('p.expiredAt >= :currentDate')
                ->andWhere('p.group IN (:userGroups)')
                ->setParameter('currentDate', new \DateTime())
                ->setParameter('userGroups', empty($activeGroups) ? 0 : $activeGroups)
                ->getQuery()
                ->getResult();
    }

    public function findByParams($params)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('p, u, g')
            ->from(Petition::class, 'p')
            ->leftJoin('p.user', 'u')
            ->leftJoin('p.group', 'g')
            ->where('p.createdAt > :start')
            ->setParameter('start', new \DateTime(isset($params['start']) ? $params['start'] : 'now'))
        ;
        if (isset($params['user'])) {
            $qb->andWhere('p.user = :user')
                ->setParameter('user', $params['user']);
        }

        return $qb->getQuery()->setMaxResults(200)->getResult();
    }

    public function getCountPerMonthPetitionByOwner(User $owner, Group $group)
    {
        $currentDate = new \DateTime();
        $resetTimeDate = new \DateTime($currentDate->format('Y-m-d'));
        $startOfMonth = $resetTimeDate->modify('first day of this month');

        $petitionCount = $this->getEntityManager()
                ->createQueryBuilder()
                ->select('count(p) as petitionCount')
                ->from(UserPetition::class, 'p')
                ->where('p.user = :user')
                ->andWhere('p.group = :group')
                ->andWhere('p.createdAt >= :startOfMonth')
                ->andWhere('p.createdAt <= :endOfMonth')
                ->setParameter('user', $owner)
                ->setParameter('group', $group)
                ->setParameter('startOfMonth', $startOfMonth)
                ->setParameter('endOfMonth', $currentDate)
                ->getQuery()
                ->getOneOrNullResult();

        return isset($petitionCount['petitionCount']) ? (int) $petitionCount['petitionCount'] : 0;
    }

    public function getPetitionForUser($petitionId, User $user)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('p, a')
            ->from(UserPetition::class, 'p')
            ->leftJoin('p.signatures', 'a', 'WITH', 'a.user = :user')
            ->where('p.id = :petitionId')
            ->setParameter('petitionId', $petitionId)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getClosedMicropetition(Group $group)
    {
        return $this->getEntityManager()->createQueryBuilder()
                ->select('p, u')
                ->from(UserPetition::class, 'p')
                ->leftJoin('p.user', 'u')
                ->where('p.expiredAt < :currentDate')
                ->andWhere('p.group = :group')
                ->orderBy('p.expiredAt', 'DESC')
                ->setParameter('currentDate', new \DateTime())
                ->setParameter('group', $group->getId())
                ->getQuery();
    }

    public function getOpenMicropetition(Group $group)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('p, u')
            ->from(UserPetition::class, 'p')
            ->leftJoin('p.user', 'u')
            ->where('p.expiredAt > :currentDate')
            ->andWhere('p.group = :group')
            ->andWhere('p.publishStatus != 1')
            ->orderBy('p.expiredAt', 'DESC')
            ->setParameter('currentDate', new \DateTime())
            ->setParameter('group', $group->getId())
            ->getQuery();
    }

    public function findActiveByHashTag($query, User $user)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('p, u, g')
            ->from(UserPetition::class, 'p')
            ->leftJoin('p.user', 'u')
            ->leftJoin('p.group', 'g')
            ->leftJoin('p.hashTags', 'h')
            ->leftJoin('g.users', 'ug')
            ->where('p.expiredAt >= :currentDate')
            ->andWhere('h.name = :query')
            ->andWhere('ug.user = :user')
            ->andWhere('ug.status = :status')
            ->setParameter('query', $query)
            ->setParameter('user', $user)
            ->setParameter('currentDate', new \DateTime())
            ->setParameter('status', UserGroup::STATUS_ACTIVE)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getFindByQuery(User $user, $criteria)
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p, u, g, a')
            ->leftJoin('p.user', 'u')
            ->leftJoin('p.group', 'g')
            ->leftJoin('p.signatures', 'a', Join::WITH, 'a.user = :user')
            ->setParameter(':user', $user)
        ;
        if (!empty($criteria['start'])) {
            $qb->andWhere('p.createdAt > :start')
                ->setParameter(':start', $criteria['start']);
        }
        if (!empty($criteria['tag'])) {
            $qb->leftJoin('p.hashTags', 'h')
                ->andWhere('h.name = :tag')
                ->setParameter(':tag', $criteria['tag']);
        }

        return $qb->getQuery();
    }

    public function getFindByUserGroupsQuery(User $user)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('p')
            ->from(UserPetition::class, 'p')
            ->leftJoin('p.user', 'u')
            ->leftJoin('u.groups', 'ug')
            ->where('ug.user = :user')
            ->setParameter(':user', $user)
            ->andWhere('ug.status = :status')
            ->setParameter(':status', UserGroup::STATUS_ACTIVE)
            ->getQuery();
    }
}
