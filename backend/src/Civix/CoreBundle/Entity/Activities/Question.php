<?php

namespace Civix\CoreBundle\Entity\Activities;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Civix\CoreBundle\Entity\Activity;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Entity
 * @Serializer\ExclusionPolicy("all")
 * @Vich\Uploadable
 */
class Question extends Activity
{
    /**
     * @ORM\Column(name="question_id", type="integer")
     *
     * @var int
     */
    protected $questionId;

    public function setQuestionId($id)
    {
        $this->questionId = $id;

        return $this;
    }

    public function getQuestionId()
    {
        return $this->questionId;
    }

    public function getEntity()
    {
        return array(
            'type' => self::TYPE_QUESTION,
            'id' => $this->getQuestionId(),
        );
    }
}
