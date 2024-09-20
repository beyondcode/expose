<script setup lang="ts">
import { Button } from './ui/button';
import { Checkbox } from './ui/checkbox';
import { Card } from './ui/card';
import ResponseBadge from '@/components/ui/ResponseBadge.vue'
import ReconnectingWebSocket from 'reconnecting-websocket';

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
import { onMounted, ref } from 'vue';

const props = defineProps({
    maxLogs: Number
})

const emit = defineEmits(['set-log'])

const logs = ref([] as ExposeRequest[]);
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



</script>

<template>
    <Card class="w-full md:w-2/5 md:max-w-[400px]">
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
                <TableRow v-for="request in logs" :key="request.id" @click="emit('set-log', request)">
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