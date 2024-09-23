<script setup lang="ts">
import { Button } from '../ui/button';
import { Card } from '../ui/card';
import ResponseBadge from '@/components/ui/ResponseBadge.vue'
import ReconnectingWebSocket from 'reconnecting-websocket';
import { Switch } from '@/components/ui/switch'

import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger
} from '@/components/ui/tooltip'
import { computed, onMounted, ref } from 'vue';

const props = defineProps({
    maxLogs: Number,
    search: String,
})

const emit = defineEmits(['set-log'])

const logs = ref([] as ExposeLog[]);
const highlightNextLog = ref(false as boolean); // TODO:
const followRequests = ref(true as boolean); // TODO:

onMounted(() => {
    connect();
    loadLogs();
});


const loadLogs = () => {
    fetch('/api/logs')
        .then((response) => {
            return response.json();
        })
        .then((data) => {
            logs.value = data;

            console.debug("loadLogs");
            console.debug(logs.value);
            emit('set-log', logs.value[0]);
        });
}

const connect = () => {
    console.debug("connecting to websocket:");
    console.debug(`ws://${window.location.hostname}:${window.location.port}/socket`);
    let conn = new ReconnectingWebSocket(`ws://${window.location.hostname}:${window.location.port}/socket`);

    conn.onmessage = (e) => {
        const request = JSON.parse(e.data);
        const index = logs.value.findIndex(log => log.id === request.id);
        if (index > -1) {
            logs.value[index] = request;
        } else {
            logs.value.unshift(request);
        }

        logs.value = logs.value.splice(0, props.maxLogs);

        if (highlightNextLog.value || props.maxLogs) {
            emit('set-log', logs.value[0]);

            highlightNextLog.value = false;
        }
    };
}


const filteredLogs = computed(() => {
    const searchTerm = props.search ?? '';
    
    if (searchTerm === '') {
        return logs.value;
    }

    return logs.value.filter(log => {
        return log.request.uri.indexOf(searchTerm) !== -1;
    })
})



</script>

<template>
    <Card class="min-w-[350px] w-full md:w-auto">
        <div class="flex items-center justify-between p-3 border-b">
            <div class="items-center flex space-x-2 text-sm">
                <Switch v-model:checked="followRequests" id="followRequests" />
                <label for="followRequests"
                    class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                    Follow Requests
                </label>
            </div>
            <Button @click="loadLogs" variant="outline" class="">
                Clear
            </Button>

        </div>
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead class="pr-0">Status</TableHead>
                    <TableHead class="pr-0">
                        URL
                    </TableHead>
                    <TableHead class="text-right pr-4 pl-0">
                        Duration
                    </TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow v-for="request in filteredLogs" :key="request.id" @click="emit('set-log', request)">
                    <TableCell class="pr-0">
                        <ResponseBadge
                            :statusCode="request.response && request.response.status ? request.response.status : null" />
                    </TableCell>

                    <TableCell class="text-left pr-0">
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger>
                                    <div class="md:max-w-[200px] truncate pt-0.5">
                                        <span class="opacity-60 text-xs">{{ request.request.method }}</span>
                                        {{ request.request.uri }}
                                    </div>
                                </TooltipTrigger>
                                <TooltipContent>
                                    {{ request.request.uri }}
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </TableCell>
                    <TableCell class="text-right text-xs pl-0 pr-4">
                        {{ request.duration }}ms
                    </TableCell>
                </TableRow>

                <TableRow v-if="logs.length === 0">
                    <TableCell class="text-center" colspan="3">
                        No logs
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </Card>

</template>