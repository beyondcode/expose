<script setup lang="ts">
import { Badge } from '@/components/ui/badge'
import { computed } from 'vue';

const props = defineProps<{
  statusCode: Number
}>()

const badgeColor = computed(() => {
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
        <Badge :class="badgeColor">{{ statusCode }}</Badge>
    </div>
</template>