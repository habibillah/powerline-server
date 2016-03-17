<?php
namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\Poll\Question\LeaderEvent;
use Civix\CoreBundle\Entity\Poll\Question\LeaderNews;
use Civix\CoreBundle\Entity\Poll\Question\PaymentRequest;
use Civix\CoreBundle\Entity\Poll\Question\Petition;
use Civix\CoreBundle\Event\Poll\QuestionEvent;
use Civix\CoreBundle\Event\PollEvents;
use Civix\CoreBundle\Service\PushTask;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PollPushSenderListener implements EventSubscriberInterface
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
            PollEvents::QUESTION_PUBLISHED => 'sendPush',
        ];
    }

    public function sendPush(QuestionEvent $event)
    {
        $entity = $event->getQuestion();
        
        if ($entity instanceof LeaderNews) {
            $params = [
                $entity->getId(),
                "Discuss: {$entity->getSubject()}",
            ];
        } elseif ($entity instanceof Petition) {
            $params = [
                $entity->getId(),
                "Sign: {$entity->getPetitionTitle()}",
                "Sign: {$entity->getPetitionBody()}",
            ];
        } elseif ($entity instanceof PaymentRequest) {
            $params = [
                $entity->getId(),
                "{$entity->getUser()->getOfficialName()} Fundraiser",
                $entity->getTitle()
            ];
        } elseif ($entity instanceof LeaderEvent) {
            $params = [
                $entity->getId(),
                "{$entity->getUser()->getOfficialName()} Event",
                "RSVP: {$entity->getTitle()}"
            ];
        } else {
            $params = [
                $entity->getId(),
                "{$entity->getUser()->getOfficialName()} Poll",
                $entity->getSubject()
            ];
        }
        
        $this->pushTask->addToQueue('sendPushPublishQuestion', $params);
    }
}