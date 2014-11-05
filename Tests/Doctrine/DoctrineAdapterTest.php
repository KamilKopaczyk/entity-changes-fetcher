<?php

namespace Daimos\ChangesFetcher\Tests\Doctrine;

use Daimos\ChangesFetcher\Adapter\DoctrineChangesFetcher;
use Daimos\ChangesFetcher\Tests\Doctrine\Entity\Role;
use Daimos\ChangesFetcher\Tests\Doctrine\Entity\User;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

class DoctrineAdapterTest extends \PHPUnit_Framework_TestCase
{

    /** @var  EntityManagerInterface */
    protected $em;

    /** @var  DoctrineChangesFetcher */
    protected $changesFetcher;

    protected static function createEntityManager()
    {
        $dbConnectionConfiguration = array(
            'driver' => 'pdo_sqlite',
            'user' => 'root',
            'password' => '',
            'dbname' => 'test',
        );

        $paths = array(__DIR__ . '/Entity');
        $isDevMode = true;

        $driver = new AnnotationDriver(new AnnotationReader(), $paths);

        $config = Setup::createAnnotationMetadataConfiguration($paths, $isDevMode);
        $config->setMetadataDriverImpl($driver);

        return EntityManager::create($dbConnectionConfiguration, $config);
    }

    public function setUp()
    {
        parent::setUp();

        $em = self::createEntityManager();

        $st = new SchemaTool($em);

        $classes = $em->getMetadataFactory()->getAllMetadata();
        $st->dropSchema($classes);
        $st->createSchema($classes);

        $this->em = $em;

        $this->changesFetcher = new DoctrineChangesFetcher($em);
    }

    public function testChange_NullToScalar()
    {
        $testValue = 'testUsername';

        $user = new User($testValue);

        $changes = $this->changesFetcher->getChanges($user);

        $this->assertArrayHasKey('username', $changes);
        $this->assertNull($changes['username'][0]);
        $this->assertEquals($testValue, $changes['username'][1]);

        return $user;
    }

    public function testChange_ScalarToScalar()
    {
        $oldValue = 'testUsername';
        $newValue = 'testUsernameNew';

        $user = new User($oldValue);

        $this->em->persist($user);
        $this->em->flush();

        $user->setUsername($newValue);

        $changes = $this->changesFetcher->getChanges($user);

        $this->assertArrayHasKey('username', $changes);
        $this->assertEquals($oldValue, $changes['username'][0]);
        $this->assertEquals($newValue, $changes['username'][1]);
    }

    public function testChange_ScalarToNull()
    {
        $oldValue = 'testUsername';
        $newValue = null;

        $user = new User($oldValue);

        $this->em->persist($user);
        $this->em->flush();

        $user->setUsername($newValue);

        $changes = $this->changesFetcher->getChanges($user);

        $this->assertArrayHasKey('username', $changes);
        $this->assertEquals($oldValue, $changes['username'][0]);
        $this->assertEquals($newValue, $changes['username'][1]);
    }

    public function testChange_NullToEntity()
    {
        $newRoleName = 'newRole';

        $newRole = new Role($newRoleName);

        $user = new User('testUser', $newRole);

        $changes = $this->changesFetcher->getChanges($user);

        $this->assertArrayHasKey('role', $changes);
        $this->assertNull($changes['role'][0]);

        $changedRole = $changes['role'][1];

        $this->assertTrue($changedRole instanceof Role);
        $this->assertEquals($newRoleName, $changedRole->getName());
    }

    public function testChange_EntityToEntity()
    {
        $oldRoleName = 'oldRole';
        $newRoleName = 'newRole';

        $oldRole = new Role($oldRoleName);
        $newRole = new Role($newRoleName);

        $this->em->persist($oldRole);
        $this->em->persist($newRole);

        $user = new User('testUser', $oldRole);

        $this->em->persist($user);
        $this->em->flush($user);

        $user->setRole($newRole);

        $changes = $this->changesFetcher->getChanges($user);

        $this->assertArrayHasKey('role', $changes);

        $oldChangedRole = $changes['role'][0];

        $this->assertTrue($oldChangedRole instanceof Role);
        $this->assertEquals($oldRoleName, $oldChangedRole->getName());

        $newChangedRole = $changes['role'][1];

        $this->assertTrue($newChangedRole instanceof Role);
        $this->assertEquals($newRoleName, $newChangedRole->getName());
    }

    public function testChange_EntityWithoutChanges()
    {
        $user = new User('testUsername');

        $this->em->persist($user);
        $this->em->flush($user);

        $changes = $this->changesFetcher->getChanges($user);

        $this->assertEmpty($changes);
    }

    /**
     * @depends testChange_NullToScalar
     */
    public function testChange_ChangeAfterComputingChangeSets(User $user)
    {
        $testRoleName = 'testRole';

        $user->setRole(new Role($testRoleName));

        $changes = $this->changesFetcher->getChanges($user);

        $this->assertArrayHasKey('role', $changes);
        $this->assertNull($changes['role'][0]);

        $changedRole = $changes['role'][1];

        $this->assertTrue($changedRole instanceof Role);
        $this->assertEquals($testRoleName, $changedRole->getName());

        $this->assertArrayHasKey('username', $changes);
        $this->assertNull($changes['username'][0]);
        $this->assertNotEmpty($changes['username'][1]);
    }
}
