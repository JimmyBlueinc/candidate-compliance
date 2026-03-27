<template>
  <div class="space-y-4">
    <Card>
      <template #content>
        <div class="flex items-start justify-between gap-6">
          <div>
            <h2 class="font-display text-xl">Platform Organizations</h2>
            <p class="text-sm text-[color:var(--p-text-muted-color)]">Provision organizations, domains, and org owners (organization owners).</p>
          </div>
          <div class="text-right">
            <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Scope</div>
            <div class="text-sm font-semibold">Landlord Console</div>
          </div>
        </div>
      </template>
    </Card>

    <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>

    <div class="grid grid-cols-12 gap-4">
      <div class="col-span-12 lg:col-span-5 space-y-4">
        <Card>
          <template #content>
            <h3 class="font-display text-lg mb-4">Create Organization</h3>
            <form class="space-y-3" @submit.prevent="createOrg">
              <div class="space-y-2">
                <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Organization Name</label>
                <InputText v-model="name" class="w-full" required size="small" />
              </div>
              <div class="space-y-2">
                <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Slug</label>
                <InputText v-model="slug" class="w-full" placeholder="acme" required size="small" />
              </div>
              <div class="space-y-2">
                <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Initial Domain (optional)</label>
                <InputText v-model="domain" class="w-full" placeholder="acme.localhost" size="small" />
              </div>
              <div>
                <Button :loading="creating" type="submit" label="Create" size="small" />
              </div>
            </form>
          </template>
        </Card>

        <Card>
          <template #content>
            <div class="flex items-center justify-between gap-4 mb-4">
              <h3 class="font-display text-lg">Organizations</h3>
              <Button label="Refresh" size="small" severity="secondary" outlined @click="load" />
            </div>

            <div v-if="loading" class="py-4 text-[color:var(--p-text-muted-color)]">Loading…</div>
            <div v-else-if="orgs.length === 0" class="py-4 text-[color:var(--p-text-muted-color)]">No organizations yet.</div>
            <Listbox
              v-else
              v-model="selectedOrgId"
              :options="orgs"
              optionLabel="name"
              optionValue="id"
              class="w-full"
              listStyle="max-height: 420px"
              size="small"
            >
              <template #option="slotProps">
                <div class="flex items-start justify-between gap-4 w-full">
                  <div class="min-w-0">
                    <div class="font-semibold truncate">{{ slotProps.option.name }}</div>
                    <div class="text-[11px] text-[color:var(--p-text-muted-color)] font-bold">{{ slotProps.option.slug }}</div>
                    <div class="mt-1 text-xs text-[color:var(--p-text-muted-color)]">Domains: {{ slotProps.option.domains?.length ?? 0 }}</div>
                  </div>
                  <Tag :severity="slotProps.option.is_active ? 'success' : 'secondary'" :value="slotProps.option.is_active ? 'Active' : 'Inactive'" />
                </div>
              </template>
            </Listbox>
          </template>
        </Card>
      </div>

      <div class="col-span-12 lg:col-span-7 space-y-4">
        <Card>
          <template #content>
            <h3 class="font-display text-lg mb-1">Selected Organization</h3>
            <p v-if="!selectedOrg" class="text-sm text-[color:var(--p-text-muted-color)]">Select an organization to provision an owner account.</p>

            <div v-else class="space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div class="p-3 rounded-2xl border border-[color:var(--p-surface-border)]">
                  <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Name</div>
                  <div class="font-semibold mt-1">{{ selectedOrg.name }}</div>
                </div>
                <div class="p-3 rounded-2xl border border-[color:var(--p-surface-border)]">
                  <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Slug</div>
                  <div class="font-semibold mt-1">{{ selectedOrg.slug }}</div>
                </div>
              </div>

              <div class="space-y-3">
                <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Domains</div>
                <DataTable :value="selectedOrg.domains || []" stripedRows responsiveLayout="scroll" size="small">
                  <Column field="domain" header="Domain" />
                  <Column header="Status" style="width: 1%; white-space: nowrap">
                    <template #body="{ data }">
                      <Tag :severity="data.is_active ? 'success' : 'secondary'" :value="data.is_active ? 'Active' : 'Inactive'" />
                    </template>
                  </Column>
                  <template #empty>
                    <div class="py-4 text-[color:var(--p-text-muted-color)]">No domains yet.</div>
                  </template>
                </DataTable>
              </div>

              <Divider />

              <div class="space-y-4">
                <h4 class="font-display text-base">Create Org Owner (org_super_admin)</h4>
                <form class="space-y-3" @submit.prevent="createOwner">
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                      <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Owner Name</label>
                      <InputText v-model="ownerName" class="w-full" required size="small" />
                    </div>
                    <div class="space-y-2">
                      <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Owner Email</label>
                      <InputText v-model="ownerEmail" type="email" class="w-full" required size="small" />
                    </div>
                  </div>

                  <div class="space-y-2">
                    <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Password (optional)</label>
                    <InputText v-model="ownerPassword" class="w-full" placeholder="Leave blank to auto-generate" size="small" />
                  </div>

                  <div>
                    <Button :loading="creatingOwner" type="submit" label="Provision" size="small" />
                  </div>
                </form>

                <Message v-if="ownerResult" severity="success" :closable="false">
                  <div class="font-semibold">Owner provisioned.</div>
                  <div v-if="ownerResult.tenant_url" class="mt-1 text-sm break-all">Organization URL: <span class="font-semibold">{{ ownerResult.tenant_url }}</span></div>
                  <div v-if="ownerResult.temp_password" class="mt-1 text-sm break-all">Temporary Password: <span class="font-semibold">{{ ownerResult.temp_password }}</span></div>
                </Message>
              </div>
            </div>
          </template>
        </Card>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { apiGet, apiPost } from '../../lib/api';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Divider from 'primevue/divider';
import InputText from 'primevue/inputtext';
import Listbox from 'primevue/listbox';
import Message from 'primevue/message';
import Tag from 'primevue/tag';

const orgs = ref([]);
const loading = ref(true);
const error = ref('');

const name = ref('');
const slug = ref('');
const domain = ref('');
const creating = ref(false);

const selectedOrgId = ref(null);
const selectedOrg = computed(() => orgs.value.find((o) => o.id === selectedOrgId.value) || null);

const ownerName = ref('');
const ownerEmail = ref('');
const ownerPassword = ref('');
const ownerResult = ref(null);
const creatingOwner = ref(false);

async function load() {
    try {
        loading.value = true;
        error.value = '';
        const res = await apiGet('/platform/organizations');
        orgs.value = res?.organizations || [];

        if (selectedOrgId.value && !orgs.value.some((o) => o.id === selectedOrgId.value)) {
            selectedOrgId.value = null;
        }
    } catch (e) {
        orgs.value = [];
        error.value = e?.response?.data?.message || e?.message || 'Failed to load organizations';
    } finally {
        loading.value = false;
    }
}

async function createOrg() {
    try {
        creating.value = true;
        error.value = '';
        await apiPost('/platform/organizations', {
            name: name.value,
            slug: slug.value,
            domain: domain.value || undefined,
            is_active: true,
        });
        name.value = '';
        slug.value = '';
        domain.value = '';
        await load();
    } catch (e) {
        error.value = e?.response?.data?.message || e?.message || 'Failed to create organization';
    } finally {
        creating.value = false;
    }
}

async function createOwner() {
    if (!selectedOrg.value) return;

    try {
        creatingOwner.value = true;
        error.value = '';
        ownerResult.value = null;

        const res = await apiPost(`/platform/organizations/${selectedOrg.value.id}/owner`, {
            name: ownerName.value,
            email: ownerEmail.value,
            password: ownerPassword.value || undefined,
        });

        ownerResult.value = res;
        ownerName.value = '';
        ownerEmail.value = '';
        ownerPassword.value = '';
        await load();
    } catch (e) {
        error.value = e?.response?.data?.message || e?.message || 'Failed to create organization owner';
    } finally {
        creatingOwner.value = false;
    }
}

onMounted(load);
</script>
