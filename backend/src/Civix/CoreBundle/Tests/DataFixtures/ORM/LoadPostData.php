<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Post;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadPostData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $user1 = $this->getReference('user_1');
        $user2 = $this->getReference('user_2');
        $user3 = $this->getReference('user_3');
        $group1 = $this->getReference('group_1');
        $group2 = $this->getReference('group_2');

        $petition = new Post();
        $petition->setUser($user1)
            ->setBody('Danielle Green\'s daughter took her own life after being tormented by bullies. Danielle started a petition to protect Indiana\'s anti-bullying laws, and after 235,000 signatures, lawmakers ceased efforts to weaken the laws.')
            ->boost()
            ->setExpiredAt(new \DateTime('+1 month'))
            ->setUserExpireInterval(1000)
            ->setGroup($group1);
        $manager->persist($petition);
        $this->addReference('post_1', $petition);

        $petition = new Post();
        $petition->setUser($user1)
            ->setBody('A campaign supported by more than 110,000 people helped push grocery chain Whole Foods Market to join the fight against food waste in the U.S.')
            ->setExpiredAt(new \DateTime('+2 months'))
            ->setUserExpireInterval(2500)
            ->setGroup($group2);
        $manager->persist($petition);
        $this->addReference('post_2', $petition);

        $petition = new Post();
        $petition->setUser($user2)
            ->setBody('The U.S. Army wouldn’t let female WWII pilots like Tiffany’s grandmother be laid to rest at Arlington National Cemetery. 175,000 signatures later, Tiffany convinced Congress and the President to change the law.')
            ->boost()
            ->setExpiredAt(new \DateTime('+1 day'))
            ->setUserExpireInterval(300)
            ->setGroup($group1);
        $manager->persist($petition);
        $this->addReference('post_3', $petition);

        $petition = new Post();
        $petition->setUser($user3)
            ->setBody('John Feal led a movement to pass the Zadroga Act to give healthcare coverage to 9/11 first responders and survivors. His campaign included a petition with more than 180,000 signatures.')
            ->boost()
            ->setExpiredAt(new \DateTime('+1 week'))
            ->setUserExpireInterval(500)
            ->setGroup($group2);
        $manager->persist($petition);
        $this->addReference('post_4', $petition);

        $petition = new Post();
        $petition->setUser($user3)
            ->setBody('Sara Wolff led a campaign signed by more than 265,000 people, urging Congress to help people with disabilities take more control over their finances.')
            ->boost()
            ->setExpiredAt(new \DateTime('-1 month'))
            ->setUserExpireInterval(100)
            ->setGroup($group1);
        $manager->persist($petition);
        $this->addReference('post_5', $petition);

        $petition = new Post();
        $petition->setUser($user3)
            ->setBody('Under ordinary circumstances, two mothers as different as we are would never have met. One of us is from Oklahoma and is a registered Republican. The other is an unmarried liberal who lives in Brooklyn.')
            ->boost()
            ->setExpiredAt(new \DateTime('-1 week'))
            ->setUserExpireInterval(3000)
            ->setGroup($group1);
        $manager->persist($petition);
        $this->addReference('post_6', $petition);

        $deleted = new Post();
        $deleted->setUser($user3)
            ->setBody('-- deleted --')
            ->boost()
            ->setExpiredAt(new \DateTime('+1 hour'))
            ->setUserExpireInterval(10000)
            ->setGroup($group1);
        $manager->persist($deleted);
        $this->addReference('post_7', $deleted);

        $manager->flush();

        $manager->remove($deleted);
        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadGroupData::class];
    }
}
