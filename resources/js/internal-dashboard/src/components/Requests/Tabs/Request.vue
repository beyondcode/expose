<script setup lang="ts">

const props = defineProps<{
    request: RequestData
}>()

</script>

<template>
     <div>
        {{ request }}
     </div>
</template>