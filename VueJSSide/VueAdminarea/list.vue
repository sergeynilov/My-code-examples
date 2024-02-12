<template>
    <article class="page_content_container">

        <listing-header :show_loading_image=!is_page_loaded
                        :current_page=current_page
                        :itemsList="events"
                        :rows_count="events_total_count"
                        :header_icon="getHeaderIcon('event')"
                        :header_title="'Events'"
                        :right_add_button_link="'adminEventEditor'"
                        :item_title="events.length| pluralize(['event', 'events'])"
                        :right_add_button_link_title="'New'"
                        parent_component_key="event"
                        :right_icon="'fa fa-refresh'"
        >
        </listing-header>

        <fieldset class="bordered text-muted p-0 m-1">
            <legend class="bordered">Filters</legend>

            <dl class="block_2columns_md m-0 p-2">
                <dt class="horiz_divider_left_13">
                    <label class="col-form-label admin_control_label" for="filter_name">
                        By name
                    </label>
                </dt>
                <dd class="horiz_divider_right_23">
                    <input style="flex: 1 0" name="filter_name" id="filter_name" class="form-control admin_control_input" type="text" value=""
                           v-model="filter_name"
                           v-on:change="eventsFilterApplied()">
                </dd>
            </dl>

            <dl class="m-0 p-3" style="display: flex;justify-content: flex-start">
                <dt class="">
                    <a class="btn btn-outline-secondary" @click.prevent="loadEvents()">
                        <i :class="'i_link '+getHeaderIcon('filter')"></i>Search
                    </a>
                </dt>
            </dl>
        </fieldset>

        <div class="table-responsive table-wrapper-for-data-listing " v-show="events.length && is_page_loaded">
            <table class="table table-striped table-data-listing">

                <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>At time</th>
                    <th>Access</th>
                    <th>Created At</th>
                    <th></th>
                </tr>
                </thead>

                <tbody>
                <tr v-for="(nextEvent,index) in events" :key="nextEvent.id">
                    <td class="text-right">
                        <router-link :to="{name: 'adminEventEditor', params: {id: nextEvent.id}}" :class="'p-1 a_edit_item_'+nextEvent.id">
                            {{ nextEvent.id }}
                        </router-link>
                    </td>
                    <td class="text-left">
                        {{ nextEvent.name }}
                    </td>
                    <td class="text-left">
                        {{ momentDatetime(nextEvent.at_time, jsMomentDatetimeFormat) }} ({{ nextEvent.duration }} mins)
                    </td>
                    <td class="text-left">
                        {{ getDictionaryLabel(nextEvent.access, eventAccessLabels) }}
                    </td>
                    <td class="text-left">
                        {{ momentDatetime(nextEvent.created_at, jsMomentDatetimeFormat) }}
                    </td>

                    <td>
                        <router-link :to="{name: 'adminEventEditor', params: {id: nextEvent.id}}" :class="'p-1 a_edit_item_'+nextEvent.id">
                            <i :class="'i_link '+getHeaderIcon('edit')" title="Edit event"></i>
                        </router-link>
                        <a v-on:click="removeEvent(nextEvent.id, nextEvent.name, index)" :class="'p-1 a_delete_item_'+nextEvent.id">
                            <i :class="'i_link '+getHeaderIcon('remove')" title="Remove event"></i>
                        </a>
                    </td>

                </tr>
                </tbody>

            </table>
        </div>

        <section class="event_pagination_container" v-show="events.length && is_page_loaded">
            <listing-pagination
                    :current="current_page"
                    :total="events_total_count"
                    :items_per_page="events_per_page"
                    :show_next_prior_buttons=false
                    @paginationPageChangedEvent="paginationPageClicked"
                    :show_page_number_label="true"
                    :show_rows_label="true"
                    :item_title="events_total_count | pluralize(['event', 'events'])"
            >
            </listing-pagination>
        </section>


        <fieldset class="blocks" v-if="demo_version && 0">
            <legend class="blocks"><i :class="getHeaderIcon('demo-info')"></i>Demo info&nbsp;</legend>

            <div class="m-1 p-1 pre-formatted description-text" style="overflow-y: auto; max-height: 200px;">
                <ol class="m-1 p-1">
                    <li>Listing of events with related tasks</li>
                    <li>Logged admin can add / remove users in the app to any event by clicking on icon in left bottom corner of any event</li>
                </ol>
            </div>

        </fieldset>


    </article> <!-- page_content_container -->
</template>

<script>
    import {bus} from '../../../main'
    import appMixin from '@/appMixin'
    import axios from 'axios'
    import {settingsJsMomentDatetimeFormat, settingCredentialsConfig, settingsEventAccessLabels} from '@/app.settings.js'

    import {retrieveAppDictionaries} from "@/commonFuncs"

    import Vue from 'vue'

    Vue.component('listing-header', require('../../../../src/components/ListingHeader.vue').default)
    Vue.component('listing-pagination', require('../../../../src/components/ListingPagination.vue').default)

    export default {
        name: 'eventsListPage',

        mixins: [appMixin],

        data() {
            return {
                events: [],
                filter_name: '',
                current_page: 1,
                order_by: 'created_at',
                order_direction: 'desc',
                events_total_count: 0,
                is_page_loaded: false,
                events_per_page: 20,
                jsMomentDatetimeFormat: settingsJsMomentDatetimeFormat,
                eventAccessLabels: settingsEventAccessLabels,
                credentialsConfig: settingCredentialsConfig,
                apiUrl: process.env.VUE_APP_API_URL,
                demo_version: process.env.VUE_APP_DEMO_VERSION

            }
        }, // data () {


        created() {
            if (!this.loggedUserIsAdmin) {
                this.showPopupMessage('Admin Area', 'You have no access to Admin Area !', 'warn')
                this.$store.dispatch('logout')
                this.$router.push('/login')
            }
        }, //  created() {

        mounted() {
            this.setAppTitle('Events', 'Events Listing', bus)

            retrieveAppDictionaries('eventsListPage', ['backend_items_per_page'])
            bus.$on('appDictionariesRetrieved', (data) => {
                if (data.request_key === 'eventsListPage') {
                    this.events_per_page = data.backend_items_per_page
                }
            })

            this.loadEvents()
            bus.$on('listingHeaderRightButtonClickedEvent', (parent_component_key) => {
                if (parent_component_key === 'event') {
                    this.loadEvents()
                }
            })

        }, // mounted() {


        methods: {
            loadEvents() {
                this.is_page_loaded = false
                let credentials= this.getClone(this.credentialsConfig)
                credentials.headers.Authorization = 'Bearer ' + this.currentLoggedUserToken
                let filters = {page: this.current_page, order_by: this.order_by, order_direction: this.order_direction, filter_name: this.filter_name}
                axios.post(this.apiUrl + '/adminarea/events-filter', filters, credentials)
                    .then(({ data }) => {
                        this.events = data.events
                        this.events_total_count = data.meta.total
                        this.events_per_page = data.meta.per_page
                        this.is_page_loaded = true
                    })
                    .catch(error => {
                        console.error(error)
                        this.showPopupMessage('Events Editor', error.response.data.message, 'warn')
                        this.is_page_loaded = true
                    })
            }, // loadEvents() {

            removeEvent(id, event_name/*, index*/) {
                if (!confirm("Do you want to delete" + " '" + event_name + "' event ?")) {
                    return
                }

                let credentials= this.getClone(this.credentialsConfig)
                credentials.headers.Authorization = 'Bearer ' + this.currentLoggedUserToken
                axios.delete(this.apiUrl + '/adminarea/events/' + id, credentials).then((/*response*/) => {
                    this.loadEvents()
                    this.showPopupMessage('Events Editor', 'Event was deleted successfully', 'success')
                }).catch((error) => {
                    console.error(error)
                    this.showPopupMessage('Events Editor', 'Error deleting event !', 'error')
                })

            }, //removeEvent(id, event_name, index) {

            paginationPageClicked(page) {
                this.current_page = page
                this.loadEvents()
            },


            eventsFilterApplied() {
                this.current_page = 1
                this.loadEvents()
            },

        }, // methods: {

        computed: {
            getHeaderTitle: function () {
                return "Events Listing"
            },

            currentLoggedUser() {
                return this.$store.getters.user
            },

            currentLoggedUserToken() {
                return this.$store.getters.token
            },

            loggedUserIsAdmin: function () {
                return this.$store.getters.is_admin
            },

        }

    }
</script>
