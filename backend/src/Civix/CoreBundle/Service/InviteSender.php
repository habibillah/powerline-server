<?php

namespace Civix\CoreBundle\Service;

use Civix\CoreBundle\Entity\Invites\UserToGroup;
use Civix\CoreBundle\Event\GroupEvents;
use Civix\CoreBundle\Event\GroupUserEvent;
use Civix\CoreBundle\Service\Mailgun\MailgunApi;
use Civix\CoreBundle\Entity\DeferredInvites;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\User;
use Cocur\Slugify\Slugify;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class InviteSender
{
    private $emailSender;
    private $templating;
    private $pushTask;
    private $entityManager;
    private $from;
    private $mailgun;
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(
        EmailSender $emailSender,
        PushTask $pushTask,
        \Doctrine\ORM\EntityManager $entityManager,
        MailgunApi $mailgunApi,
        EventDispatcherInterface $dispatcher
    ) {
        $this->emailSender = $emailSender;
        $this->entityManager = $entityManager;
        $this->pushTask = $pushTask;
        $this->mailgun = $mailgunApi;
        $this->dispatcher = $dispatcher;
    }

    public function send(array $invites, Group $group)
    {
        foreach ($invites as $invite) {
            if ($invite instanceof User) {
                $this->pushTask->addToQueue('sendInvitePush', array($invite->getId(), $group->getId()));
            } elseif ($invite instanceof DeferredInvites) {
                $this->emailSender->sendInviteFromGroup($invite->getEmail(), $invite->getGroup());
            }
        }
    }

    public function sendUserToGroupInvites(array $invites)
    {
        /* @var $invite UserToGroup */
        foreach ($invites as $invite) {
            $this->pushTask->addToQueue('sendInvitePush', array($invite->getUser()->getId(), $invite->getGroup()->getId()));
        }
    }

    public function saveInvites(array $emails, Group $group)
    {
        $emails = array_diff(
            $emails,
            $this->entityManager->getRepository('CivixCoreBundle:DeferredInvites')
                ->getEmails($group, $emails)
        );

        $users = $this->entityManager->getRepository('CivixCoreBundle:User')->getUsersByEmails($emails);
        $usersEmails = array();
        $invites = array();

        /** @var $user \Civix\CoreBundle\Entity\User */
        foreach ($users as $user) {
            if (!$group->getInvites()->contains($user) && !$group->getUsers()->contains($user)) {
                $event = new GroupUserEvent($group, $user);
                $this->dispatcher->dispatch(GroupEvents::USER_JOINED, $event);
                $user->addInvite($group);
                $invites[] = $user;
                $usersEmails[] = $user->getEmail();
            }
        }

        foreach (array_diff($emails, $usersEmails) as $email) {
            $deferredInvite = $this->createDeferredInvites($email, $group);
            $this->entityManager->persist($deferredInvite);
            $invites[] = $deferredInvite;
        }

        $this->entityManager->flush();

        return $invites;
    }

    public function sendInviteForPetition($answers, Group $group)
    {
        /* @var $signedUser \Civix\CoreBundle\Entity\User */
        foreach ($answers as $signedUserAnswer) {
            $signedUser = $signedUserAnswer->getUser();
            if (!$group->getInvites()->contains($signedUser) && !$group->getUsers()->contains($signedUser)) {
                $signedUser->addInvite($group);
                if ($signedUser->getIsRegistrationComplete()) {
                    $this->pushTask->addToQueue('sendInvitePush', array($signedUser->getId(), $group->getId()));
                } else {
                    $this->emailSender->sendInviteFromGroup($signedUser->getEmail(), $group);
                }
            }
        }
    }

    private function createDeferredInvites($email, Group $group)
    {
        $differedEntity = $this->entityManager
            ->getRepository('CivixCoreBundle:DeferredInvites')
            ->findOneBy(array(
                    'email' => $email,
                    'group' => $group,
            ));
        if (!($differedEntity instanceof DeferredInvites)) {
            $differedEntity = new DeferredInvites();
            $differedEntity->setEmail($email)
                ->setGroup($group);
        }

        return $differedEntity;
    }
}
