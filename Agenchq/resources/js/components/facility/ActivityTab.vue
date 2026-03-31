<template>
    <div class="activity-tab">
        <div class="empty-state">
            <i class="pi pi-clock text-4xl text-gray-400 mb-4"></i>
            <p class="text-gray-500">Activity tracking coming soon.</p>
            <p class="text-sm text-gray-400 mt-2">
                View contract uploads, billing changes, and other facility events.
            </p>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    facility: {
        type: Object,
        default: () => ({}),
    },
});
</script>

<style scoped>
.activity-tab {
    padding: 0.5rem;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
}
</style>
