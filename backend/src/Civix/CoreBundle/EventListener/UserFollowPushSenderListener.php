<?php
namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Event\UserEvents;
use Civix\CoreBundle\Event\UserFollowEvent;
use Civix\CoreBundle\Service\PushTask;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserFollowPushSenderListener implements EventSubscriberInterface
{
    /**
     * @var PushTask
     */
    private $pushTask;

    public function __construct(PushTask $pushTask)
    {
        $this->pushTask = $pushTask;
    }

    public static function getSubscribedEvents()
    {
        return [
            UserEvents::FOLLOWED => 'sendPush',
        ];
    }

    public function sendPush(UserFollowEvent $event)
    {
        $follow = $event->getUserFollow();
        $this->pushTask->addToQueue('sendInfluencePush', [
            $follow->getUser()->getId(), 
            $follow->getFollower()->getId(),
        ]);
    }
}