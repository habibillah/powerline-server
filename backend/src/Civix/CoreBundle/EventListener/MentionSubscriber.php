<?php
namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\HtmlBodyInterface;
use Civix\CoreBundle\Entity\User;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;

class MentionSubscriber implements EventSubscriber
{
    public function getSubscribedEvents()
    {
        return [
            Events::prePersist,
            Events::preUpdate,
        ];
    }

    public function prePersist(LifecycleEventArgs $event)
    {
        $this->parseBody($event);
    }

    public function preUpdate(LifecycleEventArgs $event)
    {
        $this->parseBody($event);
    }

    public function parseBody(LifecycleEventArgs $event)
    {
        $entity = $event->getEntity();
        $manager = $event->getEntityManager();

        if (!$entity instanceof HtmlBodyInterface) {
            return;
        }

        $content = strtr($entity->getBody(), ['<' => '&lt;', '>' => '&gt;']);
        $content = preg_replace_callback(
            '/@([a-zA-Z0-9._-]+[a-zA-Z0-9])/',
            function ($matches) use ($manager) {
                $username = $matches[1];
                $user = $manager->getRepository(User::class)
                    ->findOneBy(['username' => $username]);
                if ($user) {
                    return "<a data-user-id=\"{$user->getId()}\">@$username</a>";
                }

                return '@'.$username;
            },
            $content
        );
        $entity->setHtmlBody($content);
    }
}