<?php

namespace CuteNinja\MemoriaBundle\Repository;

use Doctrine\ORM\EntityRepository as DoctrineEntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * Class CriteriaBaseRepository
 *
 * @package CuteNinja\MemoriaBundle\Repository
 */
abstract class AbstractCriteriaRepository extends DoctrineEntityRepository
{
    /**
     * @param array|null        $select
     * @param array|null        $criteria
     * @param array|null        $orderBy
     * @param integer|null      $limit
     * @param integer|null      $offset
     * @param QueryBuilder|null $queryBuilder
     *
     * @return QueryBuilder
     */
    public function getByCriteriaQueryBuilder(
        array $select = null,
        array $criteria = null,
        array $orderBy = null,
        $limit = null,
        $offset = null,
        QueryBuilder $queryBuilder = null
    ) {
        $criteria = is_null($criteria) ? [] : $criteria;
        $orderBy  = is_null($orderBy) ? [] : $orderBy;

        if (is_null($queryBuilder)) {
            $queryBuilder = $this->getQueryBuilder();
        }

        // Select
        $select = is_null($select) ? [] : $select;
        foreach ($select as $alias) {
            $selectMethod = 'addSelect' . ucfirst($alias);
            if (method_exists($this, $selectMethod)) {
                $this->$selectMethod($queryBuilder);
            } else {
                $queryBuilder->addSelect($alias);
            }
        }

        foreach ($criteria as $field => $value) {
            if ($field) {
                $this->{'addCriteria' . ucfirst($field)}($queryBuilder, $value);
            }
        }

        foreach ($orderBy as $field => $direction) {
            if ($field) {
                $this->{'addOrderBy' . ucfirst($field)}($queryBuilder, $direction);
            }
        }

        // Pagination
        if ($limit) {
            $queryBuilder->setMaxResults($limit);
        }

        if ($offset) {
            $queryBuilder->setFirstResult($offset);
        }

        // Clean Query
        $this->cleanQueryBuilder($queryBuilder);

        return $queryBuilder;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param array        $criteria
     */
    protected function addCriteria(QueryBuilder $queryBuilder, array $criteria)
    {
        foreach ($criteria as $field => $value) {
            if ($field) {
                $this->{'addCriterion' . ucfirst($field)}($queryBuilder, $value);
            }
        }
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param array        $orderBys
     */
    protected function addOrderBys(QueryBuilder $queryBuilder, array $orderBys)
    {
        foreach ($orderBys as $criterion => $direction) {
            if ($criterion) {
                $this->{'addOrderBy' . ucfirst($criterion)}($queryBuilder, $direction);
            }
        }
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function getQueryBuilder()
    {
        // we did not put this method as abstract because we do not want to have compilation error for entities that do not require getByCriteriaQueryBuilder
        throw new \BadMethodCallException('The use of getByCriteriaQueryBuilder method require to override the method getQueryBuilder');
    }

    /**
     * Add criterion generic
     *
     * @param QueryBuilder $queryBuilder
     * @param string       $alias
     * @param string       $fieldName
     * @param mixed        $value
     * @param bool         $exclude If true, we search different value
     *
     * @return bool Condition was added or not
     */
    protected function addCriterion(QueryBuilder $queryBuilder, $alias, $fieldName, $value, $exclude = false)
    {
        list($condition, $parameter, $value) = $this->computeCriterionCondition($alias, $fieldName, $value, $exclude);

        if (is_null($condition)) {
            return false;
        }

        $queryBuilder->andWhere($condition);

        if (!is_null($parameter)) {
            $queryBuilder->setParameter($parameter, $value);
        }

        return true;
    }

    /**
     * Create DQL condition for addCriterion methods
     *
     * @param string $alias
     * @param string $fieldName
     * @param mixed  $value
     * @param bool   $exclude If true, we search different value
     *
     * @return array
     */
    protected function computeCriterionCondition($alias, $fieldName, $value, $exclude = false)
    {
        $condition      = null;
        $parameterField = null;
        $parameterValue = null;

        if (is_null($value)) {
            return [$condition, $parameterField, $parameterValue];
        }

        if (is_array($value) && !count($value)) {
            return [$condition, $parameterField, $parameterValue];
        }

        if (is_array($value)) {
            $operator       = $exclude ? 'NOT IN' : 'IN';
            $condition      = $alias . '.' . $fieldName . ' ' . $operator . ' (:' . $alias . '_' . $fieldName . ')';
            $parameterField = $alias . '_' . $fieldName;
            $parameterValue = $value;
        } elseif ('NULL' === $value) {
            $condition = $alias . '.' . $fieldName . ' IS NULL';
        } elseif ('NOT NULL' === $value) {
            $condition = $alias . '.' . $fieldName . ' IS NOT NULL';
        } else {
            $operator       = $exclude ? '!=' : '=';
            $condition      = $alias . '.' . $fieldName . ' ' . $operator . ' :' . $alias . '_' . $fieldName;
            $parameterField = $alias . '_' . $fieldName;
            $parameterValue = $value;
        }

        return [$condition, $parameterField, $parameterValue];
    }

    /**
     * Clean a QueryBuilder (Ex: Remove duplicate join)
     *
     * @param QueryBuilder $queryBuilder
     *
     * @return QueryBuilder $queryBuilder
     */
    protected function cleanQueryBuilder(QueryBuilder $queryBuilder)
    {
        $this->cleanQueryBuilderDqlPart($queryBuilder, 'join');
        $this->cleanQueryBuilderDqlPart($queryBuilder, 'select');

        return $queryBuilder;
    }

    /**
     * Remove duplication on a DQL part (join, select, ...)
     *
     * @param QueryBuilder $queryBuilder
     * @param string       $dqlPartName
     */
    protected function cleanQueryBuilderDqlPart(QueryBuilder $queryBuilder, $dqlPartName)
    {
        $dqlPart = $queryBuilder->getDQLPart($dqlPartName);
        if (count($dqlPart)) {
            $queryBuilder->resetDQLPart($dqlPartName);

            if ($dqlPartName == 'join') {
                foreach ($dqlPart as $root => $elements) {
                    $newDqlPart = [];
                    foreach ($elements as $element) {
                        preg_match('/^(?P<joinType>[^ ]+) JOIN (?P<join>[^ ]+) (?P<alias>[^ ]+)/', $element->__toString(), $matches);
                        if (!array_key_exists($matches['alias'], $newDqlPart)) {
                            $newDqlPart[$matches['alias']] = $element;
                        }
                    }
                    $dqlPart[$root] = array_values($newDqlPart);
                }

                // TODO Reorder ?
                $dqlPart = array_shift($dqlPart);
                foreach ($dqlPart as $element) {
                    $queryBuilder->add($dqlPartName, [$element], true);
                }
            } else {
                foreach ($dqlPart as $element) {
                    $newDqlPart[$element->__toString()] = $element;
                }
                $dqlPart = array_values($newDqlPart);

                // TODO Reorder ?
                foreach ($dqlPart as $element) {
                    $queryBuilder->add($dqlPartName, $element, true);
                }
            }
        }
    }
}
