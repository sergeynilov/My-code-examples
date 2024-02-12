<?php

namespace App\Policies;

use App\Models\BannerCategory;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BannerCategoryPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $this->getPolicyRule($user, null);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\BannerCategory  $bannerCategory
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, BannerCategory $bannerCategory)
    {
        return $this->getPolicyRule($user, $bannerCategory);
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $this->getPolicyRule($user, null);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\BannerCategory  $bannerCategory
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, BannerCategory $bannerCategory)
    {
        return $this->getPolicyRule($user, $bannerCategory);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\BannerCategory  $bannerCategory
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, BannerCategory $bannerCategory)
    {
        return $this->getPolicyRule($user, $bannerCategory);
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\BannerCategory  $bannerCategory
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, BannerCategory $bannerCategory)
    {
        return $this->getPolicyRule($user, $bannerCategory);
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\BannerCategory  $bannerCategory
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, BannerCategory $bannerCategory)
    {
        return $this->getPolicyRule($user, $bannerCategory);
    }

    /**
     * Determine whether the user have permanently access to all actions.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\BannerCategory  $bannerCategory
     * @return bool
     */
    private function getPolicyRule(User $user, ?BannerCategory $bannerCategory): bool
    {
        return $user->hasAnyRole(ACCESS_ROLE_MANAGER_LABEL); // 2 - Manager - can  edit pages banners/banner categories
    }

}
