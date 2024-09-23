<script setup lang="ts">

const props = defineProps<{
    response: ResponseData
}>()

</script>

<template>
     <div>
        {{ response }}
     </div>
</template>