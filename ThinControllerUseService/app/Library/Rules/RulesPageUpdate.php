<?php

namespace App\Library\Rules;

use App\Models\Page;
use App\Models\User;
use App\Enums\UpdatePageRules;
use Auth;

/**
 * Additive Validation rules for page model
 */

class RulesPageUpdate
{
    protected ?int $id;
    protected ?Page $page;
    protected string $actionLabel;

    /**
     *
     * @param string $actionLabel - http method as string
     * @param int $id - ID of page to make validation
     * @param Page $page - Model of page to make validation
     *
     */
    public function __construct(string $actionLabel, User $loggedUser, ?int $id, ?Page $page)
    {
        $this->actionLabel = $actionLabel;
        $this->id = $id;
        $this->page = $page;
        $this->loggedUser = $loggedUser;
    }

    /**
     *
     * @param array $rulesToValidate - which rules must be checked
     * @param bool $checkActiveUser - if logged user must be checked as logged
     *
     * @return array of : bool 'result' = Was validation successful,
     * string 'message' => 'Error message if validation was not successful',
     * int 'returnCode' => returned http code
     */
    public function check(array $rulesToValidate, bool $checkActiveUser = false): array
    {
        if(empty($this->loggedUser)) {
            return response()->json('Your account is not found to run "' . $this->actionLabel . '" action', HTTP_RESPONSE_UNAUTHORIZED);
        }

        if(in_array(UpdatePageRules::UPR_PAGE_NOT_FOUND_BY_ID, $rulesToValidate)) {
            // check if $this->page is valid Page model
            if (empty($this->page) or !($this->page instanceof \App\Models\Page)) {
                return [
                    'result'     => false,
                    'message'    => 'Page # ' . $this->id . ' not found',
                    'returnCode' => HTTP_RESPONSE_NOT_FOUND
                ];
            }
        }

        if(in_array(UpdatePageRules::UPR_LOGGED_USER_HAS_NOT_ACTIVE_STATUS_FOR_ACTION, $rulesToValidate)) {
            // Logged user account must be active
            if ($checkActiveUser and $this->loggedUser->status != 'A') {
                return [
                    'result' => false,
                    'message' => 'Your account is not active to run "' . $this->actionLabel . '" action',
                    'returnCode' => HTTP_RESPONSE_UNAUTHORIZED
                ];
            }
        }

        if(in_array(UpdatePageRules::UPR_LOGGED_USER_IS_NOT_ADMIN_OR_MANAGER_FOR_ACTION, $rulesToValidate)) {
            // Only Admin and Manager have access to publish/unpublish pages
            if ( ! $this->loggedUser->can(ACCESS_PERMISSION_ADMIN_LABEL) and ! $this->loggedUser->can(ACCESS_PERMISSION_MANAGER_LABEL)) {
                return [
                    'result' => false,
                    'message' => 'You have no access to "' . $this->actionLabel . '" this page ',
                    'returnCode' => HTTP_RESPONSE_UNAUTHORIZED
                ];
            }
        }

        if(in_array(UpdatePageRules::UPR_PAGE_IS_ALREADY_PUBLISHED, $rulesToValidate)) {
            if ($this->page->published) {
                return [
                    'result' => false,
                    'message' => 'Page is already published',
                    'returnCode' => HTTP_RESPONSE_BAD_REQUEST
                ];
            }
        }

        if(in_array(UpdatePageRules::UPR_PAGE_IS_ALREADY_UNPUBLISHED, $rulesToValidate)) {
            if (! $this->page->published) {
                return [
                    'result' => false,
                    'message' => 'Page is already unpublished',
                    'returnCode' => HTTP_RESPONSE_BAD_REQUEST
                ];
            }
        }


        if(in_array(UpdatePageRules::UPR_LOGGED_USER_IS_NOT_CREATOR_OF_PAGE, $rulesToValidate)) {
            // Only owner of the page can update the page
            if ($this->loggedUser->id !== $this->page->creator_id) {
                return [
                    'result' => false,
                    'message' => 'Only owner of the page can "' . $this->actionLabel . '" the page',
                    'returnCode' => HTTP_RESPONSE_UNAUTHORIZED
                ];
            }
        }

        if(in_array(UpdatePageRules::ONLY_UNPUBLISHED_PAGE_UPR_CAN_UPDATED, $rulesToValidate)) {
            // Owner of the page can update only unpublished page
            if ($this->page->published) {
                return [
                    'result' => false,
                    'message' => 'Owner of the page can "' . $this->actionLabel . '" only unpublished page',
                    'returnCode' => HTTP_RESPONSE_BAD_REQUEST
                ];
            }
        }
        return [
            'result' => true,
            'message' => '',
            'returnCode' => HTTP_RESPONSE_OK
        ];
    }

}
