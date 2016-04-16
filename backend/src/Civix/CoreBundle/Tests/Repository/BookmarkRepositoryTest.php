<?php
namespace Civix\CoreBundle\Tests\Repository;

use Civix\CoreBundle\Entity\Bookmark;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Repository\BookmarkRepository;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Liip\FunctionalTestBundle\Test\WebTestCase;

class BookmarkRepositoryTest extends WebTestCase
{
    /** @var User */
    private $user;

    /** @var BookmarkRepository */
    private $repo;

    /** @var  Bookmark */
    private $bookmark1;

    /** @var  Bookmark */
    private $bookmark2;

    /** @var  Bookmark */
    private $bookmark3;

    /** @var  Bookmark */
    private $bookmark4;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        /** @var AbstractExecutor $fixtures */
        $fixtures = $this->loadFixtures([LoadUserData::class]);
        $reference = $fixtures->getReferenceRepository();

        $this->user = $reference->getReference('testuserbookmark1');

        $this->repo = $this->getContainer()->get('doctrine')->getRepository(Bookmark::class);

        $this->bookmark1 = $this->repo->save(Bookmark::TYPE_POST, $this->user, 1);
        $this->bookmark2 = $this->repo->save(Bookmark::TYPE_POST, $this->user, 1);
        $this->bookmark3 = $this->repo->save(Bookmark::TYPE_POLL, $this->user, 1);
        $this->bookmark4 = $this->repo->save(Bookmark::TYPE_POLL, $this->user, 2);
    }

    public function testSave()
    {
        $this->assertNotEmpty($this->bookmark1->getId());
        $this->assertEquals($this->bookmark1->getId(), $this->bookmark2->getId());
        $this->assertNotEquals($this->bookmark1->getId(), $this->bookmark3->getId());
        $this->assertNotEquals($this->bookmark1->getId(), $this->bookmark4->getId());
    }

    public function testFindByType()
    {
        $savedBookmarks1 = $this->repo->findByType(Bookmark::TYPE_ALL, $this->user, 1);
        $savedBookmarks2 = $this->repo->findByType(Bookmark::TYPE_POLL, $this->user, 1);
        $savedBookmarks3 = $this->repo->findByType(Bookmark::TYPE_PETITION, $this->user, 1);

        $this->assertCount(3, $savedBookmarks1['items']);
        $this->assertCount(2, $savedBookmarks2['items']);
        $this->assertCount(0, $savedBookmarks3['items']);
    }

    public function testDelete()
    {
        $savedBookmarks = $this->repo->findByType(Bookmark::TYPE_ALL, $this->user, 1);

        $deleted = array();
        foreach($savedBookmarks['items'] as $item) {
            $deleted[] = $this->repo->delete($item->getId());
        }

        $this->assertCount(3, $deleted);
    }
}

