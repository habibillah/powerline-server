<?php
namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Event\Micropetition\PetitionEvent;
use Civix\CoreBundle\Event\MicropetitionEvents;
use Civix\CoreBundle\Service\PushTask;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MicropetitionPushSenderListener implements EventSubscriberInterface
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
            MicropetitionEvents::PETITION_ANSWERED => 'sendPush',
        ];
    }

    public function sendPush(PetitionEvent $event)
    {
        $petition = $event->getPetition();
        $this->pushTask->addToQueue(
            'sendGroupPetitionPush',
            [$petition->getGroup()->getId(), $petition->getId()]
        );
    }
}