<template>
  <div class="space-y-8">
    <UiPageHeader
      title="My Dashboard"
      subtitle="Your quick status snapshot."
    />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <UiCard
        v-motion
        :initial="{ opacity: 0, y: 10 }"
        :enter="{ opacity: 1, y: 0, transition: { delay: 0.02, duration: 0.35 } }"
        class="p-6"
      >
        <div class="flex items-start justify-between gap-4">
          <div>
            <div class="text-[10px] font-black uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Profile Strength</div>
            <div class="mt-2 text-3xl font-display" :class="ui.theme === 'light' ? 'text-slate-900' : 'text-white'">
              {{ profileStrength }}%
            </div>
          </div>
          <div class="w-11 h-11 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center">
            <UiIcon :icon="Zap" fallback="bolt" class="w-5 h-5" :style="{ color: primaryColor }" />
          </div>
        </div>
        <div class="mt-4 w-full h-2 rounded-full bg-white/5 overflow-hidden">
          <div class="h-2 rounded-full" :style="{ width: `${profileStrength}%`, backgroundColor: primaryColor }"></div>
        </div>
        <div class="mt-3 text-xs text-[color:var(--p-text-muted-color)]">
          Complete your contact info and upload required documents.
        </div>
      </UiCard>

      <UiCard
        v-motion
        :initial="{ opacity: 0, y: 10 }"
        :enter="{ opacity: 1, y: 0, transition: { delay: 0.08, duration: 0.35 } }"
        class="p-6"
      >
        <div class="flex items-start justify-between gap-4">
          <div>
            <div class="text-[10px] font-black uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Credentials</div>
            <div class="mt-2 text-3xl font-display" :class="ui.theme === 'light' ? 'text-slate-900' : 'text-white'">
              {{ credentialsCount }}
            </div>
          </div>
          <div class="w-11 h-11 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center">
            <UiIcon :icon="BadgeCheck" fallback="verified" class="w-5 h-5" :style="{ color: primaryColor }" />
          </div>
        </div>
        <div class="mt-3 text-xs text-[color:var(--p-text-muted-color)]">
          Keep documents up to date to avoid assignment delays.
        </div>
      </UiCard>

      <UiCard
        v-motion
        :initial="{ opacity: 0, y: 10 }"
        :enter="{ opacity: 1, y: 0, transition: { delay: 0.14, duration: 0.35 } }"
        class="p-6"
      >
        <div class="flex items-start justify-between gap-4">
          <div>
            <div class="text-[10px] font-black uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Next Steps</div>
            <div class="mt-2 text-sm" :class="ui.theme === 'light' ? 'text-slate-700' : 'text-slate-200'">
              Upload missing documents and confirm arrival when assigned.
            </div>
          </div>
          <div class="w-11 h-11 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center">
            <UiIcon :icon="ListChecks" fallback="check_circle" class="w-5 h-5" :style="{ color: primaryColor }" />
          </div>
        </div>
        <div class="mt-3 text-xs text-[color:var(--p-text-muted-color)]">
          Tip: Use the sidebar to manage credentials, jobs, and travel.
        </div>
      </UiCard>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { apiGet } from '../../lib/api';
import UiPageHeader from '../../components/ui/UiPageHeader.vue';
import UiCard from '../../components/ui/UiCard.vue';
import UiIcon from '../../components/ui/UiIcon.vue';
import { useBrandStore } from '../../stores/brand';
import { useUiStore } from '../../stores/ui';
import { BadgeCheck, ListChecks, Zap } from 'lucide-vue-next';

const brand = useBrandStore();
const ui = useUiStore();
const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');

const me = ref(null);
const credentialsCount = ref(0);

const profileStrength = computed(() => {
    const c = me.value?.candidate || null;
    const contactFields = [c?.first_name, c?.last_name, c?.email, c?.phone];
    const contactScore = contactFields.filter(Boolean).length / 4;

    const docs = Math.min(1, (Number(credentialsCount.value || 0) / 5));

    return Math.round((contactScore * 0.6 + docs * 0.4) * 100);
});

async function loadMe() {
    try {
        const response = await apiGet('/v1/portal/me');
        me.value = response?.data || response;
        credentialsCount.value = Number(me.value?.credentials_count || 0);
    } catch (e) {
        console.error('Failed to load candidate profile', e);
    }
}

onMounted(async () => {
    await loadMe();
});
</script>
