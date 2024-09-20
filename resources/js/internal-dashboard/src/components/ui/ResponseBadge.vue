<script setup lang="ts">
import { Badge } from '@/components/ui/badge'
import { computed } from 'vue';

const props = defineProps<{
    statusCode: Number | null
}>()

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

</script>

<template>
    <div>
        <Badge class="font-mono font-normal" :class="badgeColor">
            <span v-if="statusCode">{{ statusCode }}</span>
            <span v-else class="opacity-0">999</span>
        </Badge>
    </div>
</template>