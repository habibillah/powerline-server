<?php

namespace Civix\CoreBundle\Entity\Activities;

use Civix\CoreBundle\Entity\Micropetitions\Metadata;
use Civix\CoreBundle\Entity\Micropetitions\Petition;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Civix\CoreBundle\Entity\Activity;

/**
 * @ORM\Entity
 * @Serializer\ExclusionPolicy("all")
 */
class MicroPetition extends Activity
{
    /**
     * @ORM\Column(name="petition_id", type="integer")
     *
     * @var int
     */
    protected $petitionId;

    /**
     * @var int
     * @ORM\Column(name="quorum", type="integer")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-activities"})
     */
    protected $quorum;

    /**
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\Micropetitions\Petition", inversedBy="micropetitions")
     * @var Petition
     */
    protected $petition;

    public function setPetitionId($id)
    {
        $this->petitionId = $id;

        return $this;
    }

    public function getPetitionId()
    {
        return $this->petitionId;
    }

    /**
     * @return Petition
     */
    public function getPetition()
    {
        return $this->petition;
    }

    /**
     * @param Petition $petition
     * @return MicroPetition
     */
    public function setPetition($petition)
    {
        $this->petition = $petition;

        return $this;
    }

    /**
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"api-activities"})
     * @return null|Metadata
     */
    public function getMetadata()
    {
        if ($this->petition) {
            return $this->petition->getMetadata();
        }

        return null;
    }

    public function getEntity()
    {
        return array(
            'type' => 'micro-petition',
            'id' => $this->getPetitionId(),
            'group_id' => $this->getGroup() ? $this->getGroup()->getId() : null,
        );
    }

    /**
     * @param int $quorum
     */
    public function setQuorum($quorum)
    {
        $this->quorum = $quorum;
    }

    /**
     * @return int
     */
    public function getQuorum()
    {
        return $this->quorum;
    }
}
