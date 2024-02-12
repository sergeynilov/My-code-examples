<x-app-layout>
    <x-slot name="header">
        <h3 class="page_header">
            {!! AppContent::showIcon(App\Enums\SvgIconType::PRODUCT, App\Enums\SvgIconSize::SMALL) !!}
            Products listing
        </h3>
        <x-custom-message/>
    </x-slot>

    <div class="listing_wrapper" x-data="productsIndexComponent()" x-init="[onProductsIndexComponentInit() ]"
         @event-pagination-clicked.window="productsPaginationItemClicked($event.detail)" x-cloak>

        <div class="flex flex-col">

            @include('admin.products.index_filter', [])

            <div class="overflow-x-auto sm:mx-1 lg:mx-2">
                <div class="py-2 inline-block min-w-full sm:px-6 lg:px-8">

                    <div class="warning_message" x-show="isPageInitialized && !isProcessing && !productRows.length">
                        No products found!
                    </div>
                    <div class="listing_table_wrapper" x-show="isPageInitialized && !isProcessing && productRows.length">
                        <table class="listing_table table">
                            <thead class="bg-white border-b">
                            <tr>
                                <th scope="col" class="listing_th_cell">
                                </th>
                                <th scope="col" class="listing_th_cell">
                                    Title
                                </th>
                                <th scope="col" class="listing_th_cell">
                                    Status
                                </th>
                                <th scope="col" class="listing_th_cell">
                                    Regular Price
                                </th>
                                <th scope="col" class="listing_th_cell">
                                    Discount Price
                                </th>
                                <th scope="col" class="listing_th_cell">
                                    Is Stock
                                </th>
                                <th scope="col" class="listing_th_cell">
                                    Created
                                </th>
                                <th scope="col" class="listing_th_cell">
                                    Related data
                                </th>
                            </tr>
                            </thead>

                            <tbody>
                            <template x-for="product in productRows" :key="product._id">
                                <tr class="listing_tr">
                                    <td class="listing_td_cell">
                                        <a @click.prevent="editProduct(product._id)" class="listing_editor_link">
                                            {!! AppContent::showIcon(App\Enums\SvgIconType::EDIT,
                                            App\Enums\SvgIconSize::SMALL) !!}
                                        </a>
                                        <a @click.prevent="deleteProduct(product._id)" class="listing_delete_link">
                                            {!! AppContent::showIcon(App\Enums\SvgIconType::REMOVE,
                                            App\Enums\SvgIconSize::SMALL) !!}
                                        </a>
                                    </td>
                                    <td class="listing_td_cell">
                                        <span x-text="product.title"></span>
                                    </td>
                                    <td class="listing_td_cell">
                                        <span class="text-red-600" x-show="product.import_notices">
                                        {!! AppContent::showIcon(App\Enums\SvgIconType::ABUSE_FLAG,
                                            App\Enums\SvgIconSize::SMALL, title : 'Have import notices') !!}
                                        </span>
                                        <span x-text="product.status_label"></span>
                                    </td>
                                    <td class="listing_td_cell">
                                        <span x-text="product.regular_price_formatted"></span>
                                    </td>
                                    <td class="listing_td_cell">
                                        <span x-text="product.discount_price_formatted"></span>
                                    </td>
                                    <td class="listing_td_cell">
                                        <span x-text="product.in_stock_label"></span>
                                    </td>
                                    <td class="listing_td_cell">
                                        <span x-text="product.created_at_formatted"></span>
                                    </td>
                                    <td class="listing_td_cell">
                                        <span x-text="calcProductCategoriesRelationsCountText(product.product_categories_count)"></span>
                                    </td>
                                </tr>
                            </template>
                            </tbody>

                        </table>
                    </div>

                </div>

            </div>

            <div x-show="isPageInitialized && !isProcessing">
                <x-listing-pagination
                    :item-type="'product'"
                    :pagination-item-count="5"
                />
            </div>

        </div>

        <script>
            function productsIndexComponent() {
                pageInit('list', 'product')

                return {
                    productRows : [],
                    totalProductsCount : 0,
                    isPageInitialized : false,
                    isProcessing : false,
                    paginationCurrentPage : 1,

                    filterTitle : '',
                    filterStatus : '',
                    filterInStock : '',
                    filterFromRegularPrice : '',
                    filterTillRegularPrice : '',

                    filterHasDiscountPrice : '',
                    filterFromDiscountPrice : '',
                    filterTillDiscountPrice : '',
                    showFilterDiscountPrices : false,
                    filterImportNotice : '',

                    filterRowsSortedBy : '',
                    filtersCountText : '',

                    refreshPage: function () {
                        this.loadProductsData(this.paginationCurrentPage)
                    },
                    productsPaginationItemClicked: function (detail) {
                        if(detail.itemType === 'product') {
                            let page = detail.page
                            this.paginationCurrentPage = page
                            this.loadProductsData(this.paginationCurrentPage)
                        }
                    },
                    loadProductsData: function (page) {
                        let filtersData = {
                            page: this.paginationCurrentPage,
                            title: this.filterTitle,
                            status: this.filterStatus,
                            in_stock: this.filterInStock,
                            from_regular_price: this.filterFromRegularPrice,
                            till_regular_price: this.filterTillRegularPrice,
                            has_discount_price: this.filterHasDiscountPrice,
                            from_discount_price: this.filterFromDiscountPrice,
                            till_discount_price: this.filterTillDiscountPrice,
                            import_notice: this.filterImportNotice,
                            sorted_by: this.filterRowsSortedBy
                        }

                        this.isProcessing = true
                        axios.post('/admin/products/filter', filtersData)
                            .then(({data}) => {
                                this.productRows = data.products.data
                                this.totalProductsCount = data.totalProductsCount
                                const event = new CustomEvent('event-init-pagination', { bubbles: true,
                                    detail: { totalItemsCount: data.totalProductsCount, itemRows : data.products.data, paginationPerPage : data.paginationPerPage } });
                                window.dispatchEvent(event)

                                this.isProcessing = false
                                this.isPageInitialized = true
                            })
                            .catch(e => {
                                this.isProcessing = false
                                console.error(e)
                            })

                    },
                    saveChanges: function () {
                        let btnFiltersModalClose = document.getElementById("btn_save_changes_close")
                        if (btnFiltersModalClose) {
                            btnFiltersModalClose.click()
                        }
                    },
                    applyFilters: function () {
                        this.paginationCurrentPage = 1
                        this.loadProductsData(this.paginationCurrentPage)
                        let btnFiltersModalClose = document.getElementById("btn_filters_modal_close")
                        if (btnFiltersModalClose) {
                            btnFiltersModalClose.click()
                        }
                        this.filtersCountText = this.calcFiltersCountText()
                        if (document.getElementById("icon_listing_btn_filters")) {
                            document.getElementById("icon_listing_btn_filters").setAttribute('title', this.filtersCountText)
                        }
                        if (document.getElementById("span_icon_listing_btn_filters")) {
                            document.getElementById("span_icon_listing_btn_filters").style.display = this.filtersCountText
                                ? "inline" : "none"
                        }
                        this.mode = ''
                    },

                    clearFilters: function () {
                        this.filterTitle = ''
                        this.filterStatus = ''
                        this.filterInStock = ''
                        this.filterFromRegularPrice = ''
                        this.filterTillRegularPrice = ''
                        this.filterImportNotice = ''
                        this.filterHasDiscountPrice = ''
                        this.filterFromDiscountPrice = ''
                        this.filterTillDiscountPrice = ''
                        this.filterRowsSortedBy = ''
                    },
                    filterHasDiscountPriceOnChange: function() {
                        let filterHasDiscountPrice = document.getElementById("filterHasDiscountPriceEEEEEEEE").value
                        return 1
                    },
                    calcProductCategoriesRelationsCountText: function(product_categories) {
                        return pluralize3(product_categories, 'Not assigned to categories', 'Assigned to 1 category', 'Assigned to ' + product_categories + ' categories')
                    },
                    calcTitleItemsCountText: function() {
                        return pluralize3(this.productRows.length, 'No products', '1 product from ' + this.totalProductsCount, this.productRows.length + ' products from ' + this.totalProductsCount)
                    },
                    calcFiltersCountText: function() {
                        let ret = 0
                        if (this.filterTitle !== '') {
                            ret++
                        }
                        if (this.filterStatus !== '') {
                            ret++
                        }
                        if (this.filterInStock !== '') {
                            ret++
                        }
                        if (this.filterFromRegularPrice !== '') {
                            ret++
                        }
                        if (this.filterTillRegularPrice !== '') {
                            ret++
                        }
                        if (this.filterImportNotice !== '') {
                            ret++
                        }
                        if (this.filterHasDiscountPrice !== '') {
                            ret++
                        }
                        if (this.filterFromDiscountPrice !== '') {
                            ret++
                        }
                        if (this.filterTillDiscountPrice !== '') {
                            ret++
                        }
                        if (!ret) return ''
                        return '  '+pluralize3(ret, 'No filters set', '1 filter is set', ret + ' filters are set')
                    },


                    editProduct: function (product_id) {
                        window.location.href = '/admin/products/' + product_id + '/edit'
                    },

                    deleteProduct: function (product_id) {
                        let self = this
                        Swal.fire({
                            title: "{{ __('Do you want to remove selected product') }} ?",
                            text: "{{ __('All references at this product would be cleared') }}",
                            icon: "warning",
                            showCancelButton: true,
                            confirmButtonText: "{{ __('Yes, remove') }} !"
                        }).then(function (result) {
                            if (result.value) {
                                axios.delete('/admin/products/' + product_id)
                                    .then(({data}) => {
                                        Swal.fire(
                                            'Delete product',
                                            'Product has been deleted',
                                            'success'
                                        )
                                        self.loadProductsData(self.paginationCurrentPage)
                                    })
                                    .catch(e => {
                                        console.error(e, 'Delete product')
                                        showRTE(e)
                                    })
                            }
                        })
                    },
                    onProductsIndexComponentInit: function () {
                        if ('{{ $mode }}' === 'active_products') {
                            this.filterStatus = '{{ App\Models\Product::STATUS_ACTIVE }}'
                        }
                        if ('{{ $mode }}' === 'draft_products') {
                            this.filterStatus = '{{ App\Models\Product::STATUS_DRAFT }}'
                        }
                        if ('{{ $mode }}' === 'inactive_products') {
                            this.filterStatus = '{{ App\Models\Product::STATUS_INACTIVE }}'
                        }
                        if ('{{ $mode }}' === 'with_import_notice') {
                            this.filterImportNotice = 1
                        }
                        if (this.mode === 'hiddenonly') {
                            this.filterStatus = 0
                        }

                        this.calcFiltersCountText()

                        this.$watch('filterHasDiscountPrice', state => {
                            this.showFilterDiscountPrices = parseInt(state) === 1
                            if(!this.showFilterDiscountPrices) {
                                setElementValue("filterFromDiscountPrice", "")
                                setElementValue("filterTillDiscountPrice", "")
                            }
                        })

                        this.loadProductsData(this.paginationCurrentPage)

                        setAppTitle('{{ AppContent::getAppSettings('site_name') }}', 'Products')
                    }


                }
            } // function productsIndexComponent() {
        </script>


    </div>

</x-app-layout>
