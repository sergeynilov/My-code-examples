<template>

    <div class="flex justify-center">
        <div class="block p-6 rounded-lg shadow-lg bg-white max-w-lg">
            <h5 class="text-gray-900 text-xl leading-tight font-medium mb-2">{{ todo.name }}</h5>
            <h5 class="text-gray-900 text-sm leading-tight font-medium mb-2">
                By {{ todo.user.name }} on {{ todo.created_at_formatted }}, <strong>{{ todo.completed_label }}</strong>
            </h5>
            <p class="text-gray-700 text-base mb-4" v-html="todo.description">
            </p>

            <div class="flex justify-center">
                <div class="bg-white rounded-lg border border-gray-200 w-96 text-gray-900">
                    <template v-for="(nextTodoTask, index) in todo.todoTasks" :key="nextTodoTask.id">

                        <div class="flex justify-center">
                            <ul class="bg-white rounded-lg border border-gray-200 w-96 text-gray-900">
                                <li
                                    v-if="nextTodoTask.status === 'D'"
                                    class="px-6 py-2 border-b border-gray-200 w-full bg-gray-200 rounded-t-lg text-gray-400 cursor-default"
                                >
                                    <p class="text-gray-700 text-base mb-4">
                                        <i :class="getHeaderIcon('info')" :title="nextTodoTask.description"
                                           class="action_icon icon_right_text_margin"></i>
                                        {{ nextTodoTask.name }}
                                    </p>

                                    <p class="text-gray-700 text-base mb-4">
                                        <strong>{{ getDictionaryLabel(nextTodoTask.status, settingsTodoTaskStatusLabels ) }}</strong>, created at {{
                                            nextTodoTask.created_at_formatted
                                        }}
                                    </p>
                                </li>

                                <li
                                    v-if="nextTodoTask.status === 'C'"
                                    class="px-6 py-2 border-b border-gray-200 w-full bg-blue-200 text-white">
                                    <p class="text-gray-700 text-base mb-4">
                                        <i :class="getHeaderIcon('info')" :title="nextTodoTask.description"
                                           class="action_icon icon_right_text_margin"></i>
                                        {{ nextTodoTask.name }}
                                    </p>

                                    <p class="text-gray-700 text-base mb-4">
                                        <strong>{{ nextTodoTask.status_label }}</strong>, created at {{
                                            nextTodoTask.created_at_formatted
                                        }}
                                    </p>

                                    <JetSecondaryButton @click="uncompleteTodoTask(todo.id, nextTodoTask.id, index)">
                                        Uncomplete
                                    </JetSecondaryButton>
                                    <JetSecondaryButton @click="disableTodoTask(todo.id, nextTodoTask.id, index)" class="ml-4">
                                        Disable
                                    </JetSecondaryButton>
                                    <div v-show="is_data_loading" class="ml-2 form_processing"></div>
                                </li>

                                <li
                                    v-if="nextTodoTask.status === 'U'"
                                    class="px-6 py-2 border-b border-gray-200 w-full">
                                    <p class="text-gray-700 text-base mb-4">
                                        <i :class="getHeaderIcon('info')" :title="nextTodoTask.description"
                                           class="action_icon icon_right_text_margin"></i>
                                        {{ nextTodoTask.name }}
                                    </p>

                                    <p class="text-gray-700 text-base mb-4">
                                        <strong>{{ nextTodoTask.status_label }}</strong>, created at {{
                                            nextTodoTask.created_at_formatted
                                        }}
                                    </p>

                                    <p class="text-gray-700 text-base mb-4" v-if="nextTodoTask.is_task_expired">
                                        <i :class="getHeaderIcon('error')"
                                           title="You can not make any operations on this task"
                                           class="action_icon icon_right_text_margin"></i>
                                        Task is expired
                                    </p>
                                    <p class="text-gray-700 text-base mb-4" v-if="!nextTodoTask.is_task_expired">
                                        Deadline at {{ nextTodoTask.deadline_formatted }}
                                    </p>

                                    <JetButton @click="completeTodoTask(todo.id, nextTodoTask.id, index)"
                                               :class="{ 'opacity-25': is_data_loading }" :disabled="is_data_loading"
                                               v-if="!nextTodoTask.is_task_expired">
                                        Complete
                                    </JetButton>
                                    <JetSecondaryButton @click="disableTodoTask(todo.id, nextTodoTask.id, index)"
                                                        class="ml-4">
                                        Disable
                                    </JetSecondaryButton>

                                </li>
                            </ul>
                        </div>

                    </template>

                </div>
            </div>


        </div>
    </div>

</template>


<script>

import {
    getHeaderIcon,
    showRTE,
    getDictionaryLabel
} from '@/commonFuncs'
import {ref, computed, onMounted} from 'vue'
import JetButton from '@/Jetstream/Button.vue'
import JetInput from '@/Jetstream/Input.vue'
import JetLabel from '@/Jetstream/Label.vue'
import JetCheckbox from '@/Jetstream/Checkbox.vue'
import JetInputError from '@/Jetstream/InputError.vue'
import JetSecondaryButton from '@/Jetstream/SecondaryButton.vue'
import {useForm} from "@inertiajs/inertia-vue3";
import {Inertia} from '@inertiajs/inertia'

import {
    settingsAppColors, settingsTodoTaskStatusLabels
} from '@/app.settings.js'

import {defineComponent} from 'vue'

export default defineComponent({
    props: {
        todo: {
            type: Object,
            required: true,
        },
    },

    name: 'managerTodoDetails',
    components: {
        JetButton,
        JetSecondaryButton,
        JetInput,
        JetLabel,
        JetInputError,
        JetCheckbox,
    },
    setup(props) {
        let todo = props.todo
        let is_data_loading = ref(false)

        function disableTodoTask(todo_id, todo_task_id, index) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You what to disable this task!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: settingsAppColors.confirmButtonColor,
                cancelButtonColor: settingsAppColors.cancelButtonColor,
                confirmButtonText: 'Yes, disable!'
            }).then((result) => {
                if (result.isConfirmed) {
                    is_data_loading.value = true
                    axios.put(route('manager.todo_task.disable', {todo_id: todo_id, todo_task_id: todo_task_id}))
                        .then(({data}) => {
                            Toast.fire({
                                icon: 'success',
                                title: 'Task was disabled successfully'
                            })

                            todo.todoTasks[index].status = 'D'
                            window.emitter.emit('reloadTodoTasksEvent', { // Reload parent Todos list if need
                                todo_id: 'todo_id'
                            })
                            is_data_loading.value = false
                        })
                        .catch(e => {
                            console.error(e)
                            showRTE(e)
                            is_data_loading.value = false
                        })
                }
            })
        } // disableTodoTask() {


        function uncompleteTodoTask(todo_id, todo_task_id, index) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You what to uncomplete this task!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: settingsAppColors.confirmButtonColor,
                cancelButtonColor: settingsAppColors.cancelButtonColor,
                confirmButtonText: 'Yes, uncomplete!'
            }).then((result) => {
                if (result.isConfirmed) {
                    is_data_loading.value = true
                    axios.put(route('manager.todo_task.uncomplete', {todo_id: todo_id, todo_task_id: todo_task_id}))
                        .then(({data}) => {
                            Toast.fire({
                                icon: 'success',
                                title: 'Task was uncompleted successfully'
                            })

                            todo.todoTasks[index].status = 'U'
                            window.emitter.emit('reloadTodoTasksEvent', { // Reload parent Todos list if need
                                todo_id: 'todo_id'
                            })
                            is_data_loading.value = false
                        })
                        .catch(e => {
                            console.error(e)
                            showRTE(e)
                            is_data_loading.value = false
                        })
                }
            })
        } // uncompleteTodoTask() {


        function completeTodoTask(todo_id, todo_task_id, index) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You what to complete this task!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: settingsAppColors.confirmButtonColor,
                cancelButtonColor: settingsAppColors.cancelButtonColor,
                confirmButtonText: 'Yes, complete!'
            }).then((result) => {
                if (result.isConfirmed) {
                    is_data_loading.value = true
                    axios.put(route('manager.todo_task.complete', {todo_id: todo_id, todo_task_id: todo_task_id}))
                        .then(({data}) => {
                            Toast.fire({
                                icon: 'success',
                                title: 'Task was completed successfully'
                            })

                            todo.todoTasks[index].status = 'C'
                            window.emitter.emit('reloadTodoTasksEvent', { // Reload parent Todos list if need
                                todo_id: 'todo_id'
                            })
                            is_data_loading.value = false
                        })
                        .catch(e => {
                            console.error(e)
                            showRTE(e)
                            is_data_loading.value = false
                        })
                }
            })
        } // completeTodoTask() {


        function managerTodoDetailsOnMounted() {
        } // function managerTodoDetailsOnMounted() {

        onMounted(managerTodoDetailsOnMounted)

        return { // setup return
            // Listing Page state
            todo,
            is_data_loading,

            // Page actions
            uncompleteTodoTask,
            disableTodoTask,
            completeTodoTask,

            // Settings vars
            settingsTodoTaskStatusLabels,

            // Common methods
            getHeaderIcon,
            getDictionaryLabel,
            showRTE
        }
    }, // setup() {

})
</script>

