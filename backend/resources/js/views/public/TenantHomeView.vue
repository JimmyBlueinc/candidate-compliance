<template>
  <div class="min-h-screen bg-white text-slate-900 selection:bg-blue-600 selection:text-white antialiased">
    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/80 backdrop-blur-xl border-b border-slate-100">
      <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div
            class="w-9 h-9 rounded-xl flex items-center justify-center shadow-sm"
            :style="{ backgroundColor: primarySolid }"
          >
            <img v-if="brand.logoUrl" :src="brand.logoUrl" alt="Logo" class="h-5 w-5 object-contain" />
            <span v-else class="text-white font-bold text-sm">{{ brand.name?.charAt(0) || 'A' }}</span>
          </div>
          <span class="font-display font-semibold text-slate-900">{{ brand.name || 'Organization' }}</span>
        </div>
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-50 rounded-lg transition-colors"
            @click="goToJobs"
          >
            Jobs
          </button>
          <button
            v-if="isAdmin"
            type="button"
            class="px-4 py-2 text-sm font-medium text-white rounded-lg shadow-sm hover:opacity-90 transition-opacity"
            :style="{ backgroundColor: primarySolid }"
            @click="goToDashboard"
          >
            Dashboard
          </button>
          <button
            v-else-if="!auth.isAuthenticated"
            type="button"
            class="px-4 py-2 text-sm font-medium text-white rounded-lg shadow-sm hover:opacity-90 transition-opacity"
            :style="{ backgroundColor: primarySolid }"
            @click="goToLogin"
          >
            Sign In
          </button>
        </div>
      </div>
    </nav>

    <!-- Hero Section -->
    <section class="pt-32 pb-20 px-6 relative overflow-hidden">
      <!-- Background gradient -->
      <div class="absolute inset-0 bg-gradient-to-br from-slate-50 via-white to-blue-50/30 pointer-events-none" />
      <div class="absolute top-20 right-0 w-96 h-96 bg-blue-100/50 rounded-full blur-3xl pointer-events-none" />
      <div class="absolute bottom-0 left-0 w-64 h-64 bg-purple-100/30 rounded-full blur-3xl pointer-events-none" />
      
      <div class="max-w-4xl mx-auto text-center relative">
        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-100 border border-slate-200 text-xs font-medium text-slate-600 mb-6">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse" />
          Hiring now
        </div>
        
        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-display font-bold tracking-tight text-slate-900 mb-6">
          Find your next<br />
          <span class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">healthcare opportunity</span>
        </h1>
        
        <p class="text-lg text-slate-500 max-w-xl mx-auto mb-10 leading-relaxed">
          Join {{ brand.name || 'our' }} talent network. Get matched with positions that fit your skills, schedule, and career goals.
        </p>
        
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
          <button
            type="button"
            class="px-8 py-3.5 text-sm font-semibold text-white rounded-xl shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all"
            :style="{ backgroundColor: primarySolid }"
            @click="showRegisterModal = true"
          >
            Join Talent Network
          </button>
          <button
            type="button"
            class="px-8 py-3.5 text-sm font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 hover:border-slate-300 transition-all"
            @click="goToJobs"
          >
            Browse Open Positions
          </button>
        </div>
      </div>
    </section>

    <!-- Stats Section -->
    <section class="py-12 px-6 border-y border-slate-100 bg-slate-50/50">
      <div class="max-w-4xl mx-auto">
        <div class="grid grid-cols-3 gap-8 text-center">
          <div>
            <div class="text-2xl sm:text-3xl font-bold text-slate-900">500+</div>
            <div class="text-xs text-slate-500 mt-1">Active Positions</div>
          </div>
          <div>
            <div class="text-2xl sm:text-3xl font-bold text-slate-900">2,000+</div>
            <div class="text-xs text-slate-500 mt-1">Professionals Placed</div>
          </div>
          <div>
            <div class="text-2xl sm:text-3xl font-bold text-slate-900">98%</div>
            <div class="text-xs text-slate-500 mt-1">Satisfaction Rate</div>
          </div>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section class="py-20 px-6">
      <div class="max-w-5xl mx-auto">
        <div class="text-center mb-12">
          <h2 class="text-2xl font-display font-bold text-slate-900 mb-3">Why join our network?</h2>
          <p class="text-sm text-slate-500">Everything you need to advance your healthcare career</p>
        </div>
        
        <div class="grid md:grid-cols-3 gap-5">
          <div class="group p-6 rounded-2xl border border-slate-200 bg-white hover:border-slate-300 hover:shadow-sm transition-all">
            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center mb-4 group-hover:bg-blue-100 transition-colors">
              <span class="material-symbols-outlined text-xl text-blue-600">work</span>
            </div>
            <h3 class="font-semibold text-slate-900 mb-2">Smart Matching</h3>
            <p class="text-sm text-slate-500 leading-relaxed">
              Get notified about opportunities that match your specialty, location, and availability preferences.
            </p>
          </div>
          
          <div class="group p-6 rounded-2xl border border-slate-200 bg-white hover:border-slate-300 hover:shadow-sm transition-all">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center mb-4 group-hover:bg-emerald-100 transition-colors">
              <span class="material-symbols-outlined text-xl text-emerald-600">verified</span>
            </div>
            <h3 class="font-semibold text-slate-900 mb-2">Credential Management</h3>
            <p class="text-sm text-slate-500 leading-relaxed">
              Upload and manage your licenses, certifications, and documents in one secure place.
            </p>
          </div>
          
          <div class="group p-6 rounded-2xl border border-slate-200 bg-white hover:border-slate-300 hover:shadow-sm transition-all">
            <div class="w-10 h-10 rounded-xl bg-purple-50 flex items-center justify-center mb-4 group-hover:bg-purple-100 transition-colors">
              <span class="material-symbols-outlined text-xl text-purple-600">speed</span>
            </div>
            <h3 class="font-semibold text-slate-900 mb-2">Fast Applications</h3>
            <p class="text-sm text-slate-500 leading-relaxed">
              Apply to multiple positions with a single profile. No repetitive forms or uploads.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16 px-6 bg-slate-900">
      <div class="max-w-3xl mx-auto text-center">
        <h2 class="text-2xl font-display font-bold text-white mb-3">Ready to get started?</h2>
        <p class="text-sm text-slate-400 mb-8">Join our talent network in under 2 minutes</p>
        <button
          type="button"
          class="px-8 py-3 text-sm font-semibold text-slate-900 bg-white rounded-xl hover:bg-slate-100 transition-colors"
          @click="showRegisterModal = true"
        >
          Create Free Account
        </button>
      </div>
    </section>

    <!-- Footer -->
    <footer class="py-8 px-6 border-t border-slate-100">
      <div class="max-w-6xl mx-auto flex items-center justify-between text-xs text-slate-400">
        <span>&copy; {{ new Date().getFullYear() }} {{ brand.name || 'Organization' }}</span>
        <span>Powered by AgencyHQ</span>
      </div>
    </footer>

    <!-- Register Modal -->
    <Dialog
      v-model:visible="showRegisterModal"
      modal
      :style="{ width: 'min(440px, 95vw)' }"
      class="register-modal"
    >
      <template #header>
        <div class="pr-8">
          <h3 class="text-lg font-semibold text-slate-900">Join Talent Network</h3>
          <p class="text-sm text-slate-500 mt-1">Create your profile to get started</p>
        </div>
      </template>
      
      <div class="space-y-3 pt-2">
        <div>
          <label class="block text-xs font-medium text-slate-700 mb-1.5">Full Name *</label>
          <input
            v-model="registerName"
            type="text"
            placeholder="Jane Smith"
            class="w-full px-3.5 py-2.5 text-sm rounded-lg border border-slate-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition-all"
          />
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-700 mb-1.5">Email *</label>
          <input
            v-model="registerEmail"
            type="email"
            placeholder="you@example.com"
            class="w-full px-3.5 py-2.5 text-sm rounded-lg border border-slate-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition-all"
          />
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-700 mb-1.5">Phone</label>
          <input
            v-model="registerPhone"
            type="tel"
            placeholder="(555) 000-0000"
            class="w-full px-3.5 py-2.5 text-sm rounded-lg border border-slate-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition-all"
          />
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-700 mb-1.5">Profession</label>
          <input
            v-model="registerRole"
            type="text"
            placeholder="RN, LPN, CNA, etc."
            class="w-full px-3.5 py-2.5 text-sm rounded-lg border border-slate-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition-all"
          />
        </div>
      </div>
      
      <template #footer>
        <div class="flex gap-2 pt-2">
          <button
            type="button"
            class="flex-1 px-4 py-2.5 text-sm font-medium text-slate-700 bg-slate-100 rounded-lg hover:bg-slate-200 transition-colors"
            @click="showRegisterModal = false"
          >
            Cancel
          </button>
          <button
            type="button"
            class="flex-1 px-4 py-2.5 text-sm font-medium text-white rounded-lg hover:opacity-90 transition-opacity"
            :style="{ backgroundColor: primarySolid }"
            :disabled="registering"
            @click="handleRegister"
          >
            {{ registering ? 'Creating...' : 'Create Account' }}
          </button>
        </div>
      </template>
    </Dialog>

    <Toast position="bottom-right" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useBrandStore } from '../../stores/brand';
import { useAuthStore } from '../../stores/auth';
import { useToast } from 'primevue/usetoast';
import { apiPost } from '../../lib/api';
import Dialog from 'primevue/dialog';
import Toast from 'primevue/toast';

const router = useRouter();
const route = useRoute();
const brand = useBrandStore();
const auth = useAuthStore();
const toast = useToast();

const showRegisterModal = ref(false);
const registerEmail = ref('');
const registerName = ref('');
const registerPhone = ref('');
const registerRole = ref('');
const registering = ref(false);

const primarySolid = computed(() => brand.primaryColor || '#3b82f6');

const isAdmin = computed(() => {
  const role = auth.user?.role;
  return role && ['admin', 'super_admin', 'org_admin', 'platform_admin'].includes(role);
});

function goToJobs() {
  router.push({ name: 'tenant.jobs' });
}

function goToDashboard() {
  if (auth.isAuthenticated) {
    router.push({ name: 'dashboard.index' });
  } else {
    router.push({ name: 'login' });
  }
}

function goToLogin() {
  router.push({ name: 'login' });
}

async function handleRegister() {
  if (!registerEmail.value || !registerName.value) {
    toast.add({
      severity: 'warn',
      summary: 'Missing Information',
      detail: 'Please enter your email and full name.',
      life: 3000,
    });
    return;
  }

  // Ensure brand is loaded before registration
  if (!brand.loaded) {
    console.log('[REGISTER] Brand not loaded, loading...');
    await brand.load();
  }

  const nameParts = registerName.value.trim().split(/\s+/);
  const firstName = nameParts[0] || '';
  const lastName = nameParts.slice(1).join(' ') || '';

  registering.value = true;
  try {
    const orgSlug = route.params.slug || brand.slug;
    console.log('[REGISTER] orgSlug:', orgSlug, 'brand.slug:', brand.slug, 'brand.loaded:', brand.loaded);
    
    if (!orgSlug) {
      throw new Error('Organization not identified. Please refresh the page.');
    }
    
    const response = await apiPost(`/public/${orgSlug}/register`, {
      first_name: firstName,
      last_name: lastName,
      email: registerEmail.value,
      phone: registerPhone.value || '000-000-0000',
      role: registerRole.value || 'Healthcare Professional',
    });

    toast.add({
      severity: 'success',
      summary: 'Registration Successful',
      detail: 'Check your email for next steps to complete your profile.',
      life: 5000,
    });

    showRegisterModal.value = false;
    registerEmail.value = '';
    registerName.value = '';
    registerPhone.value = '';
    registerRole.value = '';
  } catch (e) {
    const message = e.response?.data?.message || 'Registration failed. Please try again.';
    toast.add({
      severity: 'error',
      summary: 'Registration Failed',
      detail: message,
      life: 5000,
    });
  } finally {
    registering.value = false;
  }
}

onMounted(async () => {
  if (!brand.loaded && !brand.loading) {
    await brand.load();
  }
});
</script>
