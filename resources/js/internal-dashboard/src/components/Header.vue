<script setup lang="ts">
import { useColorMode } from '@vueuse/core'
import Search from '@/components/ui/Search.vue'
import { Icon } from '@iconify/vue'
import { Button } from '@/components/ui/button'
import { ref } from 'vue';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger
} from '@/components/ui/tooltip'

defineProps<{
    subdomains: string[]
}>()


const mode = useColorMode()

const followRequests = ref(true as boolean)

const toggleAppearance = () => {
    if (mode.value === 'light') {
        mode.value = 'dark'
    } else {
        mode.value = 'light'
    }
}

const toggleFollowRequests = () => {
    followRequests.value = !followRequests.value
}


</script>


<template>
    <div>
        <div class="max-w-7xl mx-auto px-4 py-6 flex items-center justify-between">
            <div>
                <a href="https://expose.dev" target="_blank" class="inline-flex items-center self-start">
                    <img src="https://beyondco.de/apps/icons/expose.png" class="h-10">
                    <p class="ml-4 text-2xl tracking-tight font-bold">Dashboard</p>
                </a>

                <div class="text-sm mt-1">
                    <a href="{{ subdomains[0] }}" class="font-medium">
                        {{ subdomains[0].substring(subdomains[0].lastIndexOf("/") + 1) }}
                    </a>
                    <TooltipProvider>
                        <Tooltip>
                            <TooltipTrigger>
                                <span v-if="subdomains.length > 1" class="opacity-50 ">
                                    &nbsp;and {{ subdomains.length - 1 }} more
                                </span>
                            </TooltipTrigger>
                            <TooltipContent>
                                <div class="font-medium">Waiting for requests on</div>
                                <ul class="list-disc ml-4 space-y-1 mb-0.5 mt-1">
                                    <li v-for="subdomain in subdomains" :key="subdomain" :href="subdomain">
                                        <a href="{{ subdomain }}" class="">
                                            {{ subdomain.substring(subdomain.lastIndexOf("/") + 1) }}
                                        </a>
                                    </li>
                                </ul>
                            </TooltipContent>
                        </Tooltip>
                    </TooltipProvider>


                </div>
            </div>

            <div class="flex items-center space-x-4">
                <Search />
                <TooltipProvider>
                    <Tooltip>
                        <TooltipTrigger>
                            <Button @click="toggleFollowRequests" variant="outline"
                                :class="{ 'border-pink-600 text-pink-600': followRequests }">
                                <Icon icon="radix-icons:enter" class="h-[1.2rem] w-[1.2rem] " />
                                <span class="sr-only">Toggle follow requests</span>
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                            <p>Follow Requests: {{ followRequests }}</p>
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>

                <TooltipProvider>
                    <Tooltip>
                        <TooltipTrigger>
                            <Button @click="toggleAppearance" variant="outline">
                                <Icon icon="radix-icons:moon"
                                    class="h-[1.2rem] w-[1.2rem] rotate-0 scale-100 transition-all dark:-rotate-90 dark:scale-0" />
                                <Icon icon="radix-icons:sun"
                                    class="absolute h-[1.2rem] w-[1.2rem] rotate-90 scale-0 transition-all dark:rotate-0 dark:scale-100" />
                                <span class="sr-only">Toggle Appearance</span>
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                            <p>Toggle Appearance</p>
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>

            </div>

        </div>
        <div class="border-b"></div>
    </div>
</template>