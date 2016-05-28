<?php


namespace Phramework\JSONAPI;


abstract class ResourceModel
{

    /**
     * @type InternalModel
     */
    protected static $model = null;

    /**
     * @return InternalModel
     */
    public static function getModel() : InternalModel
    {
        if (static::$model === null) {
            self::defineModel();
        }

        return static::$model;
    }

    /**
     * MUST BE IMPLEMENTED
     */
    abstract public static function defineModel();

/*{
static::$model = (new InternalModel('user'))
->setPage(new Page(1));
}*/

    /**
     * @param string $name
     * @param array  $arguments
     * @return mixed
     */
    public static function __callStatic(string $name, array $arguments)
    {
        if (in_array(
            $name,
            [
                'get',
                'getById',
                'getResourceType',
            ]
        )) {
            return call_user_func_array(
                [static::getModel(), $name],
                $arguments
            );
        }

        return call_user_func_array(
            [static::getModel(), $name],
            $arguments
        );
    }

    
}
