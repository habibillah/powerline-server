<?php
namespace Civix\CoreBundle\Tests\Service\Group;

use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserGroup;
use Civix\CoreBundle\Service\Group\GroupManager;

class GroupManagerTest extends WebTestCase
{
    /**
     * @var User
     */
    private $user;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    private $container;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->container = static::$kernel->getContainer();
        $this->em = $this->container->get('doctrine')->getManager();

        $this->user = $this->em
            ->getRepository('CivixCoreBundle:User')
            ->findOneBy(array('username' => 'testuser1'));

        if ($this->user === null) {
            $this->user = new User();
            $this->user->setUsername('testuser1')
                ->setEmail('habibillah@gmail.com')
                ->setPassword('testuser1')
                ->setToken('testuser1')
                ->setBirth(new \DateTime())
                ->setDoNotDisturb(true)
                ->setIsNotifDiscussions(false)
                ->setIsNotifMessages(false)
                ->setIsRegistrationComplete(true)
                ->setIsNotifOwnPostChanged(false);

            $this->em->persist($this->user);
            $this->em->flush();
        }
    }

    public function testAutoJoinUserAFU()
    {
        $this->user
            ->setAddress1('Sudirman St')
            ->setCity('Chinde District')
            ->setState('Zambezia')
            ->setCountry('MZ');
        $this->em->persist($this->user);
        $this->em->flush();

        /** @var GroupManager $groupManager */
        $groupManager = $this->container->get('civix_core.group_manager');
        $groupManager->autoJoinUser($this->user);

        /** @var UserGroup $groups */
        $groups = $this->em
            ->getRepository('CivixCoreBundle:UserGroup')
            ->findBy(array('user' => $this->user));

        $groupLocations = array();
        foreach($groups as $item)
            $groupLocations[] = $item->getGroup()->getLocationName();

        $this->assertContains('MZ', $groupLocations);
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        if ($this->em !== null) {
            if ($this->user !== null) {
                $this->em->remove($this->user);
                $this->em->flush();
            }

            $this->em->close();
        }

        parent::tearDown();
    }
}