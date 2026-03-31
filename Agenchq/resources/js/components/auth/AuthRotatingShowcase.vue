<template>
  <section class="auth-showcase aq-on-dark relative hidden overflow-hidden lg:block">
    <div class="absolute inset-0 bg-gradient-to-br from-slate-950/92 via-slate-900/86 to-indigo-950/78" />

    <div class="relative z-10 flex h-full flex-col justify-between p-12 text-white">
      <div>
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-100/90">{{ kicker }}</p>
        <h2 class="mt-4 max-w-xl text-4xl font-semibold leading-tight tracking-tight">{{ heading }}</h2>
        <p class="mt-4 max-w-lg text-sm leading-relaxed text-slate-200/90">{{ subtitle }}</p>
      </div>

      <Transition name="card-fade-slide" mode="out-in">
        <article :key="activeCard.id" class="showcase-card">
          <div class="overflow-hidden rounded-xl border border-white/12 bg-slate-900/55">
            <img :src="activeCard.image" :alt="activeCard.title" class="h-44 w-full object-cover" loading="lazy" />
          </div>
          <p class="text-[11px] font-semibold uppercase tracking-[0.15em] text-indigo-200">{{ activeCard.badge }}</p>
          <h3 class="mt-2 text-xl font-semibold">{{ activeCard.title }}</h3>
          <p class="mt-2 text-sm leading-relaxed text-slate-200">{{ activeCard.body }}</p>
          <div class="mt-4 inline-flex rounded-full border border-white/25 bg-white/10 px-3 py-1 text-[11px] font-semibold">
            {{ activeCard.meta }}
          </div>
        </article>
      </Transition>
    </div>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
  kicker: { type: String, default: 'Workforce Intelligence' },
  heading: { type: String, default: 'Your hiring and career operations, elevated.' },
  subtitle: { type: String, default: 'A staffing platform designed for speed, trust, and high-quality placements.' },
});

const cards = [
  {
    id: 'job-opportunity',
    badge: 'Featured Opportunity',
    title: 'ICU RN - Austin, TX',
    body: '13-week contract with premium differential, rapid credentialing, and dedicated recruiter support.',
    meta: 'New roles updated daily',
    image: 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=1800&q=80',
  },
  {
    id: 'candidate-success',
    badge: 'Candidate Success',
    title: 'Placed in 6 days',
    body: 'Multi-state Med-Surg candidate matched quickly through verified profile + fast interview scheduling.',
    meta: 'Speed with quality',
    image: 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=1800&q=80',
  },
  {
    id: 'trusted-org',
    badge: 'Trusted Employer',
    title: 'Top regional facilities hiring now',
    body: 'Work with organizations that prioritize transparent communication and predictable onboarding.',
    meta: '30+ trusted partners',
    image: 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?auto=format&fit=crop&w=1800&q=80',
  },
  {
    id: 'platform-value',
    badge: 'Platform Advantage',
    title: 'One profile, many opportunities',
    body: 'Track compliance, applications, and recruiter updates in one premium career workspace.',
    meta: 'Built for staffing workflows',
    image: 'https://images.unsplash.com/photo-1521791136064-7986c2920216?auto=format&fit=crop&w=1800&q=80',
  },
];

const currentIndex = ref(0);
const activeCard = computed(() => cards[currentIndex.value] || cards[0]);

let timer = null;

function startRotatingCards() {
  timer = window.setInterval(() => {
    currentIndex.value = (currentIndex.value + 1) % cards.length;
  }, 5000);
}

onMounted(() => {
  startRotatingCards();
});

onBeforeUnmount(() => {
  if (timer) window.clearInterval(timer);
});
</script>

<style scoped>
.auth-showcase {
  min-height: 100vh;
}

.showcase-card {
  max-width: 32rem;
  border-radius: 1.35rem;
  border: 1px solid rgba(255, 255, 255, 0.24);
  background: rgba(15, 23, 42, 0.34);
  backdrop-filter: blur(10px);
  padding: 1rem 1.05rem 1.1rem;
  box-shadow: 0 28px 54px -34px rgba(15, 23, 42, 0.8);
  display: flex;
  flex-direction: column;
  gap: 0.7rem;
}

.card-fade-slide-enter-active,
.card-fade-slide-leave-active {
  transition: opacity 380ms ease, transform 380ms ease;
}

.card-fade-slide-enter-from {
  opacity: 0;
  transform: translateY(10px);
}

.card-fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
