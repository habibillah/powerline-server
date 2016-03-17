<?php
namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\SocialActivity;
use Civix\CoreBundle\Service\PushTask;
use Doctrine\ORM\Event\LifecycleEventArgs;

class SocialActivityPushSenderListener
{
    /**
     * @var PushTask
     */
    private $pushTask;

    public function __construct(PushTask $pushTask)
    {
        $this->pushTask = $pushTask;
    }
    
    public function postPersist(LifecycleEventArgs $event)
    {
        $activity = $event->getEntity();
        
        if ($activity instanceof SocialActivity) {
            return;
        }
        
        $this->pushTask->addToQueue('sendSocialActivity', [
            $activity->getId()
        ]);
    }
}