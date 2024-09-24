<script setup lang="ts">
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import Request from './Tabs/Request.vue';
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
import { Switch } from '@/components/ui/switch'
import { ref } from 'vue';
import { copyToClipboard } from '@/lib/utils';

defineProps<{
    log: ExposeLog
}>()

const sideBySide = ref(true as boolean)
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
                                    {{ log.request.uri }}
                                </div>
                            </TooltipTrigger>
                            <TooltipContent>
                                {{ log.request.uri }}
                            </TooltipContent>
                        </Tooltip>
                    </TooltipProvider>
                    <div class="flex items-center justify-end w-full mb-2 sm:mb-0 sm:w-auto space-x-2">
                        <Button variant="outline">Replay</Button>
                        <Button variant="outline" @click="copyToClipboard(log.request.curl)">
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
        <div class="w-full mt-16">
            <div class="flex items-center justify-end space-x-2 mb-4">
                <Switch v-model:checked="sideBySide" id="tabs" />
                <label for="tabs"
                    class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                    Tabs
                </label>
            </div>
            <Tabs v-if="sideBySide" default-value="request" class="">
                <TabsList class="w-full">
                    <TabsTrigger value="request" class="w-full">
                        Request
                    </TabsTrigger>
                    <TabsTrigger value="response" class="w-full">
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

            <div v-else>
                <div class="text-sm font-medium rounded-sm border-4 border-gray-100 px-3 py-1.5 text-center">
                    Request
                </div>
                <Request :request="log.request" />
                <div class="text-sm font-medium rounded-sm border-4 border-gray-100 px-3 py-1.5 text-center">
                    Response
                </div>
                <Response :response="log.response" />
            </div>
        </div>
    </div>
</template>