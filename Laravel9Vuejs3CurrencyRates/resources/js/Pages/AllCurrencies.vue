<template>
    <frontend-layout>
        <TopCurrenciesBlock :show_only_top_currencies="false"></TopCurrenciesBlock>
    </frontend-layout>
</template>


<script>
import FrontendLayout from '@/Layouts/FrontendLayout'
import TopCurrenciesBlock from '@/Pages/Frontend/Home/TopCurrenciesBlock.vue'
import { defineComponent } from 'vue'

export default defineComponent({
    name: 'HomePage',
    components: {
        FrontendLayout,
        TopCurrenciesBlock,
    },
    setup(props) {
        return { // setup return
        }
    }, // setup() {

})
</script>
