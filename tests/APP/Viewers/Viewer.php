<?php
namespace Phramework\JSONAPI\APP\Viewers;

/**
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class Viewer implements \Phramework\Viewers\IViewer
{
    protected static $buffer = [];
    /**
     * Display output
     *
     * @param array $parameters Output parameters to display
     */
    public function view($parameters)
    {
        static::$buffer[] = $parameters;
    }

    public function __destruct()
    {
        foreach (static::$buffer as $parameters) {
            echo PHP_EOL;
            echo json_encode($parameters, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            echo PHP_EOL;
        }
    }
}
