<template>
  <div class="space-y-8">
    <UiPageHeader
      title="My Travel"
      subtitle="Your travel and housing details for your assignment."
    >
      <template #actions>
        <button
          type="button"
          class="px-3 py-1.5 rounded-full text-xs font-bold border transition-colors"
          :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
          @click="refresh"
        >
          Refresh
        </button>
      </template>
    </UiPageHeader>

    <UiCard
      v-motion
      :initial="{ opacity: 0, y: 10 }"
      :enter="{ opacity: 1, y: 0, transition: { duration: 0.35 } }"
      class="p-8"
    >
      <div v-if="loading" class="mt-2 text-sm text-[color:var(--p-text-muted-color)]">Loading...</div>

      <div v-else-if="!placement" class="mt-2 text-sm text-[color:var(--p-text-muted-color)]">
        No active or placed assignment yet.
      </div>

      <div v-else class="mt-6 space-y-6">
        <div class="p-5 rounded-2xl bg-white/[0.03] border border-white/5">
          <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Assignment</div>
          <div class="mt-2 font-semibold text-white">
            {{ placement.job_order?.title || 'Placement' }}
            <span class="opacity-40">•</span>
            {{ placement.job_order?.facility_name || '—' }}
          </div>
          <div class="mt-1 text-xs text-[color:var(--p-text-muted-color)]">
            Stage: {{ placement.stage }}
            <span class="opacity-40">•</span>
            Start: {{ placement.start_date || '—' }}
          </div>

          <div class="mt-5" v-if="showConfirmArrival">
            <button
              type="button"
              class="w-full px-6 py-4 rounded-2xl text-sm font-black tracking-widest uppercase border transition-colors"
              :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
              :disabled="confirming"
              @click="confirmArrival"
            >
              {{ confirming ? 'Confirming…' : 'Confirm Arrival' }}
            </button>
          </div>

          <div v-else class="mt-4 text-sm text-[color:var(--p-text-muted-color)]">
            <span v-if="placement.arrival_confirmed_at">Arrival confirmed.</span>
            <span v-else>Arrival confirmation will appear on your start date.</span>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
          <div class="p-5 rounded-2xl bg-white/[0.03] border border-white/5">
            <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Housing</div>
            <div v-if="placement.housing" class="mt-3 space-y-2">
              <div class="text-sm text-white">{{ placement.housing.address || '—' }}</div>
              <div class="text-xs text-[color:var(--p-text-muted-color)]">Landlord: {{ placement.housing.landlord_contact || '—' }}</div>
              <div class="text-xs text-[color:var(--p-text-muted-color)]">Lease: {{ placement.housing.lease_start || '—' }} → {{ placement.housing.lease_end || '—' }}</div>
            </div>
            <div v-else class="mt-3 text-sm text-[color:var(--p-text-muted-color)]">No housing details on file.</div>
          </div>

          <div class="p-5 rounded-2xl bg-white/[0.03] border border-white/5">
            <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Travel</div>
            <div v-if="(placement.travel || []).length" class="mt-3 space-y-3">
              <div v-for="t in placement.travel" :key="t.id" class="p-4 rounded-2xl bg-white/[0.02] border border-white/5">
                <div class="flex items-start justify-between gap-3">
                  <div class="min-w-0">
                    <div class="font-semibold text-white capitalize">{{ t.type }}</div>
                    <div v-if="t.details" class="mt-1 text-xs text-[color:var(--p-text-muted-color)]">{{ t.details }}</div>
                    <div v-if="t.confirmation_number" class="mt-1 text-xs text-[color:var(--p-text-muted-color)]">Confirmation: {{ t.confirmation_number }}</div>
                    <div class="mt-2 text-xs text-[color:var(--p-text-muted-color)]">{{ t.start_date || '—' }} → {{ t.end_date || '—' }}</div>
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="mt-3 text-sm text-[color:var(--p-text-muted-color)]">No travel itinerary on file.</div>
          </div>
        </div>

        <div v-if="message" class="text-sm text-[color:var(--p-text-muted-color)]">{{ message }}</div>
      </div>
    </UiCard>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { apiGet, apiPost } from '../../lib/api';
import UiCard from '../../components/ui/UiCard.vue';
import UiPageHeader from '../../components/ui/UiPageHeader.vue';
import { useBrandStore } from '../../stores/brand';

const brand = useBrandStore();

const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');
const primarySoftBg = computed(() => `color-mix(in srgb, ${primaryColor.value} 14%, transparent)`);
const primarySoftBorder = computed(() => `color-mix(in srgb, ${primaryColor.value} 28%, transparent)`);

const placement = ref(null);
const loading = ref(false);
const confirming = ref(false);
const message = ref('');

function todayYmd() {
    const d = new Date();
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
}

const showConfirmArrival = computed(() => {
    if (!placement.value) return false;
    if (placement.value.arrival_confirmed_at) return false;
    if (!placement.value.start_date) return false;
    return placement.value.start_date === todayYmd();
});

async function refresh() {
    loading.value = true;
    message.value = '';
    try {
        const res = await apiGet('/v1/portal/my-travel');
        placement.value = res?.data || null;
    } finally {
        loading.value = false;
    }
}

async function confirmArrival() {
    if (!placement.value) return;

    confirming.value = true;
    message.value = '';
    try {
        const res = await apiPost(`/v1/portal/placements/${placement.value.id}/confirm-arrival`);
        message.value = res?.message || 'Arrival confirmed.';
        await refresh();
    } finally {
        confirming.value = false;
    }
}

refresh();
</script>
