<?php

namespace Saccas\JsonApiClientGenerator;

class RelationshipTypeService
{
    public function __construct(
        protected array $modelSchemaConfiguration,
    ) {
    }

    public function getSchemaForRelationship(string $schema, string $relationshipName, array $relationShipSchema): string
    {
        $override = $this->modelSchemaConfiguration[$schema]['relationships'][$relationshipName]['targetSchema'] ?? null;
        if (isset($override)) {
            return $override;
        }

        if ($relationShipSchema['properties']['data']['$ref'] === 'relationshipToMany') {
            return substr($relationshipName, -1);
        }

        return $relationshipName;
    }
}
