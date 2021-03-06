<?php

namespace Civix\ApiBundle\Tests\DataFixtures\ORM\Micropetitions;

use Civix\CoreBundle\Entity\UserPetition\Comment;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadCommentData extends AbstractFixture implements FixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $firstComment = new Comment();
        $firstComment->setCommentBody('comment');
        $firstComment->setParentComment(null);
        $firstComment->setPetition($this->getReference('petition1'));

        $this->addReference('commentPetition1', $firstComment);
        $manager->persist($firstComment);
        $manager->flush();
    }
}
