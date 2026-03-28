<template>
  <div class="p-4 text-center">Loading...</div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';

const router = useRouter();
const auth = useAuthStore();

onMounted(async () => {
    try {
        const role = auth.user?.role;

        if (role === 'platform_admin') {
            await router.replace({ name: 'dashboard.platform_health' });
            return;
        }

        if (role === 'facility') {
            await router.replace({ name: 'facility.dashboard' });
            return;
        }

        if (role === 'finance') {
            await router.replace({ name: 'dashboard.finance' });
            return;
        }

        if (role === 'compliance') {
            await router.replace({ name: 'dashboard.compliance' });
            return;
        }

        if (role === 'scheduler') {
            await router.replace({ name: 'dashboard.shifts' });
            return;
        }

        if (role === 'logistics') {
            await router.replace({ name: 'dashboard.logistics' });
            return;
        }

        await router.replace({ name: 'dashboard.placements' });
    } catch (err) {
        console.error('[DASHBOARD_REDIRECT] ERROR:', err);
    }
});
</script>
