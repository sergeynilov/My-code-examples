<?php

namespace App\Library\Services;

use App\Library\Services\MlImportUsersInterface;
use App\Library\Services\MlCommon;

class MlImportUsers extends MlCommon implements MlImportUsersInterface
{
    /**
     *Run import for all users/subscribers in app group
     *
     * @param array $authorData
     *
     * @return \stdClass
     */
    public function runImport(array $subscribers): array
    {
        try {
            $authorsGroupId = $this->checkAuthorsGroupExists($this->authorsGroupTitle);
            if ( ! $authorsGroupId) {
                $newAuthorsGroup = $this->createGroup($this->authorsGroupTitle);
                $authorsGroupId = $newAuthorsGroup->id;
            }
            foreach ($subscribers as $key => $subscriber) {
                $subscribers[$key]['fields']['company']    = 'fake_company';
                $subscribers[$key]['fields']['first_name'] = $subscriber['first_name'];
                $subscribers[$key]['fields']['last_name']  = $subscriber['last_name'];
                $subscribers[$key]['fields']['phone']      = $subscriber['phone'];
            }
            $options = [
                'resubscribe'    => false,
                'autoresponders' => false // send autoresponders for successfully imported subscribers
            ];

            $addedSubscribers = $this->getGroupsApi()->importSubscribers($authorsGroupId, $subscribers,
                $options); // returns imported subscribers divided into groups by import status

            if(!empty($addedSubscribers->imported) and is_array($addedSubscribers->imported)) {
                foreach( $addedSubscribers->imported as $importedSubscriber ) {
                    $authorModel = Author::getByEmail($importedSubscriber->email)->first();
                    if(!empty($importedSubscriber->id) and !empty($authorModel) and $authorModel->subscriber_id !== $importedSubscriber->id ) {
                        $authorModel->subscriber_id = $importedSubscriber->id;
                        $authorModel->save();
                    }
                }
            }

        } catch (\Exception $e) {
            return sendErrorResponse($e->getMessage(), HTTP_RESPONSE_INTERNAL_SERVER_ERROR);
        }
        $retArray = [
            'importedCount'  => ! empty($addedSubscribers->imported) ? count($addedSubscribers->imported) : 0,
            'updatedCount'   => ! empty($addedSubscribers->updated) ? count($addedSubscribers->updated) : 0,
            'unchangedCount' => ! empty($addedSubscribers->unchanged) ? count($addedSubscribers->unchanged) : 0,
        ];

        return $retArray;
    }

}
