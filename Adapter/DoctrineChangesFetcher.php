<?php


namespace Daimos\ChangesFetcher\Adapter;


use Daimos\ChangesFetcher\ChangesFetcher;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;

class DoctrineChangesFetcher implements ChangesFetcher
{

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getChanges($entity)
    {
        /** @var UnitOfWork $uow */
        $uow = $this->entityManager->getUnitOfWork();

        try {
            if($uow->getEntityState($entity) === UnitOfWork::STATE_NEW) {
                return array();
            }
        }
        catch(\Doctrine\ORM\Mapping\MappingException $e) {
            return array();
        }
        catch(\Doctrine\Common\Persistence\Mapping\MappingException $e) {
            return array();
        }

        $classMetaData = $this->entityManager->getClassMetadata(get_class($entity));

        $uow->computeChangeSet($classMetaData, $entity);

        $changes = $uow->getEntityChangeSet($entity);

        # Clear ChangeSet so it can be recomputed when flushing
        $uow->clearEntityChangeSet(spl_object_hash($entity));

        return $changes;
    }
}