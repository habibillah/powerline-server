<?php
namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\Micropetitions\Comment;
use Civix\CoreBundle\Service\PushTask;
use Doctrine\ORM\Event\LifecycleEventArgs;

class MicropetitionCommentPushSenderListener
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
        $comment = $event->getEntity();
        
        if (!$comment instanceof Comment) {
            return;
        }
        
        $this->pushTask->addToQueue('sendPostCommentedPush', [$comment->getId()]);
    }
}