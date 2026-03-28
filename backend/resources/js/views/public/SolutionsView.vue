<template>
  <div class="min-h-screen bg-white text-slate-900">
    <div class="max-w-6xl mx-auto px-6 py-14">
      <header class="flex items-center justify-between mb-10">
        <RouterLink to="/" class="text-lg font-bold tracking-tight">AgencyHQ</RouterLink>
        <RouterLink to="/signup" class="px-4 py-2 rounded-xl text-sm font-semibold text-white bg-slate-900">Get Started</RouterLink>
      </header>

      <section class="rounded-3xl border border-slate-200 p-8 md:p-12 bg-gradient-to-br from-slate-50 to-white">
        <h1 class="text-4xl md:text-5xl font-bold tracking-tight">Solutions for healthcare staffing operations</h1>
        <p class="mt-4 text-slate-600 max-w-3xl">
          Built to unify compliance, recruiting, scheduling, finance, and facility workflows in one operating system.
        </p>
      </section>

      <section class="mt-10 grid grid-cols-1 md:grid-cols-3 gap-5">
        <article class="rounded-2xl border border-slate-200 p-6">
          <h2 class="font-semibold text-lg">Recruiting Velocity</h2>
          <p class="mt-2 text-sm text-slate-600">Source candidates, track pipeline stages, and reduce placement time.</p>
        </article>
        <article class="rounded-2xl border border-slate-200 p-6">
          <h2 class="font-semibold text-lg">Compliance Confidence</h2>
          <p class="mt-2 text-sm text-slate-600">Automate credential visibility, expirations, and review workflows.</p>
        </article>
        <article class="rounded-2xl border border-slate-200 p-6">
          <h2 class="font-semibold text-lg">Finance Intelligence</h2>
          <p class="mt-2 text-sm text-slate-600">Track margin by facility, export reports, and streamline invoicing.</p>
        </article>
      </section>
    </div>
  </div>
</template>
