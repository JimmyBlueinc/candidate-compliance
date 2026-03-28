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

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <AppCard title="Workspace Preferences" subtitle="Control default behavior for your organization admins.">
        <div class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-2">
              <label class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Language</label>
              <select v-model="preferences.language" class="app-input">
                <option value="en">English</option>
                <option value="es">Spanish</option>
                <option value="fr">French</option>
              </select>
            </div>
            <div class="space-y-2">
              <label class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Timezone</label>
              <input v-model="preferences.timezone" class="app-input" placeholder="America/New_York" />
            </div>
          </div>

          <label class="setting-row">
            <span class="text-sm text-[color:var(--aq-fg)]">Collapse sidebar by default</span>
            <input v-model="preferences.sidebar_collapsed" type="checkbox" />
          </label>
          <label class="setting-row">
            <span class="text-sm text-[color:var(--aq-fg)]">Enable in-app notifications</span>
            <input v-model="preferences.notifications_enabled" type="checkbox" />
          </label>
          <label class="setting-row">
            <span class="text-sm text-[color:var(--aq-fg)]">Enable email notifications</span>
            <input v-model="preferences.email_notifications_enabled" type="checkbox" />
          </label>
          <label class="setting-row">
            <span class="text-sm text-[color:var(--aq-fg)]">Credential expiry reminders</span>
            <input v-model="preferences.expiry_reminders_enabled" type="checkbox" />
          </label>

          <div class="space-y-2">
            <label class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Reminder Days Before Expiry</label>
            <input v-model.number="preferences.reminder_days_before" type="number" min="1" max="365" class="app-input" />
          </div>

          <div class="pt-3 border-t border-[color:var(--aq-border)]">
            <h4 class="text-sm font-semibold text-[color:var(--aq-fg)]">Public Candidate Home Content</h4>
            <p class="text-xs text-[color:var(--aq-muted)] mt-1">
              Control the text shown on your public organization page (`/home`) for candidates.
            </p>
          </div>

          <div class="space-y-2">
            <label class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Hero Heading</label>
            <input v-model="preferences.public_home_content.hero_heading" class="app-input" maxlength="160" />
          </div>
          <div class="space-y-2">
            <label class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Hero Subheading</label>
            <textarea v-model="preferences.public_home_content.hero_subheading" class="app-input min-h-[80px]" maxlength="600" />
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-2">
              <label class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Primary CTA Label</label>
              <input v-model="preferences.public_home_content.hero_primary_cta_label" class="app-input" maxlength="80" />
            </div>
            <div class="space-y-2">
              <label class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Secondary CTA Label</label>
              <input v-model="preferences.public_home_content.hero_secondary_cta_label" class="app-input" maxlength="80" />
            </div>
          </div>

          <div class="space-y-2">
            <label class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Why Join Heading</label>
            <input v-model="preferences.public_home_content.why_join_heading" class="app-input" maxlength="160" />
          </div>

          <div class="space-y-2">
            <label class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Talent Pool Heading</label>
            <input v-model="preferences.public_home_content.talent_pool_heading" class="app-input" maxlength="160" />
          </div>
          <div class="space-y-2">
            <label class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Talent Pool Subheading</label>
            <textarea v-model="preferences.public_home_content.talent_pool_subheading" class="app-input min-h-[80px]" maxlength="600" />
          </div>

          <div class="space-y-2">
            <label class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Final CTA Heading</label>
            <input v-model="preferences.public_home_content.final_cta_heading" class="app-input" maxlength="160" />
          </div>
          <div class="space-y-2">
            <label class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Final CTA Subheading</label>
            <textarea v-model="preferences.public_home_content.final_cta_subheading" class="app-input min-h-[80px]" maxlength="600" />
          </div>

          <div class="pt-2">
            <AppButton variant="secondary" :loading="savingPreferences" @click="savePreferences">
              <Save class="w-4 h-4" />
              Save Workspace Preferences
            </AppButton>
          </div>
        </div>
      </AppCard>

      <AppCard title="Feature Modules" subtitle="Enable optional modules without redeploying core workflows.">
        <div class="space-y-3">
          <div v-for="flag in featureFlagDefs" :key="flag.key" class="setting-row">
            <div>
              <div class="text-sm font-semibold text-[color:var(--aq-fg)]">{{ flag.label }}</div>
              <div class="text-xs text-[color:var(--aq-muted)] mt-0.5">{{ flag.description }}</div>
            </div>
            <input
              type="checkbox"
              :checked="featureFlags[flag.key] === true"
              @change="toggleFeatureFlag(flag.key, $event.target.checked)"
            />
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
import { apiGet, apiPost, apiPut } from '../../lib/api';
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
const savingPreferences = ref(false);
const status = ref('');
const error = ref('');
const preferences = ref({
  language: 'en',
  timezone: 'UTC',
  sidebar_collapsed: false,
  notifications_enabled: true,
  email_notifications_enabled: true,
  expiry_reminders_enabled: true,
  reminder_days_before: 30,
  public_home_content: {
    hero_heading: 'Build your next chapter with our team.',
    hero_subheading: 'Discover meaningful healthcare staffing opportunities and apply in minutes.',
    hero_primary_cta_label: 'Browse Open Jobs',
    hero_secondary_cta_label: 'Join Talent Pool',
    why_join_heading: 'A team built for growth, support, and meaningful impact.',
    talent_pool_heading: 'Get matched with the right opportunities faster.',
    talent_pool_subheading: 'Share your profile once and get notified when the right role opens.',
    final_cta_heading: 'Ready to apply or join our talent network?',
    final_cta_subheading: 'Start with open roles now or submit your profile for future opportunities.',
  },
});
const featureFlags = ref({});

const featureFlagDefs = [
  {
    key: 'dashboard.command_palette',
    label: 'Command Palette',
    description: 'Global quick navigation and actions with Cmd/Ctrl+K.',
  },
  {
    key: 'dashboard.live_activity_feed',
    label: 'Live Activity Feed',
    description: 'Show operational timeline cards in dashboard views.',
  },
  {
    key: 'dashboard.advanced_exports',
    label: 'Advanced Exports',
    description: 'Enable extended CSV export entry points.',
  },
];

function unwrap(res) {
  if (!res || typeof res !== 'object') return res;
  if (Object.prototype.hasOwnProperty.call(res, 'data')) return res.data;
  return res;
}

async function reload() {
  status.value = '';
  error.value = '';
  try {
    const [brandRes, settingsRes, flagsRes] = await Promise.all([
      apiGet('/brand'),
      apiGet('/v1/agency/settings'),
      apiGet('/feature-flags'),
    ]);

    const b = unwrap(brandRes);
    const s = unwrap(settingsRes)?.settings || {};
    const f = unwrap(flagsRes)?.flags || {};

    primaryColor.value = b?.brand?.primary_color || b?.primary_color || '';
    logoPreviewUrl.value = b?.brand?.logo_url || b?.logo_url || '';

    preferences.value = {
      language: s.language || 'en',
      timezone: s.timezone || 'UTC',
      sidebar_collapsed: !!s.sidebar_collapsed,
      notifications_enabled: s.notifications_enabled !== false,
      email_notifications_enabled: s.email_notifications_enabled !== false,
      expiry_reminders_enabled: s.expiry_reminders_enabled !== false,
      reminder_days_before: Number(s.reminder_days_before || 30),
      public_home_content: {
        ...preferences.value.public_home_content,
        ...(s.public_home_content || {}),
      },
    };

    const mappedFlags = {};
    for (const def of featureFlagDefs) {
      mappedFlags[def.key] = !!f?.[def.key]?.enabled;
    }
    featureFlags.value = mappedFlags;
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

async function savePreferences() {
  savingPreferences.value = true;
  status.value = '';
  error.value = '';
  try {
    await apiPut('/v1/agency/settings', {
      language: preferences.value.language,
      timezone: preferences.value.timezone,
      sidebar_collapsed: !!preferences.value.sidebar_collapsed,
      notifications_enabled: !!preferences.value.notifications_enabled,
      email_notifications_enabled: !!preferences.value.email_notifications_enabled,
      expiry_reminders_enabled: !!preferences.value.expiry_reminders_enabled,
      reminder_days_before: Number(preferences.value.reminder_days_before || 30),
      public_home_content: {
        ...preferences.value.public_home_content,
      },
    });
    status.value = 'Workspace preferences updated.';
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'Failed to save workspace preferences.';
  } finally {
    savingPreferences.value = false;
  }
}

async function toggleFeatureFlag(flagKey, enabled) {
  featureFlags.value[flagKey] = !!enabled;
  try {
    await apiPut(`/feature-flags/${encodeURIComponent(flagKey)}`, {
      enabled: !!enabled,
      payload: { source: 'agency-settings' },
    });
    status.value = 'Feature module settings updated.';
  } catch (e) {
    featureFlags.value[flagKey] = !enabled;
    error.value = e?.response?.data?.message || e?.message || 'Failed to update feature module.';
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

.setting-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.72rem 0.8rem;
  border: 1px solid var(--aq-border);
  border-radius: var(--radius-lg);
  background: color-mix(in srgb, var(--aq-surface-2) 84%, transparent);
}
</style>
