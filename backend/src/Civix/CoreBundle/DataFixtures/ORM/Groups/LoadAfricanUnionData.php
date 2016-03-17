<?php

namespace Civix\CoreBundle\DataFixtures\ORM\Groups;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\User;
use Symfony\Component\Security\Core\Util\SecureRandom;

class LoadAfricanUnionData implements FixtureInterface, OrderedFixtureInterface, ContainerAwareInterface
{
    const COMMON_STATE_GROUP_EMAIL = 'support@powerli.ne';
    /**
     * @var ContainerInterface
     */
    private $container;
    private $dataFile;

    public function __construct()
    {
        $this->dataFile = __DIR__ . '/african_union.csv';
    }

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager)
    {
        //EU group
        $euGroup = $this->creatGroup(
            'AFU',
            'AFU1',
            'African Union',
            Group::GROUP_LOCATION_NAME_AFRICAN_UNION,
            Group::GROUP_TYPE_SPECIAL
        );
        $manager->persist($euGroup);

        //create state groups
        $dataFileHandler = fopen($this->dataFile, 'r');
        while (($csvRow = fgetcsv($dataFileHandler)) !== false) {
            $countryGroup = $this->creatGroup(
                'african_union_' . $csvRow[1],
                $csvRow[1].'1',
                $csvRow[0],
                $csvRow[1],
                Group::GROUP_TYPE_COUNTRY,
                $euGroup
            );

            $manager->persist($countryGroup);
        }

        fclose($dataFileHandler);

        $manager->flush();

        //update current users
        $allUsers = $manager->getRepository(User::class)
            ->findAll();
        foreach ($allUsers as $currentUser) {
             $this->container->get('civix_core.group_manager')
                ->autoJoinUser($currentUser);
             $manager->persist($currentUser);
        }
        $manager->flush();
    }

    public function getOrder()
    {
        return 4;
    }

    private function creatGroup($username, $password, $officialName, $locationName, $groupType, $parent = null)
    {
        $countryGroup = new Group();
        $countryGroup->setUsername($username);
        $countryGroup->setManagerEmail(self::COMMON_STATE_GROUP_EMAIL);
        $countryGroup->setOfficialName($officialName);
        $countryGroup->setGroupType($groupType);
        $countryGroup->setParent($parent);
        $countryGroup->setLocationName($locationName);

        $generator = new SecureRandom();

        $factory = $this->container->get('security.encoder_factory');
        $encoder = $factory->getEncoder($countryGroup);
        $password = $encoder->encodePassword(sha1($generator->nextBytes(10)), $countryGroup->getSalt());
        $countryGroup->setPassword($password);

        return $countryGroup;
    }
}
