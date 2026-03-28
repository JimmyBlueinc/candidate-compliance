<template>
  <div class="min-h-screen bg-white text-slate-900">
    <div class="max-w-6xl mx-auto px-6 py-14">
      <header class="flex items-center justify-between mb-10">
        <RouterLink to="/" class="text-lg font-bold tracking-tight">AgencyHQ</RouterLink>
        <RouterLink to="/signup" class="px-4 py-2 rounded-xl text-sm font-semibold text-white bg-slate-900">Get Started</RouterLink>
      </header>

      <section class="rounded-3xl border border-slate-200 p-8 md:p-12 bg-gradient-to-br from-emerald-50/60 to-white">
        <h1 class="text-4xl md:text-5xl font-bold tracking-tight">Trusted by growing healthcare staffing teams</h1>
        <p class="mt-4 text-slate-600 max-w-3xl">
          Agencies use AgencyHQ to run recruiting, compliance, and facility operations with fewer systems and better visibility.
        </p>
      </section>

      <section class="mt-10 grid grid-cols-1 md:grid-cols-2 gap-5">
        <article class="rounded-2xl border border-slate-200 p-6">
          <div class="text-xs uppercase tracking-wider font-semibold text-slate-500">Case Highlight</div>
          <h2 class="mt-2 font-semibold text-lg">Regional Nursing Network</h2>
          <p class="mt-2 text-sm text-slate-600">Improved fill-rate visibility and reduced compliance turnaround time across facilities.</p>
        </article>
        <article class="rounded-2xl border border-slate-200 p-6">
          <div class="text-xs uppercase tracking-wider font-semibold text-slate-500">Case Highlight</div>
          <h2 class="mt-2 font-semibold text-lg">Multi-state Healthcare Agency</h2>
          <p class="mt-2 text-sm text-slate-600">Standardized staffing operations and created one source of truth for finance and contracts.</p>
        </article>
      </section>
    </div>
  </div>
</template>
