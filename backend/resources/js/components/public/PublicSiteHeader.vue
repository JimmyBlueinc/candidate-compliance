<template>
  <header class="fixed top-0 left-0 right-0 z-50 border-b border-slate-200/70 bg-white/92 backdrop-blur">
    <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between gap-4">
      <RouterLink :to="mode === 'tenant' ? '/home' : '/'" class="flex items-center gap-2 min-w-0">
        <div class="h-9 w-9 rounded-xl flex items-center justify-center text-white font-bold shrink-0" :style="{ backgroundColor: primarySolid }">
          {{ initials }}
        </div>
        <span class="font-bold tracking-tight truncate">{{ resolvedBrandTitle }}</span>
      </RouterLink>

      <nav v-if="mode === 'apex'" class="hidden md:flex items-center gap-7 text-sm font-medium text-slate-600">
        <RouterLink to="/about" class="hover:text-slate-900">About</RouterLink>
        <RouterLink to="/features" class="hover:text-slate-900">Features</RouterLink>
        <RouterLink to="/solutions" class="hover:text-slate-900">Solutions</RouterLink>
        <RouterLink to="/customers" class="hover:text-slate-900">Customers</RouterLink>
        <RouterLink to="/pricing" class="hover:text-slate-900">Pricing</RouterLink>
        <RouterLink to="/contact" class="hover:text-slate-900">Contact</RouterLink>
      </nav>

      <div class="flex items-center gap-2">
        <template v-if="mode === 'tenant'">
          <button type="button" class="px-3.5 py-2 rounded-xl text-sm font-semibold border border-slate-300 bg-white text-slate-700 hover:bg-slate-50" @click="$emit('tenant-jobs')">
            Jobs
          </button>
          <button
            v-if="canShowTenantDashboard"
            type="button"
            class="px-3.5 py-2 rounded-xl text-sm font-semibold text-white"
            :style="{ backgroundColor: primarySolid }"
            @click="$emit('tenant-dashboard')"
          >
            Dashboard
          </button>
          <button
            v-else-if="showSignInButton"
            type="button"
            class="px-3.5 py-2 rounded-xl text-sm font-semibold text-white"
            :style="{ backgroundColor: primarySolid }"
            @click="$emit('tenant-signin')"
          >
            Sign In
          </button>
        </template>

        <template v-else>
          <button type="button" class="hidden md:inline-flex px-4 py-2 text-sm font-medium text-slate-700" @click="$emit('apex-login')">Log in</button>
          <RouterLink to="/signup" class="hidden md:inline-flex px-4 py-2 rounded-xl text-sm font-semibold text-white" :style="{ backgroundColor: primarySolid }">
            Get Started
          </RouterLink>
        </template>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  mode: {
    type: String,
    default: 'apex', // apex | tenant
  },
  brandName: {
    type: String,
    default: 'AgencHQ',
  },
  primaryColor: {
    type: String,
    default: '#2563eb',
  },
  showDashboardButton: {
    type: Boolean,
    default: false,
  },
  showSignInButton: {
    type: Boolean,
    default: false,
  },
  currentRole: {
    type: String,
    default: '',
  },
});

defineEmits(['apex-login', 'tenant-jobs', 'tenant-dashboard', 'tenant-signin']);

const brandTitle = computed(() => String(props.brandName || 'AgencHQ'));
const resolvedBrandTitle = computed(() => (props.mode === 'apex' ? 'AgencHQ' : brandTitle.value));
const primarySolid = computed(() => {
  const c = String(props.primaryColor || '').trim();
  return c || '#2563eb';
});
const initials = computed(() => {
  const name = resolvedBrandTitle.value;
  const first = name.charAt(0).toUpperCase();
  return first || 'A';
});

const canShowTenantDashboard = computed(() => {
  if (!props.showDashboardButton) return false;
  const role = String(props.currentRole || '').trim().toLowerCase();
  return [
    'platform_admin',
    'super_admin',
    'org_super_admin',
    'org_owner',
    'org_admin',
    'admin',
    'recruiter',
    'scheduler',
    'compliance',
    'finance',
    'logistics',
  ].includes(role);
});
</script>

