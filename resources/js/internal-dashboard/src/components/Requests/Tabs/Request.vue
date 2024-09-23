<script setup lang="ts">
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion'
import { Button } from '@/components/ui/button'
import { Icon } from '@iconify/vue'
import { JsonViewer } from "vue3-json-viewer"
import "vue3-json-viewer/dist/index.css";


const props = defineProps<{
    request: RequestData
}>()

const requestIsJson = () => {
    if (!props.request || !props.request.headers || props.request.headers['Content-Type'] === null) {
        return false;
    }

    const contentType = props.request.headers['Content-Type'];
    let hasContentType = contentType ? /application\/json/g.test(contentType) : false;
    try {
        if (props.request.body) {
            JSON.parse(props.request.body);
        }
        return hasContentType;
    } catch (e) {
        return false;
    }
}

</script>

<template>
    <div class="max-w-full">
        <Accordion type="single" collapsible default-value="item-1">
            <AccordionItem value="item-1">
                <AccordionTrigger>
                    <div class="flex justify-between items-center w-full pr-4">
                        Headers
                        <Button variant="outline">
                            <Icon icon="radix-icons:clipboard-copy" class="h-4 w-4 mr-2" />
                            PHP array
                        </Button>
                    </div>
                </AccordionTrigger>
                <AccordionContent>
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
                <JsonViewer v-if="requestIsJson()" :expand-depth="2" :value="JSON.parse(request.body ?? '')" />
                <pre v-else class="p-6 prettyprint break-all whitespace-pre-wrap">{{ request.body ?? '' }}
            </pre>
            </div>
        </div>
    </div>
</template>