<?php

namespace App\Library\Services;

use App\Library\Services\MlEmailingServiceInterface;
use App\Library\Services\MlCommon;

class MlEmailingService extends MlCommon implements MlEmailingServiceInterface  {
    /**
     * Subscribe author in MailerLite in $authorsGroupTitle group
     *
     * @param array $authorData (data of user)
     *
     * @return \stdClass | bool
     */
    public function subscribeAuthorIntoAppGroup(Array $authorData): \stdClass | bool
    {
        try {
            $authorsGroupId = $this->checkAuthorsGroupExists($this->authorsGroupTitle);
            if(!$authorsGroupId) { // if Authors Group does not exists - create it
                $newAuthorsGroup = $this->createGroup($this->authorsGroupTitle);
                $authorsGroupId = $newAuthorsGroup->id;
            }

            $authorIsInGroupId = $this->checkIsAuthorInGroup($authorsGroupId, $authorData);
            if(!$authorIsInGroupId) { // Need to assign Author to existing group
                $assignedAuthor = $this->assignAuthorToGroup($authorsGroupId, $authorData);
                $assignedAuthor->subscriber_id = $assignedAuthor->id;
                return $assignedAuthor;
            }
            $authorData['subscriber_id'] = $authorIsInGroupId;
            return (object)$authorData;
        } catch (\Exception $e) {
            return sendErrorResponse($e->getMessage(), HTTP_RESPONSE_INTERNAL_SERVER_ERROR);
        }
        return false;
    }

    /**
     * Remove author from MailerLite in $authorsGroupTitle group
     *
     * @param array $authorData (data of user)
     *
     * @return bool
     */
    public function removeAuthorFromAppGroup(Array $authorData): bool
    {
        try {
            $authorsGroupId = $this->checkAuthorsGroupExists($this->authorsGroupTitle);
            if($authorsGroupId) { // We found author's group
                $subscriberId = $authorData['subscriber_id'];
                $removeSubscriberRet = $this->groupsApi->removeSubscriber($authorsGroupId, $subscriberId); // returns empty response
            }
            return true;
        } catch (\Exception $e) {
            return sendErrorResponse($e->getMessage(), HTTP_RESPONSE_INTERNAL_SERVER_ERROR);
        }
        return false;
    }

}
