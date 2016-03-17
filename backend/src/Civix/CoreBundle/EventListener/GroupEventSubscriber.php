<?php
namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Event\GroupEvent;
use Civix\CoreBundle\Event\GroupEvents;
use Civix\CoreBundle\Event\GroupUserEvent;
use Civix\CoreBundle\Exception\MailgunException;
use Civix\CoreBundle\Repository\UserRepository;
use Civix\CoreBundle\Service\Mailgun\MailgunApi;
use Cocur\Slugify\Slugify;
use Mailgun\Connection\Exceptions\GenericHTTPError;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class GroupEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var MailgunApi
     */
    private $mailgunApi;
    /**
     * @var UserRepository
     */
    private $repository;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        MailgunApi $mailgunApi,
        UserRepository $repository,
        LoggerInterface $logger
    )
    {
        $this->mailgunApi = $mailgunApi;
        $this->repository = $repository;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents()
    {
        return [
            GroupEvents::CREATED => 'onCreated',
            GroupEvents::USER_JOINED => 'onUserJoined',
            GroupEvents::USER_BEFORE_UNJOIN => 'onUserBeforeUnjoin',
            GroupEvents::BEFORE_DELETE => 'onBeforeDelete',
        ];
    }

    public function onCreated(GroupEvent $event)
    {
        $group = $event->getGroup();
        
        $groupName = $this->slugify($group->getOfficialName());

        try {
            $this->mailgunApi->listCreateAction(
                $groupName,
                $group->getOfficialDescription()
            );
            $this->mailgunApi->listAddMemberAction(
                $groupName,
                $group->getManagerEmail(),
                $group->getManagerFullName()
            );
        } catch (GenericHTTPError $e) {
            $this->logError($e);
        }
    }

    public function onUserJoined(GroupUserEvent $event)
    {
        $group = $event->getGroup();
        $user = $event->getUser();

        $groupName = $this->slugify($group->getOfficialName());

        try {
            if (!$this->mailgunApi->listExistsAction($groupName)) {
                $this->mailgunApi->listCreateAction(
                    $groupName,
                    $group->getOfficialDescription()
                ); 
                $users = array_map(function(User $user) {
                    return [
                        'name' => $user->getFullName(),
                        'address' => $user->getEmail(),
                    ];
                }, $this->repository->getUsersEmailsByGroup($group->getId()));
                $this->mailgunApi->listAddMembersAction($groupName, $users);
            }
            $this->mailgunApi->listAddMemberAction(
                $groupName,
                $user->getEmail(),
                $user->getFullName()
            );
        } catch (GenericHTTPError $e) {
            $this->logError($e);
        }
    }

    public function onUserBeforeUnjoin(GroupUserEvent $event)
    {
        $group = $event->getGroup();
        $user = $event->getUser();

        $groupName = $this->slugify($group->getOfficialName());

        try {
            if ($this->mailgunApi->listExistsAction($groupName)) {
                $this->mailgunApi->listRemoveMemberAction(
                    $groupName,
                    $user->getEmail()
                );
            }
        } catch (GenericHTTPError $e) {
            $this->logError($e);
            throw new MailgunException("An error has occurred in ".__FUNCTION__, $e->getCode(), $e);
        }
    }

    public function onBeforeDelete(GroupEvent $event)
    {
        $group = $event->getGroup();

        $groupName = $this->slugify($group->getOfficialName());

        try {
            $this->mailgunApi->listRemoveAction($groupName);
        } catch (GenericHTTPError $e) {
            $this->logError($e);
            throw new MailgunException("An error has occurred in ".__FUNCTION__, $e->getCode(), $e);
        }
    }

    /**
     * Slugify a string
     *
     * @param string $string
     * @return string
     */
    private function slugify($string)
    {
        $slugify = new Slugify();

        return $slugify->slugify($string, '');
    }

    /**
     * @param GenericHTTPError $e
     */
    private function logError(GenericHTTPError $e)
    {
        $this->logger->error(
            $e->getMessage(),
            [
                'response_code' => $e->getHttpResponseCode(),
                'response_body' => $e->getHttpResponseBody(),
            ]
        );
    }
}