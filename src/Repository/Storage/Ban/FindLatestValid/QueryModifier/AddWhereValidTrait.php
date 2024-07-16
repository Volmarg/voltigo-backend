<?php

namespace App\Repository\Storage\Ban\FindLatestValid\QueryModifier;

use Doctrine\ORM\QueryBuilder;

/**
 * Adds the conditions to only fetch the valid entries
 */
trait AddWhereValidTrait
{
    /**
     * @param QueryBuilder $queryBuilder
     * @param string       $alias
     *
     * @return QueryBuilder
     */
    protected function addWhereValid(QueryBuilder $queryBuilder, string $alias): QueryBuilder
    {
        $queryBuilder->andWhere(
            $queryBuilder->expr()->orX(
                "{$alias}.lifetime = 1",
                $queryBuilder->expr()->andX(
                    "{$alias}.validTill IS NOT NULL",
                    "{$alias}.validTill < NOW()" // cannot use interval here, not supported by doctrine
                )
            )
        );

        return $queryBuilder;
    }
}