<script setup lang="ts">
import { Card } from './ui/card';
import ResponseBadge from '@/components/ui/ResponseBadge.vue'
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

defineProps(["requests"])

</script>

<template>
    <Card class="w-full">
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
                <TableRow v-for="request in requests" :key="request.id">
                    <TableCell class="pr-0">
                        <ResponseBadge :statusCode="request.response.status" />
                    </TableCell>

                    <TableCell class="text-left pr-0">
                        <TooltipProvider class="h-full">
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
            </TableBody>
        </Table>
    </Card>

</template>