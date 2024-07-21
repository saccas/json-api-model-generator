<?php

namespace SacCas\JsonApiClientGenerator;

class RelationshipTypeService
{
    public function __construct(
        protected array $mapping,
    ) {
    }

    public function getTypeForRelationship(string $schema, string $relationshipName, array $relationShipSchema): string
    {
        if (isset($this->mapping[$schema][$relationshipName])) {
            return $this->mapping[$schema][$relationshipName];
        }

        if ($relationShipSchema['properties']['data']['$ref'] === 'relationshipToMany') {
            return substr($relationshipName, -1);
        }

        return $relationshipName;
    }
}