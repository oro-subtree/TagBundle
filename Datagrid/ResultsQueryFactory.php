<?php

namespace Oro\Bundle\TagBundle\Datagrid;

use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\GridBundle\Datagrid\ORM\QueryFactory\EntityQueryFactory;

class ResultsQueryFactory extends EntityQueryFactory
{
    /**
     * @var ObjectMapper
     */
    protected $mapper;

    /**
     * {@inheritDoc}
     */
    public function __construct(RegistryInterface $registry, $className, ObjectMapper $mapper)
    {
        parent::__construct($registry, $className);

        $this->mapper = $mapper;
    }

    /**
     * {@inheritDoc}
     */
    public function createQuery()
    {
        $em = $this->registry->getEntityManagerForClass($this->className);
        $this->queryBuilder = $em->getRepository($this->className)->createQueryBuilder($this->alias);

        if (!$this->queryBuilder) {
            throw new \LogicException('Can\'t create datagrid query. Query builder is not configured.');
        }

        return new ResultsQuery($this->queryBuilder, $em, $this->mapper);
    }
}
