<!-- Subscription Editor Form BLOCK START -->

<?php $errorFieldsArray = array_values(!empty($errors) ? $errors->keys() : []); ?>

<div class="" x-data="subscriptionFormComponent()" x-init="[onSubscriptionFormComponentInit() ]" x-cloak>

    <x-validation-errors :errors="$errors" :showErrorsList="false"/>

    <div class="editor_field_block_wrapper">
        <div class="editor_field_block_device_splitter">
            <div class="w-4/12 pb-0 pl-2 md:pt-3 ">
                <x-label for="title" :value="__('Title')"/>
            </div>
            <div class="p-2 w-full">
                <x-input id="title" class="editor_form_input" type="text" name="title"
                         value="{{ old('title', $subscription->title ?? '') }}" autofocus
                         aria-describedby="titleHelp" placeholder="Enter title" autocomplete="off"/>
                @error('title')
                <p class="validation_error">{{ $message }}</p>
                @else
                    <small id="titleHelp" class="editor_form_help">
                        This title will be used in products subscription.
                    </small>
                    @enderror
            </div>
        </div>
    </div>

    <div class="editor_field_block_wrapper">
        <div class="editor_field_block_device_splitter">
            <div class="w-4/12 pb-0 pl-2 md:pt-3 ">
                <x-label for="published" :value="__('Published')"/>
            </div>
            <div class="p-2 w-full">
                <input type="checkbox"
                       @checked(old('published', ( $subscription->published ?? null) ) == 1)
                       class="form-check-input editor_form_checkbox"
                       value="1"   id="published" name="published"
                       aria-describedby="publishedHelp">
                <label class="form-check-label editor_for{m_checkbox_label" for="published">
                    Set published
                </label>
                <small id="publishedHelp" class="editor_form_help">
                    Only published subscription are available for products
                </small>
            </div>
        </div>
    </div>

    <div class="block">
        <div class="flex">
            <div class="p-2 w-full">
                <x-input id="text" type="hidden" name="text" value="{{ $subscription->text ?? '' }}"/>
                <textarea id="text_editor" name="text" class="block w-full mt-1 rounded-md"
                     rows="5">{{ old('text', $subscription->text ?? '') }}</textarea>
                @error('text')
                <p class="validation_error">{{ $message }}</p>
                @else
                    <small id="textHelp" class="editor_form_help">
                        This text will be used in products subscription.
                    </small>
                    @enderror
            </div>
        </div>
    </div>

    @if(!$isInsert)
        <div class="editor_field_block_wrapper">
            <div class="editor_field_block_device_splitter">
                <div class="w-4/12 pb-0 pl-2 md:pt-3 ">
                    <x-label for="created_at" :value="__('Created at')"/>
                </div>
                <div class="p-2 w-full">
                    <input type="text" disabled class="form-label editor_form_readonly" id="created_at"
                           name="created_at"
                           value="{{DateConv::getFormattedDateTime($subscription->created_at ?? '') ?? ''}}">
                </div>
            </div>
        </div>
    @endif

    @if(!empty($subscription->updated_at))
        <div class="editor_field_block_wrapper">
            <div class="editor_field_block_device_splitter">
                <div class="w-4/12 pb-0 pl-2 md:pt-3 ">
                    <x-label for="updated_at" :value="__('Updated at')"/>
                </div>
                <div class="p-2 w-full">
                    <input type="text" disabled class="form-label editor_form_readonly" id="updated_at"
                           name="updated_at"
                           value="{{ DateConv::getFormattedDateTime($subscription->updated_at ?? '') ?? ''}}">
                </div>
            </div>
        </div>

    @endif

    <div class="editor_form_btns_divider">
        <button type="submit" class="editor_form_btn_save">
            {!! AppContent::showIcon(App\Enums\SvgIconType::SAVE, App\Enums\SvgIconSize::MEDIUM) !!}
            @if ($isInsert) Add @else Update @endif
        </button>
        <button type="button" class="editor_form_btn_cancel ml-4">
            <a href="{{ route('admin.subscriptions.index') }}" class="">
                {!! AppContent::showIcon(App\Enums\SvgIconType::CANCEL, App\Enums\SvgIconSize::SMALL) !!}
                Cancel
            </a>
        </button>
    </div>
    {{--    @push('scripts')--}}
    <script src="https://cdn.ckeditor.com/ckeditor5/34.2.0/classic/ckeditor.js"></script>
    <script>
        function subscriptionFormComponent() {
            pageInit('editor', 'subscription')

            return {
                initCKEditor: function () {
                    ClassicEditor
                        .create(document.querySelector('#text_editor'))
                        .then(editor => {
                            editor.ui.focusTracker.on('change:isFocused', (evt, name, isFocused) => {
                                if (!isFocused) {
                                    // Do whatever you want with current editor data:
                                    setElementValue("text", editor.getData())
                                }
                            }); // 'change:isFocused'
                            // editor.ui.view.editable.editableElement.style.height = '300px';

                            editor.plugins.get('FileRepository').on('change:uploaded', evt => {
                                console.log('Image uploaded evt');
                                console.log(evt);
                            });
                        })

                        .catch(error => {
                            console.error(error);
                        });
                },
                onSubscriptionFormComponentInit: function () {
                    this.initCKEditor()
                    setAppTitle('{{ AppContent::getAppSettings('site_name') }}', 'Subscription')
                }


            }
        } // function subscriptionFormComponent() {
    </script>
    {{--    @endpush--}}
</div>

<!-- Subscription Editor Form BLOCK END -->
