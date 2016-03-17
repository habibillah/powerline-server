<?php
namespace Civix\CoreBundle\Event;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\User;

class GroupUserEvent extends GroupEvent
{
    /**
     * @var User
     */
    private $user;

    public function __construct(Group $group, User $user)
    {
        parent::__construct($group);
        $this->user = $user;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }
}