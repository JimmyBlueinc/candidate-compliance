<template>
  <div class="space-y-6">
    <div class="glass-dark rounded-[32px] p-8 border border-white/5">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h2 class="font-display text-2xl text-white">External Intake</h2>
          <p class="text-sm text-[color:var(--p-text-muted-color)] mt-1">Generate API tokens for external systems to submit candidates.</p>
        </div>
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="px-3 py-1.5 rounded-full text-xs font-bold border transition-colors"
            :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
            @click="refresh"
          >
            Refresh
          </button>
        </div>
      </div>

      <Message v-if="msg" :severity="msgSeverity" :closable="false" class="mt-4">{{ msg }}</Message>

      <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="p-5 rounded-2xl border border-white/5 bg-white/[0.02]">
          <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Endpoint</div>
          <div class="mt-2 font-mono text-sm text-white break-all">POST /api/v1/intake/candidate</div>
          <div class="mt-3 text-xs text-[color:var(--p-text-muted-color)]">
            Use a Bearer token generated below. The token is shown once.
          </div>
        </div>

        <div class="p-5 rounded-2xl border border-white/5 bg-white/[0.02]">
          <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Example Payload</div>
          <pre class="mt-2 text-xs text-slate-200 whitespace-pre-wrap">{{ examplePayload }}</pre>
        </div>
      </div>

      <div class="mt-6 p-5 rounded-2xl border border-white/5 bg-white/[0.02]">
        <div class="flex items-center justify-between gap-4">
          <div>
            <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Generate Token</div>
            <div class="mt-1 text-sm text-[color:var(--p-text-muted-color)]">Recommended: 90-day expiry</div>
          </div>
          <div class="flex items-center gap-2">
            <InputText v-model="expiresInDays" type="number" class="w-28" size="small" />
            <Button label="Generate" size="small" :loading="generating" @click="generate" />
          </div>
        </div>

        <div v-if="generatedToken" class="mt-4">
          <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">New Token (copy now)</div>
          <div class="mt-2 flex items-start gap-2">
            <Textarea v-model="generatedToken" class="w-full" rows="3" autoResize />
            <Button label="Copy" size="small" @click="copyNewToken" />
          </div>
        </div>
      </div>

      <div class="mt-6 p-5 rounded-2xl border border-white/5 bg-white/[0.02]">
        <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Active Tokens</div>

        <div v-if="loading" class="mt-3 text-sm text-[color:var(--p-text-muted-color)]">Loading...</div>
        <div v-else-if="tokens.length === 0" class="mt-3 text-sm text-[color:var(--p-text-muted-color)]">No tokens.</div>

        <div v-else class="mt-3 space-y-2">
          <div v-for="t in tokens" :key="t.id" class="flex items-center justify-between gap-4 p-3 rounded-xl border border-white/5 bg-white/[0.02]">
            <div class="min-w-0">
              <div class="text-sm font-semibold text-white">Token #{{ t.id }}</div>
              <div class="mt-1 text-xs text-[color:var(--p-text-muted-color)]">
                Created: {{ formatDateTime(t.created_at) }}
                <span class="opacity-40">•</span>
                Expires: {{ t.expires_at ? formatDateTime(t.expires_at) : '—' }}
              </div>
            </div>
            <Button label="Revoke" severity="danger" outlined size="small" :loading="revokingId === t.id" @click="revoke(t.id)" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { apiDelete, apiGet, apiPost, normalizeApiList } from '../../lib/api';
import { useBrandStore } from '../../stores/brand';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';
import Textarea from 'primevue/textarea';

const brand = useBrandStore();
const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');
const primarySoftBg = computed(() => `color-mix(in srgb, ${primaryColor.value} 14%, transparent)`);
const primarySoftBorder = computed(() => `color-mix(in srgb, ${primaryColor.value} 28%, transparent)`);

const examplePayload = computed(() => {
    return JSON.stringify(
        {
            first_name: 'Jane',
            last_name: 'Doe',
            email: 'jane@example.com',
            phone: '555-555-5555',
            specialty: 'ICU RN',
        },
        null,
        2,
    );
});

const loading = ref(false);
const tokens = ref([]);

const generating = ref(false);
const expiresInDays = ref('90');
const generatedToken = ref('');

const msg = ref('');
const msgSeverity = ref('success');

const revokingId = ref(null);

function formatDateTime(v) {
    if (!v) return '—';
    const d = new Date(v);
    if (Number.isNaN(d.getTime())) return String(v);
    return d.toLocaleString();
}

async function refresh() {
    loading.value = true;
    try {
        const res = await apiGet('/v1/intake/tokens');
        tokens.value = normalizeApiList(res);
    } catch (e) {
        msgSeverity.value = 'error';
        msg.value = e?.response?.data?.message || e?.message || 'Failed to load tokens.';
    } finally {
        loading.value = false;
    }
}

async function generate() {
    generating.value = true;
    msg.value = '';
    generatedToken.value = '';
    try {
        const days = Number(expiresInDays.value || 0);
        const res = await apiPost('/v1/intake/tokens', {
            expires_in_days: days > 0 ? days : null,
        });
        generatedToken.value = String(res?.data?.token || '');
        msgSeverity.value = 'success';
        msg.value = 'Token generated. Copy it now.';
        await refresh();
    } catch (e) {
        msgSeverity.value = 'error';
        msg.value = e?.response?.data?.message || e?.message || 'Failed to generate token.';
    } finally {
        generating.value = false;
    }
}

async function copyNewToken() {
    if (!generatedToken.value) return;
    await navigator.clipboard.writeText(generatedToken.value);
}

async function revoke(id) {
    revokingId.value = id;
    try {
        await apiDelete(`/v1/intake/tokens/${id}`);
        msgSeverity.value = 'success';
        msg.value = 'Token revoked.';
        await refresh();
    } catch (e) {
        msgSeverity.value = 'error';
        msg.value = e?.response?.data?.message || e?.message || 'Failed to revoke token.';
    } finally {
        revokingId.value = null;
    }
}

refresh();
</script>
