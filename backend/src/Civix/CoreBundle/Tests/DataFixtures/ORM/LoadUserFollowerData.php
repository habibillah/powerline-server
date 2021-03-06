<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserFollow;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadUserFollowerData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $user1 = $this->getReference('user_1');
        $user2 = $this->getReference('user_2');
        $user3 = $this->getReference('user_3');
        $user4 = $this->getReference('user_4');
        
        $userFollow = $this->generateUserFollow($user2, $user1, UserFollow::STATUS_ACTIVE);
        $manager->persist($userFollow);
        $this->addReference('user_2_user_1', $userFollow);
        $userFollow = $this->generateUserFollow($user3, $user1, UserFollow::STATUS_PENDING);
        $manager->persist($userFollow);
        $this->addReference('user_3_user_1', $userFollow);
        $userFollow = $this->generateUserFollow($user4, $user1, UserFollow::STATUS_ACTIVE);
        $manager->persist($userFollow);
        
        $manager->flush();
    }

    /**
     * @param User $user
     * @param User$follower
     * @param $status
     * @return UserFollow
     */
    private function generateUserFollow($user, $follower, $status)
    {
        $userFollow = new UserFollow();
        $userFollow->setUser($user);
        $userFollow->setFollower($follower);
        $userFollow->setDateCreate(new \DateTime());
        if ($status == UserFollow::STATUS_ACTIVE) {
            $userFollow->setDateApproval(new \DateTime('-1 week'));
        }
        $userFollow->setStatus($status);
        $user->addFollowing($userFollow);
        $follower->addFollower($userFollow);
        
        return $userFollow;
    }

    public function getDependencies()
    {
        return [LoadUserData::class];
    }
}