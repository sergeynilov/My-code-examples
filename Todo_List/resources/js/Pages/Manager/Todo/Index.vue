<template>
    <div class="p-6 sm:px-20 bg-white border-b border-gray-200">

        <div class="mt-8 text-2xl">
            {{ todoRows.length }} todo{{pluralize(todoRows.length, '', 's') }} from {{ todos_filtered_count }}

        </div>

        <div class="mt-6 text-gray-500">
            As manager, you can publish/unpublish tasks.
        </div>
    </div>

    <div class="bg-gray-200 bg-opacity-25">

        <div class="my-0 mx-2 p-0 overflow-x-auto ">
            <table class="w-full editor_listing_table">
                <thead class="editor_listing_table_header">
                <tr>
                    <th>
                        Todo Lists with tasks
                    </th>
                </tr>
                </thead>
                <tbody>

                <tr v-for="(nextTodo, index) in todoRows" :key="index">
                    <td class="p-2">
                        <todo-details :todo="nextTodo" :key="'todo-details-' + nextTodo.id" ></todo-details>
                    </td>

                </tr>

                </tbody>
            </table>


        </div>

        <div class="p-4">
            <paginate
                v-show="todos_pages_count > 1"
                v-model="current_page"
                :page-count="todos_pages_count"
                :click-handler="paginateClick"
                :first-last-button="false"
                :page-range="2"
                :margin-pages="3"
                :prev-text="'<'"
                :next-text="'>'"
                :container-class="'pagination'"
            >
            </paginate>

        </div>


    </div>

</template>

<script>

import {
    getHeaderIcon,
    pluralize,
    showFlashMessage,
} from '@/commonFuncs'
import {ref, computed, onMounted} from 'vue'
import JetButton from '@/Jetstream/Button.vue'
import JetInput from '@/Jetstream/Input.vue'
import JetLabel from '@/Jetstream/Label.vue'
import TodoDetails from '@/Pages/Manager/Todo/TodoDetails'

import { defineComponent } from 'vue'

export default defineComponent({
    props: {},
    name: 'managerTodosList',
    components: {
        JetButton,
        JetInput,
        JetLabel,
        TodoDetails
    },
    setup(props) {
        let order_by = ref('name')
        let order_direction = ref('asc')
        let filter_name = ref('')
        let is_data_loading = ref(false)


        let todos_per_page = ref(2)
        let current_page = ref(1)

        let todos_filtered_count = ref(0)
        let todos_pages_count = ref(0)
        let todoRows = ref([])


        function loadTodos() {
            let filters = {
                page: current_page.value,
                order_by: order_by.value,
                order_direction: order_direction.value,
                filter_name: filter_name.value,
            }
            is_data_loading.value =  true

            axios.post(route('manager.todos.filter'), filters)
                .then(({data}) => {
                    todoRows.value = data.data
                    todos_filtered_count.value = data.meta.total
                    todos_pages_count.value = data.meta.last_page
                    todos_per_page.value = parseInt(data.meta.per_page)
                    is_data_loading.value =  false
                })
                .catch(e => {
                    is_data_loading.value =  false
                    console.error(e)
                })
        } // loadTodos() {


        function paginateClick(page) {
            current_page.value = page
            loadTodos()
        }



        function managerTodosListOnMounted() {
            showFlashMessage()
            loadTodos()
            window.emitter.on('reloadTodoTasksEvent', params => {
                loadTodos()
           })

        } // function managerTodosListOnMounted() {

        onMounted(managerTodosListOnMounted)

        return { // setup return
            // Listing Page state
            current_page,
            todos_per_page,
            todoRows,
            todos_filtered_count,
            todos_pages_count,
            is_data_loading,

            // Page actions
            paginateClick,

            // Common methods
            pluralize,
            getHeaderIcon,
            showFlashMessage,

        }
    }, // setup() {

})
</script>

