<?php
namespace Civix\CoreBundle\Event;

use Civix\CoreBundle\Entity\UserFollow;
use Symfony\Component\EventDispatcher\Event;

class UserFollowEvent extends Event
{
    /**
     * @var UserFollow
     */
    private $userFollow;

    public function __construct(UserFollow $userFollow)
    {
        $this->userFollow = $userFollow;
    }

    /**
     * @return UserFollow
     */
    public function getUserFollow()
    {
        return $this->userFollow;
    }
}