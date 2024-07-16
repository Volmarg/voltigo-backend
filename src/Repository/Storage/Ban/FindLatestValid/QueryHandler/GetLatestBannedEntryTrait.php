<?php

namespace App\Repository\Storage\Ban\FindLatestValid\QueryHandler;

use App\Entity\Storage\Ban\BaseBanStorage;
use Doctrine\ORM\QueryBuilder;

/**
 * Handles fetching the latest found banned entry
 */
trait GetLatestBannedEntryTrait
{

    /**
     * @param QueryBuilder $queryBuilder
     * @param int          $validTillMinutesOffset
     *
     * @return BaseBanStorage|null
     */
    public function getLatestBannedEntry(QueryBuilder $queryBuilder, int $validTillMinutesOffset): ?BaseBanStorage
    {
        /** @var BaseBanStorage[] $results */
        $results = $queryBuilder->getQuery()->execute();
        foreach ($results as $result) {
            if ($result->isLifetime()) {
                return $result;
            }

            if (empty($result->getValidTill())) {
                continue;
            }

            $clonedValidDate = clone $result->getValidTill();
            if ($clonedValidDate->modify("+{$validTillMinutesOffset} MINUTES")) {
                return $result;
            }
        }

        return null;
    }
}