<template>
  <div class="space-y-6">
    <div class="glass-dark rounded-[32px] p-8 border border-white/5">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h2 class="font-display text-2xl text-white">Agency Settings</h2>
          <p class="text-sm text-[color:var(--p-text-muted-color)] mt-1">White-label branding controls.</p>
        </div>
      </div>

      <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="p-6 rounded-2xl bg-white/[0.03] border border-white/5">
          <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Primary Color</div>
          <div class="mt-4 flex items-center gap-3">
            <input v-model="primaryColor" class="flex-1 px-4 py-3 rounded-2xl bg-white/5 border border-white/10 text-white" placeholder="#8B5CF6" />
            <input ref="colorPicker" v-model="primaryColor" type="color" class="sr-only" />
            <button
              type="button"
              class="w-10 h-10 rounded-2xl border border-white/10"
              :style="{ backgroundColor: primaryColor || '#000' }"
              @click="colorPicker?.click()"
            />
          </div>

          <div class="mt-4 flex items-center gap-2">
            <button
              type="button"
              class="px-4 py-3 rounded-2xl text-xs font-black tracking-widest uppercase border transition-colors"
              :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColorComputed }"
              :disabled="saving"
              @click="save"
            >
              {{ saving ? 'Saving…' : 'Save' }}
            </button>

            <button
              type="button"
              class="px-4 py-3 rounded-2xl text-xs font-black tracking-widest uppercase border border-white/10 bg-white/5 text-slate-200 hover:bg-white/10"
              :disabled="saving"
              @click="reload"
            >
              Reload
            </button>
          </div>

          <div v-if="status" class="mt-3 text-sm text-[color:var(--p-text-muted-color)]">{{ status }}</div>
          <div v-if="error" class="mt-3 text-sm text-red-400">{{ error }}</div>
        </div>

        <div class="p-6 rounded-2xl bg-white/[0.03] border border-white/5">
          <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Logo</div>
          <div class="mt-2 text-sm text-[color:var(--p-text-muted-color)]">Upload a small image (png/jpg/webp/svg).</div>

          <div v-if="logoPreviewUrl" class="mt-4">
            <img :src="logoPreviewUrl" alt="Logo preview" class="h-12 w-auto rounded-xl border border-white/10 bg-white/5 p-2" />
          </div>

          <input
            type="file"
            accept="image/png,image/jpeg,image/webp,image/svg+xml"
            class="mt-4 block w-full text-sm text-slate-300"
            @change="onLogoSelected"
          />
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

const primaryColorComputed = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');
const primarySoftBg = computed(() => `color-mix(in srgb, ${primaryColorComputed.value} 14%, transparent)`);
const primarySoftBorder = computed(() => `color-mix(in srgb, ${primaryColorComputed.value} 28%, transparent)`);

const primaryColor = ref('');
const colorPicker = ref(null);
const logoFile = ref(null);
const logoPreviewUrl = ref('');
const saving = ref(false);
const status = ref('');
const error = ref('');

async function reload() {
    status.value = '';
    error.value = '';
    const res = await apiGet('/brand');
    primaryColor.value = res?.brand?.primary_color || '';
    logoPreviewUrl.value = res?.brand?.logo_url || '';
}

function onLogoSelected(e) {
    const f = e?.target?.files?.[0] || null;
    logoFile.value = f;
    if (!f) return;
    logoPreviewUrl.value = URL.createObjectURL(f);
}

async function save() {
    saving.value = true;
    status.value = '';
    error.value = '';
    try {
        const fd = new FormData();
        fd.append('_method', 'PUT'); // Laravel method spoofing for multipart
        fd.append('primary_color', primaryColor.value || '');
        if (logoFile.value) {
            fd.append('logo', logoFile.value);
        }

        console.log('[BRAND SAVE] FormData entries:', [...fd.entries()].map(([k, v]) => [k, v instanceof File ? v.name : v]));

        const res = await apiPost('/v1/agency/branding', fd);

        // API response is wrapped: { data: { tenant_id, name, logo_url, ... }, message: "..." }
        // apiPost returns res.data, so brand data is in res.data or just res
        const brandData = res?.data || res;
        if (brandData) {
            brand.updateFromResponse(brandData);
        }

        status.value = 'Saved.';
    } catch (e) {
        error.value = e?.message || 'Failed to save.';
    } finally {
        saving.value = false;
    }
}

reload();
</script>
