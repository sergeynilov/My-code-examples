<template>

    <vue-final-modal
        v-model="task_todo_editor_modal"
        classes="admin_listing_modal_container"
        content-class="admin_listing_modal_content"
    >
        <h5 class="admin_listing_modal_header m-0 m-0">
            <i :class="getHeaderIcon('edit')" class="action_icon icon_right_text_margin"></i>
            Task editor
        </h5>

        <form @submit.prevent="saveTodoTask">
            <div class="content admin_listing_modal_content_editor_form ">

                <div class="mb-4" v-if="!is_insert">
                    <JetLabel for="id" value="Id"/>
                    <JetInput
                        id="id"
                        v-model="formEditor.id"
                        type="text"
                        class="mt-1 block w-full"
                        disabled
                    />
                </div>

                <div class="mb-4">
                    <JetLabel for="name" value="Name"/>
                    <JetInput
                        id="name"
                        v-model="formEditor.name"
                        type="text"
                        class="mt-1 block w-full"
                        autofocus
                    />
                    <JetInputError :message="formEditor.errors.name" class="mt-2"/>
                </div>

                <div class="mb-4">
                    <JetLabel for="assigned_to_user" value=""/>
                    <Datepicker
                        id="deadline"
                        v-model="formEditor.deadline"
                        locale="en-US"
                        format="d MMMM, yyyy"
                        :enableTimePicker="false"
                    >
                        <template v-slot:belowDate>
                            <span></span>
                        </template>
                    </datepicker>
                    <JetInputError :message="formEditor.errors.deadline" class="mt-2"/>
                </div>

                <div class="mb-4">
                    <JetLabel for="assigned_to_user" value=""/>
                    <Multiselect
                        v-model="formEditor.status"
                        id="status"
                        mode="single"
                        :options="settingsTodoTaskStatusLabels"
                        valueProp="code"
                        :searchable="true"
                        :max="-1"
                        ref="multiselect"
                        label="label"
                        track-by="label"
                        placeholder="Select status"
                        class="admin_multiselect"
                    />
                    <JetInputError :message="formEditor.errors.status" class="mt-2"/>
                </div>


                <div class="mb-4">
                    <JetLabel for="description" value="Description"/>
                    <JetInput
                        id="description"
                        v-model="formEditor.description"
                        type="text"
                        class="mt-1 block w-full"
                    />
                    <JetInputError :message="formEditor.errors.description" class="mt-2"/>
                </div>

            </div>

            <div class="admin_listing_modal_footer flex flex-nowrap">
                <JetSecondaryButton @click="hideTaskTodoEditorModal">
                    Cancel
                </JetSecondaryButton>
                <JetButton class="ml-4" :class="{ 'opacity-25': formEditor.processing }"
                           :disabled="formEditor.processing">
                    {{ getSubmitBtnTitle }}
                </JetButton>
                <div v-show="formEditor.processing" class="ml-2 form_processing"></div>
            </div>
        </form>

    </vue-final-modal>

    <div class="p-2 sm:px-20 bg-white border-b border-gray-200">

        <div class="mt-2 text-2xl">
            {{ todoTasksRows.length }} task{{ pluralize(todoTasksRows.length, '', 's') }}
        </div>

        <div class="mt-2 text-gray-500">
            With your access you can edit / delete tasks.
        </div>
    </div>

    <div class="bg-gray-200 bg-opacity-25">

        <div class="mt-8 mb-4 flex flex-nowrap">
            <div class="w-12 align-items-end justify-end align-top mr-2">
                <JetSecondaryButton @click="addTodoTask()">
                    <i :class="getHeaderIcon('add')" class="action_icon icon_right_text_margin"></i>Add
                </JetSecondaryButton>
            </div>
        </div>

        <div class="my-0 mx-2 p-0 overflow-x-auto ">
            <table class="w-full editor_listing_table">
                <thead class="editor_listing_table_header">
                <tr>
                    <th>
                    </th>
                    <th>
                        Name
                    </th>

                    <th>
                        Deadline
                    </th>

                    <th>
                        Status
                    </th>

                    <th>
                        Created at
                    </th>
                </tr>
                </thead>
                <tbody>

                <tr v-for="(nextTodoTask, index) in todoTasksRows" :key="index">
                    <td class="text-right my-4 py-4">
                        <button type="button"
                                class="btn btn-info btn-sm "
                                @click="editTodoTask(nextTodoTask.id)">
                            <i :class="getHeaderIcon('edit')"
                               class="action_icon icon_right_text_margin"></i>
                        </button>
                    </td>

                    <td class="whitespace-nowrap py-1 ">
                        {{ nextTodoTask.name }}
                    </td>
                    <td class="whitespace-nowrap py-1 ">
                        {{ nextTodoTask.deadline_formatted }}
                    </td>
                    <td class="whitespace-nowrap py-1 ">
                        {{ nextTodoTask.status_label }}
                    </td>
                    <td class="whitespace-nowrap py-1  text-center">
                        {{ nextTodoTask.created_at_formatted }}
                    </td>
                </tr>

                </tbody>
            </table>

        </div>

    </div>

</template>

<script>

import {
    getHeaderIcon,
    pluralize,
    pluralize3,
    isEmpty,
    formatValue,
    showFlashMessage,
    dateIntoDbFormat,
} from '@/commonFuncs'
import {settingsAppColors, settingsTodoTaskStatusLabels} from '@/app.settings.js'
import {ModalsContainer, VueFinalModal} from "vue-final-modal";

import {onMounted, ref, computed} from "vue";
import axios from "axios";
import Datepicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import Multiselect from '@vueform/multiselect'


import JetLabel from '@/Jetstream/Label.vue'
import JetInput from '@/Jetstream/Input.vue'
import JetInputError from '@/Jetstream/InputError.vue'
import JetSecondaryButton from '@/Jetstream/SecondaryButton.vue'
import JetButton from '@/Jetstream/Button.vue'

import {defineComponent} from 'vue'
import {useForm} from "@inertiajs/inertia-vue3";

export default defineComponent({

    props: {
        todo: {
            type: Object,
            required: true,
        },
    },

    name: 'adminTodoTasksListing',
    components: {
        JetLabel,
        VueFinalModal,
        ModalsContainer,
        Datepicker,
        JetInput,
        JetInputError,
        JetSecondaryButton,
        JetButton,
        Multiselect
    },
    setup(props) {
        let todo = props.todo.data
        let task_todo_editor_modal = ref(false)

        let formEditor = ref(useForm({
            id: '',
            todo_id: String(todo.id),
            name: '',
            description: '',
            deadline: null,
            status: '',
        }))
        let is_insert = ref(false)

        let todoTasksRows = ref([])
        let is_data_loading = ref(false)

        let getSubmitBtnTitle = computed(() => {
            return is_insert.value ? 'Create' : 'Update'
        });

        function loadTodoTasks() {
            let url = route('admin.todo_tasks.index', todo.id)
            is_data_loading.value = true
            axios.get(url)
                .then(({data}) => {
                    todoTasksRows.value = data.data
                    is_data_loading.value = false
                })
                .catch(e => {
                    is_data_loading.value = false
                    console.error(e)
                })
        } // loadTodoTasks() {


        function editTodoTask(todo_task_id) {
            axios.get(route('admin.todo_tasks.details', {todo_id: todo.id, todo_task_id: todo_task_id}))
                .then(({data}) => {
                    formEditor.value.id = String(data.data.id)
                    formEditor.value.todo_id = String(data.data.todo_id)
                    formEditor.value.name = data.data.name,
                        formEditor.value.description = data.data.description
                    formEditor.value.deadline = data.data.deadline
                    formEditor.value.status = data.data.status
                    task_todo_editor_modal.value = true
                    is_insert.value = false
                })
                .catch(e => {
                    console.error(e)
                })

        }

        function addTodoTask() {
            task_todo_editor_modal.value = true
            is_insert.value = true
            formEditor.value.id = ''
            formEditor.value.name = ''
            formEditor.value.description = ''
            formEditor.value.deadline = null
            formEditor.value.status = ''
        }


        function hideTaskTodoEditorModal() {
            task_todo_editor_modal.value = false
        }

        function saveTodoTask() {
            if (is_insert.value) {
                formEditor.value.post(route('admin.todo_tasks.store', formEditor.value.todo_id), {
                    preserveScroll: true,
                    onSuccess: (resp) => {
                        Swal.fire(
                            'Saved!',
                            'Task successfully added !',
                            'success'
                        )
                        task_todo_editor_modal.value = false
                        is_insert.value = false
                        loadTodoTasks()
                    },
                    onError: (e) => {
                        console.log(e)
                        Toast.fire({
                            icon: 'error',
                            title: 'Adding task error'
                        })
                    }
                })
            } else {
                formEditor.value.put(route('admin.todo_tasks.update', formEditor.value.todo_id ), {
                    preserveScroll: true,
                    onSuccess: (resp) => {
                        Swal.fire(
                            'Saved!',
                            'Task successfully updated !',
                            'success'
                        )
                        task_todo_editor_modal.value = false
                        loadTodoTasks()
                    },
                    onError: (e) => {
                        Toast.fire({
                            icon: 'error',
                            title: 'Updating task error'
                        })
                    }
                })
            }
        }

        function deleteTodoTask(todoTask) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You what to delete this task!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: settingsAppColors.confirmButtonColor,
                cancelButtonColor: settingsAppColors.cancelButtonColor,
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // console.log('deleteTodoTask todoTask::')
                    // console.log(todoTask)
                    axios.delete(route('admin.todo_tasks.destroy', todoTask.id))
                        .then(({data}) => {
                            loadTodoTasks()
                            Swal.fire(
                                'Deleted!',
                                'Task has been deleted.',
                                'success'
                            )
                        })
                        .catch(e => {
                            console.error(e)
                        })
                }
            })
        } // function deleteTodoTask(todoTask) {


        function adminTodoTasksListingOnMounted() {
            loadTodoTasks()
        }

        onMounted(adminTodoTasksListingOnMounted)

        return { // setup return

            // Listing Page state
            todo,
            todoTasksRows,
            formEditor,
            is_insert,
            is_data_loading,
            task_todo_editor_modal,

            // methods
            deleteTodoTask,
            editTodoTask,
            addTodoTask,
            hideTaskTodoEditorModal,
            saveTodoTask,
            getSubmitBtnTitle,

            // Common methods
            getHeaderIcon,
            pluralize,
            pluralize3,
            isEmpty,
            showFlashMessage,
            dateIntoDbFormat,
            formatValue,

            // Settings vars
            settingsTodoTaskStatusLabels
        }
    }, // setup() {

})
</script>
