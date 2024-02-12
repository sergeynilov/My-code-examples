<template>

    <admin-layout>

        <template #header>
            <h1 class="m-0">Dashboard</h1>
        </template>

        <section class="content admin_color" style="height: 100% !important; margin-bottom: 50px !important;">

            <div class="container-fluid admin_content_text pb-1">
                <h4 class="ml-2">{{ $page.props.user.name }}</h4>
                <ul class="admin_content_text">
                    <li class="mb-2" v-if="$page.props.auth.is_logged_user_admin" >
                        <i :class="getHeaderIcon('admin')" class="action_icon"
                           :title=" 'You have Admin rights'"></i>
                        As admin, you have access to all pages.
                    </li>
                    <li class="mb-2" v-if="$page.props.auth.is_logged_user_support_manager" >
                        <i :class="getHeaderIcon('support_manager')" class="action_icon"
                           :title=" 'You have support manager rights'"></i>
                        As support manager, you have access to contact us page.
                    </li>
                    <li class="mb-2" v-if="$page.props.auth.is_logged_user_content_editor" >
                        <i :class="getHeaderIcon('content_editor')" class="action_icon"
                           :title=" 'You have content editor rights'"></i>
                        As content editor, you have access to cms items page.
                    </li>
                </ul>
            </div>

            <div class="container-fluid">
                <h4>Currencies</h4>
                <div class="row"> <!-- Currencies ROW BLOCK START -->

                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-info elevation-1"><i class="fas fa-cog"></i></span>

                            <div class="info-box-content">
                                <span class="info-box-text">All</span>
                                <span class="info-box-number">
                                    {{ allCurrenciesCount }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="info-box mb-3">
                                <span class="info-box-icon bg-success elevation-1"><i
                                    class="fab fa-creative-commons-sa"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Active</span>
                                <span class="info-box-number">{{ activeCurrenciesCount }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- fix for small devices only -->
                    <div class="clearfix hidden-md-up"></div>

                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="info-box mb-3">
                            <span class="info-box-icon bg-danger elevation-1"><i
                                class="fab fa-creative-commons-nc"></i></span>

                            <div class="info-box-content">
                                <span class="info-box-text">Inactive</span>
                                <span class="info-box-number">{{ inactiveCurrenciesCount }}</span>
                            </div>
                        </div>
                    </div>
                    <!-- /.col -->
                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="info-box mb-3">
                            <span class="info-box-icon bg-info elevation-1"><i class="fas fa-users"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Histories values</span>
                                <span class="info-box-number">{{ allCurrenciesHistoriesCount }}</span>
                            </div>
                        </div>
                    </div>
                </div> <!-- Currencies ROW BLOCK END -->


                <h4>Users</h4>
                <div class="row"> <!-- Users ROW BLOCK START -->

                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-info elevation-1"><i class="fas fa-cog"></i></span>

                            <div class="info-box-content">
                                <span class="info-box-text">All</span>
                                <span class="info-box-number">
                                    {{ allUsersCount }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="info-box mb-3">
                                <span class="info-box-icon bg-success elevation-1"><i
                                    class="fab fa-creative-commons-sa"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Active</span>
                                <span class="info-box-number">{{ activeUsersCount }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- fix for small devices only -->
                    <div class="clearfix hidden-md-up"></div>

                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="info-box mb-3">
                            <span class="info-box-icon bg-danger elevation-1"><i
                                class="fab fa-creative-commons-nc"></i></span>

                            <div class="info-box-content">
                                <span class="info-box-text">Inactive</span>
                                <span class="info-box-number">{{ inactiveUsersCount }}</span>
                            </div>
                        </div>
                    </div>
                    <!-- /.col -->
                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="info-box mb-3">
                            <span class="info-box-icon bg-info elevation-1"><i class="fas fa-users"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">New( Waiting activation )</span>
                                <span class="info-box-number">{{ newUsersCount }}</span>
                            </div>
                        </div>
                    </div>
                </div> <!-- Users ROW BLOCK END -->


                <h4>Currency subscriptions</h4>
                <div class="row mb-8 pb-8 "> <!-- USER_CURRENCY_SUBSCRIPTIONS ROW BLOCK START -->
                    <div class="col-12 col-sm-6 col-md-4 pb-8"  v-for="(nextUserCurrencySubscription, index) in userCurrencySubscriptions" :key="index">
                        <div class='currency-image-wrapper'>
                            <img :src="nextUserCurrencySubscription.currency_media_image_url" class="img-fluid border-upper-radius admin_dashboard_img_icon_wrapper" alt="#">
                            <p class="currency-top-count">{{ nextUserCurrencySubscription.currency_subscriptions_count }}</p>
                        </div>
                        <p class="info-box-text">
                            {{ nextUserCurrencySubscription.currency_char_code }}/
                            {{ nextUserCurrencySubscription.currency_name }}
                        </p>
                    </div>
                </div> <!-- USER_CURRENCY_SUBSCRIPTIONS ROW BLOCK END -->


                <!-- /.row -->
            </div><!--/. container-fluid -->
        </section>

    </admin-layout>

</template>


<script>
import AdminLayout from '@/Layouts/AdminLayout'


import {
    getHeaderIcon,
    pluralize,
    pluralize3,
    momentDatetime,
    showFlashMessage,
    getDictionaryLabel,
} from '@/commonFuncs'
import {dashboardJsMomentDatetimeFormat} from '@/app.settings.js'
import * as sanitizeHtml from 'sanitize-html'

import {onMounted, ref} from "vue";
import axios from "axios";
import {usePage} from '@inertiajs/inertia-vue3';

import { defineComponent } from 'vue'

export default defineComponent({
    props: [],

    name: 'DashboardEdit',
    components: {
        AdminLayout,
    },
    setup(props) {
        let activeCurrenciesCount = ref(0)
        let inactiveCurrenciesCount = ref(0)
        let allCurrenciesCount = ref(0)
        let allCurrenciesHistoriesCount = ref(0)

        let activeUsersCount = ref(0)
        let inactiveUsersCount = ref(0)
        let newUsersCount = ref(0)
        let allUsersCount = ref(0)
        let userCurrencySubscriptions = ref([])

        function LoadDashboardCurrencyInfo() {
            axios.get(route('admin.dashboard.get_currency_info'))
                .then(({data}) => {
                    activeCurrenciesCount.value = data.activeCurrenciesCount
                    inactiveCurrenciesCount.value = data.inactiveCurrenciesCount
                    allCurrenciesCount.value = data.allCurrenciesCount
                    allCurrenciesHistoriesCount.value = data.allCurrenciesHistoriesCount
                })
                .catch(e => {
                    console.error(e)
                })
        }

        function LoadDashboardUsersInfo() {
            axios.get(route('admin.dashboard.get_users_info'))
                .then(({data}) => {
                    activeUsersCount.value = data.activeUsersCount
                    inactiveUsersCount.value = data.inactiveUsersCount
                    newUsersCount.value = data.newUsersCount
                    allUsersCount.value = data.allUsersCount
                })
                .catch(e => {
                    console.error(e)
                })
        } // LoadDashboardUsersInfo

        function LoadDashboardUserCurrencySubscriptionsInfo() {
            axios.get(route('admin.dashboard.get_user_currency_subscriptions_info'))
                .then(({data}) => {
                    userCurrencySubscriptions.value = data.userCurrencySubscriptions
                })
                .catch(e => {
                    console.error(e)
                })
        } // LoadDashboardUserCurrencySubscriptionsInfo

        function adminDashboardEditOnMounted() {
            showFlashMessage()
            LoadDashboardCurrencyInfo()
            LoadDashboardUsersInfo()
            // get_user_currency_subscriptions_info
            LoadDashboardUserCurrencySubscriptionsInfo()
        }

        onMounted(adminDashboardEditOnMounted)

        return { // setup return
            // Page state
            LoadDashboardCurrencyInfo,
            LoadDashboardUsersInfo,
            LoadDashboardUserCurrencySubscriptionsInfo,
            // LoadDashboardCurrencyInfo,
            activeCurrenciesCount,
            inactiveCurrenciesCount,
            allCurrenciesCount,
            allCurrenciesHistoriesCount,

            activeUsersCount,
            inactiveUsersCount,
            newUsersCount,
            allUsersCount,

            userCurrencySubscriptions,
            // Common methods
            getHeaderIcon,
            pluralize,
            pluralize3,
            momentDatetime,
            showFlashMessage,
            getDictionaryLabel,
            sanitizeHtml,
        }
    }, // setup() {

})
</script>

<style>

.currency-image-wrapper {
    position: relative;
}
.currency-image-wrapper .currency-top-count {
    position: absolute;
    display: inline-block;
    padding: 2px 5px;
    background: #eee;
    color: #000;
    z-index: 2;
}
.currency-image-wrapper .currency-top-count {
    background: green;
    color: white;
    top: 8px;
    left: 8px;
    border: 2px solid white;
}

</style>


