<?php


namespace Phramework\JSONAPI;


interface IDirective
{
    public function validate(InternalModel $model);

    /**
     * @todo clarify request
     */
    public function parseFromRequest(\stdClass $request, InternalModel $model);
}
