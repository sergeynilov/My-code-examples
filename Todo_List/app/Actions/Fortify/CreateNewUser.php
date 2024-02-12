<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use DB;

//use Laravel\Fortify\Contracts\CreatesNewUsers;
use App\Actions\Fortify\CreatesNewUsers;

// /_wwwroot/lar/hostels4j/resources/fortify/CreatesNewUsers.php
use Laravel\Jetstream\Jetstream;
use Spatie\Permission\Models\Permission;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param array $input
     *
     * @return \App\Models\User
     */
    public function create(array $input, bool $make_validation, array $hasPermissions)
    {
        if ($make_validation) {
            $userValidationRulesArray = User::getUserValidationRulesArray(null,  []);
            if (\App::runningInConsole()) {
                unset($userValidationRulesArray['password_2']);
            }


            $validator = Validator::make($input, $userValidationRulesArray);//->validate();
            if ($validator->fails()) {
                $errorMsg = $validator->getMessageBag();
                if (\App::runningInConsole()) {
                    echo '::$errorMsg::' . print_r($errorMsg, true) . '</pre>';
                }

                return $errorMsg;
            }
        } // if($make_validation) {

        $newUserData = [
            'name'         => $input['name'],
            'email'        => $input['email'],
        ];

        if (isset($input['id'])) {
            $newUserData['id'] = $input['id'];
        }
        if (isset($input['confirmation_code'])) {
            $newUserData['confirmation_code'] = $input['confirmation_code'];
        }
        if (isset($input['phone'])) {
            $newUserData['phone'] = $input['phone'];
        }
        if (isset($input['website'])) {
            $newUserData['website'] = $input['website'];
        }
        if (isset($input['notes'])) {
            $newUserData['notes'] = $input['notes'];
        }

        if (isset($input['first_name'])) {
            $newUserData['first_name'] = $input['first_name'];
        }
        if (isset($input['last_name'])) {
            $newUserData['last_name'] = $input['last_name'];
        }

        if (isset($input['password'])) {
            $newUserData['password'] = Hash::make($input['password']);
        }
        if (isset($input['status'])) {
            $newUserData['status'] = $input['status'];
        }

        if (isset($input['phone'])) {
            $newUserData['phone'] = $input['phone'];
        }
        if (isset($input['website'])) {
            $newUserData['website'] = $input['website'];
        }

        if (isset($input['notes'])) {
            $newUserData['notes'] = $input['notes'];
        }

        if (isset($input['last_login'])) {
            $newUserData['last_login'] = $input['last_login'];
        }

        if (isset($input['first_name'])) {
            $newUserData['first_name'] = $input['first_name'];
        }

        if (isset($input['last_name'])) {
            $newUserData['last_name'] = $input['last_name'];
        }

        if (isset($input['email_verified_at'])) {
            $newUserData['email_verified_at'] = $input['email_verified_at'];
        }


        try {
            DB::beginTransaction();

            $newUser = User::create($newUserData);
            foreach ($hasPermissions as $nextHasPermission) {
                $appAdminPermission = Permission::findByName($nextHasPermission, 'web');
                if ($appAdminPermission) {
                    $newUser->givePermissionTo($appAdminPermission);
                }

            }
            DB::commit();
            return $newUser;

        } catch (QueryException $e) {
            DB::rollBack();
            $errorMessages = new \Illuminate\Support\MessageBag;
            $errorMessages->add('name', $e->getMessage());
            if (\App::runningInConsole()) {
                echo '::$e->getMessage()::' . print_r($e->getMessage(), true) . '</pre>';
            }
            return $errorMessages;
        }
        return false;
    }
}
