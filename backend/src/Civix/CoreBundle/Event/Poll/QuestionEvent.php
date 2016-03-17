<?php
namespace Civix\CoreBundle\Event\Poll;

use Civix\CoreBundle\Entity\Poll\Question;
use Symfony\Component\EventDispatcher\Event;

class QuestionEvent extends Event
{
    /**
     * @var Question
     */
    private $question;

    public function __construct(Question $question)
    {
        $this->question = $question;
    }

    /**
     * @return Question
     */
    public function getQuestion()
    {
        return $this->question;
    }
}