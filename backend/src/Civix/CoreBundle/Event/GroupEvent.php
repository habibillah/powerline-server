<?php
namespace Civix\CoreBundle\Event;

use Civix\CoreBundle\Entity\Group;
use Symfony\Component\EventDispatcher\Event;

class GroupEvent extends Event
{
    /**
     * @var Group
     */
    private $group;

    public function __construct(Group $group)
    {
        $this->group = $group;
    }

    /**
     * @return Group
     */
    public function getGroup()
    {
        return $this->group;
    }
}