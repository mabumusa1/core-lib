<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment;

use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\Column;
use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Segment\Decorator\FilterDecoratorInterface;
use Mautic\LeadBundle\Segment\FilterQueryBuilder\BaseFilterQueryBuilder;
use Mautic\LeadBundle\Services\LeadSegmentFilterQueryBuilderTrait;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

class LeadSegmentFilter
{
    use LeadSegmentFilterQueryBuilderTrait;

    /**
     * @var LeadSegmentFilterCrate
     */
    private $leadSegmentFilterCrate;

    /**
     * @var FilterDecoratorInterface
     */
    private $filterDecorator;

    /**
     * @var BaseFilterQueryBuilder
     */
    private $queryBuilder;

    /** @var Column */
    private $dbColumn;

    public function __construct(
        LeadSegmentFilterCrate $leadSegmentFilterCrate,
        FilterDecoratorInterface $filterDecorator,
        EntityManager $em = null
    ) {
        $this->leadSegmentFilterCrate = $leadSegmentFilterCrate;
        $this->filterDecorator        = $filterDecorator;
        $this->em                     = $em;
    }

    /**
     * @param null $argument
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getFilterConditionValue($argument = null)
    {
        switch ($this->getDBColumn()->getType()->getName()) {
            case 'number':
            case 'integer':
            case 'float':
                return ':'.$argument;
            case 'datetime':
            case 'date':
                return sprintf('":%s"', $argument);
            case 'text':
            case 'string':
                switch ($this->getFunc()) {
                    case 'eq':
                    case 'ne':
                    case 'neq':
                        return sprintf("':%s'", $argument);
                    default:
                        throw new \Exception('Unknown operator '.$this->getFunc());
                }
            default:
                var_dump($this->getDBColumn()->getType()->getName());
                var_dump($this);
                die();
        }
        var_dump($filter);
        throw new \Exception(sprintf('Unknown value type \'%s\'.', $filter->getName()));
    }

    public function createQuery(QueryBuilder $queryBuilder, $alias = false)
    {
        dump('creating query:'.$this->getObject());
        $glueFunc = $this->getGlue().'Where';

        $parameterName = $this->generateRandomParameterName();

        $queryBuilder = $this->createExpression($queryBuilder, $parameterName, $this->getFunc());

        $queryBuilder->setParameter($parameterName, $this->getFilter());

        dump($queryBuilder->getSQL());

        return $queryBuilder;
    }

    public function createExpression(QueryBuilder $queryBuilder, $parameterName, $func = null)
    {
        dump('creating query:'.$this->getField());
        $func  = is_null($func) ? $this->getFunc() : $func;
        $alias = $this->getTableAlias($this->getEntityName(), $queryBuilder);
        $desc  = $this->getQueryDescription();
        if (!$alias) {
            if ($desc['func']) {
                $queryBuilder = $this->createJoin($queryBuilder, $this->getEntityName(), $alias = $this->generateRandomParameterName());
                $expr         = $queryBuilder->expr()->$func($desc['func'].'('.$alias.'.'.$this->getDBColumn()->getName().')', $this->getFilterConditionValue($parameterName));
                $queryBuilder = $queryBuilder->andHaving($expr);
            } else {
                if ($alias != 'l') {
                    $queryBuilder = $this->createJoin($queryBuilder, $this->getEntityName(), $alias = $this->generateRandomParameterName());
                    $expr         = $queryBuilder->expr()->$func($alias.'.'.$this->getDBColumn()->getName(), $this->getFilterConditionValue($parameterName));
                    $queryBuilder = $this->AddJoinCondition($queryBuilder, $alias, $expr);
                } else {
                    dump('lead restriction');
                    $expr = $queryBuilder->expr()->$func($alias.'.'.$this->getDBColumn()->getName(), $this->getFilterConditionValue($parameterName));
                    var_dump($expr);
                    die();
                    $queryBuilder = $queryBuilder->andWhere($expr);
                }
            }
        } else {
            if ($alias != 'l') {
                $expr         = $queryBuilder->expr()->$func($alias.'.'.$this->getDBColumn()->getName(), $this->getFilterConditionValue($parameterName));
                $queryBuilder = $this->AddJoinCondition($queryBuilder, $alias, $expr);
            } else {
                $expr         = $queryBuilder->expr()->$func($alias.'.'.$this->getDBColumn()->getName(), $this->getFilterConditionValue($parameterName));
                $queryBuilder = $queryBuilder->andWhere($expr);
            }
        }

        return $queryBuilder;
    }

    public function getDBTable()
    {
        //@todo cache metadata
        try {
            $tableName = $this->em->getClassMetadata($this->getEntityName())->getTableName();
        } catch (MappingException $e) {
            return $this->getObject();
        }

        return $tableName;
    }

    public function getEntityName()
    {
        $converter = new CamelCaseToSnakeCaseNameConverter();
        if ($this->getQueryDescription()) {
            $table = $this->queryDescription['foreign_table'];
        } else {
            $table = $this->getObject();
        }

        $entity = sprintf('MauticLeadBundle:%s', ucfirst($converter->denormalize($table)));

        return $entity;
    }

    /**
     * @return Column
     *
     * @throws \Exception
     */
    public function getDBColumn()
    {
        if (is_null($this->dbColumn)) {
            if ($descr = $this->getQueryDescription()) {
                $this->dbColumn = $this->em->getConnection()->getSchemaManager()->listTableColumns($this->queryDescription['foreign_table'])[$this->queryDescription['field']];
            } else {
                $dbTableColumns = $this->em->getConnection()->getSchemaManager()->listTableColumns($this->getDBTable());
                if (!$dbTableColumns) {
                    var_dump($this);
                    throw new \Exception('Unknown database table and no translation provided for type "'.$this->getType().'"');
                }
                if (!isset($dbTableColumns[$this->getField()])) {
                    throw new \Exception('Unknown database column and no translation provided for type "'.$this->getType().'"');
                }
                $this->dbColumn = $dbTableColumns[$this->getField()];
            }
        }

        return $this->dbColumn;
    }

    /**
     * @return string|null
     */
    public function getOperator()
    {
        return $this->filterDecorator->getOperator($this->leadSegmentFilterCrate);
    }

    public function getField()
    {
        return $this->filterDecorator->getField($this->leadSegmentFilterCrate);
    }

    public function getTable()
    {
        return $this->filterDecorator->getTable($this->leadSegmentFilterCrate);
    }

    public function getParameterHolder($argument)
    {
        return $this->filterDecorator->getParameterHolder($this->leadSegmentFilterCrate, $argument);
    }

    public function getParameterValue()
    {
        return $this->filterDecorator->getParameterValue($this->leadSegmentFilterCrate);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'glue'     => $this->leadSegmentFilterCrate->getGlue(),
            'field'    => $this->leadSegmentFilterCrate->getField(),
            'object'   => $this->leadSegmentFilterCrate->getObject(),
            'type'     => $this->leadSegmentFilterCrate->getType(),
            'filter'   => $this->leadSegmentFilterCrate->getFilter(),
            'display'  => $this->leadSegmentFilterCrate->getDisplay(),
            'operator' => $this->leadSegmentFilterCrate->getOperator(),
            'func'     => $this->leadSegmentFilterCrate->getFunc(),
        ];
    }

    /**
     * @return BaseFilterQueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     * @param BaseFilterQueryBuilder $queryBuilder
     *
     * @return LeadSegmentFilter
     */
    public function setQueryBuilder($queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf('%s %s = %s', $this->getObject(), $this->getField(), $this->getField());
    }
}
