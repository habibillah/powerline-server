<?php

namespace Civix\CoreBundle\Service;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Entity\UserPetition\Signature;
use Civix\CoreBundle\Event\UserPetition\SignatureEvent;
use Civix\CoreBundle\Event\UserPetitionEvent;
use Civix\CoreBundle\Event\UserPetitionEvents;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class UserPetitionManager
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(
        EntityManager $entityManager, 
        EventDispatcherInterface $dispatcher
    )
    {
        $this->entityManager = $entityManager;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Sign a petition with an answer
     *
     * @param UserPetition $petition
     * @param User $user
     *
     * @return Signature
     */
    public function signPetition(UserPetition $petition, User $user)
    {
        $signature = $petition->sign($user);
        $this->entityManager->persist($petition);
        $this->entityManager->flush();

        $signatureEvent = new SignatureEvent($signature);
        $this->dispatcher->dispatch(UserPetitionEvents::PETITION_SIGN, $signatureEvent);

        if (!$petition->isBoosted()
            && $this->checkIfNeedBoost($petition)
        ) {
            $petitionEvent = new UserPetitionEvent($petition);
            $this->dispatcher->dispatch(UserPetitionEvents::PETITION_BOOST, $petitionEvent);
        }

        return $signature;
    }

    /**
     * Unsign a petition with an answer
     *
     * @param Signature $signature
     * @return Signature
     */
    public function unsignPetition(Signature $signature)
    {
        $this->entityManager->remove($signature);
        $this->entityManager->flush();

        $event = new SignatureEvent($signature);
        $this->dispatcher->dispatch(UserPetitionEvents::PETITION_UNSIGN, $event);

        return $signature;
    }

    /**
     * One user can create only 5 micropetition in one group per month.
     * 
     * @param \Civix\CoreBundle\Entity\User  $user
     * @param \Civix\CoreBundle\Entity\Group $petitionGroup
     *
     * @return bool
     */
    public function checkPetitionLimitPerMonth(User $user, Group $petitionGroup)
    {
        $currentPetitionCount = $this->entityManager
            ->getRepository(UserPetition::class)
            ->getCountPerMonthPetitionByOwner($user, $petitionGroup);

        return $currentPetitionCount < $petitionGroup->getPetitionPerMonth();
    }

    /**
     * Check count answers from group of petition. If it greater than 10% group's followers
     * than need to publish to activity.
     *  
     * @param \Civix\CoreBundle\Entity\UserPetition $petition
     * 
     * @return bool
     */
    public function checkIfNeedBoost(UserPetition $petition)
    {
        if ($petition->isOrganizationNeeded()) {
            return false;
        }
        $groupAnswers = $this->entityManager->getRepository(Signature::class)
             ->getCountSignatureFromGroup($petition);

        return $groupAnswers >= $petition->getQuorumCount();
    }

    /**
     * @param UserPetition $petition
     * @return UserPetition
     */
    public function savePetition(UserPetition $petition)
    {
        $isNew = !$petition->getId();

        $this->entityManager->persist($petition);
        $this->entityManager->flush();

        $event = new UserPetitionEvent($petition);
        $eventName = $isNew ? UserPetitionEvents::PETITION_CREATE : UserPetitionEvents::PETITION_UPDATE;
        $this->dispatcher->dispatch($eventName, $event);

        return $petition;
    }
}
