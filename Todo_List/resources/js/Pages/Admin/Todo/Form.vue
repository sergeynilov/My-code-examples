<template>
    <form @submit.prevent="saveTodo">

        <div class="mb-4">
            <JetLabel for="id" value="Id"/>
            <JetInput
                id="id"
                v-model="formEditor.id"
                type="text"
                class="mt-1 block w-full"
                disabled
            />
            <JetInputError :message="formEditor.errors.id" class="mt-2"/>
        </div>

        <div class="mb-4">
            <JetLabel for="assigned_to_user" value="Assigned to user"/>
            <JetInput
                id="assigned_to_user"
                v-model="todo.user.name"
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
            <JetLabel for="completed" value="Completed"/>
            <JetCheckbox v-model:checked="formEditor.completed" />
            <JetInputError :message="formEditor.errors.completed" class="mt-2"/>
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

        <div class="mb-4">
            <JetLabel for="created_at_formatted" value="Created at"/>
            <JetInput
                id="created_at_formatted"
                v-model="todo.created_at_formatted"
                type="text"
                class="mt-1 block w-full"
                disabled
            />
        </div>

        <div class="mb-4" v-if="formEditor.updated_at_formatted">
            <JetLabel for="updated_at_formatted" value="Updated at"/>
            <JetInput
                id="updated_at_formatted"
                v-model="todo.updated_at_formatted"
                type="text"
                class="mt-1 block w-full"
                disabled
            />
        </div>


        <div class="mt-8 mb-4 flex flex-nowrap">

            <div class="flex flex-grow pl-2">
                <JetSecondaryButton @click="cancelTodoEditor">
                    Cancel
                </JetSecondaryButton>
                <JetButton class="ml-4" :class="{ 'opacity-25': formEditor.processing }" :disabled="formEditor.processing">
                    Update
                </JetButton>
                <div v-show="formEditor.processing" class="ml-2 form_processing"></div>
            </div>
            <div class="w-12 align-items-end justify-end align-top mr-2">
                <JetSecondaryButton @click="deleteTodo">
                    <i :class="getHeaderIcon('remove')"
                       class="action_icon icon_right_text_margin"></i>
                </JetSecondaryButton>
            </div>

        </div>

    </form>


</template>


<script>

import {
    getHeaderIcon
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
    settingsAppColors
} from '@/app.settings.js'

// import ListingHeader from '@/components/ListingHeader.vue'
import {defineComponent} from 'vue'

export default defineComponent({
    props: {
        todo: {
            type: Object,
            required: true,
        },
    },

    name: 'adminTodoForm',
    components: {
        JetButton,
        JetSecondaryButton,
        JetInput,
        JetLabel,
        JetInputError,
        JetCheckbox,
    },
    setup(props) {
        let todo = props.todo.data

        let formEditor = ref(useForm({
            id: String(todo.id),
            user_id: String(todo.user_id),
            name: todo.name,
            completed: Boolean(todo.completed),
            description: todo.description,
        }))


        function cancelTodoEditor() {
            Inertia.visit(route('admin.todos.index'), {method: 'get'});
        }

        function saveTodo() {
            formEditor.value.patch(route('admin.todos.update', formEditor.value.id ), {
                preserveScroll: true,
                onSuccess:(resp) => {
                    Toast.fire({
                        icon: 'success',
                        title: 'Todo was updated successfully'
                    })
                },

                onError: (e) => {
                    console.error(e)
                    Toast.fire({
                        icon: 'error',
                        title: 'Updating todo error!'
                    })
                }
            })
        } // saveTodo() {


        function deleteTodo() {
            Swal.fire({
                title: 'Are you sure?',
                text: "You what to delete this Todo List with all related data!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: settingsAppColors.confirmButtonColor,
                cancelButtonColor: settingsAppColors.cancelButtonColor,
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    formEditor.value.delete(route('admin.todos.destroy', formEditor.value.id), {
                        preserveScroll: true,
                        onSuccess: (data) => {
                        }
                    })
                }
            })
        } // deleteTodo() {

        function adminTodoFormOnMounted() {
        } // function adminTodoFormOnMounted() {

        onMounted(adminTodoFormOnMounted)

        return { // setup return
            // Listing Page state
            todo,
            formEditor,

            // Page actions
            saveTodo,
            cancelTodoEditor,
            deleteTodo,

            // Settings vars

            // Common methods
            getHeaderIcon,

        }
    }, // setup() {

})
</script>

