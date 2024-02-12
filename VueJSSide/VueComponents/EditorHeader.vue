<template>
	<div class="column_content_centered p-2 m-2">

		<div class="row_content_centered">

			<div v-show="showLoadingImage" class="loading"></div>
			<div style="display:flex; flex : 1 0 310px; align-self: center;" class="text-primary p-0" v-show="!showLoadingImage">
				<h3 style="display: flex; flex-wrap: nowrap" >
					<i v-show="headerIcon" :class="headerIcon+' m-0 mr-1 p-0'"></i>
          <span v-html="headerTitle"></span>
				</h3>
			</div>

			<div v-if="rightIcon!= ''" style="display:flex; align-self: flex-end;" class="mb-2">
				<i :class="'i_link '+rightIconClass" title="Refresh" @click="triggerEditorHeaderRightButtonClickedEvent"></i>
			</div>

		</div>

		<div v-if="message" class="text-danger m-2">
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
            headerTitle: {
                type: String,
                required: true,
                default: ''
            },
            headerIcon: {
                type: String,
                required: false,
                default: ''
            },
            showLoadingImage: {
                type: Boolean,
                required: true,
                default: true
            },

            rightIcon: {
                type: String,
                required: false,
                default: ''
            },
            parentComponentKey: {
                type: String,
                required: false,
                default: ''
            },

        },

        methods: {

            triggerEditorHeaderRightButtonClickedEvent() {
                bus.$emit('editorHeaderRightButtonClickedEvent', this.parentComponentKey)
            }

        },
        computed: {

            rightIconClass() {
                return this.rightIcon + ' a_link p-1 m-0'
            },

        }, // computed: {

    }
</script>
