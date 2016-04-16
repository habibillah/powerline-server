<?php

namespace Civix\CoreBundle\Tests\Service;

use Liip\FunctionalTestBundle\Test\WebTestCase;
use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Entity\District;

class CiceroApiTest extends WebTestCase
{
    private $serviceObj;
    private $representativeObj;
    private $districtObj;

    public function setUp()
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();

        $this->responseCandidates = json_decode(file_get_contents(__DIR__.'/TestData/responseCandidates.json'));
        $this->resultCandidatesDistricts = array(19, 533, 10859, 47, 757336, 4652);

        $this->responseRepresentative = json_decode(file_get_contents(__DIR__.'/TestData/representative.json'));

        $this->representativeObj = new Representative();
        $this->districtObj = new District();
    }

    /**
     * Test method GetRepresentativeByLocation.
     *
     * @group cicero
     */
    public function testGetRepresentativeByLocation()
    {
        $this->loadFixtures(array(
            'Civix\CoreBundle\Tests\DataFixtures\ORM\LoadDistrictData',
            'Civix\CoreBundle\Tests\DataFixtures\ORM\LoadInitRepresentativeData',
        ));

        $mock = $this->getMock('Civix\CoreBundle\Service\CiceroApi',
            null,
            array(),
            '',
            false
        );
        $ciceroCallsMock = $this->getMock('Civix\CoreBundle\Service\CiceroCalls',
            array('getResponse'),
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

        $openstatesApi = $this->getMock('Civix\CoreBundle\Service\OpenstatesApi',
            array('updateReprStorageProfile'),
            array(),
            '',
            false
        );

        $vichUploader = $this->getMock('Vich\UploaderBundle\Templating\Helper\UploaderHelper',
            array('asset'),
            array(),
            '',
            false
        );
        $vichUploader->expects($this->any())
            ->method('asset')
            ->will($this->returnValue(null));

        $fileSystem = $this->getMockBuilder('Knp\Bundle\GaufretteBundle\FilesystemMap')
            ->disableOriginalConstructor()
            ->getMock();
        $storage = $this->getMockBuilder('Vich\UploaderBundle\Storage\GaufretteStorage')
            ->disableOriginalConstructor()
            ->getMock();
        $logger = $this->getMockBuilder('Symfony\Bridge\Monolog\Logger')
            ->disableOriginalConstructor()
            ->getMock();

        static::$kernel->getContainer()->set('knp_gaufrette.filesystem_map', $fileSystem);
        static::$kernel->getContainer()->set('vich_uploader.storage.gaufrette', $storage);

        $mock->setCropImage($this->getMock('Civix\CoreBundle\Service\CropImage'));
        $mock->setVichService($vichUploader);
        $mock->setEntityManager(static::$kernel->getContainer()->get('doctrine.orm.entity_manager'));
        $mock->setCongressApi($congressMock);
        $mock->setOpenstatesApi($openstatesApi);
        $mock->setLogger($logger);

        $ciceroCallsMock->expects($this->any())
           ->method('getResponse')
           ->will($this->returnValue($this->responseCandidates));
        $mock->setCiceroCalls($ciceroCallsMock);

        $districts = $mock->getRepresentativeByLocation('108 4th St', 'Hoboken', 'NJ');

        $districtsIds = array_map(function ($district) {
            return $district->getId();
        }, $districts);

        $this->assertEmpty(
            array_diff($districtsIds, $this->resultCandidatesDistricts),
            'Should return empty array of differences'
        );
    }

    /**
     * Test method UpdateByRepresentativeInfo.
     *
     * @group cicero
     */
    public function testUpdateByRepresentativeInfo()
    {
        $this->loadFixtures(array(
            'Civix\CoreBundle\Tests\DataFixtures\ORM\LoadDistrictData',
            'Civix\CoreBundle\Tests\DataFixtures\ORM\LoadInitRepresentativeData',
        ));

        $mock = $this->getMock('Civix\CoreBundle\Service\CiceroApi',
            null,
            array(),
            '',
            false
        );

        $ciceroCallsMock = $this->getMock('Civix\CoreBundle\Service\CiceroCalls',
            array('getResponse'),
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

        $openstatesApi = $this->getMock('Civix\CoreBundle\Service\OpenstatesApi',
            array('updateReprStorageProfile'),
            array(),
            '',
            false
        );

        $vichUploader = $this->getMock('Vich\UploaderBundle\Templating\Helper\UploaderHelper',
            array('asset'),
            array(),
            '',
            false
        );
        $vichUploader->expects($this->any())
            ->method('asset')
            ->will($this->returnValue(null));

        $fileSystem = $this->getMockBuilder('Knp\Bundle\GaufretteBundle\FilesystemMap')
            ->disableOriginalConstructor()
            ->getMock();
        $storage = $this->getMockBuilder('Vich\UploaderBundle\Storage\GaufretteStorage')
            ->disableOriginalConstructor()
            ->getMock();
        $logger = $this->getMockBuilder('Symfony\Bridge\Monolog\Logger')
            ->disableOriginalConstructor()
            ->getMock();

        static::$kernel->getContainer()->set('knp_gaufrette.filesystem_map', $fileSystem);
        static::$kernel->getContainer()->set('vich_uploader.storage.gaufrette', $storage);

        $mock->setCropImage($this->getMock('Civix\CoreBundle\Service\CropImage'));
        $mock->setVichService($vichUploader);
        $mock->setEntityManager(static::$kernel->getContainer()->get('doctrine.orm.entity_manager'));
        $mock->setCongressApi($congressMock);
        $mock->setOpenstatesApi($openstatesApi);
        $mock->setLogger($logger);

        $ciceroCallsMock->expects($this->any())
                   ->method('getResponse')
                   ->will($this->returnValue($this->responseRepresentative));
        $mock->setCiceroCalls($ciceroCallsMock);

        $district = $mock->updateByRepresentativeInfo($this->representativeObj);

        $this->assertInstanceOf('Civix\CoreBundle\Entity\District', $district, 'Should return correct type District');
        $this->assertEquals($district->getId(), 19, 'Should return correct district id (19)');
        $this->assertEquals(
            $this->representativeObj->getStorageId(),
            44926,
            'Should return correct storage id (44926)'
        );
    }
}
