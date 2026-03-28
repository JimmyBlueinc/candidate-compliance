<template>
  <div class="min-h-screen bg-[var(--app-bg)] text-[var(--app-fg)] selection:bg-purple-500/30 relative overflow-hidden">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(255,255,255,0.06),transparent_55%)] pointer-events-none" />

    <div class="relative z-10 max-w-3xl mx-auto px-6 py-10">
      <div class="flex items-start justify-between gap-6">
        <div>
          <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Onboarding</div>
          <h1 class="mt-2 text-3xl sm:text-4xl font-display font-bold tracking-tight">Set up your organization</h1>
          <p class="mt-3 text-sm text-[color:var(--p-text-muted-color)]">
            Choose your subdomain and branding. You’ll be redirected to the dashboard when setup is complete.
          </p>
        </div>

        <button
          type="button"
          class="rounded-full border px-4 py-2 text-xs font-black transition"
          :class="logoutClass"
          @click="logout"
        >
          Logout
        </button>
      </div>

      <div v-if="errorMessage" class="mt-6 p-3 bg-red-500/10 border border-red-500/20 text-red-400 text-xs font-bold rounded-2xl flex items-center gap-2">
        <span class="material-symbols-outlined text-sm">error</span>
        {{ errorMessage }}
      </div>

      <div class="mt-6">
        <div class="flex items-center justify-between text-[11px] font-black uppercase tracking-widest text-[color:var(--p-text-muted-color)]">
          <div class="flex items-center gap-2">
            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full border" :class="stepCircleClass(1)">1</span>
            Subdomain
          </div>
          <div class="flex-1 h-[2px] mx-4" :class="stepLineClass(2)" />
          <div class="flex items-center gap-2">
            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full border" :class="stepCircleClass(2)">2</span>
            Branding
          </div>
          <div class="flex-1 h-[2px] mx-4" :class="stepLineClass(3)" />
          <div class="flex items-center gap-2">
            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full border" :class="stepCircleClass(3)">3</span>
            Finish
          </div>
        </div>
      </div>

      <div class="mt-8 glass-dark rounded-[28px] border border-white/5 p-8">
        <div class="flex items-center justify-between">
          <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Step</div>
          <div class="text-xs font-black" :style="{ color: primaryColor }">{{ stepLabel }}</div>
        </div>

        <div class="mt-6">
          <div v-if="step === 'subdomain'" class="space-y-5">
            <div>
              <label class="text-[11px] font-black text-[color:var(--p-text-muted-color)] uppercase tracking-widest ml-1">Subdomain</label>
              <div class="mt-2 flex items-center gap-2">
                <input
                  v-model="subdomain"
                  type="text"
                  class="flex-1 rounded-2xl px-5 py-3 text-sm placeholder-slate-600 focus:outline-none transition-all duration-300"
                  :class="inputClass"
                  placeholder="acme-staffing"
                  :disabled="submitting"
                  @input="debouncedCheck"
                />
                <button
                  type="button"
                  class="rounded-2xl px-5 py-3 text-xs font-black border transition"
                  :style="primaryButtonStyle"
                  :disabled="submitting || !subdomainOk"
                  @click="saveSubdomain"
                >
                  Save
                </button>
              </div>

              <div class="mt-3 text-xs" :class="availabilityClass">
                {{ availabilityMessage }}
              </div>

              <div class="mt-2 text-xs text-[color:var(--p-text-muted-color)]">
                This becomes your tenant URL: <span class="font-black">{{ subdomainPreview }}</span>
              </div>

              <div class="mt-5 p-4 rounded-2xl bg-white/5 border border-white/10">
                <div class="text-xs font-black uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Rules</div>
                <div class="mt-2 space-y-1 text-xs text-[color:var(--p-text-muted-color)]">
                  <div>- Use lowercase letters, numbers, and hyphens</div>
                  <div>- Must start and end with a letter or number</div>
                  <div>- 3–50 characters</div>
                  <div>- Common names like <span class="font-black">api</span> / <span class="font-black">www</span> are reserved</div>
                </div>
              </div>
            </div>
          </div>

          <div v-else-if="step === 'branding'" class="space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
              <div class="sm:col-span-2 space-y-6">
                <div class="space-y-2">
                  <label class="text-[11px] font-black text-[color:var(--p-text-muted-color)] uppercase tracking-widest ml-1">Primary color</label>
                  <div class="flex items-center gap-3">
                    <input v-model="primaryColorInput" type="color" class="h-12 w-16 rounded-xl border border-white/10 bg-transparent" :disabled="submitting" />
                    <input
                      v-model="primaryColorInput"
                      type="text"
                      class="flex-1 rounded-2xl px-5 py-3 text-sm placeholder-slate-600 focus:outline-none transition-all duration-300"
                      :class="inputClass"
                      placeholder="#6D28D9"
                      :disabled="submitting"
                    />
                  </div>
                  <div class="text-xs text-[color:var(--p-text-muted-color)]">
                    Tip: this will update buttons and highlights across your tenant.
                  </div>
                </div>

                <div class="space-y-2">
                  <label class="text-[11px] font-black text-[color:var(--p-text-muted-color)] uppercase tracking-widest ml-1">Logo (optional)</label>
                  <input
                    ref="logoEl"
                    type="file"
                    accept=".png,.jpg,.jpeg,.webp,.svg"
                    class="block w-full text-xs text-[color:var(--p-text-muted-color)]"
                    :disabled="submitting"
                    @change="onLogoSelected"
                  />
                  <div class="text-xs text-[color:var(--p-text-muted-color)]">Recommended: square image, transparent background.</div>
                </div>
              </div>

              <div class="sm:col-span-1">
                <div class="text-[11px] font-black text-[color:var(--p-text-muted-color)] uppercase tracking-widest">Preview</div>
                <div class="mt-3 rounded-[22px] border border-white/10 bg-white/5 p-4">
                  <div class="flex items-center gap-3">
                    <div class="aq-on-dark w-12 h-12 rounded-2xl overflow-hidden border border-white/10 bg-black/20 flex items-center justify-center">
                      <img v-if="logoPreviewUrl" :src="logoPreviewUrl" alt="Logo preview" class="w-full h-full object-contain" />
                      <img v-else-if="brand.logoUrl" :src="brand.logoUrl" alt="Logo" class="w-full h-full object-contain" />
                      <span v-else class="material-symbols-outlined text-white/70">shield_person</span>
                    </div>
                    <div class="min-w-0">
                      <div class="text-sm font-black truncate">{{ brand.name || auth.user?.organization?.name || 'Your organization' }}</div>
                      <div class="text-xs text-[color:var(--p-text-muted-color)] truncate">{{ subdomainPreview }}</div>
                    </div>
                  </div>

                  <div class="mt-4">
                    <div class="text-[11px] font-black uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Primary color</div>
                    <div class="mt-2 flex items-center gap-2">
                      <div class="w-6 h-6 rounded-lg border border-white/10" :style="{ backgroundColor: primaryColorInput }" />
                      <div class="text-xs font-black">{{ primaryColorInput }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="flex items-center justify-end gap-3">
              <button
                type="button"
                class="rounded-2xl px-5 py-3 text-xs font-black border transition"
                :style="primaryButtonStyle"
                :disabled="submitting"
                @click="saveBranding"
              >
                Save branding
              </button>
            </div>
          </div>

          <div v-else-if="step === 'complete'" class="space-y-6">
            <div class="p-4 rounded-2xl bg-white/5 border border-white/10">
              <div class="text-sm font-black">Almost done</div>
              <div class="mt-2 text-xs text-[color:var(--p-text-muted-color)]">
                Your org is configured. Finalize onboarding to unlock the dashboard.
              </div>
            </div>

            <button
              type="button"
              class="w-full font-black py-3 rounded-xl hover:scale-[1.01] active:scale-[0.99] transition-all duration-300 shadow-xl disabled:opacity-50 disabled:hover:scale-100 flex items-center justify-center gap-2 text-xs border"
              :style="primaryButtonStyle"
              :disabled="submitting"
              @click="completeOnboarding"
            >
              <template v-if="submitting">
                <span class="w-5 h-5 border-2 border-black/20 border-t-black rounded-full animate-spin" />
              </template>
              <template v-else>
                <span>Finish setup</span>
                <span class="material-symbols-outlined text-[18px]">arrow_right_alt</span>
              </template>
            </button>
          </div>

          <div v-else class="text-sm text-[color:var(--p-text-muted-color)]">
            Loading…
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, onBeforeUnmount, ref } from 'vue';
import { getHttp } from '../../lib/api';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { useBrandStore } from '../../stores/brand';
import { useUiStore } from '../../stores/ui';

const router = useRouter();
const auth = useAuthStore();
const brand = useBrandStore();
const ui = useUiStore();

const step = ref(null);
const submitting = ref(false);
const errorMessage = ref(null);

const subdomain = ref('');
const availability = ref(null);
const checking = ref(false);
let checkTimer = null;

const primaryColorInput = ref('#6D28D9');
const logoEl = ref(null);
const logoPreviewUrl = ref(null);

const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');

const inputClass = computed(() =>
  'bg-[color:var(--p-surface-0)] text-[color:var(--p-text-color)] border-2 border-[color:var(--p-surface-border)] focus:border-[color:var(--p-primary-color)]'
);

const primaryButtonStyle = computed(() => ({
  backgroundColor: primaryColor.value,
  borderColor: `color-mix(in srgb, ${primaryColor.value} 55%, rgba(15,23,42,0.25))`,
  color: '#ffffff',
}));

const logoutClass = computed(() =>
  ui.theme === 'light'
    ? 'border-slate-200 bg-white text-slate-900 hover:bg-slate-50'
    : 'border-white/10 bg-white/5 text-white hover:bg-white/10'
);

const stepLabel = computed(() => {
  if (step.value === 'subdomain') return 'Subdomain';
  if (step.value === 'branding') return 'Branding';
  if (step.value === 'complete') return 'Complete';
  return 'Loading';
});

const stepIndex = computed(() => {
  if (step.value === 'subdomain') return 1;
  if (step.value === 'branding') return 2;
  if (step.value === 'complete') return 3;
  return 1;
});

function stepCircleClass(idx) {
  if (idx < stepIndex.value) return 'border-white/10 bg-white/10 text-white';
  if (idx === stepIndex.value) return 'border-white/10 text-white';
  return 'border-white/10 text-white/40';
}

function stepLineClass(idx) {
  return idx <= stepIndex.value ? 'bg-white/15' : 'bg-white/5';
}

const availabilityClass = computed(() => {
  if (!subdomain.value) return 'text-[color:var(--p-text-muted-color)]';
  if (checking.value) return 'text-[color:var(--p-text-muted-color)]';
  if (availability.value?.available) return 'text-emerald-300';
  return 'text-red-400';
});

const subdomainOk = computed(() => Boolean(availability.value?.available));

const subdomainPreview = computed(() => {
  if (!subdomain.value) return 'your-subdomain';
  return subdomain.value;
});

const availabilityMessage = computed(() => {
  if (!subdomain.value) return 'Enter a subdomain to check availability.';
  if (checking.value) return 'Checking availability…';
  if (availability.value?.available) return 'Available — you can claim this subdomain.';
  if (availability.value && availability.value.reserved) return 'Reserved — choose a different subdomain.';
  if (availability.value && availability.value.format_ok === false) return 'Invalid format — use letters, numbers, and hyphens only.';
  if (availability.value) return 'Taken — this subdomain is already in use.';
  return 'Not available.';
});

function debouncedCheck() {
  if (checkTimer) {
    clearTimeout(checkTimer);
  }

  availability.value = null;
  checking.value = true;

  checkTimer = setTimeout(() => {
    checkSubdomain();
  }, 350);
}

async function checkSubdomain() {
  const val = String(subdomain.value || '').trim();
  if (!val) {
    checking.value = false;
    availability.value = null;
    return;
  }

  try {
    const res = await getHttp().get('/api/public/subdomain/check', {
      params: { subdomain: val },
    });

    availability.value = res?.data?.data || null;
  } catch (e) {
    availability.value = null;
  } finally {
    checking.value = false;
  }
}

function onLogoSelected() {
  const file = logoEl.value?.files?.[0] || null;

  if (logoPreviewUrl.value) {
    URL.revokeObjectURL(logoPreviewUrl.value);
    logoPreviewUrl.value = null;
  }

  if (file) {
    logoPreviewUrl.value = URL.createObjectURL(file);
  }
}

async function loadStatus() {
  errorMessage.value = null;

  let res;
  try {
    res = await getHttp().get('/api/v1/onboarding/status');
  } catch (e) {
    const status = e?.response?.status;
    if (status === 401 || status === 403) {
      await auth.logout();
      await router.push({ name: 'login' });
      return;
    }

    errorMessage.value = e?.response?.data?.message || 'Failed to load onboarding status.';
    step.value = null;
    return;
  }

  const org = res?.data?.data?.organization || null;

  if (!org) {
    step.value = null;
    return;
  }

  if (!org.subdomain) {
    step.value = 'subdomain';
  } else if (!org.onboarding_completed_at) {
    if (org.onboarding_step === 'branding') {
      step.value = 'branding';
    } else if (org.onboarding_step === 'complete') {
      step.value = 'complete';
    } else if (org.onboarding_step === 'done') {
      step.value = 'complete';
    } else {
      step.value = 'branding';
    }
  } else {
    step.value = 'complete';
  }

  if (org.subdomain) {
    subdomain.value = org.subdomain;
  }

  if (org.primary_color) {
    primaryColorInput.value = org.primary_color;
  }
}

async function saveSubdomain() {
  if (submitting.value) return;
  if (!subdomainOk.value) return;

  submitting.value = true;
  errorMessage.value = null;

  try {
    await getHttp().post('/api/v1/onboarding/subdomain', {
      subdomain: subdomain.value,
    });

    await loadStatus();
  } catch (e) {
    errorMessage.value = e?.response?.data?.message || 'Failed to save subdomain.';
  } finally {
    submitting.value = false;
  }
}

async function saveBranding() {
  if (submitting.value) return;

  submitting.value = true;
  errorMessage.value = null;

  try {
    const fd = new FormData();
    fd.append('_method', 'POST'); // Ensure POST for multipart
    if (primaryColorInput.value) {
      fd.append('primary_color', primaryColorInput.value);
    }

    const file = logoEl.value?.files?.[0] || null;
    if (file) {
      fd.append('logo', file);
    }

    const res = await getHttp().post('/api/v1/onboarding/branding', fd);

    // API response is wrapped: { data: { tenant_id, name, logo_url, ... }, message: "..." }
    // axios returns res.data, so the actual brand data is in res.data.data or res.data
    const brandData = res?.data?.data || res?.data;
    if (brandData) {
      brand.updateFromResponse(brandData);
    }

    await loadStatus();
  } catch (e) {
    errorMessage.value = e?.response?.data?.message || 'Failed to save branding.';
  } finally {
    submitting.value = false;
  }
}

async function completeOnboarding() {
  if (submitting.value) return;

  submitting.value = true;
  errorMessage.value = null;

  try {
    const res = await getHttp().post('/api/v1/onboarding/complete');
    const data = res?.data?.data || res?.data || {};
    
    await auth.fetchUser();
    
    // Redirect to the organization subdomain if available
    const subdomainUrl = data?.redirect_url;
    if (subdomainUrl) {
      window.location.href = subdomainUrl + '/dashboard';
    } else {
      // Fallback: stay on current host
      await router.push({ name: 'dashboard.index' });
    }
  } catch (e) {
    errorMessage.value = e?.response?.data?.message || 'Failed to complete onboarding.';
  } finally {
    submitting.value = false;
  }
}

async function logout() {
  await auth.logout();
  await router.push({ name: 'login' });
}

onMounted(async () => {
  await loadStatus();

  if (!subdomain.value) {
    availability.value = null;
  } else {
    debouncedCheck();
  }
});

onBeforeUnmount(() => {
  if (checkTimer) {
    clearTimeout(checkTimer);
    checkTimer = null;
  }

  if (logoPreviewUrl.value) {
    URL.revokeObjectURL(logoPreviewUrl.value);
    logoPreviewUrl.value = null;
  }
});
</script>
