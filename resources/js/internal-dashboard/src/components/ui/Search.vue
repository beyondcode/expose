<script setup lang="ts">
import { ref, defineEmits, defineProps } from 'vue';
import { Input } from '@/components/ui/input';

defineProps<{ modelValue: string }>();

const emit = defineEmits(['update:modelValue']);

const onInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    emit('update:modelValue', target.value);
};
</script>

<template>
    <div>
        <Input type="search" :modelValue="modelValue" @input="onInput" placeholder="Search..."
            class="md:w-[100px] lg:w-[300px]" />
    </div>
</template>