<script setup lang="ts">
import Header from '@/components/Header.vue'
import Requests from '@/components/Requests.vue'
import { exampleSubdomains, exampleUser } from './lib/devUtils';
import { Card } from './components/ui/card';
import { ref } from 'vue';
import { isEmptyObject } from './lib/utils';

const props = defineProps<{
    pageData?: InternalDashboardPageData
}>();

const page: InternalDashboardPageData = {
    subdomains: props.pageData?.subdomains ?? exampleSubdomains(),
    user: props.pageData?.user ?? exampleUser(),
    max_logs: props.pageData?.max_logs ?? 100,
};

const currentLog = ref({} as ExposeRequest)

console.debug(page);

const setLog = (log: ExposeRequest) => {
    currentLog.value = log;
}
</script>

<template>
    <div class="px-4">
        <Header :subdomains="page.subdomains" />


        <div class="flex flex-col md:flex-row items-start max-w-7xl mx-auto mt-8 space-y-4 md:space-y-0 md:space-x-4">
            <Requests :maxLogs="page.max_logs" @set-log="setLog" />

            <Card class="p-4 w-full">
                <template v-if="isEmptyObject(currentLog)">
                    //
                </template>
                <template v-else>
                    {{ currentLog.id }}<br/>
                    {{ currentLog.request.uri }}
                </template>
            </Card>
        </div>

    </div>
</template>