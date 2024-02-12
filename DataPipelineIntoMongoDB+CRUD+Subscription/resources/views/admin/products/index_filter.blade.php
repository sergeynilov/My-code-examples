<div class="flex w-full">
    <div class="flex flex-grow pl-2">

        <div class="spinner-border animate-spin inline-block w-6 h-6 border-4 rounded-full mr-2"
             role="status" x-show="isPageInitialized && isProcessing">
            <span class="visually-hidden">Loading...</span>
        </div>

        <button type="button" class="listing_btn_filters" data-bs-toggle="modal"
                data-bs-target="#showFiltersModal" x-show="isPageInitialized && !isProcessing">
                                <span id="span_icon_listing_btn_filters" style="display:none">
                                    {!! AppContent::showIcon(App\Enums\SvgIconType::FILTER,
                                    App\Enums\SvgIconSize::SMALL, '00 Filters are set', 'icon_listing_btn_filters') !!}
                                </span>
            Filter
        </button>

        <!-- Modal -->
        <div class="modal fade fixed top-0 left-0 hidden w-full h-full outline-none overflow-x-hidden overflow-y-auto"
             id="showFiltersModal" tabindex="-1" aria-labelledby="showFiltersModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-scrollable relative w-auto pointer-events-none">
                <div
                    class="modal-content shadow-lg relative flex flex-col w-full pointer-events-auto
                    bg-white bg-clip-padding rounded-md outline-none text-current border-2 border-gray-300 rounded-lg">
                    <div
                        class="modal-header flex flex-shrink-0 items-center justify-between p-4 border-b border-gray-200 rounded-t-md">
                        <h5 class="text-xl font-medium leading-normal text-gray-800" id="showFiltersModalLabel">
                            {!! AppContent::showIcon(App\Enums\SvgIconType::FILTER,
                            App\Enums\SvgIconSize::SMALL) !!}
                            Products filter
                        </h5>
                        <button type="button" id="btn_filters_modal_close"
                                class="btn-close box-content w-4 h-4 p-1 text-black border-none rounded-none opacity-50 focus:shadow-none focus:outline-none focus:opacity-100 hover:text-black hover:opacity-75 hover:no-underline"
                                data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body relative p-4">
                        <div class="editor_field_block_wrapper">
                            <div class="editor_field_block_device_splitter">
                                <div class="w-6/12 pb-0 pl-2 md:pt-3 ">
                                    <x-label for="filter_title" :value="__('By title')"/>
                                </div>
                                <div class="p-2 w-full">
                                    <x-input x-model="filterTitle" id="filter_title"
                                             class="editor_form_input" type="text" autofocus
                                             placeholder="Enter title" autocomplete="off"/>
                                </div>
                            </div>
                        </div>

                        <div class="editor_field_block_wrapper">
                            <div class="editor_field_block_device_splitter">
                                <div class="w-6/12 pb-0 pl-2 md:pt-3 ">
                                    <x-label for="filter_status" :value="__('By status')"/>
                                </div>
                                <div class="p-2 w-full">
                                    <select x-model="filterStatus" id="filter_status" class="editor_form_input">
                                        <option value=""> -Select all-</option>
                                        @foreach($statusSelectionItems as $statusSelectionItem)
                                            <option value="{{ $statusSelectionItem['key'] }}">
                                                {{ $statusSelectionItem['label'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="editor_field_block_wrapper">
                            <div class="editor_field_block_device_splitter">
                                <div class="w-6/12 pb-0 pl-2 md:pt-3 ">
                                    <x-label for="filter_in_stock" :value="__('By In Stock')"/>
                                </div>
                                <div class="p-2 w-full">
                                    <select x-model="filterInStock" id="filter_in_stock" class="editor_form_input">
                                        <option value=""> -Select all-</option>
                                        @foreach($inStockSelectionItems as $inStockSelectionItem)
                                            <option value="{{ $inStockSelectionItem['key'] }}">
                                                {{ $inStockSelectionItem['label'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="editor_field_block_wrapper">
                            <div class="editor_field_block_device_splitter">
                                <div class="w-6/12 pb-0 pl-2 md:pt-3 ">
                                    <x-label for="filter_from_regular_price" :value="__('From regular price')"/>
                                </div>
                                <div class="p-2 w-full">
                                    <x-input x-model="filterFromRegularPrice" id="filter_from_regular_price"
                                             class="editor_form_input" type="text"
                                             placeholder="Enter valid decimal value" autocomplete="off"/>
                                </div>
                            </div>
                        </div>

                        <div class="editor_field_block_wrapper">
                            <div class="editor_field_block_device_splitter">
                                <div class="w-6/12 pb-0 pl-2 md:pt-3 ">
                                    <x-label for="filter_till_regular_price" :value="__('Till regular price')"/>
                                </div>
                                <div class="p-2 w-full">
                                    <x-input x-model="filterTillRegularPrice" id="filter_till_regular_price"
                                             class="editor_form_input" type="text"
                                             placeholder="Enter valid decimal value" autocomplete="off"/>
                                </div>
                            </div>
                        </div>

                        <div class="editor_field_block_wrapper" >
                            <div class="editor_field_block_device_splitter">
                                <div class="w-6/12 pb-0 pl-2 md:pt-3 ">
                                    <x-label for="filter_has_discount_price" :value="__('By Has Discount Price')"/>
                                </div>
                                <div class="p-2 w-full">
                                    <select x-model="filterHasDiscountPrice" id="filter_has_discount_price" class="editor_form_input" @onchange="filterHasDiscountPriceOnChange">
                                        <option value=""> -Select all-</option>
                                        @foreach( $hasDiscountPriceSelectionItems as $hasDiscountPriceSelectionItem)
                                            <option value="{{ $hasDiscountPriceSelectionItem['key'] }}">
                                                {{ $hasDiscountPriceSelectionItem['label'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="editor_field_block_wrapper" x-show="showFilterDiscountPrices">
                            <div class="editor_field_block_device_splitter">
                                <div class="w-6/12 pb-0 pl-2 md:pt-3 ">
                                    <x-label for="filter_from_discount_price" :value="__('From discount price')"/>
                                </div>
                                <div class="p-2 w-full">
                                    <x-input x-model="filterFromDiscountPrice" id="filter_from_discount_price"
                                             class="editor_form_input" type="text"
                                             placeholder="Enter valid decimal value" autocomplete="off"/>
                                </div>
                            </div>
                        </div>

                        <div class="editor_field_block_wrapper" x-show="showFilterDiscountPrices">
                            <div class="editor_field_block_device_splitter">
                                <div class="w-6/12 pb-0 pl-2 md:pt-3 ">
                                    <x-label for="filter_till_discount_price" :value="__('Till discount price')"/>
                                </div>
                                <div class="p-2 w-full">
                                    <x-input x-model="filterTillDiscountPrice" id="filter_till_discount_price"
                                             class="editor_form_input" type="text"
                                             placeholder="Enter valid decimal value" autocomplete="off"/>
                                </div>
                            </div>
                        </div>

                        <div class="editor_field_block_wrapper">
                            <div class="editor_field_block_device_splitter">
                                <div class="w-4/12 pb-0 pl-2 md:pt-3 ">
                                    <x-label for="filter_import_notice" :value="__('By import notices')"/>
                                </div>
                                <div class="p-2 w-full">
                                    <select x-model="filterImportNotice" id="filter_import_notice" class="editor_form_input">
                                        <option value=""> -Select import notice-</option>
                                        @foreach($havingImportSelectionItems as $havingImportSelectionItem)
                                            <option value="{{ $havingImportSelectionItem['key'] }}">
                                                {{ $havingImportSelectionItem['label'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="editor_field_block_wrapper">
                            <div class="editor_field_block_device_splitter">
                                <div class="w-6/12 pb-0 pl-2 md:pt-3 ">
                                    <x-label for="filter_rows_sorted_by" :value="__('Sorted by')"/>
                                </div>
                                <div class="p-2 w-full">
                                    <select x-model="filterRowsSortedBy" id="filter_rows_sorted_by" class="editor_form_input">
                                        <option value=""> -Select sorted by-</option>
                                        <option value="status_in_stock">{{ __('Status/In stock') }}</option>
                                        <option value="status_title">{{ __('Status/Title') }}</option>
                                        <option value="in_stock_regular_price">{{ __('In stock/Regular price') }}</option>
                                        <option value="related_users_count">{{ __('Number of users ???') }}</option>
                                        <option value="last_created">{{ __('Last created') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer editor_form_btns_divider">
                        <button type="button" class="listing_delete_link mr-6" @click.prevent="clearFilters()"
                                x-show="filterTitle || filterStatus || filterInStock || filterFromRegularPrice || filterTillRegularPrice || filterImportNotice">
                            {!! AppContent::showIcon(App\Enums\SvgIconType::REMOVE,
                            App\Enums\SvgIconSize::MEDIUM, "Clear filters") !!}
                        </button>
                        <button type="button" class="editor_form_btn_cancel" data-bs-dismiss="modal">
                            Close
                        </button>
                        <button type="button" class="editor_form_btn_save ml-3" @click.prevent="applyFilters()">
                            {!! AppContent::showIcon(App\Enums\SvgIconType::SAVE, App\Enums\SvgIconSize::MEDIUM) !!}
                            Apply
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <span x-text="calcTitleItemsCountText()" x-show="isPageInitialized && !isProcessing  && productRows.length" class="mt-1 ml-2">
                            </span>
    </div>
    <div class="w-32 align-items-end justify-end align-top mt-2 whitespace-nowrap" x-show="isPageInitialized && !isProcessing">
        <button type="button" class="listing_btn_ad_item" title="Add product"
                onclick="location.href='{{ route('admin.products.create') }}'">
            +
        </button>

        <button type="button" class="listing_btn_ad_item" title="Add product"
                @click.prevent="refreshPage()">
            {!! AppContent::showIcon(App\Enums\SvgIconType::REFRESH,
            App\Enums\SvgIconSize::MEDIUM, "Refresh page") !!}
        </button>
    </div>
</div>
