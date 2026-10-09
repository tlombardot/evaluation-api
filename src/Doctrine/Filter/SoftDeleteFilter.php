<?php

namespace App\Doctrine\Filter;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

final class SoftDeleteFilter extends SQLFilter
{
    /**
     * Masque les lignes supprimées en douceur dans toute requête que Doctrine construit.
     */
    public function addFilterConstraint(ClassMetadata $targetEntity, string $targetTableAlias): string
    {
        // Doctrine appelle cette méthode pour chaque entité de chaque requête, y compris celles
        // qui ne portent aucune date de suppression : une chaîne vide n'ajoute rien
        if (!$targetEntity->hasField('deletedAt')) {
            return '';
        }

        // le nom est celui de la colonne en base, pas celui de la propriété PHP : ce fragment
        // part tel quel dans le SQL
        return sprintf('%s.deleted_by_id IS NULL', $targetTableAlias);
    }
}
