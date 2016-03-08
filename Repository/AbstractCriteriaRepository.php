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
     * @param array $criteria
     * @param array $order
     * @param bool  $withJoin
     *
     * @return QueryBuilder
     */
    abstract public function getForListActionQueryBuilder($criteria, $order, $withJoin);

    /**
     * @param $entityId
     *
     * @return mixed
     */
    abstract public function getForGetAction($entityId);

    /**
     * @param QueryBuilder $queryBuilder
     * @param array        $criteria
     */
    public function addCriteria(QueryBuilder $queryBuilder, array $criteria)
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
    public function addOrderBys(QueryBuilder $queryBuilder, array $orderBys)
    {
        foreach ($orderBys as $criterion => $direction) {
            if ($criterion) {
                $this->{'addOrderBy' . ucfirst($criterion)}($queryBuilder, $direction);
            }
        }
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
