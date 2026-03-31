<template>
    <div class="contacts-tab">
        <div v-if="contacts.length === 0" class="empty-state">
            <i class="pi pi-users text-4xl text-gray-400 mb-4"></i>
            <p class="text-gray-500">No contacts configured for this facility.</p>
        </div>
        
        <div v-else class="contacts-list">
            <Card v-for="contact in contacts" :key="contact.type" class="contact-card mb-4">
                <template #content>
                    <div class="flex items-start justify-between">
                        <div class="contact-info">
                            <div class="contact-name font-semibold text-lg">
                                {{ contact.name || 'Primary Contact' }}
                            </div>
                            <div class="contact-type text-sm text-gray-500 capitalize">
                                {{ contact.type }} Contact
                            </div>
                        </div>
                        <Tag :value="contact.type" class="capitalize" />
                    </div>
                    
                    <div class="contact-details mt-4 space-y-2">
                        <div v-if="contact.email" class="flex items-center gap-2">
                            <i class="pi pi-envelope text-gray-400"></i>
                            <a :href="`mailto:${contact.email}`" class="text-blue-600 hover:underline">
                                {{ contact.email }}
                            </a>
                        </div>
                        <div v-if="contact.phone" class="flex items-center gap-2">
                            <i class="pi pi-phone text-gray-400"></i>
                            <a :href="`tel:${contact.phone}`" class="text-blue-600 hover:underline">
                                {{ contact.phone }}
                            </a>
                        </div>
                    </div>
                </template>
            </Card>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import Card from 'primevue/card';
import Tag from 'primevue/tag';

const props = defineProps({
    facility: {
        type: Object,
        default: () => ({}),
    },
});

const contacts = computed(() => props.facility?.contacts || []);
</script>

<style scoped>
.contacts-tab {
    padding: 0.5rem;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
}

.contact-card {
    max-width: 600px;
}
</style>
