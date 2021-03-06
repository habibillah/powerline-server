<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserFollowData;
use Symfony\Bundle\FrameworkBundle\Client;

class UsersControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/users';

    /**
     * @var Client
     */
    private $client = null;

    public function setUp()
    {
        $this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
    }

    public function tearDown()
    {
        $this->client = NULL;
        parent::tearDown();
    }

    public function testGetNotFollowingUserProfileIsOk()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
        ])->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('userfollowtest1');
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT.'/'.$user->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="followertest"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayNotHasKey('city', $data);
        $this->assertArrayNotHasKey('state', $data);
        $this->assertEquals($user->getSlogan(), $data['slogan']);
        $this->assertEquals($user->getBio(), $data['bio']);
    }

    public function testGetFollowingUserProfileIsOk()
    {
        $repository = $this->loadFixtures([
            LoadUserFollowData::class,
        ])->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('userfollowtest1');
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT.'/'.$user->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="followertest"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('city', $data);
        $this->assertArrayHasKey('state', $data);
        $this->assertEquals($user->getSlogan(), $data['slogan']);
        $this->assertEquals($user->getBio(), $data['bio']);
    }
}