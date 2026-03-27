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
        console.log('[DASHBOARD_REDIRECT] START');
        console.log('[DASHBOARD_REDIRECT] auth.user:', auth.user);
        console.log('[DASHBOARD_REDIRECT] auth.user?.role:', auth.user?.role);

        const role = auth.user?.role;
        console.log('[DASHBOARD_REDIRECT] role:', role);

        if (role === 'platform_admin') {
            console.log('[DASHBOARD_REDIRECT] redirecting to platform_health');
            await router.replace({ name: 'dashboard.platform_health' });
            return;
        }

        if (role === 'facility') {
            console.log('[DASHBOARD_REDIRECT] redirecting to facility.dashboard');
            await router.replace({ name: 'facility.dashboard' });
            return;
        }

        if (role === 'finance') {
            console.log('[DASHBOARD_REDIRECT] redirecting to finance');
            await router.replace({ name: 'dashboard.finance' });
            return;
        }

        if (role === 'compliance') {
            console.log('[DASHBOARD_REDIRECT] redirecting to compliance');
            await router.replace({ name: 'dashboard.compliance' });
            return;
        }

        if (role === 'scheduler') {
            console.log('[DASHBOARD_REDIRECT] redirecting to shifts');
            await router.replace({ name: 'dashboard.shifts' });
            return;
        }

        if (role === 'logistics') {
            console.log('[DASHBOARD_REDIRECT] redirecting to logistics');
            await router.replace({ name: 'dashboard.logistics' });
            return;
        }

        console.log('[DASHBOARD_REDIRECT] fallback to placements');
        await router.replace({ name: 'dashboard.placements' });
    } catch (err) {
        console.error('[DASHBOARD_REDIRECT] ERROR:', err);
    }
});
</script>
