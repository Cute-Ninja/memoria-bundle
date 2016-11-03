<?php

namespace CuteNinja\MemoriaBundle\Repository;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;

/**
 * Interface CuteNinjaRepositoryInterface
 *
 * @package CuteNinja\MemoriaBundle\Repository
 */
interface CuteNinjaRepositoryInterface
{
    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder ();

    /**
     * @param array $criteria
     * @param array $selects
     *
     * @return mixed
     *
     * @throws NonUniqueResultException
     */
    public function getOneByCriteria (array $criteria = [], array $selects = []);

    /**
     * @param array $criteria
     * @param array $orders
     * @param array $selects
     *
     * @return QueryBuilder
     */
    public function getManyByCriteriaQueryBuilder (array $criteria = [], array $selects = [], array $orders = []);

    /**
     * @param QueryBuilder    $queryBuilder
     * @param string|string[] $status
     *
     * @return boolean
     */
    public function addCriterionStatus (QueryBuilder $queryBuilder, $status);

    /**
     * @param QueryBuilder    $queryBuilder
     * @param string|string[] $status
     *
     * @return boolean
     */
    public function addCriterionExcludedStatus (QueryBuilder $queryBuilder, $status);

    /**
     * @param QueryBuilder $queryBuilder
     * @param string       $direction Values allowed: DESC, ASC
     *
     * @return boolean
     */
    public function addOrderByStatus (QueryBuilder $queryBuilder, $direction);
}
