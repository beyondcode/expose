<script setup lang="ts">
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import Request from './Tabs/request.vue';
import Response from './Tabs/Response.vue';
import ResponseBadge from '../ui/ResponseBadge.vue';
import { Button } from '../ui/button';
import { Icon } from '@iconify/vue'
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger
} from '@/components/ui/tooltip'


const props = defineProps<{
    log: ExposeLog
}>()

</script>

<template>
    <div>
        <div class="flex flex-col md:flex-row items-start justify-between">
            <div class="w-full">
                <div class="flex flex-col-reverse items-start sm:flex-row sm:items-center sm:justify-between">

                    <TooltipProvider>
                        <Tooltip>
                            <TooltipTrigger>
                                <div class="text-lg font-medium truncate pt-0.5">
                                    <span class="opacity-60">{{ log.request.method }}</span>
                                    {{ log.request.uri }}/very/long/request/indeed/yes
                                </div>
                            </TooltipTrigger>
                            <TooltipContent>
                                {{ log.request.uri }}
                            </TooltipContent>
                        </Tooltip>
                    </TooltipProvider>
                    <div class="flex items-center justify-end w-full mb-2 sm:mb-0 sm:w-auto space-x-2">
                        <Button variant="outline">Replay</Button>
                        <Button variant="outline">
                            <Icon icon="radix-icons:clipboard-copy" class="h-4 w-4 mr-2" />
                            cURL
                        </Button>
                    </div>
                </div>
                <div class="flex items-end justify-between mt-2">
                    <ResponseBadge :status-code="log.response.status" :reason="log.response.reason" />

                    <div class="opacity-75 text-sm">
                        Received at <span class="font-medium">{{ log.performed_at }}</span>
                    </div>
                </div>
            </div>
        </div>
        <Tabs default-value="request" class="w-[400px] mt-16">
            <TabsList>
                <TabsTrigger value="request">
                    Request
                </TabsTrigger>
                <TabsTrigger value="response">
                    Response
                </TabsTrigger>
            </TabsList>
            <TabsContent value="request">
                <!-- <Request :request="log.request" /> -->
            </TabsContent>
            <TabsContent value="response">
                <!-- <Response :response="log.response" /> -->
            </TabsContent>
        </Tabs>
    </div>
</template>