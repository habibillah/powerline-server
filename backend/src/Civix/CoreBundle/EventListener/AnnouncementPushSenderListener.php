<?php
namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\Announcement\RepresentativeAnnouncement;
use Civix\CoreBundle\Event\AnnouncementEvent;
use Civix\CoreBundle\Event\AnnouncementEvents;
use Civix\CoreBundle\Service\PushTask;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AnnouncementPushSenderListener implements EventSubscriberInterface
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
            AnnouncementEvents::PUBLISHED => 'sendPush',
        ];
    }

    public function sendPush(AnnouncementEvent $event)
    {
        $announcement = $event->getAnnouncement();
        if ($announcement instanceof RepresentativeAnnouncement) {
            $method = 'sendRepresentativeAnnouncementPush';
        } else {
            $method = 'sendGroupAnnouncementPush';
        }
        $this->pushTask->addToQueue($method, [
            $announcement->getUser()->getId(), 
            $announcement->getContent()
        ]);
    }
}