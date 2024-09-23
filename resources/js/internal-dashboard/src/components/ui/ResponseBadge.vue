<script setup lang="ts">
import { Badge } from '@/components/ui/badge'
import { computed } from 'vue';

const props = withDefaults(defineProps<{
    statusCode: number | null,
    size?: string | null
}>(), {
    size: 'xs'
})

const badgeColor = computed(() => {
    if (props.statusCode === null) {
        return 'bg-gray-100 dark:bg-gray-800 animate-pulse';
    }

    const startsWith = props.statusCode.toString().charAt(0);

    switch (startsWith) {
        case '2':
            return 'bg-green-500'
        case '3':
            return 'bg-yellow-500'
        case '4':
            return 'bg-orange-500'
        case '5':
            return 'bg-red-500'
        default:
            return 'bg-gray-500'
    }
})

const badgeSize = computed(() => {
    switch (props.size) {
        case 'base':
            return 'text-base'
        case 'sm':
            return 'text-sm'
        default:
            return 'text-xs'
    }
})

</script>

<template>
    <div>
        <Badge class="font-mono" :class="[badgeColor, badgeSize]">
            <span v-if="statusCode">{{ statusCode }}</span>
            <span v-else class="opacity-0">999</span>
        </Badge>
    </div>
</template>