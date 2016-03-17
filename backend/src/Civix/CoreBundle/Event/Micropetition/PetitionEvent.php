<?php
namespace Civix\CoreBundle\Event\Micropetition;

use Civix\CoreBundle\Entity\Micropetitions\Petition;
use Symfony\Component\EventDispatcher\Event;

class PetitionEvent extends Event
{
    /**
     * @var Petition
     */
    private $petition;

    public function __construct(Petition $petition)
    {
        $this->petition = $petition;
    }

    /**
     * @return Petition
     */
    public function getPetition()
    {
        return $this->petition;
    }
}