<?php

namespace App\Library\Rules;

use App\Enums\UploadImageRulesParameter;
use App\Enums\UploadImageRules;

/**
 * Validation rules for image uploading request
 */
class RulesImageUploading
{
    protected int $uploadImageRules;
    protected int $maxSizeInBytes;
    protected int $imageMaxWidth;

    /**
     *
     * @param UploadImageRules $uploadImageRules - For which model/field image is uploaded
     *
     */
    public function __construct(int $uploadImageRules)
    {
        $this->mimesStr         = '';
        $this->uploadImageRules = $uploadImageRules;
        if ($this->uploadImageRules === UploadImageRules::UIR_PAGE_COVER_IMAGE) {
            $uploadedFileMaxMib  = (float)config('app.page_cover_image_file_max_mib', 0); // in Mib
            $fileExtensions      = config('app.page_cover_image_extensions', []);
            $fileDimensionLimits = config('app.page_cover_dimension_limits');
        }
        if ($this->uploadImageRules === UploadImageRules::UIR_DOCUMENT) {
            $uploadedFileMaxMib = (float)config('app.page_document_file_max_mib', 0); // in Mib
            $fileExtensions     = config('app.page_document_extensions', []);
        }
        if ($this->uploadImageRules === UploadImageRules::UIR_AUTHOR_AVATAR) {
            $uploadedFileMaxMib  = (float)config('app.author_avatar_file_max_mib', 0); // in Mib
            $fileExtensions      = config('app.author_avatar_extensions', []);
            $fileDimensionLimits = config('app.author_avatar_dimension_limits');
        }
        $this->maxSizeInBytes = 1024 * $uploadedFileMaxMib;
        foreach ($fileExtensions as $nextUploadedDocumentsExtension) {
            $this->mimesStr .= $nextUploadedDocumentsExtension . ',';
        }
        $this->mimesStr      = trimRightSubString($this->mimesStr, ',');
        $this->imageMaxWidth = $fileDimensionLimits['max_width'] ?? 0;
    }

    /**
     *
     * @param UploadImageRulesParameter $parameter - one of possisble image upload validation
     *
     * @return requested in $parameter value from config/app
     */
    public function getRuleParameterValue(int $parameter)
    {
        if ($parameter === UploadImageRulesParameter::UIRPV_REQUIRED) {
            return 'required';
        }
        if ($parameter === UploadImageRulesParameter::UIRPV_ACCEPTABLE_FILE_MIMES) {
            return $this->mimesStr;
        }
        if ($parameter === UploadImageRulesParameter::UIRPV_MAX_SIZE_IN_BYTES) {
            return $this->maxSizeInBytes;
        }
        if ($parameter === UploadImageRulesParameter::UIRPV_DIMENSIONS_MAX_WIDTH) {
            return $this->imageMaxWidth;
        }
    }

    /**
     * @return array of rules for image uploading from config/app
     */
    public function getRules(): array
    {
        $rules = [
            'nullable',
        ];
        if ( ! empty($this->maxSizeInBytes)) {
            $rules[] = 'max:' . $this->maxSizeInBytes;
        }
        if ( ! empty($this->imageMaxWidth)) {
            $rules[] = 'dimensions:max_width=' . $this->imageMaxWidth;
        }
        if ( ! empty($this->mimesStr)) {
            $rules[] = 'mimes:' . $this->mimesStr;
        }

        return $rules;
    }

}
