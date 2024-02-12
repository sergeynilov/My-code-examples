<template>
    <div>
        <div class="card-header">
            <h3 class="card-title admin_color">
                <i :class="getHeaderIcon('settings')" class="action_icon icon_right_text_margin"></i>
                Edit Settings
            </h3>
        </div> <!-- card-header -->

        <form @submit.prevent="saveSettings">

            <div class="card-body p-0">

                <div class="block_2columns_md p-2"> <!-- site_name -->
                    <div class="horiz_divider_left_13">
                        <jet-label for="site_name" value="Site Name:" class="admin_editable_label"/>
                    </div>
                    <div class="horiz_divider_right_23">
                        <jet-input id="site_name" type="text" class="form-control admin_editable_input"
                                   v-model="formEditor.site_name" placeholder="Site descriptive name"
                                   :class="{ 'is-invalid' : formEditor.errors && formEditor.errors.site_name }"
                                   autocomplete="off"/>
                        <div class="invalid-feedback mb-3" v-if="formEditor.errors"
                             :class="{ 'd-block' : formEditor.errors && formEditor.errors.site_name}">
                            {{ formEditor.errors.site_name }}
                        </div>
                    </div>
                </div>

                <div class="block_2columns_md p-2"> <!-- site_heading -->
                    <div class="horiz_divider_left_13">
                        <jet-label for="site_heading" value="Site heading:" class="admin_editable_label"/>
                    </div>
                    <div class="horiz_divider_right_23">
                        <jet-input id="site_heading" type="text" class="form-control admin_editable_input"
                                   v-model="formEditor.site_heading" placeholder="Site descriptive heading"
                                   :class="{ 'is-invalid' : formEditor.errors && formEditor.errors.site_heading }"
                                   autocomplete="off"/>
                        <div class="invalid-feedback mb-3" v-if="formEditor.errors"
                             :class="{ 'd-block' : formEditor.errors && formEditor.errors.site_heading}">
                            {{ formEditor.errors.site_heading }}
                        </div>
                    </div>
                </div>

                <div class="block_2columns_md p-2"> <!-- copyright_text -->
                    <div class="horiz_divider_left_13">
                        <jet-label for="copyright_text" value="Copyright text:" class="admin_editable_label"/>
                    </div>
                    <div class="horiz_divider_right_23">
                        <jet-input id="copyright_text" type="text" class="form-control admin_editable_input"
                                   v-model="formEditor.copyright_text" placeholder="Site copyright text"
                                   :class="{ 'is-invalid' : formEditor.errors && formEditor.errors.copyright_text }"
                                   autocomplete="off"/>

                        <div class="invalid-feedback mb-3" v-if="formEditor.errors"
                             :class="{ 'd-block' : formEditor.errors && formEditor.errors.copyright_text}">
                            {{ formEditor.errors.copyright_text }}
                        </div>
                    </div>
                </div>

                <div class="block_2columns_md p-2"> <!-- base_currency -->
                    <div class="horiz_divider_left_13">
                        <jet-label for="base_currency" value="Base currency:" class="admin_editable_label"/>
                    </div>
                    <div class="horiz_divider_right_23">
                        <Multiselect
                            v-model="formEditor.base_currency"
                            id="base_currency"
                            mode="single"
                            :options="currenciesSelectionArray"
                            valueProp="char_code"
                            :searchable="true"
                            :max="-1"
                            ref="multiselect"
                            label="name"
                            track-by="name"
                            placeholder="Select currency"
                            class="admin_multiselect_lte admin_editable_input"
                        />
                        <div class="invalid-feedback mb-3" v-if="formEditor.errors"
                             :class="{ 'd-block' : formEditor.errors && formEditor.errors.base_currency}">
                            {{ formEditor.errors.base_currency }}
                        </div>
                    </div>
                </div>

                <div class="block_2columns_md p-2"> <!-- backend_items_per_page -->
                    <div class="horiz_divider_left_13">
                        <jet-label for="backend_items_per_page" value="Backend items per page:" class="admin_editable_label"/>
                    </div>
                    <div class="horiz_divider_right_23">
                        <jet-input id="backend_items_per_page" type="text" class="form-control admin_editable_input"
                                   v-model="formEditor.backend_items_per_page" placeholder="Valid integer value"
                                   :class="{ 'is-invalid' : formEditor.errors && formEditor.errors.backend_items_per_page }"
                                   autocomplete="off"/>
                        <div class="invalid-feedback mb-3" v-if="formEditor.errors"
                             :class="{ 'd-block' : formEditor.errors && formEditor.errors.backend_items_per_page}">
                            {{ formEditor.errors.backend_items_per_page }}
                        </div>
                    </div>
                </div>

                <div class="block_2columns_md p-2"> <!-- rate_decimal_numbers -->
                    <div class="horiz_divider_left_13">
                        <jet-label for="rate_decimal_numbers" value="Rate decimal numbers:" class="admin_editable_label"/>
                    </div>
                    <div class="horiz_divider_right_23">
                        <jet-input id="rate_decimal_numbers" type="text" class="form-control admin_editable_input"
                                   v-model="formEditor.rate_decimal_numbers" placeholder="Valid integer value"
                                   :class="{ 'is-invalid' : formEditor.errors && formEditor.errors.rate_decimal_numbers }"
                                   autocomplete="off"/>
                        <div class="invalid-feedback mb-3" v-if="formEditor.errors"
                             :class="{ 'd-block' : formEditor.errors && formEditor.errors.rate_decimal_numbers}">
                            {{ formEditor.errors.rate_decimal_numbers }}
                        </div>
                    </div>
                </div>

                <div class="block_2columns_md p-2"> <!-- items_per_page -->
                    <div class="horiz_divider_left_13">
                        <jet-label for="items_per_page" value="Items per page:" class="admin_editable_label"/>
                    </div>
                    <div class="horiz_divider_right_23 ">
                        <jet-input id="items_per_page" type="text" class="form-control admin_editable_input"
                                   v-model="formEditor.items_per_page" placeholder="Valid integer value"
                                   :class="{ 'is-invalid' : formEditor.errors && formEditor.errors.items_per_page }"
                                   autocomplete="off"/>
                        <div class="invalid-feedback mb-3" v-if="formEditor.errors"
                             :class="{ 'd-block' : formEditor.errors && formEditor.errors.items_per_page}">
                            {{ formEditor.errors.items_per_page }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer clearfix flex mb-2 d-flex flex-nowrap">
                <jet-button button_type="admin_cancel" @click="cancelSettingsEdit" :disabled="formEditor.processing">
                    <i :class="getHeaderIcon('cancel')" class="action_icon icon_right_text_margin"></i>Cancel
                </jet-button>

                <jet-button type="submit"  button_type="admin_save" :disabled="formEditor.processing">
                    <i :class="getHeaderIcon('save')" class="action_icon icon_right_text_margin"></i>Update
                </jet-button>
                <div v-show="formEditor.processing" class="form_processing"></div>
                <div v-show="formEditor.isDirty">
                    <p class="text-md text-warning p-0 m-0 pl-4 mt-1">
                        <i :class="getHeaderIcon('info')" class="icon_right_text_margin"></i>
                        You have unsaved data
                    </p>
                </div>
            </div>

        </form>
    </div>
</template>

<script>
import AdminLayout from '@/Layouts/AdminLayout'
import Multiselect from '@vueform/multiselect'

import {
    getHeaderIcon,
    momentDatetime,
    pluralize,
    pluralize3,
    getErrorMessage,
    showFlashMessage,
    getDictionaryLabel
} from '@/commonFuncs'
import {settingsJsMomentDatetimeFormat} from '@/app.settings.js'
import {ref, onMounted, computed} from "vue";
import {useForm} from '@inertiajs/inertia-vue3';
import {Inertia} from '@inertiajs/inertia'
import JetApplicationLogo from '@/Jetstream/ApplicationLogo.vue'
import JetButton from '@/Jetstream/Button.vue'
import JetInput from '@/Jetstream/Input.vue'
import JetLabel from '@/Jetstream/Label.vue'

export default {
    props: ['settingsData', 'currenciesSelectionArray'],

    name: 'SettingsForm',
    components: {
        AdminLayout,
        Multiselect,
        JetApplicationLogo,
        JetButton,
        JetInput,
        JetLabel,
    },
    setup(props) {
        let formEditor = ref(useForm({
            site_name: props.settingsData.site_name,
            site_heading: props.settingsData.site_heading,
            copyright_text: props.settingsData.copyright_text,
            base_currency: props.settingsData.base_currency,
            backend_items_per_page: props.settingsData.backend_items_per_page,
            rate_decimal_numbers: props.settingsData.rate_decimal_numbers,
            items_per_page: props.settingsData.items_per_page,
        }))

        function cancelSettingsEdit() {
            Inertia.visit(route('dashboard.index'), {method: 'get'});
        }


        function saveSettings() {
            formEditor.value.put(route('admin.settings.update'), {
                preserveScroll: true,
                onSuccess: (resp) => {
                },
                onError: (e) => {
                    Toast.fire({
                        icon: 'error',
                        title: 'Updating setting error!'
                    })
                }
            })
        } // saveSettings() {

        function adminSettingsFormOnMounted() {
        }
        onMounted(adminSettingsFormOnMounted)

        return { // setup return
            // Listing Page state
            formEditor,
            cancelSettingsEdit,
            saveSettings,

            // Common methods
            getHeaderIcon,
            pluralize,
            pluralize3,
            momentDatetime,
            getErrorMessage,
            showFlashMessage,
            getDictionaryLabel

        }
    }, // setup() {

}
</script>
