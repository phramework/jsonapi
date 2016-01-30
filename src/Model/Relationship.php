<?php
/**
 * Created by PhpStorm.
 * User: nohponex
 * Date: 30/1/2016
 * Time: 12:56 μμ
 */

namespace Phramework\JSONAPI\Model;


use Phramework\JSONAPI\Resource;

abstract class Relationship extends Get
{
    /**
     * Get resource's relationships
     * @return object Object with Relationship objects as values
     */
    public static function getRelationships()
    {
        return new \stdClass();
    }

    public static function getRelationship($relationshipKey)
    {
        $relationships = static::getRelationships();

        if (!isset($relationships->{$relationshipKey})) {
            throw new \Exception('Not a valid relationship key');
        }

        return $relationships->{$relationshipKey};
    }
    /**
     * Check if relationship exists
     * @param  string $relationshipKey Relationship's key (alias)
     * @return Boolean
     */
    public static function relationshipExists($relationshipKey)
    {
        $relationships = static::getRelationships();

        return isset($relationships->{$relationshipKey});
    }

    /**
     * Get records from a relationship link
     * @param  string $relationshipKey
     * @param  string $id
     * @return stdClass|stdClass[]
     * @throws \Phramework\Exceptions\ServerException If relationship doesn't exist
     * @throws \Phramework\Exceptions\ServerException If relationship's class method is
     * not defined
     */
    public static function getRelationshipData(
        $relationshipKey,
        $id,
        $primaryDataParameters  = [],
        $relationshipParameters = []
    ) {
        if (!static::relationshipExists($relationshipKey)) {
            throw new \Phramework\Exceptions\ServerException(
                'Not a valid relationship key'
            );
        }

        $relationship = static::getRelationship($relationshipKey);

        switch ($relationship->getRelationshipType()) {
            case Relationship::TYPE_TO_ONE:
                $callMethod = [
                    static::class,
                    'getById'
                ];

                /*if (!is_callable($callMethod)) {
                    throw new \Phramework\Exceptions\ServerException(
                        $callMethod[0] . '::' . $callMethod[1]
                        . ' is not implemented'
                    );
                }*/

                //We have to get this type's resource
                $resource = call_user_func_array(
                    $callMethod,
                    array_merge([$id], $primaryDataParameters)
                );

                if (!$resource) {
                    return null;
                }

                //And use it's relationships data for this relationship
                return (
                isset($resource->relationships->{$relationshipKey}->data)
                    ? $resource->relationships->{$relationshipKey}->data
                    : null
                );

                break;
            case Relationship::TYPE_TO_MANY:
            default:
                $callMethod = [
                    $relationship->getRelationshipClass(),
                    self::GET_RELATIONSHIP_BY_PREFIX . ucfirst(static::getType())
                ];

                if (!is_callable($callMethod)) {
                    throw new \Phramework\Exceptions\ServerException(
                        $callMethod[0] . '::' . $callMethod[1]
                        . ' is not implemented'
                    );
                }

                //also we could attempt to use getById like the above TO_ONE
                //to use relationships data

                return call_user_func_array(
                    $callMethod,
                    array_merge([$id], $relationshipParameters)
                );
                break;
        }
    }

    /**
     * Get jsonapi's included object, selected by include argument,
     * using id's of relationship's data from resources in primary data object
     * @param  Resource|Resource[]  $primaryData Primary data resource or resources
     * @param  string[]             $include     An array with the keys of relationships to include
     * @return Resource[]           An array with all included related data
     * @throws \Phramework\Exceptions\RequestException When a relationship is not found
     * @throws \Phramework\Exceptions\ServerException
     * @todo handle Relationship resource cannot be accessed
     * @todo include second level relationships
     * @todo multiple getById at once
     */
    public static function getIncludedData(
        $primaryData,
        $include = [],
        $additionalResourceParameters = []
    ) {
        //Store relationshipKeys as key and ids of their related data as value
        $temp = [];

        //check if relationship exists
        foreach ($include as $relationshipKey) {
            if (!static::relationshipExists($relationshipKey)) {
                throw new \Phramework\Exceptions\RequestException(sprintf(
                    'Relationship "%s" not found',
                    $relationshipKey
                ));
            }

            //Will hold ids of related data
            $temp[$relationshipKey] = [];
        }

        if (empty($include) || empty($primaryData)) {
            return [];
        }

        //iterate through all primary data

        //if a single resource
        if (!is_array($primaryData)) {
            $primaryData = [$primaryData];
        }

        foreach ($primaryData as $resource) {
            //ignore if relationships are not set
            if (!property_exists($resource, 'relationships')) {
                continue;
            }

            foreach ($include as $relationshipKey) {
                //ignore if this relationship is not set
                if (!isset($resource->relationships->{$relationshipKey})) {
                    continue;
                }

                if (!isset($resource->relationships->{$relationshipKey}->data)) {
                    continue;
                }

                //if single
                $relationshipData = $resource->relationships->{$relationshipKey}->data;

                if (!$relationshipData || empty($relationshipData)) {
                    continue;
                }

                //if a single resource
                if (!is_array($relationshipData)) {
                    $relationshipData = [$relationshipData];
                }

                foreach ($relationshipData as $primaryKeyAndType) {
                    //push primary key (use type? $primaryKeyAndType->type)
                    $temp[$relationshipKey][] = $primaryKeyAndType->id;
                }
            }
        }

        $included = [];

        //remove duplicates
        foreach ($include as $relationshipKey) {
            $relationship = static::getRelationship($relationshipKey);

            $callMethod = [
                $relationship->getRelationshipClass(),
                'getById'
            ];

            /*if (!is_callable($callMethod)) {
                throw new \Phramework\Exceptions\ServerException(
                    $callMethod[0] . '::' . $callMethod[1]
                    . ' is not implemented'
                );
            }*/

            foreach (array_unique($temp[$relationshipKey]) as $idAttribute) {
                $additionalArgument = (
                isset($additionalResourceParameters[$relationshipKey])
                    ? $additionalResourceParameters[$relationshipKey]
                    : []
                );

                $resource = call_user_func_array(
                    $callMethod,
                    array_merge([$idAttribute], $additionalArgument)
                );

                if (!$resource) {
                    //throw new \Exception('Relationship resource cannot be accessed');
                } else {
                    //push to included
                    $included[] = $resource;
                }
            }
        }

        return $included;
    }
}