<template>

<!--    imageUploader::{{ imageUploader }}<br>-->
<!--    class="btn btn-outline-primary btn-block"-->
    <file-upload
        ref="upload"
        v-model="imageFiles"
        post-action="/post.method"
        put-action="/put.method"
        @input-file="inputFile"
        @input-filter="inputFilter"
        class="btn btn-primary btn-block"
        v-show="imageFiles.length === 0"
    >
        <i :class="getHeaderIcon('upload')" class="action_icon icon_right_text_margin"></i>
        Upload file
    </file-upload>

    <div v-show="image_url && imageFiles.length === 0" class="p-2">
        <img :src="image_url" :class="layout+'_img_preview_wrapper'">
        <p class="text-sm text-info p-2" v-show="image_info">
            <i :class="getHeaderIcon('info')"></i>
            {{ image_info }}
        </p>
    </div>

    <div v-show="imageFiles.length > 0" class="p-2">
        <table class="m-2 p-0">
            <tr v-for="nextFile in imageFiles" :key="nextFile.name">
                <td>
                    <img :src="nextFile.blob" :class="layout+'_img_preview_wrapper'" id="uploaded_image_file"/>

                    <div class="invalid-feedback mb-3" v-if="imageUploader.errors"
                         :class="{ 'd-block' : imageUploader.errors && imageUploader.errors.image}">
                        {{ imageUploader.errors.image }}
                    </div>

                    <div :class="layout + '_content_text row_content_centered p-3'">
                        <div class="p-2">
                            Name : <strong>{{ nextFile.name }}</strong>
                        </div>
                        <div class="p-2">
                            Size : <strong>{{ getFileSizeAsString(nextFile.size) }}</strong>
                        </div>

                        <div v-show="uploaded_image_width" class="p-2">
                            Width : <strong>{{ uploaded_image_width }}px</strong>
                        </div>

                        <div v-show="uploaded_image_height" class="p-2">
                            Height : <strong>{{ uploaded_image_height }}px</strong>
                        </div>

                    </div>
                </td>
            </tr>
        </table>
<!--        -1 imageFiles.length::{{ imageFiles.length }}<br>-->
        <p class="text-sm text-info p-2" v-if="show_bottom_info_text && imageFiles.length > 0">
            <i :class="getHeaderIcon('info')" class="icon_right_text_margin"></i>
            To upload image click "Upload" button<br>
            Click "Reset" button if you want to select other image.
        </p>
    </div>

    <div class="row_content_right_aligned  d-flex flex-nowrap" v-if="imageFiles.length > 0">
        <jet-button :button_type="layout+'_cancel'" @click="cancelImageUpload">
            <i :class="getHeaderIcon('cancel')" class="action_icon icon_right_text_margin"></i>Reset
        </jet-button>
        <jet-button :button_type="layout+'_save'"  @click.prevent="uploadImage()">
            <i :class="getHeaderIcon('save')" class="action_icon icon_right_text_margin"></i>Upload
        </jet-button>
        <div v-show="imageUploader.processing" class="form_processing"></div>
    </div>

    <jet-section-border></jet-section-border>
    <jet-section-border></jet-section-border>

</template>


<script>
import {ref, computed, onMounted, watchEffect} from 'vue'

import {
    getHeaderIcon,
    pluralize,
    pluralize3,
    isEmpty,
    formatValue,
    momentDatetime,
    getErrorMessage,
    showFlashMessage,
    getDictionaryLabel,
    dateIntoDbFormat,
    getFileSizeAsString,
} from '@/commonFuncs'

import {} from '@/app.settings.js'

// SectionBorder.vue
import JetSectionBorder from '@/Jetstream/SectionBorder.vue'
import JetButton from '@/Jetstream/Button.vue'

export default {
    name: 'FileUploaderPreviewer',
    props: {
        imageUploader: {
            type: Object,
            required: true,
        },
        image_url: {
            type: String,
            required: false,
        },
        image_info: {
            type: String,
            required: true,
        },
        parent_component_key: {
            type: String,
            required: true,
        },
        layout: {
            type: String,
            required: true,
        },
        show_bottom_info_text: {
            type: Boolean,
            required: false,
            default: false,
        }
    },

    components: {
        JetSectionBorder,
        JetButton
    },
    setup(props, attrs) {
        let imageUploader = ref(props.imageUploader)
        let image_url = ref(props.image_url)
        let image_info = ref(props.image_info)
        let parent_component_key = ref(props.parent_component_key)
        let layout = ref(props.layout)

        let imageFiles = ref([])
        let uploaded_image_width = ref(null)
        let uploaded_image_height = ref(null)
        let uploadedImageFile = null

        watchEffect(() => {
            if (imageFiles.value) {
                if (typeof imageFiles.value[0] === 'undefined') return
                var image = new Image()
                image.src = imageFiles.value[0].blob
                image.onload = function () {
                    window.emitter.emit('imageUploadedEvent', {width: this.width, height: this.height})
                }
            }
        })

        // let files=ref([])
        /**
         * Has changed
         * @param  Object|undefined   newFile   Read only
         * @param  Object|undefined   oldFile   Read only
         * @return undefined
         */
        function inputFile(newFile, oldFile) {
            if (newFile) {
                // Get response data
                uploadedImageFile = newFile
                if (newFile.xhr) {
                    //  Get the response status code
                    console.log('status', newFile.xhr.status)
                }
            }
        }

        /**
         * Pretreatment
         * @param  Object|undefined   newFile   Read and write
         * @param  Object|undefined   oldFile   Read only
         * @param  Function           prevent   Prevent changing
         * @return undefined
         */
        function inputFilter(newFile, oldFile, prevent) {
            if (newFile && !oldFile) {
                // Filter non-image file
                if (!/\.(jpeg|jpe|jpg|gif|png|webp)$/i.test(newFile.name)) {
                    return prevent()
                }
            }
            // Create a blob field
            newFile.blob = ''
            let URL = window.URL || window.webkitURL
            if (URL && URL.createObjectURL) {
                newFile.blob = URL.createObjectURL(newFile.file)
            }
        }

        function uploadImage() {
            window.emitter.emit('FileUploaderPreviewerUploadImageEvent', {
                parent_component_key: parent_component_key.value,
                uploadedImageFile: uploadedImageFile,
            })
        }

        function cancelImageUpload() {
            imageFiles.value = []
            uploaded_image_width.value = null
            uploaded_image_height.value = null
            imageUploader.value.image = null
            imageUploader.value.image_filename = ''
            imageUploader.value.errors = {}
        }


        function fileUploaderPreviewerOnMounted() {
            window.emitter.on('imageUploadedEvent', params => {
                uploaded_image_width.value = params.width
                uploaded_image_height.value = params.height
            })
            window.emitter.on('imageBlobFetchedEvent', params => {
                if (params.parent_component_key === parent_component_key.value) {
                    cancelImageUpload()
                }
            })

        }

        onMounted(fileUploaderPreviewerOnMounted)

        return { // setup return
            inputFilter,
            inputFile,
            imageFiles,
            uploaded_image_width,
            uploaded_image_height,
            cancelImageUpload,
            uploadedImageFile,
            uploadImage,
            imageUploader,
            image_url,
            image_info,
            parent_component_key,
            layout,


            // Common methods
            getHeaderIcon,
            pluralize,
            pluralize3,
            isEmpty,
            momentDatetime,
            getErrorMessage,
            showFlashMessage,
            getDictionaryLabel,
            dateIntoDbFormat,
            formatValue,
            getFileSizeAsString
            // app_version

        }
    }, // setup() {

}
</script>
