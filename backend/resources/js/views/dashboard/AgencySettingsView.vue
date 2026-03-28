<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <AppPageHeader title="Agency Settings" subtitle="White-label branding controls for your organization.">
      <template #actions>
        <AppButton variant="secondary" size="sm" @click="reload" :disabled="saving">
          <RefreshCw class="w-4 h-4" />
          Reload
        </AppButton>
      </template>
    </AppPageHeader>

    <!-- Status Messages -->
    <div v-if="status" class="px-4 py-3 rounded-[var(--radius-lg)] bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm">
      {{ status }}
    </div>
    <div v-if="error" class="px-4 py-3 rounded-[var(--radius-lg)] bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm">
      {{ error }}
    </div>

    <!-- Settings Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Primary Color Card -->
      <AppCard title="Primary Color" subtitle="Customize your brand's primary accent color.">
        <div class="space-y-4">
          <div class="flex items-center gap-3">
            <input
              v-model="primaryColor"
              type="text"
              class="app-input flex-1"
              placeholder="#8B5CF6"
            />
            <input ref="colorPicker" v-model="primaryColor" type="color" class="sr-only" />
            <button
              type="button"
              class="w-12 h-12 rounded-[var(--radius-lg)] border-2 border-[color:var(--aq-border)] transition-transform hover:scale-105"
              :style="{ backgroundColor: primaryColor || '#000' }"
              @click="colorPicker?.click()"
            />
          </div>

          <div class="pt-4 flex items-center gap-3">
            <AppButton :loading="saving" @click="save">
              <Save class="w-4 h-4" />
              Save Changes
            </AppButton>
          </div>
        </div>
      </AppCard>

      <!-- Logo Card -->
      <AppCard title="Logo" subtitle="Upload your organization's logo (png/jpg/webp/svg).">
        <div class="space-y-4">
          <div v-if="logoPreviewUrl" class="p-4 rounded-[var(--radius-lg)] bg-[color:var(--aq-surface-2)] border border-[color:var(--aq-border)]">
            <img :src="logoPreviewUrl" alt="Logo preview" class="h-12 w-auto mx-auto" />
          </div>

          <div class="space-y-2">
            <label class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Upload Logo</label>
            <input
              type="file"
              accept="image/png,image/jpeg,image/webp,image/svg+xml"
              class="block w-full text-sm text-[color:var(--aq-muted)] file:mr-4 file:py-2 file:px-4 file:rounded-[var(--radius-lg)] file:border-0 file:text-sm file:font-semibold file:bg-[color:var(--aq-primary)] file:text-white hover:file:bg-[color:var(--aq-primary)]/90 transition-colors"
              @change="onLogoSelected"
            />
          </div>

          <div class="pt-4">
            <AppButton variant="secondary" :loading="saving" @click="save" :disabled="!logoFile">
              <Upload class="w-4 h-4" />
              Upload Logo
            </AppButton>
          </div>
        </div>
      </AppCard>
    </div>

    <!-- Brand Preview -->
    <AppCard title="Brand Preview" subtitle="See how your brand colors will appear.">
      <div class="space-y-4">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
          <div class="p-4 rounded-[var(--radius-lg)] border border-[color:var(--aq-border)]" :style="{ backgroundColor: primaryColor || '#8B5CF6' }">
            <div class="text-white text-xs font-semibold uppercase tracking-wider">Primary</div>
          </div>
          <div class="p-4 rounded-[var(--radius-lg)] border border-[color:var(--aq-border)] bg-[color:var(--aq-primary)]/10">
            <div class="text-[color:var(--aq-primary)] text-xs font-semibold uppercase tracking-wider">Primary / 10</div>
          </div>
          <div class="p-4 rounded-[var(--radius-lg)] border border-[color:var(--aq-border)] bg-[color:var(--aq-primary)]/20">
            <div class="text-[color:var(--aq-primary)] text-xs font-semibold uppercase tracking-wider">Primary / 20</div>
          </div>
          <div class="p-4 rounded-[var(--radius-lg)] border border-[color:var(--aq-border)] bg-[color:var(--aq-primary)]/30">
            <div class="text-[color:var(--aq-primary)] text-xs font-semibold uppercase tracking-wider">Primary / 30</div>
          </div>
        </div>

        <div class="pt-4">
          <AppButton :style="{ backgroundColor: primaryColor || undefined }">
            Sample Button
          </AppButton>
        </div>
      </div>
    </AppCard>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { apiGet, apiPost } from '../../lib/api';
import { useBrandStore } from '../../stores/brand';
import { RefreshCw, Save, Upload } from 'lucide-vue-next';
import AppPageHeader from '../../components/ui/AppPageHeader.vue';
import AppCard from '../../components/ui/AppCard.vue';
import AppButton from '../../components/ui/AppButton.vue';

const brand = useBrandStore();

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
  try {
    const res = await apiGet('/brand');
    primaryColor.value = res?.brand?.primary_color || '';
    logoPreviewUrl.value = res?.brand?.logo_url || '';
  } catch (e) {
    error.value = e?.message || 'Failed to load settings.';
  }
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
    fd.append('_method', 'PUT');
    fd.append('primary_color', primaryColor.value || '');
    if (logoFile.value) {
      fd.append('logo', logoFile.value);
    }

    const res = await apiPost('/v1/agency/branding', fd);

    const brandData = res?.data || res;
    if (brandData) {
      brand.updateFromResponse(brandData);
    }

    status.value = 'Settings saved successfully.';
    logoFile.value = null;
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'Failed to save settings.';
  } finally {
    saving.value = false;
  }
}

reload();
</script>

<style scoped>
.app-input {
  width: 100%;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  border-radius: var(--radius-lg);
  border: 1px solid var(--aq-border);
  background: var(--aq-surface-2);
  color: var(--aq-fg);
  transition: border-color var(--transition-fast), box-shadow var(--transition-fast);
}

.app-input::placeholder {
  color: var(--aq-muted);
}

.app-input:focus {
  outline: none;
  border-color: color-mix(in srgb, var(--aq-primary) 50%, transparent);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--aq-primary) 10%, transparent);
}
</style>
