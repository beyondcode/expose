<script setup lang="ts">
import Header from '@/components/Header.vue'
import ResponseBadge from '@/components/ui/ResponseBadge.vue'
import { exampleRequests, exampleSubdomains, exampleUser } from './lib/devUtils';
import { Card } from './components/ui/card';
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
    <div class="">
        <Header :subdomains="page.subdomains" />


        <div class="flex items-start max-w-7xl mx-auto mt-8 space-x-8">
            <div class="">
                <Card>
                    <!-- <Button>
                        Clear
                    </Button> -->
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead></TableHead>
                                <TableHead class="">
                                    URL
                                </TableHead>
                                <TableHead class="text-right">
                                    Duration
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="request in requests" :key="request.id">
                                <TableCell>
                                    <ResponseBadge :statusCode="request.response.status" />
                                </TableCell>
                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger>
                                            <TableCell class="font-medium max-w-[200px] truncate text-left">
                                                <span class="opacity-60">{{ request.request.method }}</span> <br />{{
                                                request.request.uri }}
                                            </TableCell>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            {{ request.request.uri }}
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>


                                <TableCell>
                                    {{ request.duration }}ms
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </Card>
            </div>

            <Card class="">
                {{ requests }}
            </Card>
        </div>

    </div>
</template>