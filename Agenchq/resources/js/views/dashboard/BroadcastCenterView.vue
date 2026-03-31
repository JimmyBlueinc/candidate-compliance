<template>
  <div class="space-y-6">
    <div class="glass-dark rounded-[32px] p-8 border border-white/5">
      <div>
        <h2 class="font-display text-2xl text-white">Global Broadcast</h2>
        <p class="text-sm text-[color:var(--p-text-muted-color)] mt-1">Send a system-wide message banner.</p>
      </div>

      <div class="mt-6 p-6 rounded-2xl bg-white/[0.03] border border-white/5">
        <textarea
          v-model="message"
          class="w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 text-white"
          rows="5"
          placeholder="Type a broadcast message..."
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
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { apiPost } from '../../lib/api';
import { useBrandStore } from '../../stores/brand';

const brand = useBrandStore();

const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');
const primarySoftBg = computed(() => `color-mix(in srgb, ${primaryColor.value} 14%, transparent)`);
const primarySoftBorder = computed(() => `color-mix(in srgb, ${primaryColor.value} 28%, transparent)`);

const message = ref('');
const saving = ref(false);
const clearing = ref(false);
const status = ref('');

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
</script>
