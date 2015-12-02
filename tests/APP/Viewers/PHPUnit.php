<?php
namespace Phramework\JSONAPI\APP\Viewers;

/**
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class PHPUnit implements \Phramework\Viewers\IViewer
{
    protected static $callback;

    public static function setCallback($callback)
    {
        self::$callback = $callback;
    }

    /**
     * Display output
     *
     * @param array $parameters Output parameters to display
     */
    public function view($parameters)
    {
        if (self::$callback) {
            call_user_func(
                self::$callback,
                $parameters
            );
        }
    }
}
