<template>
    <div>
        <div class="p-6 sm:px-20 bg-white border-b border-gray-200">
            <div class="mt-8 text-2xl">
                Todo List editor
            </div>

            <div class="mt-6 text-gray-500">
                With your access you can edit / delete this Todo List.
            </div>
        </div>

        <div class="bg-gray-200 bg-opacity-25 p-8">

            <div class="container mx-auto">
                <ul class="flex space-x-2 text-white">
                    <li>
                        <button
                            @click="setActiveTab(1)"
                            class="inline-block px-4 py-2 bg-green-700"
                            :class="{ 'active_tab': active_tab == 1 }"
                        >
                            Details
                        </button>
                    </li>
                    <li>
                        <button
                            @click="setActiveTab(2)"
                            class="inline-block px-4 py-2 bg-green-700"
                            :class="{ 'active_tab': active_tab == 2 }"
                        >
                            Tasks
                        </button>
                    </li>
                </ul>
                <div class="p-3 mt-6 bg-white border">
                    <div v-show="active_tab === 1">
                        <todo-form-editor :todo="todo"></todo-form-editor>
                    </div>
                    <div v-show="active_tab === 2">
                        <todo-tasks-listing :todo="todo"></todo-tasks-listing>
                    </div>
                </div>
            </div>

        </div>

    </div>
</template>

<script>
import TodoFormEditor from '@/Pages/Admin/Todo/Form'
import TodoTasksListing from '@/Pages/Admin/Todo/TodoTasksListing'

import {
    getHeaderIcon,
    showFlashMessage,
} from '@/commonFuncs'
import {ref, computed, onMounted} from 'vue'
import JetButton from '@/Jetstream/Button.vue'
import JetInput from '@/Jetstream/Input.vue'
import JetLabel from '@/Jetstream/Label.vue'
import JetCheckbox from '@/Jetstream/Checkbox.vue'
import JetInputError from '@/Jetstream/InputError.vue'
import JetSecondaryButton from '@/Jetstream/SecondaryButton.vue'
import {defineComponent} from 'vue'

export default defineComponent({
    props: {
        todo: {
            type: Object,
            required: true,
        },
    },

    name: 'adminTodoEditor',
    components: {
        JetButton,
        JetSecondaryButton,
        JetInput,
        JetLabel,
        JetInputError,
        JetCheckbox,
        TodoTasksListing,
        TodoFormEditor
    },
    setup(props) {
        let todo = props.todo.data
        let active_tab = ref(1)

        function setActiveTab(tab) {
            active_tab.value = tab;
        }

        function adminTodoEditorOnMounted() {
            showFlashMessage()
        } // function adminTodoEditorOnMounted() {

        onMounted(adminTodoEditorOnMounted)

        return { // setup return
            // Listing Page state
            TodoFormEditor,
            active_tab,

            // Page actions
            setActiveTab,

            // Settings vars

            // Common methods
            getHeaderIcon,
            showFlashMessage,

        }
    }, // setup() {

})
</script>

