<template>
    <admin-layout>

        <div class="card card-primary card-tabs">
            <div class="card-header p-2">
                <ul class="nav nav-tabs" id="custom-tabs-one-tab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="CMSItem-details-tab" data-toggle="pill"
                           href="#custom-tabs-one-home" role="tab"
                           aria-controls="custom-tabs-one-home" aria-selected="true">Details</a>
                    </li>
                    <li class="nav-item">
                        <!--                                            active-->
                        <a class="nav-link" id="CMSItem-tab" data-toggle="pill"
                           href="#CMSItem" role="tab"
                           aria-controls="CMSItem"
                           aria-selected="false">Image</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link " id="custom-tabs-one-text-tab"
                           data-toggle="pill"
                           href="#custom-tabs-one-text" role="tab"
                           aria-controls="custom-tabs-one-text"
                           aria-selected="false">Text</a>
                    </li>
                </ul>
            </div>

            <div class="card-body">
                <div class="tab-content h-100" id="custom-tabs-one-tabContent">
                    <!--                                                show active-->
                    <div class="tab-pane show active" id="custom-tabs-one-home" role="tabpanel"
                         aria-labelledby="CMSItem-details-tab">
                        <CMSItem-form-editor :is_insert="false" :CMSItem="CMSItem"></CMSItem-form-editor>
                    </div>

                    <div class="tab-pane fade" id="CMSItem" role="tabpanel"
                         aria-labelledby="CMSItem-tab">
                        <FileUploaderPreviewer
                            :imageUploader="CMSItemImageUploader"
                            :image_url="cmsItemImageUrl"
                            :image_info="cmsItemImageInfo"
                            :parent_component_key="'CMSItem_editor'"
                            :layout="'admin'"
                        ></FileUploaderPreviewer>
                    </div>

                    <div class="tab-pane fade" id="custom-tabs-one-text" role="tabpanel"
                         aria-labelledby="custom-tabs-one-text-tab">
                        <quill-editor
                            :options="editorOptions"
                            theme="snow"
                            v-model:content="textFormEditor.text"
                            text-change="textChangeText"
                            editorChange="editorChangeText"
                            contentType="html"
                        >{{ textFormEditor.text }}
                        </quill-editor>
                        <JetInputError :message="textFormEditor.errors.text"/>


                        <div class="admin_listing_modal_footer">
                            <button type="button"
                                    class="btn btn-success btn-sm text-uppercase right_btn_from_left_margin"
                                    @click="saveTextFormEditor">
                                <i :class="getHeaderIcon('save')"
                                   class="action_icon icon_right_text_margin"></i>Save
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- /.card -->

        </div>

    </admin-layout>
</template>

<script>
import AdminLayout from '@/Layouts/AdminLayout'


import {
    getHeaderIcon,
    pluralize,
    pluralize3,
    isEmpty,
    formatValue,
    momentDatetime,
    showFlashMessage,
    getDictionaryLabel,
    dateIntoDbFormat,
    showRTE,
    getFileSizeAsString
} from '@/commonFuncs'
import {settingsJsMomentDatetimeFormat, settingsAppColors} from '@/app.settings.js'

import CMSItemFormEditor from '@/Pages/Admin/CMSItems/Form'
import {onMounted, ref, watchEffect} from "vue";
import axios from "axios";
import ListingHeader from '@/components/ListingHeader.vue'
import FileUploaderPreviewer from '@/components/FileUploaderPreviewer.vue'


import {QuillEditor} from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css';

import {useForm} from "@inertiajs/inertia-vue3"
import JetInputError from '@/Jetstream/InputError.vue'

import {Inertia} from '@inertiajs/inertia'
import { defineComponent } from 'vue'

export default defineComponent({

    props: {
        CMSItem: {
            type: Object,
            required: true,
        }
    },

    name: 'adminCMSItemsEdit',
    components: {
        AdminLayout,
        ListingHeader,
        FileUploaderPreviewer,
        CMSItemFormEditor,
        QuillEditor,
        JetInputError
    },
    setup(props) {
        let CMSItem = props.CMSItem.data
        let cmsItemImageUrl = ref(CMSItem.cmsItemImageProps.url)
        let cmsItemImageInfo = ref(CMSItem.cmsItemImageProps.file_name + ', ' + getFileSizeAsString(CMSItem.cmsItemImageProps.size) + ', ' + CMSItem.cmsItemImageProps.width + '*' + CMSItem.cmsItemImageProps.height)
        let CMSItemImageUploader = ref(useForm({
            image : '',
            cmsItemId : props.CMSItem.data.id,
            imageFilename : '',
        }))

        let textFormEditor = ref(useForm({
            id: props.CMSItem.data.id,
            text: props.CMSItem.data.text,
        }))

        let editorOptions = ref(
            [['better-table', 'bold', 'italic'], ['link', 'image']]
        )

        function saveTextFormEditor() {
            textFormEditor.value.put(route('admin.cms_items.text_save', textFormEditor.value.id), {
                preserveScroll: false,
                onSuccess: (resp) => {
                    Swal.fire(
                        'Saved!',
                        'Text successfully saved !',
                        'success'
                    )
                },
                onError: (e) => {
                    console.log(e)
                    showRTE(e)
                }
            })
        }

        function fetchCMSItemImage(CMSItemImageFile) {
            fetch(CMSItemImageFile.blob).then(function (response) {
                if (response.ok) {
                    return response.blob().then(function (imageBlob) {
                        CMSItemImageUploader.value.image = imageBlob
                        CMSItemImageUploader.value.imageFilename = CMSItemImageFile.name

                        CMSItemImageUploader.value.post(route('admin.cms_items.uploadImage'), {
                            preserveScroll: true,
                            onSuccess: (resp) => {
                                Toast.fire({
                                    icon: 'success',
                                    title: 'You have uploaded image successfully'
                                })
                                window.emitter.emit('imageBlobFetchedEvent', {
                                    parent_component_key: 'CMSItem_editor',
                                    resp: resp,
                                })
                                Inertia.visit(route('admin.cms_items.edit', CMSItemImageUploader.value.cmsItemId) )
                            },
                            onError: (e) => {
                                console.log(e)
                                showRTE(e)
                            }
                        })
                    })
                } else {
                    return response.json().then(function (jsonError) {
                        console.error(jsonError)
                        showRTE(jsonError)
                    })
                }
            }).catch(function (e) {
                console.error(e)
                console.log('There has been a problem with your fetch operation: ', e.message)
            }) // fetch(CMSItemImageFile.blob).then(function (response) {

        }

        function adminCMSItemEditOnMounted() {
            showFlashMessage()
            window.emitter.on('FileUploaderPreviewerUploadImageEvent', params => {
                if (params.parent_component_key === 'CMSItem_editor') {
                    fetchCMSItemImage(params.uploadedImageFile)
                }
            })
        }
        onMounted(adminCMSItemEditOnMounted)

        return { // setup return
            // Page state
            CMSItem,
            cmsItemImageUrl,
            cmsItemImageInfo,
            textFormEditor,
            saveTextFormEditor,
            editorOptions,

            // Listing filtering
            CMSItemImageUploader,

            // Common methods
            getHeaderIcon,
            pluralize,
            pluralize3,
            isEmpty,
            momentDatetime,
            showFlashMessage,
            getDictionaryLabel,
            dateIntoDbFormat,
            formatValue,
            showRTE,
            getFileSizeAsString
        }
    }, // setup() {

})
</script>
