<script setup lang="ts">
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import Request from './Tabs/request.vue';
import Response from './Tabs/Response.vue';
import ResponseBadge from '../ui/ResponseBadge.vue';


const props = defineProps<{
    log: ExposeLog
}>()

</script>

<template>
    <div>
        <div class="flex items-start justify-between">
            <div class="">
                <div class="flex items-center space-x-4">
                    <ResponseBadge :status-code="log.response.status" size="sm"/>
                    {{ log.request.method }} {{ log.request.uri }}
                </div>
                <div>
                    Received at {{ log.performed_at }}
                </div>
            </div>
        </div>
        <Tabs default-value="request" class="w-[400px]">
            <TabsList>
                <TabsTrigger value="request">
                    Request
                </TabsTrigger>
                <TabsTrigger value="response">
                    Response
                </TabsTrigger>
            </TabsList>
            <TabsContent value="request">
                <Request :request="log.request" />
            </TabsContent>
            <TabsContent value="response">
                <Response :response="log.response" />
            </TabsContent>
        </Tabs>
    </div>
</template>