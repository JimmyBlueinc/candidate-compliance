<template>
  <div class="min-h-screen bg-white selection:bg-blue-600 selection:text-white text-blue-950 antialiased">
    <nav
      class="fixed top-0 left-0 right-0 z-50 transition-all duration-500"
      :class="isScrolled ? 'bg-white/70 backdrop-blur-xl border-b border-blue-100/50 py-3' : 'bg-transparent py-6'"
    >
      <div class="max-w-7xl mx-auto px-6 flex items-center justify-between">
        <RouterLink to="/" class="flex items-center gap-2 group cursor-pointer">
          <div
            class="w-9 h-9 rounded-xl flex items-center justify-center transition-transform group-hover:rotate-12"
            :style="{ backgroundColor: primarySolid }"
          >
            <img v-if="brand.logoUrl" :src="brand.logoUrl" alt="Logo" class="h-5 w-5 object-contain" />
            <span v-else class="material-symbols-outlined text-white text-[18px]">activity</span>
          </div>
          <span class="text-xl font-bold tracking-tighter text-blue-950">{{ brand.name || 'AgencyHQ' }}</span>
        </RouterLink>

        <div class="hidden md:flex items-center gap-10">
          <a href="#" class="text-[13px] font-medium text-slate-500 hover:text-blue-600 transition-colors">Platform</a>
          <a href="#" class="text-[13px] font-medium text-slate-500 hover:text-blue-600 transition-colors">Solutions</a>
          <a href="#" class="text-[13px] font-medium text-slate-500 hover:text-blue-600 transition-colors">Customers</a>
          <a href="#" class="text-[13px] font-medium text-slate-500 hover:text-blue-600 transition-colors">Pricing</a>
          <RouterLink to="/jobs" class="text-[13px] font-medium text-slate-500 hover:text-blue-600 transition-colors">Jobs</RouterLink>
        </div>

        <div class="hidden md:flex items-center gap-4">
          <button type="button" @click="handleLoginClick" class="text-[13px] font-medium text-slate-500 hover:text-blue-600 px-4">Log in</button>
          <RouterLink
            to="/signup"
            class="text-[13px] py-2.5 px-6 rounded-full font-medium transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0 active:scale-95"
            :style="{ backgroundColor: primarySolid, color: '#fff' }"
          >
            Get Started
          </RouterLink>
        </div>

        <button class="md:hidden p-2" type="button" @click="isMobileMenuOpen = !isMobileMenuOpen">
          <span class="material-symbols-outlined text-[22px]">{{ isMobileMenuOpen ? 'close' : 'menu' }}</span>
        </button>
      </div>

      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 -translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-2"
      >
        <div
          v-if="isMobileMenuOpen"
          class="absolute top-full left-0 right-0 bg-white border-b border-blue-100 p-8 flex flex-col gap-6 md:hidden shadow-2xl"
        >
          <a href="#" class="text-xl font-semibold text-blue-950">Platform</a>
          <a href="#" class="text-xl font-semibold text-blue-950">Solutions</a>
          <a href="#" class="text-xl font-semibold text-blue-950">Customers</a>
          <a href="#" class="text-xl font-semibold text-blue-950">Pricing</a>
          <RouterLink to="/jobs" class="text-xl font-semibold text-blue-950">Jobs</RouterLink>
          <div class="pt-4 flex flex-col gap-4">
            <button
              type="button"
              @click="handleLoginClick"
              class="w-full px-6 py-3 bg-white text-blue-900 border border-blue-100 rounded-full font-medium transition-all duration-300 hover:bg-blue-50 hover:border-blue-200 text-center"
            >
              Log in
            </button>
            <RouterLink
              to="/signup"
              class="w-full px-6 py-3 rounded-full font-medium transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0 active:scale-95 text-center"
              :style="{ backgroundColor: primarySolid, color: '#fff' }"
            >
              Get Started
            </RouterLink>
          </div>
        </div>
      </Transition>
    </nav>

    <section class="relative pt-40 pb-20 px-6">
      <div class="absolute inset-0 pointer-events-none bg-white" />
      <div class="absolute inset-0 pointer-events-none bg-grid opacity-100" />
      <div class="absolute inset-0 bg-gradient-to-b from-white via-transparent to-white pointer-events-none" />

      <div class="max-w-7xl mx-auto relative">
        <div class="text-center max-w-4xl mx-auto mb-20">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 border border-blue-100 text-blue-600 text-[11px] font-bold uppercase tracking-wider mb-8">
            <span class="w-1.5 h-1.5 rounded-full" :style="{ backgroundColor: primarySolid }" />
            v2.0 is now live
          </div>
          <h1 class="text-6xl md:text-8xl font-bold tracking-tighter leading-[0.9] mb-8 text-gradient">
            The operating system for healthcare staffing.
          </h1>
          <p class="text-xl md:text-2xl text-slate-500 mb-10 max-w-2xl mx-auto leading-tight font-medium">
            AgencyHQ is the central command for modern healthcare agencies. Scale your operations with automated scheduling, credentialing, and payroll.
          </p>
          <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <RouterLink
              to="/signup"
              class="px-10 py-4 text-base flex items-center justify-center gap-2 rounded-full font-medium transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0 active:scale-95"
              :style="{ backgroundColor: primarySolid, color: '#fff' }"
            >
              Start your trial
              <span class="material-symbols-outlined text-[18px]">arrow_right_alt</span>
            </RouterLink>
            <RouterLink
              to="/login"
              class="px-10 py-4 text-base bg-white text-blue-900 border border-blue-100 rounded-full font-medium transition-all duration-300 hover:bg-blue-50 hover:border-blue-200 text-center"
            >
              View Demo
            </RouterLink>
          </div>
        </div>

        <div class="relative max-w-6xl mx-auto">
          <div class="relative rounded-3xl border border-blue-100 shadow-[0_30px_120px_rgba(30,58,138,0.10)] overflow-hidden bg-white">
            <div class="grid grid-cols-1 lg:grid-cols-12 min-h-[500px]">
              <div class="lg:col-span-7 relative">
                <img
                  src="https://images.unsplash.com/photo-1516549655669-df72c3c1f866?auto=format&fit=crop&w=1600&q=80"
                  alt="Healthcare command center"
                  class="absolute inset-0 h-full w-full object-cover"
                />
                <div class="absolute inset-0 bg-gradient-to-r from-slate-950/65 via-slate-900/45 to-slate-900/10" />
                <div class="relative z-10 p-8 md:p-10 h-full flex flex-col justify-end">
                  <p class="text-[11px] uppercase tracking-[0.2em] text-white/75 font-bold">Healthcare Operations</p>
                  <h3 class="text-3xl md:text-4xl font-bold text-white tracking-tight mt-2">Run staffing like a modern command center.</h3>
                  <p class="text-sm text-white/80 mt-3 max-w-lg">
                    Real-time visibility across placements, facilities, compliance, and payroll in one premium system.
                  </p>
                </div>
              </div>
              <div class="lg:col-span-5 p-6 md:p-8 bg-gradient-to-b from-white to-blue-50/45">
                <div class="grid grid-cols-2 gap-4">
                  <div class="rounded-2xl overflow-hidden border border-blue-100 bg-white">
                    <img
                      src="https://images.unsplash.com/photo-1580281657521-8cf0f6f5f6f3?auto=format&fit=crop&w=900&q=80"
                      alt="Nurse team collaboration"
                      class="h-28 w-full object-cover"
                    />
                    <div class="p-3">
                      <div class="text-xs font-semibold text-blue-900">Staffing Velocity</div>
                      <div class="text-[11px] text-slate-500 mt-1">Scale teams faster.</div>
                    </div>
                  </div>
                  <div class="rounded-2xl overflow-hidden border border-blue-100 bg-white">
                    <img
                      src="https://images.unsplash.com/photo-1576091160550-2173dba999ef?auto=format&fit=crop&w=900&q=80"
                      alt="Clinical data workflows"
                      class="h-28 w-full object-cover"
                    />
                    <div class="p-3">
                      <div class="text-xs font-semibold text-blue-900">Compliance Clarity</div>
                      <div class="text-[11px] text-slate-500 mt-1">Track every credential.</div>
                    </div>
                  </div>
                  <div class="col-span-2 rounded-2xl border border-blue-100 p-4 bg-white">
                    <div class="flex items-center justify-between">
                      <span class="text-xs uppercase tracking-wider font-bold text-slate-500">Live Fill Rate</span>
                      <span class="text-sm font-bold text-emerald-600">94.2%</span>
                    </div>
                    <div class="mt-3 h-2 rounded-full bg-blue-50 overflow-hidden">
                      <div class="h-full bg-emerald-500 w-[94%]" />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="absolute -right-12 top-1/4 hidden lg:block w-48 glass-card p-4 rounded-2xl animate-float-slow">
            <div class="flex items-center gap-3 mb-3">
              <div class="w-2 h-2 rounded-full bg-emerald-500" />
              <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Live Fill Rate</span>
            </div>
            <div class="text-2xl font-bold text-blue-950">94.2%</div>
            <div class="mt-2 h-1 w-full bg-blue-50 rounded-full overflow-hidden">
              <div class="h-full bg-emerald-500 w-[94%]" />
            </div>
          </div>

          <div class="absolute -left-12 bottom-1/4 hidden lg:block w-56 glass-card p-4 rounded-2xl animate-float-fast">
            <div class="flex items-center gap-3 mb-3">
              <span class="material-symbols-outlined text-[16px]" :style="{ color: primarySolid }">schedule</span>
              <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Next Payroll</span>
            </div>
            <div class="text-lg font-bold text-blue-950">In 2 days</div>
            <div class="text-[10px] text-slate-400 mt-1">142 timesheets pending</div>
          </div>
        </div>

        <div class="mt-20">
          <p class="text-center text-[11px] font-bold uppercase tracking-[0.2em] text-slate-400 mb-8">Trusted by industry leaders</p>
          <div class="flex flex-wrap justify-center items-center gap-x-12 gap-y-8 opacity-40 grayscale transition-all hover:opacity-70">
            <span class="text-xl font-black italic tracking-tighter text-blue-900">Acme Health</span>
            <span class="text-xl font-black italic tracking-tighter text-blue-900">Global Care</span>
            <span class="text-xl font-black italic tracking-tighter text-blue-900">Nova Medical</span>
            <span class="text-xl font-black italic tracking-tighter text-blue-900">Unity Staffing</span>
            <span class="text-xl font-black italic tracking-tighter text-blue-900">Apex Nursing</span>
          </div>
        </div>
      </div>
    </section>

    <section class="py-32 px-6">
      <div class="max-w-7xl mx-auto">
        <div class="mb-20">
          <h2 class="text-4xl md:text-6xl font-bold tracking-tighter mb-6 text-blue-950">Built for scale.</h2>
          <p class="text-xl text-slate-500 max-w-2xl font-medium">
            AgencyHQ replaces your fragmented tools with a single, cohesive platform designed for high-growth staffing agencies.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="p-10 rounded-[2rem] border border-blue-50 bg-white hover:border-blue-200 transition-all group md:col-span-2 md:row-span-2">
            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center mb-8 group-hover:text-white transition-colors" :style="{ color: primarySolid }">
              <span class="material-symbols-outlined text-[20px]">dashboard</span>
            </div>
            <h3 class="text-2xl font-bold mb-4 tracking-tight text-blue-950">Command Center</h3>
            <p class="text-slate-500 leading-snug font-medium">A unified dashboard to monitor every shift, credential, and invoice in real-time.</p>
          </div>

          <div class="p-10 rounded-[2rem] border border-blue-50 bg-white hover:border-blue-200 transition-all group">
            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center mb-8 group-hover:text-white transition-colors" :style="{ color: primarySolid }">
              <span class="material-symbols-outlined text-[20px]">database</span>
            </div>
            <h3 class="text-2xl font-bold mb-4 tracking-tight text-blue-950">Smart Matching</h3>
            <p class="text-slate-500 leading-snug font-medium">Match the right staff to the right facility based on skills, distance, and historical performance.</p>
          </div>

          <div class="p-10 rounded-[2rem] border border-blue-50 bg-white hover:border-blue-200 transition-all group">
            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center mb-8 group-hover:text-white transition-colors" :style="{ color: primarySolid }">
              <span class="material-symbols-outlined text-[20px]">layers</span>
            </div>
            <h3 class="text-2xl font-bold mb-4 tracking-tight text-blue-950">Auto-Compliance</h3>
            <p class="text-slate-500 leading-snug font-medium">Automated license checks and document tracking that never sleeps.</p>
          </div>

          <div class="p-10 rounded-[2rem] border border-blue-50 bg-white hover:border-blue-200 transition-all group md:col-span-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center mb-8 group-hover:text-white transition-colors" :style="{ color: primarySolid }">
              <span class="material-symbols-outlined text-[20px]">bolt</span>
            </div>
            <h3 class="text-2xl font-bold mb-4 tracking-tight text-blue-950">Instant Payments</h3>
            <p class="text-slate-500 leading-snug font-medium">Streamline your cash flow with automated invoicing and staff payments.</p>
          </div>
        </div>
      </div>
    </section>

    <section class="py-32 px-6 border-y border-blue-50">
      <div class="max-w-7xl mx-auto grid grid-cols-2 md:grid-cols-4 gap-12">
        <div class="text-center">
          <div class="text-4xl md:text-6xl font-bold tracking-tighter mb-2 text-blue-950">2.4M+</div>
          <div class="text-[11px] font-bold uppercase tracking-[0.2em] text-slate-400">Shifts Managed</div>
        </div>
        <div class="text-center">
          <div class="text-4xl md:text-6xl font-bold tracking-tighter mb-2 text-blue-950">45k+</div>
          <div class="text-[11px] font-bold uppercase tracking-[0.2em] text-slate-400">Active Staff</div>
        </div>
        <div class="text-center">
          <div class="text-4xl md:text-6xl font-bold tracking-tighter mb-2 text-blue-950">60%</div>
          <div class="text-[11px] font-bold uppercase tracking-[0.2em] text-slate-400">Time Saved</div>
        </div>
        <div class="text-center">
          <div class="text-4xl md:text-6xl font-bold tracking-tighter mb-2 text-blue-950">98.2%</div>
          <div class="text-[11px] font-bold uppercase tracking-[0.2em] text-slate-400">Fill Rate</div>
        </div>
      </div>
    </section>

    <section class="py-40 px-6">
      <div class="max-w-4xl mx-auto text-center">
        <h2 class="text-5xl md:text-8xl font-bold tracking-tighter mb-10 text-gradient">Ready to lead the industry?</h2>
        <p class="text-xl md:text-2xl text-slate-500 mb-12 font-medium">
          Join the agencies that are defining the future of healthcare staffing.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <RouterLink
            to="/signup"
            class="px-12 py-5 text-lg rounded-full font-medium transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0 active:scale-95"
            :style="{ backgroundColor: primarySolid, color: '#fff' }"
          >
            Get Started Now
          </RouterLink>
          <RouterLink
            to="/login"
            class="px-12 py-5 text-lg bg-white text-blue-900 border border-blue-100 rounded-full font-medium transition-all duration-300 hover:bg-blue-50 hover:border-blue-200"
          >
            Contact Sales
          </RouterLink>
        </div>
      </div>
    </section>

    <footer class="py-20 px-6 border-t border-blue-50 bg-blue-50/20">
      <div class="max-w-7xl mx-auto">
        <div class="grid grid-cols-2 md:grid-cols-5 gap-12 mb-20">
          <div class="col-span-2">
            <div class="flex items-center gap-2 mb-6">
              <span class="material-symbols-outlined text-[24px]" :style="{ color: primarySolid }">activity</span>
              <span class="text-xl font-bold tracking-tighter text-blue-950">{{ brand.name || 'AgencyHQ' }}</span>
            </div>
            <p class="text-slate-500 text-sm max-w-xs font-medium leading-relaxed">
              The operating system for modern healthcare staffing agencies.
            </p>
          </div>
          <div>
            <h4 class="text-[11px] font-bold uppercase tracking-widest text-blue-950 mb-6">Product</h4>
            <ul class="space-y-4 text-sm text-slate-500 font-medium">
              <li><a href="#" class="hover:text-blue-600 transition-colors">Platform</a></li>
              <li><a href="#" class="hover:text-blue-600 transition-colors">Integrations</a></li>
              <li><a href="#" class="hover:text-blue-600 transition-colors">Security</a></li>
              <li><a href="#" class="hover:text-blue-600 transition-colors">Pricing</a></li>
            </ul>
          </div>
          <div>
            <h4 class="text-[11px] font-bold uppercase tracking-widest text-blue-950 mb-6">Company</h4>
            <ul class="space-y-4 text-sm text-slate-500 font-medium">
              <li><a href="#" class="hover:text-blue-600 transition-colors">About</a></li>
              <li><a href="#" class="hover:text-blue-600 transition-colors">Blog</a></li>
              <li><a href="#" class="hover:text-blue-600 transition-colors">Careers</a></li>
              <li><a href="#" class="hover:text-blue-600 transition-colors">Contact</a></li>
            </ul>
          </div>
          <div>
            <h4 class="text-[11px] font-bold uppercase tracking-widest text-blue-950 mb-6">Social</h4>
            <ul class="space-y-4 text-sm text-slate-500 font-medium">
              <li><a href="#" class="hover:text-blue-600 transition-colors">Twitter</a></li>
              <li><a href="#" class="hover:text-blue-600 transition-colors">LinkedIn</a></li>
              <li><a href="#" class="hover:text-blue-600 transition-colors">GitHub</a></li>
            </ul>
          </div>
        </div>
        <div class="flex flex-col md:flex-row justify-between items-center pt-10 border-t border-blue-100/50 text-[11px] font-bold uppercase tracking-widest text-slate-400">
          <p>© {{ new Date().getFullYear() }} AgencyHQ Inc.</p>
          <div class="flex gap-8 mt-6 md:mt-0">
            <a href="#" class="hover:text-blue-600 transition-colors">Privacy</a>
            <a href="#" class="hover:text-blue-600 transition-colors">Terms</a>
            <a href="#" class="hover:text-blue-600 transition-colors">Cookies</a>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useBrandStore } from '../../stores/brand';
import { useAuthStore } from '../../stores/auth';

const router = useRouter();
const brand = useBrandStore();
const auth = useAuthStore();

const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');
const primarySolid = computed(() => {
    const c = primaryColor.value;
    return typeof c === 'string' && c.trim().length ? c : '#2563eb';
});

const isScrolled = ref(false);
const isMobileMenuOpen = ref(false);

function onScroll() {
    isScrolled.value = window.scrollY > 20;
}

// Handle Login click - redirect authenticated org users to tenant dashboard
function handleLoginClick() {
    if (auth.isAuthenticated) {
        // Initialize brand from storage if needed
        if (!brand.loaded) {
            brand.initFromStorage();
        }
        
        const isOrgUser = !['candidate', 'facility', 'platform_admin'].includes(auth.user?.role);
        
        if (brand.subdomain && isOrgUser) {
            // Redirect to tenant dashboard
            const tenantUrl = `https://${brand.subdomain}.agenchq.com/dashboard`;
            window.location.href = tenantUrl;
            return;
        }
    }
    
    // Not authenticated or no tenant subdomain - go to login page
    router.push({ name: 'login' });
}

onMounted(() => {
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
});

onBeforeUnmount(() => {
    window.removeEventListener('scroll', onScroll);
});
</script>

<style scoped>
.bg-grid {
  background-image: radial-gradient(circle at 1px 1px, rgba(219, 234, 254, 1) 1px, transparent 0);
  background-size: 40px 40px;
}

.text-gradient {
  background-image: linear-gradient(to bottom, #172554, #2563eb);
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
}

.glass-card {
  background: rgba(255, 255, 255, 0.7);
  border: 1px solid rgba(219, 234, 254, 0.65);
  backdrop-filter: blur(24px);
  box-shadow: 0 8px 30px rgba(30, 58, 138, 0.04);
}

@keyframes floatSlow {
  0% { transform: translateY(0px); }
  50% { transform: translateY(-10px); }
  100% { transform: translateY(0px); }
}

@keyframes floatFast {
  0% { transform: translateY(0px); }
  50% { transform: translateY(10px); }
  100% { transform: translateY(0px); }
}

.animate-float-slow {
  animation: floatSlow 4s ease-in-out infinite;
}

.animate-float-fast {
  animation: floatFast 5s ease-in-out infinite;
}
</style>
