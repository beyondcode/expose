<script setup lang="ts">
import Header from '@/components/Header.vue'
import Requests from '@/components/Requests.vue'
import { exampleRequests, exampleSubdomains, exampleUser } from './lib/devUtils';
import { Card } from './components/ui/card';

const props = defineProps<{
    pageData?: InternalDashboardPageData
}>();

const page: InternalDashboardPageData = {
    subdomains: props.pageData?.subdomains ?? exampleSubdomains(),
    user: props.pageData?.user ?? exampleUser(),
    max_logs: props.pageData?.max_logs ?? 100,
};

console.debug(page);

const requests = exampleRequests() as ExposeRequest[]
</script>

<template>
    <div class="px-4 md:px-0">
        <Header :subdomains="page.subdomains" />


        <div class="flex flex-col md:flex-row items-start max-w-7xl mx-auto mt-8 space-y-4 md:space-y-0 md:space-x-4">
            <Requests :requests="requests"/>

            <Card class="">
                {{ requests }}
            </Card>
        </div>

    </div>
</template>