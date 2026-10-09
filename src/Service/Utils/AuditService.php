<?php

namespace App\Service\Utils;

use App\Entity\Impl\AbstractEntity;
use DateTimeImmutable;
use Symfony\Bundle\SecurityBundle\Security;

class AuditService
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    /**
     * Estampille une entité comme créée maintenant, par l'utilisateur courant s'il y en a un.
     */
    public function stampCreation(AbstractEntity $entity): void
    {
        $entity->setCreatedAt(new DateTimeImmutable());
        $entity->setCreatedBy($this->security->getUser());
    }

    /**
     * Estampille une entité comme modifiée maintenant, par l'utilisateur courant s'il y en a un.
     */
    public function stampUpdate(AbstractEntity $entity): void
    {
        $entity->setUpdatedAt(new DateTimeImmutable());
        $entity->setUpdatedBy($this->security->getUser());
    }

    /**
     * Marque une entité comme supprimée maintenant, par l'utilisateur courant s'il y en a un.
     */
    public function markDeleted(AbstractEntity $entity): void
    {
        $entity->setDeletedAt(new DateTimeImmutable());
        $entity->setDeletedBy($this->security->getUser());
    }
}
