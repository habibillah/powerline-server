<?php

namespace Civix\CoreBundle\Tests\Command;

use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Civix\CoreBundle\Command\CiceroSynchCommand;

class CiceroSynchCommandTest extends WebTestCase
{
    public function setUp()
    {
        $this->responseRepresentative = json_decode(
            file_get_contents(__DIR__.'/../Service/TestData/representative.json')
        );
        $this->responseRepresentativeTitle = json_decode(
            file_get_contents(__DIR__.'/../Service/TestData/representativeChangeTitle.json')
        );
        $this->responseRepresentativeDistrict = json_decode(
            file_get_contents(__DIR__.'/../Service/TestData/representativeChangeDistrict.json')
        );
        $this->responseRepresentativeNotFound = json_decode(
            file_get_contents(__DIR__.'/../Service/TestData/representativeNotFound.json')
        );
    }

    /**
     * @group cicero
     * @group cicero-cmd
     */
    public function testSynch()
    {
        $executor = $this->loadFixtures(array(
            'Civix\CoreBundle\Tests\DataFixtures\ORM\LoadDistrictData',
            'Civix\CoreBundle\Tests\DataFixtures\ORM\LoadSTRepresentativeData',
        ));

        $representative = $executor->getReferenceRepository()->getReference('vice_president');
        $lastUpdatedAt = $representative->getUpdatedAt();
        $officialName = $representative->getOfficialTitle();
        $avatarSrc = $representative->getAvatarSrc();
        $districtId = $representative->getDistrictId();

        $container = $this->getContainerForCheck($this->responseRepresentative);
        $commandTester = $this->getCommandTester($container);

        $this->assertRegExp('/Checking Joseph Biden/', $commandTester->getDisplay());
        $this->assertRegExp('/Synchronization is completed/', $commandTester->getDisplay());

        $representativeUpdated = $container->get('doctrine')->getManager()
            ->getRepository('CivixCoreBundle:RepresentativeStorage')->findOneByLastName('Biden');

        $this->assertTrue(
            $officialName == $representativeUpdated->getOfficialTitle(),
            'Official title should n\'t be changed'
        );
        $this->assertFalse(
            $avatarSrc == $representativeUpdated->getAvatarSrc(),
            'Avatar src should be changed'
        );
        $this->assertTrue(
            $districtId == $representativeUpdated->getDistrictId(),
            'District should n\'t be changed'
        );
    }

    /**
     * @group cicero
     * @group cicero-cmd
     */
    public function testSynchLink()
    {
        $executor = $this->loadFixtures(array(
            'Civix\CoreBundle\Tests\DataFixtures\ORM\LoadDistrictData',
            'Civix\CoreBundle\Tests\DataFixtures\ORM\LoadSTRepresentativeData',
            'Civix\CoreBundle\Tests\DataFixtures\ORM\LoadRepresentativeData',
        ));

        $representativeSt = $executor->getReferenceRepository()->getReference('vice_president');
        $lastUpdatedAt = $representativeSt->getUpdatedAt();
        $officialName = $representativeSt->getOfficialTitle();
        $avatarSrc = $representativeSt->getAvatarSrc();
        $districtId = $representativeSt->getDistrictId();

        $container = $this->getContainerForCheck($this->responseRepresentative);
        $commandTester = $this->getCommandTester($container);

        $this->assertRegExp('/Checking Joseph Biden/', $commandTester->getDisplay());
        $this->assertRegExp('/Synchronization is completed/', $commandTester->getDisplay());

        $representativeUpdated = $container->get('doctrine')->getManager()
            ->getRepository('CivixCoreBundle:Representative')->findOneByLastName('Biden');
        $this->assertInstanceOf('Civix\CoreBundle\Entity\Representative', $representativeUpdated);
        $representativeStUpdated = $representativeUpdated->getRepresentativeStorage();
        $this->assertInstanceOf('Civix\CoreBundle\Entity\RepresentativeStorage', $representativeStUpdated);

        //check links
        $this->assertNotNull($representativeUpdated->getStorageId(), 'Storage id in representative must be not null');
        $this->assertTrue(
            $representativeStUpdated->getStorageId() == $representativeUpdated->getStorageId(),
            'Storage ids should be equals'
        );

        $this->assertTrue(
            $officialName == $representativeStUpdated->getOfficialTitle(),
            'Official title of storage representative should not be changed'
        );
        $this->assertTrue(
            $officialName == $representativeUpdated->getOfficialTitle(),
            'Official title of representative should not be changed'
        );
        $this->assertTrue(
            $districtId == $representativeStUpdated->getDistrictId(),
            'District should not be changed'
        );
        $this->assertTrue(
            $districtId == $representativeStUpdated->getDistrictId(),
            'District of storage representative should not be changed'
        );
        $this->assertTrue(
            $districtId == $representativeUpdated->getDistrictId(),
            'District of representative should not be changed'
        );
    }

    /**
     * @group cicero
     * @group cicero-cmd
     */
    public function testSynchWithChangedOfficialTitle()
    {
        $executor = $this->loadFixtures(array(
            'Civix\CoreBundle\Tests\DataFixtures\ORM\LoadDistrictData',
            'Civix\CoreBundle\Tests\DataFixtures\ORM\LoadSTRepresentativeData',
        ));

        $representative = $executor->getReferenceRepository()->getReference('vice_president');
        $lastUpdatedAt = $representative->getUpdatedAt();
        $officialName = $representative->getOfficialTitle();
        $avatarSrc = $representative->getAvatarSrc();
        $districtId = $representative->getDistrictId();

        $container = $this->getContainerForCheck($this->responseRepresentativeTitle);
        $commandTester = $this->getCommandTester($container);

        $this->assertRegExp('/Checking Joseph Biden/', $commandTester->getDisplay());
        $this->assertRegExp('/Synchronization is completed/', $commandTester->getDisplay());

        $representativeUpdated = $container->get('doctrine')->getManager()
            ->getRepository('CivixCoreBundle:RepresentativeStorage')->findOneByLastName('Biden');

        $this->assertFalse(
            $officialName == $representativeUpdated->getOfficialTitle(),
            'Official title should be changed'
        );
        $this->assertFalse(
            $avatarSrc == $representativeUpdated->getAvatarSrc(),
            'Avatar src should be changed'
        );
        $this->assertTrue(
            $districtId == $representativeUpdated->getDistrictId(),
            'District should n\'t be changed'
        );
    }

    /**
     * @group cicero
     * @group cicero-cmd
     */
    public function testSynchWithChangedOfficialTitleLink()
    {
        $executor = $this->loadFixtures(array(
            'Civix\CoreBundle\Tests\DataFixtures\ORM\LoadDistrictData',
            'Civix\CoreBundle\Tests\DataFixtures\ORM\LoadSTRepresentativeData',
            'Civix\CoreBundle\Tests\DataFixtures\ORM\LoadRepresentativeData',
        ));

        $representativeSt = $executor->getReferenceRepository()->getReference('vice_president');
        $lastUpdatedAt = $representativeSt->getUpdatedAt();
        $officialName = $representativeSt->getOfficialTitle();
        $districtId = $representativeSt->getDistrictId();

        $container = $this->getContainerForCheck($this->responseRepresentativeTitle);
        $commandTester = $this->getCommandTester($container);

        $this->assertRegExp('/Checking Joseph Biden/', $commandTester->getDisplay());
        $this->assertRegExp('/Synchronization is completed/', $commandTester->getDisplay());

        $representativeUpdated = $container->get('doctrine')->getManager()
            ->getRepository('CivixCoreBundle:Representative')->findOneByLastName('Biden');
        $this->assertInstanceOf('Civix\CoreBundle\Entity\Representative', $representativeUpdated);
        $representativeStUpdated = $representativeUpdated->getRepresentativeStorage();
        $this->assertInstanceOf('Civix\CoreBundle\Entity\RepresentativeStorage', $representativeStUpdated);

        $this->assertFalse(
            $officialName == $representativeStUpdated->getOfficialTitle(),
            'Official of storage representative title should be changed'
        );
        $this->assertTrue(
            $representativeUpdated->getOfficialTitle() == $representativeStUpdated->getOfficialTitle(),
            'Official of representative title should be changed'
        );
        $this->assertTrue(
            $districtId == $representativeUpdated->getDistrictId(),
            'District of storage representative should not be changed'
        );
        $this->assertTrue(
            $districtId == $representativeStUpdated->getDistrictId(),
            'District of representative should not be changed'
        );
    }

    /**
     * @group cicero
     * @group cicero-cmd
     */
    public function testSynchWithChangedDistrict()
    {
        $executor = $this->loadFixtures(array(
            'Civix\CoreBundle\Tests\DataFixtures\ORM\LoadDistrictData',
            'Civix\CoreBundle\Tests\DataFixtures\ORM\LoadSTRepresentativeData',
        ));

        $representative = $executor->getReferenceRepository()->getReference('vice_president');
        $lastUpdatedAt = $representative->getUpdatedAt();
        $officialName = $representative->getOfficialTitle();
        $avatarSrc = $representative->getAvatarSrc();
        $districtId = $representative->getDistrictId();

        $container = $this->getContainerForCheck($this->responseRepresentativeDistrict);
        $commandTester = $this->getCommandTester($container);

        $this->assertRegExp('/Checking Joseph Biden/', $commandTester->getDisplay());
        $this->assertRegExp('/Synchronization is completed/', $commandTester->getDisplay());

        $representativeUpdated = $container->get('doctrine')->getManager()
            ->getRepository('CivixCoreBundle:RepresentativeStorage')->findOneByLastName('Biden');

        $this->assertTrue(
            $officialName == $representativeUpdated->getOfficialTitle(),
            'Official title should n\'t be changed'
        );
        $this->assertFalse(
            $avatarSrc == $representativeUpdated->getAvatarSrc(),
            'Avatar src should be changed'
        );
        $this->assertFalse(
            $districtId == $representativeUpdated->getDistrictId(),
            'District should be changed'
        );
    }

    /**
     * @group cicero
     * @group cicero-cmd
     */
    public function testSynchWithChangedDistrictLink()
    {
        $executor = $this->loadFixtures(array(
            'Civix\CoreBundle\Tests\DataFixtures\ORM\LoadDistrictData',
            'Civix\CoreBundle\Tests\DataFixtures\ORM\LoadSTRepresentativeData',
            'Civix\CoreBundle\Tests\DataFixtures\ORM\LoadRepresentativeData',
        ));

        $representative = $executor->getReferenceRepository()->getReference('vice_president');
        $lastUpdatedAt = $representative->getUpdatedAt();
        $officialName = $representative->getOfficialTitle();
        $districtId = $representative->getDistrictId();

        $container = $this->getContainerForCheck($this->responseRepresentativeDistrict);
        $commandTester = $this->getCommandTester($container);

        $this->assertRegExp('/Checking Joseph Biden/', $commandTester->getDisplay());
        $this->assertRegExp('/Synchronization is completed/', $commandTester->getDisplay());

        $representativeUpdated = $container->get('doctrine')->getManager()
            ->getRepository('CivixCoreBundle:Representative')->findOneByLastName('Biden');
        $this->assertInstanceOf('Civix\CoreBundle\Entity\Representative', $representativeUpdated);
        $representativeStUpdated = $representativeUpdated->getRepresentativeStorage();
        $this->assertInstanceOf('Civix\CoreBundle\Entity\RepresentativeStorage', $representativeStUpdated);

        $this->assertTrue(
            $officialName == $representativeStUpdated->getOfficialTitle(),
            'Official title should not be changed'
        );
        $this->assertFalse(
            $districtId == $representativeStUpdated->getDistrictId(),
            'District should be changed for storage representative'
        );
        $this->assertTrue(
            $representativeUpdated->getDistrictId() == $representativeStUpdated->getDistrictId(),
            'District should be changed for representative'
        );
    }

    /**
     * @group cicero
     * @group cicero-cmd
     */
    public function testSynchRepresentativeNotFound()
    {
        $this->loadFixtures(array(
            'Civix\CoreBundle\Tests\DataFixtures\ORM\LoadDistrictData',
            'Civix\CoreBundle\Tests\DataFixtures\ORM\LoadSTRepresentativeData',
        ));

        $container = $this->getContainerForCheck($this->responseRepresentativeNotFound);
        $commandTester = $this->getCommandTester($container);

        $this->assertRegExp('/Checking Joseph Biden/', $commandTester->getDisplay());
        $this->assertRegExp('/Joseph Biden is not found and will be removed/', $commandTester->getDisplay());
        $this->assertRegExp('/Synchronization is completed/', $commandTester->getDisplay());

        $representativeUpdated = $container->get('doctrine')->getManager()
            ->getRepository('CivixCoreBundle:RepresentativeStorage')->findOneByLastName('Biden');

        $this->assertNull(
            $representativeUpdated,
            'Representative should be removed from representative storage'
        );
    }

    /**
     * @group cicero
     * @group cicero-cmd
     */
    public function testSynchRepresentativeNotFoundLink()
    {
        $this->loadFixtures(array(
            'Civix\CoreBundle\Tests\DataFixtures\ORM\LoadDistrictData',
            'Civix\CoreBundle\Tests\DataFixtures\ORM\LoadSTRepresentativeData',
            'Civix\CoreBundle\Tests\DataFixtures\ORM\LoadRepresentativeData',
        ));

        $container = $this->getContainerForCheck($this->responseRepresentativeNotFound);
        $commandTester = $this->getCommandTester($container);

        $this->assertRegExp('/Checking Joseph Biden/', $commandTester->getDisplay());
        $this->assertRegExp('/Joseph Biden is not found and will be removed/', $commandTester->getDisplay());
        $this->assertRegExp('/Synchronization is completed/', $commandTester->getDisplay());

        $representativeStUpdated = $container->get('doctrine')->getManager()
            ->getRepository('CivixCoreBundle:RepresentativeStorage')->findOneByLastName('Biden');
        $this->assertNull(
            $representativeStUpdated,
            'Representative should be removed from representative storage'
        );
        $representativeUpdated = $container->get('doctrine')->getManager()
            ->getRepository('CivixCoreBundle:Representative')->findOneByLastName('Biden');
        $this->assertInstanceOf('Civix\CoreBundle\Entity\Representative', $representativeUpdated);
        $this->assertNull(
            $representativeUpdated->getStorageId(),
            'Representative should be removed from representative storage '.
            '(no link between representative and representative storage)'
        );
        $this->assertNull(
            $representativeUpdated->getDistrictId(),
            'District should be null'
        );
    }

    private function getCommandTester($container)
    {
        $application = new Application();
        $application->add(new CiceroSynchCommand());

        $command = $application->find('cicero:synch');
        $command->setContainer($container);
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()));

        return $commandTester;
    }

    private function getContainerForCheck($ciceroReturnResult)
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();

        $ciceroMock = $this->getMock('Civix\CoreBundle\Service\CiceroCalls',
            array('getResponse'),
            array(),
            '',
            false
        );
        $ciceroMock->expects($this->any())
           ->method('getResponse')
           ->will($this->returnValue($ciceroReturnResult));

        $openstateServiceMock = $this->getMock('Civix\CoreBundle\Service\OpenstatesApi',
            array('updateReprStorageProfile'),
            array(),
            '',
            false
        );

        $congressMock = $this->getMock('Civix\CoreBundle\Service\CongressApi',
            array('updateReprStorageProfile'),
            array(),
            '',
            false
        );

        $fileSystem = $this->getMockBuilder('Knp\Bundle\GaufretteBundle\FilesystemMap')
            ->disableOriginalConstructor()
            ->getMock();
        $storage = $this->getMockBuilder('Vich\UploaderBundle\Storage\GaufretteStorage')
            ->disableOriginalConstructor()
            ->getMock();

        $container = static::$kernel->getContainer();
        $container->set('civix_core.cicero_calls', $ciceroMock);
        $container->set('civix_core.openstates_api', $openstateServiceMock);
        $container->set('civix_core.congress_api', $congressMock);
        $container->set('knp_gaufrette.filesystem_map', $fileSystem);
        $container->set('vich_uploader.storage.gaufrette', $storage);

        return $container;
    }
}
