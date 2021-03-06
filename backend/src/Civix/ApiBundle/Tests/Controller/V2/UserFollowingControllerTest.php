<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserFollow;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserFollowData;
use Symfony\Bundle\FrameworkBundle\Client;

class UserFollowingControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/user/followings';

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var Client
     */
    private $client = null;

    public function setUp()
    {
        $this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);

        $this->em = $this->getContainer()->get('doctrine')->getManager();
    }

    public function tearDown()
    {
        $this->client = NULL;
        $this->em = null;
        parent::tearDown();
    }

    public function testGetFollowings()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadUserFollowData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="followertest"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(1, $data['page']);
        $this->assertSame(20, $data['items']);
        $this->assertSame(3, $data['totalItems']);
        $this->assertCount(3, $data['payload']);
        foreach ($data['payload'] as $item) {
            $this->assertArrayHasKey('status', $item);
            $this->assertThat(
                $item['id'],
                $this->logicalOr(
                    $repository->getReference('userfollowtest1')->getId(),
                    $repository->getReference('userfollowtest2')->getId(),
                    $repository->getReference('userfollowtest3')->getId()
                )
            );
        }
    }

    public function testGetFollowed()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadUserFollowData::class,
        ])->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('userfollowtest1');
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT.'/'.$user->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="followertest"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(19, $data);
        $this->assertEquals('active', $data['status']);
        $this->assertEquals($user->getId(), $data['id']);
        $this->assertEquals($user->getType(), $data['type']);
        $this->assertEquals($user->getUsername(), $data['username']);
        $this->assertEquals($user->getFirstName(), $data['first_name']);
        $this->assertEquals($user->getLastName(), $data['last_name']);
        $this->assertEquals($user->getFullName(), $data['full_name']);
        $this->assertContains(User::DEFAULT_AVATAR, $data['avatar_file_name']);
        $this->assertEquals($user->getBirth(), new \DateTime($data['birth']));
        $this->assertEquals($user->getCity(), $data['city']);
        $this->assertEquals($user->getState(), $data['state']);
        $this->assertEquals($user->getCountry(), $data['country']);
        $this->assertEquals($user->getFacebookLink(), $data['facebook_link']);
        $this->assertEquals($user->getTwitterLink(), $data['twitter_link']);
        $this->assertEquals($user->getBio(), $data['bio']);
        $this->assertEquals($user->getSlogan(), $data['slogan']);
        $this->assertArrayHasKey('date_create', $data);
        $this->assertArrayHasKey('date_approval', $data);
    }

    public function testGetPendingFollowed()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadUserFollowData::class,
        ])->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('userfollowtest2');
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT.'/'.$user->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="followertest"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(14, $data);
        $this->assertEquals('pending', $data['status']);
        $this->assertEquals($user->getId(), $data['id']);
        $this->assertEquals($user->getFullName(), $data['full_name']);
        $this->assertEquals($user->getUsername(), $data['username']);
        $this->assertEquals($user->getFirstName(), $data['first_name']);
        $this->assertEquals($user->getLastName(), $data['last_name']);
        $this->assertContains(User::DEFAULT_AVATAR, $data['avatar_file_name']);
        $this->assertEquals($user->getBirth(), new \DateTime($data['birth']));
        $this->assertEquals($user->getCountry(), $data['country']);
        $this->assertEquals($user->getBio(), $data['bio']);
        $this->assertEquals($user->getSlogan(), $data['slogan']);
        $this->assertArrayHasKey('date_create', $data);
        $this->assertArrayHasKey('date_approval', $data);
    }

    public function testFollowUserIsSuccessful()
    {
        $repository = $this->loadFixtures([LoadUserData::class])->getReferenceRepository();
        /** @var UserFollow $userFollow */
        $user = $repository->getReference('userfollowtest1');
        $follower = $repository->getReference('followertest');
        $client = $this->client;
        $client->request('PUT', self::API_ENDPOINT.'/'.$user->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="followertest"']);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $this->assertEmpty($response->getContent());
        /** @var UserFollow[] $userFollow */
        $userFollow = $this->em->getRepository(UserFollow::class)
            ->findBy(['user' => $user]);
        $this->assertCount(1, $userFollow);
        $this->assertSame($follower->getId(), $userFollow[0]->getFollower()->getId());
        $this->assertSame(UserFollow::STATUS_PENDING, $userFollow[0]->getStatus());
    }

    public function testUnfollowActiveUserIsSuccessful()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadUserFollowData::class,
        ])->getReferenceRepository();
        $user = $repository->getReference('userfollowtest1');
        $client = $this->client;
        $client->request('DELETE', self::API_ENDPOINT.'/'.$user->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="followertest"']);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $followers = $this->em->getRepository(UserFollow::class)->findBy(['user' => $user]);
        $this->assertCount(0, $followers);
    }

    public function testUnfollowPendingUserIsSuccessful()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadUserFollowData::class,
        ])->getReferenceRepository();
        $user = $repository->getReference('userfollowtest2');
        $client = $this->client;
        $client->request('DELETE', self::API_ENDPOINT.'/'.$user->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="followertest"']);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $followers = $this->em->getRepository(UserFollow::class)->findBy(['user' => $user]);
        $this->assertCount(0, $followers);
    }
}