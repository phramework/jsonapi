<?php


namespace src;


use Phramework\JSONAPI\APP\Models\User;
use Phramework\JSONAPI\APP\Models\Administrator\User as AdministratorUser;

class ResourceModelTest extends \PHPUnit_Framework_TestCase
{
    public function testInheritance()
    {
        $administratorModel = AdministratorUser::getModel();
        $model              = User::getModel();

        var_dump(AdministratorUser::class);
        /*var_dump(
            $administratorModel->getDefaultDirectives()
        );*/
        AdministratorUser::get();

        var_dump(User::class);
        /*
        var_dump($model->getDefaultDirectives());*/
        User::get();

    }

}
