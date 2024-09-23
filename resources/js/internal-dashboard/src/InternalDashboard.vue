<script setup lang="ts">
import Header from '@/components/Header.vue'
import Logs from '@/components/Requests/Logs.vue'
import LogDetail from '@/components/Requests/LogDetail.vue'
import { exampleSubdomains, exampleUser } from './lib/devUtils';
import { Card } from './components/ui/card';
import { ref } from 'vue';
import { isEmptyObject } from './lib/utils';
import EmptyState from './components/Requests/EmptyState.vue';

const props = defineProps<{
    pageData?: InternalDashboardPageData
}>();

const page: InternalDashboardPageData = {
    subdomains: props.pageData?.subdomains ?? exampleSubdomains(),
    user: props.pageData?.user ?? exampleUser(),
    max_logs: props.pageData?.max_logs ?? 100,
};

const currentLog = ref({} as ExposeLog)
const search = ref('' as string)

console.debug(page);

const setLog = (log: ExposeLog) => {
    currentLog.value = log;
}
</script>

<template>
    <div class="px-4">
        <Header :subdomains="page.subdomains" @search-updated="search = $event" />


        <div class="flex flex-col md:flex-row items-start max-w-7xl mx-auto mt-8 space-y-4 md:space-y-0 md:space-x-4">
            <Logs :maxLogs="page.max_logs" :search="search" @set-log="setLog" />

            <Card class="p-4 w-full">
                <EmptyState v-if="isEmptyObject(currentLog)" />
                <LogDetail v-else :log="currentLog" />
            </Card>
        </div>

    </div>
</template>