<template>
  <div class="min-h-screen bg-white text-slate-900">
    <div class="max-w-6xl mx-auto px-6 py-14">
      <header class="flex items-center justify-between mb-10">
        <RouterLink to="/" class="text-lg font-bold tracking-tight">AgencyHQ</RouterLink>
        <RouterLink to="/signup" class="px-4 py-2 rounded-xl text-sm font-semibold text-white bg-slate-900">Start Trial</RouterLink>
      </header>

      <section class="rounded-3xl border border-slate-200 p-8 md:p-12 bg-gradient-to-br from-violet-50/55 to-white">
        <h1 class="text-4xl md:text-5xl font-bold tracking-tight">Pricing built for agency growth</h1>
        <p class="mt-4 text-slate-600 max-w-3xl">
          Start with core operations and scale modules as your staffing footprint grows.
        </p>
      </section>

      <section class="mt-10 grid grid-cols-1 md:grid-cols-3 gap-5">
        <article class="rounded-2xl border border-slate-200 p-6">
          <div class="text-xs uppercase tracking-wider font-semibold text-slate-500">Starter</div>
          <div class="mt-3 text-3xl font-bold">$299<span class="text-base font-medium text-slate-500">/mo</span></div>
          <ul class="mt-4 space-y-2 text-sm text-slate-600">
            <li>Recruiting + candidate pipeline</li>
            <li>Basic credential tracking</li>
            <li>Core reporting</li>
          </ul>
        </article>
        <article class="rounded-2xl border-2 border-slate-900 p-6">
          <div class="text-xs uppercase tracking-wider font-semibold text-slate-500">Growth</div>
          <div class="mt-3 text-3xl font-bold">$699<span class="text-base font-medium text-slate-500">/mo</span></div>
          <ul class="mt-4 space-y-2 text-sm text-slate-600">
            <li>Everything in Starter</li>
            <li>Advanced dashboards + exports</li>
            <li>Facilities contracts workflows</li>
          </ul>
        </article>
        <article class="rounded-2xl border border-slate-200 p-6">
          <div class="text-xs uppercase tracking-wider font-semibold text-slate-500">Enterprise</div>
          <div class="mt-3 text-3xl font-bold">Custom</div>
          <ul class="mt-4 space-y-2 text-sm text-slate-600">
            <li>Org-wide integrations hub</li>
            <li>Priority support + onboarding</li>
            <li>Custom security/governance</li>
          </ul>
        </article>
      </section>
    </div>
  </div>
</template>
