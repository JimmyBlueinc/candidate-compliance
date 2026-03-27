<template>
  <div class="space-y-6">
    <div class="glass-dark rounded-[32px] p-8 border border-white/5">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h2 class="font-display text-2xl text-white">Platform Health</h2>
          <p class="text-sm text-[color:var(--p-text-muted-color)] mt-1">Global organization health & broadcast messaging.</p>
        </div>
        <button
          type="button"
          class="px-3 py-1.5 rounded-full text-xs font-bold border transition-colors"
          :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
          @click="refresh"
        >
          Refresh
        </button>
      </div>

      <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="p-6 rounded-2xl bg-white/[0.03] border border-white/5">
          <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Global Broadcast</div>
          <div class="mt-2 text-sm text-slate-300">Send a system-wide message shown at the top of every screen.</div>

          <textarea
            v-model="message"
            class="mt-4 w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 text-white"
            rows="4"
            placeholder="System maintenance tonight at 10pm..."
          ></textarea>

          <div class="mt-4 flex items-center gap-2">
            <button
              type="button"
              class="px-4 py-3 rounded-2xl text-xs font-black tracking-widest uppercase border transition-colors"
              :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
              :disabled="saving"
              @click="send"
            >
              {{ saving ? 'Sending…' : 'Broadcast' }}
            </button>

            <button
              type="button"
              class="px-4 py-3 rounded-2xl text-xs font-black tracking-widest uppercase border border-red-500/30 bg-red-500/10 text-red-400 hover:bg-red-500/15"
              :disabled="clearing"
              @click="clear"
            >
              {{ clearing ? 'Clearing…' : 'Clear' }}
            </button>
          </div>

          <div v-if="status" class="mt-3 text-sm text-[color:var(--p-text-muted-color)]">{{ status }}</div>
        </div>

        <div class="p-6 rounded-2xl bg-white/[0.03] border border-white/5">
          <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Organizations</div>
          <div class="mt-2 text-sm text-slate-300">Active placement counts by organization.</div>

          <div v-if="loading" class="mt-4 text-sm text-[color:var(--p-text-muted-color)]">Loading...</div>
          <div v-else class="mt-4 space-y-3">
            <div
              v-for="t in tenants"
              :key="t.tenant_id"
              class="p-4 rounded-2xl border border-white/5 bg-white/[0.02]"
            >
              <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                  <div class="font-semibold text-white truncate">{{ t.name }}</div>
                  <div class="mt-1 text-xs text-[color:var(--p-text-muted-color)] truncate">{{ t.slug || '—' }}</div>
                </div>
                <div class="shrink-0 text-right">
                  <div class="text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Active</div>
                  <div class="mt-1 text-lg font-display" :style="{ color: primaryColor }">{{ t.active_placements }}</div>
                </div>
              </div>
            </div>

            <div v-if="tenants.length === 0" class="text-sm text-[color:var(--p-text-muted-color)]">No organizations found.</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { apiGet, apiPost } from '../../lib/api';
import { useBrandStore } from '../../stores/brand';

const brand = useBrandStore();

const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');
const primarySoftBg = computed(() => `color-mix(in srgb, ${primaryColor.value} 14%, transparent)`);
const primarySoftBorder = computed(() => `color-mix(in srgb, ${primaryColor.value} 28%, transparent)`);

const loading = ref(false);
const tenants = ref([]);

const message = ref('');
const saving = ref(false);
const clearing = ref(false);
const status = ref('');

async function refresh() {
    loading.value = true;
    try {
        const res = await apiGet('/v1/admin/platform-health');
        tenants.value = Array.isArray(res?.data) ? res.data : [];
    } finally {
        loading.value = false;
    }
}

async function send() {
    saving.value = true;
    status.value = '';
    try {
        await apiPost('/v1/admin/system-message', { message: message.value, is_active: true });
        status.value = 'Broadcast sent.';
    } finally {
        saving.value = false;
    }
}

async function clear() {
    clearing.value = true;
    status.value = '';
    try {
        await apiPost('/v1/admin/system-message/clear');
        status.value = 'Broadcast cleared.';
    } finally {
        clearing.value = false;
    }
}

refresh();
</script>
