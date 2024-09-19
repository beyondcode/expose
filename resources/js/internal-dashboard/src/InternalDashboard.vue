<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { ref } from 'vue';

const counter = ref(0 as number)

</script>

<template>
    <div>
        <Button @click="counter++">Hello there</Button>

        <div>
            {{ counter }}
        </div>
    </div>

</template>