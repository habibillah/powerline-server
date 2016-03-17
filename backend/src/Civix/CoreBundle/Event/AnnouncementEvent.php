<?php
namespace Civix\CoreBundle\Event;

use Civix\CoreBundle\Entity\Announcement;

class AnnouncementEvent
{
    /**
     * @var Announcement
     */
    private $announcement;

    public function __construct(Announcement $announcement)
    {
        $this->announcement = $announcement;
    }

    /**
     * @return Announcement
     */
    public function getAnnouncement()
    {
        return $this->announcement;
    }
}