<script setup lang="ts">

const props = defineProps<{
    response: ResponseData
}>()

</script>

<template>
     <div>
        {{ response }}
        {{ props }}
     </div>
</template>