<script setup lang="ts">
import {
    Table,
    TableBody,
    TableCell,
    TableRow,
} from '@/components/ui/table'
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion'
import { Button } from '@/components/ui/button'
import { Icon } from '@iconify/vue'
import { JsonViewer } from "vue3-json-viewer"
import "vue3-json-viewer/dist/index.css";
import { bodyIsJson, copyToClipboard, toPhpArray } from '@/lib/utils'


const props = defineProps<{
    request: RequestData
}>()

</script>

<template>
    <div class="max-w-full">
        <div v-if="Object.keys(request.query).length > 0" class="mb-4">
            <div class="pt-4 font-medium text-base mb-2">Query parameters</div>
            <div class="flex justify-end">
                <Button @click="copyToClipboard(toPhpArray(request.query, 'queryParameters'))" variant="outline">
                    <Icon icon="radix-icons:clipboard-copy" class="h-4 w-4 mr-2" />
                    Copy as PHP array
                </Button>
            </div>
            <Table class="max-w-full">
                <TableBody>
                    <TableRow v-for="[key, value] of Object.entries(request.query)" :key="key">
                        <TableCell class="w-2/5">
                            {{ key }}
                        </TableCell>

                        <TableCell class="w-3/5 break-all">
                            {{ value }}
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
        <Accordion type="single" collapsible default-value="item-1">
            <AccordionItem value="item-1">
                <AccordionTrigger>
                    <div class="flex relative z-10 justify-between items-center w-full pr-4">
                        Headers
                    </div>
                </AccordionTrigger>
                <AccordionContent>
                    <div class="flex justify-end">
                        <Button @click="copyToClipboard(toPhpArray(request.headers, 'headers'))" variant="outline">
                            <Icon icon="radix-icons:clipboard-copy" class="h-4 w-4 mr-2" />
                            Copy as PHP array
                        </Button>
                    </div>
                    <Table class="max-w-full">
                        <TableBody>
                            <TableRow v-for="[key, value] of Object.entries(request.headers)" :key="key">
                                <TableCell class="w-2/5">
                                    {{ key }}
                                </TableCell>

                                <TableCell class="w-3/5 break-all">
                                    {{ value }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                </AccordionContent>
            </AccordionItem>
        </Accordion>

        <div class="mt-4">
            <div class="pt-4 font-medium text-base">Body</div>


            <div v-if="request.body === null || request.body === undefined || request.body === ''">
                <span class="text-sm opacity-75 font-mono">Request body is empty.</span>
            </div>

            <div v-else>
                <div class="flex justify-end">
                    <Button @click="copyToClipboard(request.body)" variant="outline">
                        <Icon icon="radix-icons:clipboard-copy" class="h-4 w-4 mr-2" />
                        Copy
                    </Button>
                </div>
                <JsonViewer v-if="bodyIsJson(request)" :expand-depth="2" :value="JSON.parse(request.body ?? '')" />
                <pre v-else class="p-6 prettyprint break-all whitespace-pre-wrap">{{ request.body ?? '' }}
            </pre>
            </div>
        </div>
    </div>
</template>