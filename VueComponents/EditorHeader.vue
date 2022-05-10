<template>
	<div class="column_content_centered p-2 m-2">

		<div class="row_content_centered">

			<div v-show="show_loading_image" class="loading"></div>
			<div style="display:flex; flex : 1 0 310px; align-self: center; border: 0px dotted red; " class="text-primary p-0" v-show="!show_loading_image">
				<h3 style="display: flex; flex-wrap: nowrap" >
					<i v-show="header_icon" :class="header_icon+' m-0 mr-1 p-0'"></i>
                    <span v-html="header_title"></span>
				</h3>
			</div>

			<div v-if="right_icon!= ''" style="display:flex; align-self: flex-end; border: 0px dotted blue; " class="mb-2">
				<i :class="'i_link '+right_icon_class" title="Refresh" @click="triggerEditorHeaderRightButtonClickedEvent"></i>
			</div>

		</div>

		<div v-if="message" class="text-danger m-2" style="border: 0px dotted grey; ">
			<center><strong>{{ message }}</strong></center>
		</div>

	</div>

</template>

<script>

    import { bus } from '../main'
    import appMixin from '@/appMixin'

    export default {

        mixins: [appMixin],

        props: {
            message: {
                required: false,
                default: ''
            },
            header_title: {
                type: String,
                required: true,
                default: ''
            },
            header_icon: {
                type: String,
                required: false,
                default: ''
            },
            show_loading_image: {
                type: Boolean,
                required: true,
                default: true
            },

            right_icon: {
                type: String,
                required: false,
                default: ''
            },
            parent_component_key: {
                type: String,
                required: false,
                default: ''
            },

        },

        methods: {

            triggerEditorHeaderRightButtonClickedEvent() {
                bus.$emit('editorHeaderRightButtonClickedEvent', this.parent_component_key)
            }

        },
        computed: {

            right_icon_class() {
                return this.right_icon + ' a_link p-1 m-0'
            },

        }, // computed: {

    }
</script>
