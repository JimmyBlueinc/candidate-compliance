<template>
  <div class="space-y-8">
    <UiPageHeader
      title="Available Jobs"
      subtitle="Open roles that match your specialty."
    >
      <template #actions>
        <button
          type="button"
          class="px-3 py-1.5 rounded-full text-xs font-bold border transition-colors"
          :style="{ backgroundColor: activeFilter === 'bookmarked' ? primarySoftBg : 'transparent', borderColor: primarySoftBorder, color: primaryColor }"
          @click="toggleFilter"
        >
          {{ activeFilter === 'bookmarked' ? 'Showing Saved' : 'Saved Jobs' }}
        </button>
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
      <div v-else-if="filteredItems.length === 0" class="mt-2 text-sm text-[color:var(--p-text-muted-color)]">
        {{ activeFilter === 'bookmarked' ? 'No bookmarked jobs yet.' : 'No matching jobs right now.' }}
      </div>

      <div v-else class="mt-6 space-y-3">
        <div
          v-for="(j, idx) in filteredItems"
          :key="j.id"
          v-motion
          :initial="{ opacity: 0, y: 8 }"
          :enter="{ opacity: 1, y: 0, transition: { delay: 0.02 + idx * 0.02, duration: 0.3 } }"
          class="p-4 rounded-2xl bg-white/[0.03] border border-white/5 hover:bg-white/[0.06] hover:border-white/10 transition-all"
          role="button"
          tabindex="0"
          @click="goToDetail(j)"
        >
          <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
              <div class="font-semibold text-white truncate">{{ j.title }}</div>
              <div class="mt-1 text-xs text-[color:var(--p-text-muted-color)] truncate">
                {{ j.facility_name }}
                <span class="opacity-40">•</span>
                {{ j.specialty || '—' }}
              </div>
              <div class="mt-2 text-xs text-slate-300">
                Bill: {{ money(j.bill_rate) }}
                <span class="opacity-40">•</span>
                Pay: {{ money(j.pay_rate) }}
              </div>
            </div>

            <button
              type="button"
              class="px-3 py-1.5 rounded-full text-xs font-bold border transition-colors shrink-0"
              :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
              :disabled="actingId === j.id"
              @click.stop="expressInterest(j)"
            >
              {{ actingId === j.id ? 'Saving…' : 'Express Interest' }}
            </button>
            <button
              type="button"
              class="px-3 py-1.5 rounded-full text-xs font-bold border transition-colors shrink-0"
              :style="{ borderColor: primarySoftBorder, color: primaryColor, backgroundColor: j.is_bookmarked ? primarySoftBg : 'transparent' }"
              :disabled="bookmarkActingId === j.id"
              @click.stop="toggleBookmark(j)"
            >
              {{ bookmarkActingId === j.id ? 'Updating…' : (j.is_bookmarked ? 'Saved' : 'Save') }}
            </button>
          </div>
        </div>
      </div>

      <div v-if="message" class="mt-4 text-sm text-[color:var(--p-text-muted-color)]">{{ message }}</div>
    </UiCard>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { apiDelete, apiGet, apiPost, apiPut, normalizeApiList } from '../../lib/api';
import UiCard from '../../components/ui/UiCard.vue';
import UiPageHeader from '../../components/ui/UiPageHeader.vue';
import { useBrandStore } from '../../stores/brand';

const router = useRouter();
const brand = useBrandStore();

const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');
const primarySoftBg = computed(() => `color-mix(in srgb, ${primaryColor.value} 14%, transparent)`);
const primarySoftBorder = computed(() => `color-mix(in srgb, ${primaryColor.value} 28%, transparent)`);

const items = ref([]);
const loading = ref(false);
const actingId = ref(null);
const bookmarkActingId = ref(null);
const message = ref('');
const activeFilter = ref('all');
let refreshTimer = null;

const filteredItems = computed(() => {
    if (activeFilter.value === 'bookmarked') {
        return items.value.filter((i) => i?.is_bookmarked === true);
    }
    return items.value;
});

function money(v) {
    if (v === null || v === undefined || v === '') return '—';
    const n = Number(v);
    if (Number.isNaN(n)) return '—';
    return `$${n.toFixed(2)}`;
}

async function refresh() {
    loading.value = true;
    message.value = '';
    try {
        const res = await apiGet('/v1/portal/jobs');
        items.value = normalizeApiList(res);
    } finally {
        loading.value = false;
    }
}

async function goToDetail(job) {
    if (!job?.id) return;
    await router.push({ name: 'portal.jobs.detail', params: { id: job.id } });
}

async function expressInterest(job) {
    actingId.value = job.id;
    message.value = '';
    try {
        await apiPost(`/v1/placements/express-interest/${job.id}`);
        message.value = 'Interest submitted. A recruiter will review your application.';
    } finally {
        actingId.value = null;
    }
}

async function toggleBookmark(job) {
    if (!job?.id) return;
    bookmarkActingId.value = job.id;
    try {
        if (job.is_bookmarked) {
            await apiDelete(`/v1/portal/bookmarks/${job.id}`);
            job.is_bookmarked = false;
        } else {
            await apiPut(`/v1/portal/bookmarks/${job.id}`, {});
            job.is_bookmarked = true;
        }
    } finally {
        bookmarkActingId.value = null;
    }
}

function toggleFilter() {
    activeFilter.value = activeFilter.value === 'all' ? 'bookmarked' : 'all';
}

onMounted(() => {
    refresh();
    // Keep candidate job list up to date with newly posted org jobs.
    refreshTimer = setInterval(() => {
        refresh();
    }, 45000);
});

onUnmounted(() => {
    if (refreshTimer) {
        clearInterval(refreshTimer);
        refreshTimer = null;
    }
});
</script>
