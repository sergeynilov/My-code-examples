<?php

//namespace App\Repositories\Interfaces;
namespace App\Library\Services\Interfaces;
//

//use App\DTO\TaskDTO;
use Illuminate\Database\Eloquent\Model;

interface StubsInterface
{


    /**
     * Replace variables of stub with the class value
     *
     * @param $stub
     * @param array $stubVariables
     * @return string
     */
    public function getStubContents($stub, $stubVariables = []): string;

    /**
     * Get the stub path and the stub variables and generate resulting code string
     *
     * @return string
     *
     */
    public function generateResultingCode():string;

    /**
     **
     * Map the stub variables present in stub to its value
     *
     * @return array
     *
     */
    public function getStubVars(): array;

}
