<?php

namespace App\Library\Services;

use App\Enums\UserAccountManagerEnum;
use App\Library\Services\Interfaces\UserAccountManager;
use Auth;
use App\Exceptions\UserAccountManagerAccessException;

/*
*
Class to check if logged user has access to some resource by provided rules
*
* Example of use :
        $this->userAccountManager->checkPermissions(
            [
                UserAccountManagerEnum::UAM_USER_HAS_ACTIVE_STATUS,
                UserAccountManagerEnum::UAM_USER_IS_DEVELOPERS_TEAM,
                UserAccountManagerEnum::UAM_USER_HAS_POSITIVE_BALANCE,
                UserAccountManagerEnum::UAM_USER_HAS_NO_BAN,
                UserAccountManagerEnum::UAM_USER_HAS_ARTICLES_ACCESS,
            ])

*/

class LocalUserAccountManager implements UserAccountManager
{
    public function checkPermissions(array $permissions, string $actionLabel): bool
    {
        $loggedUser = $this->getAuthUser();
        throw_if(! $loggedUser, UserAccountManagerAccessException::class, 'Your are not logged  in "' .  $actionLabel . '" method !' );

        $userHasActiveStatus    = $loggedUser->status === 'A';
        $userIsInDevelopersTeam = $this->getIsInDevelopersTeam();
        $userHasPositiveBalance = $this->getHasPositiveBalance();
        $userHasActiveBan       = $this->getHasActiveBan();
        $userHasVotesAccess     = $this->getHasVotesAccess();
        $userHasArticlesAccess  = $this->getHasArticlesAccess();

        foreach ($permissions as $permission) {
            if ($permission === UserAccountManagerEnum::UAM_USER_HAS_ACTIVE_STATUS and ! $userHasActiveStatus) {
                throw new UserAccountManagerAccessException('Your account must be active in "' .  $actionLabel . '" method !');
            }
            if ($permission === UserAccountManagerEnum::UAM_USER_IS_DEVELOPERS_TEAM and !$userIsInDevelopersTeam) {
                throw new UserAccountManagerAccessException('Your are not in developers team in "' .  $actionLabel . '" method !');
            }

            if ($permission === UserAccountManagerEnum::UAM_USER_HAS_POSITIVE_BALANCE and !$userHasPositiveBalance) {
                throw new UserAccountManagerAccessException('Your account has no positive balance in "' .  $actionLabel . '" method !');
            }
            if ($permission === UserAccountManagerEnum::UAM_USER_HAS_NO_BAN and $userHasActiveBan) {
                throw new UserAccountManagerAccessException('Your account has active ban in "' .  $actionLabel . '" method !');
            }


            if ($permission === UserAccountManagerEnum::UAM_USER_HAS_VOTES_ACCESS and ! $userHasVotesAccess) {
                throw new UserAccountManagerAccessException('Your have no access to votes in "' .  $actionLabel . '" method !');
            }
            if ($permission === UserAccountManagerEnum::UAM_USER_HAS_ARTICLES_ACCESS and ! $userHasArticlesAccess) {
                throw new UserAccountManagerAccessException('Your have no access to articles in "' .  $actionLabel . '" method !');
            }
        }
        return true;
    }

    public function getAuthUser(): bool|\App\Models\User
    {
        $loggedUser = Auth::user();
        if (! $loggedUser) {
            return false;
        }

        return $loggedUser;
    }

    /*
     * Calling method of service to check if current user is in developer's team
     */
    public function getIsInDevelopersTeam(): bool
    {
        return true;
    }

    /*
     * Calling method of service to check if current user has active ban
     */
    public function getHasActiveBan(): bool
    {
        return false;
    }

    /*
     * Calling method of service to check if current user has access to articles
     */
    public function getHasArticlesAccess(): bool
    {
        return true;
    }

    /*
     * Calling method of service to check if current user has access to votes
     */
    public function getHasVotesAccess(): bool
    {
        return true;
    }

    /*
    * Calling method of service to check if current user has positive balance
    */
    public function getHasPositiveBalance(): bool
    {
        return true;
    }

}
