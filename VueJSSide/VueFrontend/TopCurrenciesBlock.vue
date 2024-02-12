<template>

    <section class="pt-4">

        <div class="container">
            <div class="row justify-content-center mb-6">
                <div class="col-lg-6 col-xxl-5 text-center mx-auto mb-7" v-if="baseCurrency.char_code">
                    <h5 class="fw-bold fs-3 fs-lg-5 fs-xxl-7 lh-sm mb-3">
                        {{ sanitizeHtml(mainPageCurrenciesListBlockHeaderTitle) }} for {{ baseCurrency.char_code }}/{{ baseCurrency.name }}
                    </h5>
                    <p class="mb-0">{{ sanitizeHtml(mainPageCurrenciesListBlockHeaderText) }}</p>
                </div>

                <div class="col-xxl-11">
                    <div class="row flex-center gx-3">
                        <div class="col-sm-6 col-lg-4 mb-4 text-center shadow p-2 "
                             style="height:400px !important;"
                             v-for="(nextActiveCurrencyRow, index) in activeCurrencyRows" :key="index">
                            <div style="height: 320px !important; border: 0px dotted maroon !important;">
                                <img class="currency_big_image" :src="nextActiveCurrencyRow.currencyImageProps.url"/>

                                <div class="flex-center p-0 m-0 "
                                     :style="'border: 0px dotted blue !important;    color:'+nextActiveCurrencyRow.color +'; background-color:'+nextActiveCurrencyRow.bgcolor">

                                    <div class="p-1 m-0" style="height:40px !important;">
                                        <div class="p-0 m-0 float-start">
                                            <button class="btn btn-sm btn-info p-0 px-1  m-0 "
                                                    @click.prevent="showCurrencyDetailsModal(nextActiveCurrencyRow.id, nextActiveCurrencyRow.name)">
                                                <i :class="getHeaderIcon('info')" class="i"
                                                   :title="'Open description about '+nextActiveCurrencyRow.name+' currency'"></i>
                                            </button>
                                        </div>
                                        <div class=" p-1 m-0 text-end">
                                            <button class="btn btn-sm btn-info p-0 px-1  m-0 "
                                                    @click.prevent="showCurrencyHistoryModal(nextActiveCurrencyRow.id, nextActiveCurrencyRow.name, nextActiveCurrencyRow.char_code)">
                                                <i :class="getHeaderIcon('currencies_history')" class="i"
                                                   :title="'Open currency history : '+nextActiveCurrencyRow.currency_histories_count + ' item(s)'"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="p-1 m-0" style="height:40px !important;">
                                        <div class="p-0 m-0 float-start">
<!--                                            {{ nextActiveCurrencyRow.id }}::-->
                                            {{ nextActiveCurrencyRow.char_code }}/{{ nextActiveCurrencyRow.name }}
                                        </div>
                                        <div class=" p-1 m-0 text-end">
                                            <span v-if="nextActiveCurrencyRow"
                                             class="align-content-end text-end">
                                            {{formatValue(nextActiveCurrencyRow.latest_currency_history_value, rateDecimalNumbers) }}
                                        </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    <h5 class="mx-2 text-end" v-if="showOnlyTopCurrencies">
                        <inertia-link :href="route('frontend.all_currencies')">
                            All currencies
                        </inertia-link>
                    </h5>
                </div>
            </div>
        </div>
        <!-- end of .container-->

    </section>
    <!-- <section> close ============================-->

    <vue-final-modal
        v-model="isVisibleCurrencyHistoryModal"
        classes="frontend_modal_container"
        content-class="frontend_modal_content"
    >
        <div class="row flex-center frontend_modal_header">
            <div class="col-md-6 order-0">
                <div class="p-1 m-0 text-start">

                    <h5 v-if="currencyHistoryCharCode && currencyHistoryName">
                        <i :class="getHeaderIcon('currencies_history')" class="m-1 p-0 "
                           style="margin-bottom: -2px !important; "></i> {{ currencyHistoryCharCode }} /
                        {{ currencyHistoryName }} currency history
                    </h5>

                </div>
            </div>
            <div class="col-md-6 order-1">
                <p class=" p-1 m-0 text-end">
                    <button class="frontend_modal_close p-0" @click="hideCurrencyHistoryModal">
                        x
                    </button>
                </p>
            </div>
        </div>

        <div class="content frontend_modal_content_editor_form frontend_modal_content_editor_form">
            <div class="p-0" v-if="!currencyHistoryRows">
                <p class="text-sm text-warning p-2 pl-4">
                    <i :class="getHeaderIcon('info')" class="icon_right_text_margin"></i>
                    No data found. Try to change filter options.
                </p>
            </div>

            <div class="table-responsive p-0" v-if="currencyHistoryRows.length > 0"
                 style="border:0px dotted red !important;  overflow-y: scroll; max-height: 600px;">
                <table class="table table-striped table-hover text-nowrap">
                    <thead>
                    <tr class="frontend_listing_header">
                        <th class="text-capitalize">Day</th>
                        <th class="text-capitalize text-right">Rate</th>
                    </tr>
                    </thead>

                    <tbody>
                    <tr v-for="(currency, index) in currencyHistoryRows" :key="index" class="frontend_listing_cell">
                        <td>{{ currency.day_label }}</td>
                        <td class="text-right">{{ formatValue(currency.value, rateDecimalNumbers) }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <paginate
                v-show="currenciesHistoryPagesCount > 1"
                v-model="currencyHistoryCurrentPage"
                :page-count="currenciesHistoryPagesCount"
                :click-handler="paginateClick"
                :first-last-button="false"
                :page-range="2"
                :margin-pages="3"
                :prev-text="'<'"
                :next-text="'>'"
                :container-class="'frontend_pagination'"
            >
            </paginate>

            <div class="frontend_modal_footer">
                <jet-button button_type="frontend_cancel" @click="hideCurrencyHistoryModal" >
                    <i :class="getHeaderIcon('cancel')" class="action_icon icon_right_text_margin"></i>Cancel
                </jet-button>
            </div>
        </div>
    </vue-final-modal> <!-- isVisibleCurrencyHistoryModal -->


    <vue-final-modal
        v-model="showCurrencyDescriptionModal"
        classes="frontend_modal_container"
        content-class="frontend_modal_content"
    >
        <div class="row flex-center frontend_modal_header">
            <div class="col-md-10 order-0">
                <div class="p-1 m-0 text-start">
                    <h5 class="pt-2" v-if="currencyDetails">
                        <i :class="getHeaderIcon('info')" class="icon_right_text_margin"
                           style="margin-bottom: -2px !important; "></i> {{ currencyDetails.char_code }} /
                        {{ currencyDetails.name }} description
                    </h5>
                </div>
            </div>
            <div class="col-md-6 order-1">
                <p class=" p-1 m-0 text-end">
                    <button class="frontend_modal_close p-0" @click="hideCurrencyDescriptionModal">
                        x
                    </button>
                </p>
            </div>
        </div>

        <div class="content frontend_modal_content_editor_form">
            <div style="height: 620px;   overflow-y: scroll; overflow-x: auto;">
                <div class="block_2columns_md p-2">
                    <div>
                        <div class="float-start" v-if="currencyDetailsImage.currencyImageProps">
                            <img class="currency_image_left_aligned" :src="currencyDetailsImage.currencyImageProps.url">
                        </div>
                        <div>
                            <div v-html="sanitizeHtml(currencyDetails.description)" class="pre-formatted"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="frontend_modal_footer">
            <jet-button button_type="frontend_cancel" @click="hideCurrencyDescriptionModal" >
                <i :class="getHeaderIcon('cancel')" class="action_icon icon_right_text_margin"></i>Cancel
            </jet-button>

        </div>
    </vue-final-modal> <!-- showCurrencyDescriptionModal -->

</template>


<script>
import FrontendLayout from '@/Layouts/FrontendLayout'
import axios from 'axios'
import Multiselect from '@vueform/multiselect'
import {$vfm, VueFinalModal, ModalsContainer} from 'vue-final-modal'

import {
    getHeaderIcon,
    pluralize,
    pluralize3,
    momentDatetime,
    getErrorMessage,
    getDictionaryLabel,
    formatValue
} from '@/commonFuncs'
import {ref, computed, onMounted} from 'vue'

import {
    settingsJsMomentDatetimeFormat, settingsCurrencyActiveLabels, settingsCurrencyIsTopLabels
} from '@/app.settings.js'
import * as sanitizeHtml from 'sanitize-html';
import JetButton from '@/Jetstream/Button.vue'

export default {
    name: 'TopCurrenciesBlockPage',
    props: {
        showOnlyTopCurrencies: {
            type: Boolean,
            required: false,
            default: false,
        }},

    components: {
        FrontendLayout,
        JetButton,
        Multiselect,
        VueFinalModal,
        ModalsContainer
    },
    setup(props) {
        let showOnlyTopCurrencies = ref(props.showOnlyTopCurrencies)
        let isVisibleCurrencyHistoryModal = ref(false)
        let currencyHistoryRows = ref([])
        let currenciesHistoryPagesCount = ref(0)
        let showCurrencyDescriptionModal = ref(false)
        let currencyDetails = ref({})
        let currencyDetailsImage = ref({})
        let rateDecimalNumbers = ref(2)
        let baseCurrency = ref({})
        let activeCurrencyRows = ref([])
        let currencyHistoryId = ref(null)
        let currencyHistoryCharCode = ref('')
        let currencyHistoryName = ref('')

        let currencyHistoryCurrentPage = ref(1)
        let mainPageCurrenciesListBlockHeaderTitle = ref('')
        let mainPageCurrenciesListBlockHeaderText = ref('')
        let mainPageCurrenciesListBlockHeaderImageUrl = ref('')

        function showCurrencyDetailsModal(currencyId) {
            axios.get(route('frontend.get_currency_details', currencyId))
                .then(({data}) => {
                    // console.log('get_currency_details data::')
                    // console.log(data)
                    showCurrencyDescriptionModal.value = true
                    currencyDetails.value = data.data
                    currencyDetailsImage.value = data.data
                })
                .catch(e => {
                    console.error(e)
                })
        }

        function hideCurrencyDescriptionModal() {
            showCurrencyDescriptionModal.value = false
        }

        function showCurrencyHistoryModal(currencyId, currencyName, currencyCharCode) {
            currencyHistoryCurrentPage.value =  1
            currencyHistoryId.value= currencyId
            currencyHistoryCharCode.value= currencyCharCode
            currencyHistoryName.value= currencyName
            loadCurrencyHistory()
        }

        function loadCurrencyHistory() {
            axios.get(route('frontend.get_currency_history', { id:currencyHistoryId.value, page:currencyHistoryCurrentPage.value}))
                .then(({data}) => {
                    currencyHistoryRows.value = data.data
                  isVisibleCurrencyHistoryModal.value = true
                    currenciesHistoryPagesCount.value = data.meta.last_page
                })
                .catch(e => {
                    console.error(e)
                })
        }

        function paginateClick(page) {
            currencyHistoryCurrentPage.value= page
            loadCurrencyHistory()
        }

        function hideCurrencyHistoryModal() {
          isVisibleCurrencyHistoryModal.value = false
        }

        function loadActiveCurrencies() {
            let filters = {showOnlyTopCurrencies: showOnlyTopCurrencies.value}
            axios.post(route('frontend.currencies_rates.filter'), filters)
                .then(({data}) => {
                    activeCurrencyRows.value = data.data
                })
                .catch(e => {
                    console.error(e)
                })
        } // loadActiveCurrencies() {


        function TopCurrenciesOnMounted() {
            window.emitter.on('listingHeaderRightButtonClickedEvent', params => {
                if (params.parent_component_key === 'currency') {
                    loadActiveCurrencies()
                }
            })

            axios.get(route('get_settings_value', {key: 'rateDecimalNumbers'}))
                .then(({data}) => {
                    rateDecimalNumbers.value = data.value
                })
                .catch(e => {
                    console.error(e)
                })

            axios.get(route('get_base_currency'))
                .then(({data}) => {
                    baseCurrency.value = data.baseCurrency
                })
                .catch(e => {
                    console.error(e)
                })

            loadActiveCurrencies()

            axios.get(route('frontend.get_block_cms_item', 'main_page_currencies_list_block_header'))
                .then(({data}) => {
                    mainPageCurrenciesListBlockHeaderTitle.value = data.cMSItem.title
                    mainPageCurrenciesListBlockHeaderText.value = data.cMSItem.text
                    mainPageCurrenciesListBlockHeaderImageUrl.value = data.cMSItem.cmsItemImageProps.url
                })
                .catch(e => {
                    console.error(e)
                })


        } // TopCurrenciesOnMounted() {

        onMounted(TopCurrenciesOnMounted)

        return { // setup return
            // Listing Page state
            currencyHistoryCurrentPage,
            activeCurrencyRows,
            showOnlyTopCurrencies,
            mainPageCurrenciesListBlockHeaderTitle,
            mainPageCurrenciesListBlockHeaderText,
            mainPageCurrenciesListBlockHeaderImageUrl,


            // Currency History Modal State
            isVisibleCurrencyHistoryModal,
            hideCurrencyHistoryModal,
            paginateClick,
            currenciesHistoryPagesCount,
            currencyHistoryRows,
            showCurrencyDescriptionModal,
            currencyDetails,
            currencyDetailsImage,
            currencyHistoryId,
            currencyHistoryCharCode,
            currencyHistoryName,
            showCurrencyDetailsModal,
            hideCurrencyDescriptionModal,

            // Page actions
            loadActiveCurrencies,

            // Settings vars
            rateDecimalNumbers,
            baseCurrency,
            settingsJsMomentDatetimeFormat,
            settingsCurrencyActiveLabels,
            settingsCurrencyIsTopLabels,

            // Listing filtering

            // Common methods
            pluralize,
            pluralize3,
            momentDatetime,
            getErrorMessage,
            getHeaderIcon,
            getDictionaryLabel,
            formatValue,
            sanitizeHtml,
        }
    }, // setup() {

}
</script>
