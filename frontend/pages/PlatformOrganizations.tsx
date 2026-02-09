import React, { useEffect, useMemo, useState } from 'react';
import { useApi } from '../lib/api';

type OrgDomain = {
  id: number;
  domain: string;
  is_active: boolean;
};

type Organization = {
  id: number;
  name: string;
  slug: string;
  is_active: boolean;
  domains: OrgDomain[];
};

const PlatformOrganizations: React.FC = () => {
  const { request } = useApi();
  const [orgs, setOrgs] = useState<Organization[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const [name, setName] = useState('');
  const [slug, setSlug] = useState('');
  const [domain, setDomain] = useState('');
  const [creating, setCreating] = useState(false);

  const [selectedOrgId, setSelectedOrgId] = useState<number | null>(null);
  const selectedOrg = useMemo(() => orgs.find(o => o.id === selectedOrgId) || null, [orgs, selectedOrgId]);

  const [ownerName, setOwnerName] = useState('');
  const [ownerEmail, setOwnerEmail] = useState('');
  const [ownerPassword, setOwnerPassword] = useState('');
  const [ownerResult, setOwnerResult] = useState<any>(null);
  const [creatingOwner, setCreatingOwner] = useState(false);

  const load = async () => {
    try {
      setLoading(true);
      setError('');
      const res: any = await request('/platform/organizations');
      setOrgs(res?.organizations || []);
    } catch (e: any) {
      setOrgs([]);
      setError(e?.message || 'Failed to load organizations');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const createOrg = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      setCreating(true);
      setError('');
      await request('/platform/organizations', {
        method: 'POST',
        body: JSON.stringify({
          name,
          slug,
          domain: domain || undefined,
          is_active: true,
        }),
      });
      setName('');
      setSlug('');
      setDomain('');
      await load();
    } catch (e: any) {
      setError(e?.message || 'Failed to create organization');
    } finally {
      setCreating(false);
    }
  };

  const createOwner = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedOrg) return;

    try {
      setCreatingOwner(true);
      setError('');
      setOwnerResult(null);
      const res: any = await request(`/platform/organizations/${selectedOrg.id}/owner`, {
        method: 'POST',
        body: JSON.stringify({
          name: ownerName,
          email: ownerEmail,
          password: ownerPassword || undefined,
        }),
      });
      setOwnerResult(res);
      setOwnerName('');
      setOwnerEmail('');
      setOwnerPassword('');
      await load();
    } catch (e: any) {
      setError(e?.message || 'Failed to create organization owner');
    } finally {
      setCreatingOwner(false);
    }
  };

  return (
    <div className="space-y-8">
      <div className="glass-dark rounded-[32px] border border-white/5 p-8">
        <div className="flex items-start justify-between gap-6">
          <div>
            <h2 className="font-display text-2xl text-white">Platform Organizations</h2>
            <p className="text-slate-500 text-sm">Provision organizations, domains, and org owners (tenant super admins).</p>
          </div>
          <div className="text-right">
            <div className="text-[10px] uppercase tracking-[0.25em] text-slate-500 font-black">Scope</div>
            <div className="text-slate-200 text-sm font-semibold">Landlord Console</div>
          </div>
        </div>
      </div>

      {error && (
        <div className="glass-dark rounded-2xl border border-red-500/20 bg-red-500/10 p-4 text-sm text-red-300">
          {error}
        </div>
      )}

      <div className="grid grid-cols-12 gap-8">
        <div className="col-span-12 lg:col-span-5 space-y-6">
          <div className="glass-dark rounded-[32px] border border-white/5 p-8">
            <h3 className="font-display text-xl text-white mb-6">Create Organization</h3>
            <form onSubmit={createOrg} className="space-y-4">
              <div className="space-y-2">
                <label className="text-[10px] uppercase tracking-[0.25em] text-slate-500 font-black">Organization Name</label>
                <input className="w-full bg-white/[0.03] border border-white/10 rounded-2xl px-4 py-3 text-white focus:outline-none focus:border-primary/50" value={name} onChange={(e) => setName(e.target.value)} required />
              </div>
              <div className="space-y-2">
                <label className="text-[10px] uppercase tracking-[0.25em] text-slate-500 font-black">Slug</label>
                <input className="w-full bg-white/[0.03] border border-white/10 rounded-2xl px-4 py-3 text-white focus:outline-none focus:border-primary/50" value={slug} onChange={(e) => setSlug(e.target.value)} placeholder="acme" required />
              </div>
              <div className="space-y-2">
                <label className="text-[10px] uppercase tracking-[0.25em] text-slate-500 font-black">Initial Domain (optional)</label>
                <input className="w-full bg-white/[0.03] border border-white/10 rounded-2xl px-4 py-3 text-white focus:outline-none focus:border-primary/50" value={domain} onChange={(e) => setDomain(e.target.value)} placeholder="acme.localhost" />
              </div>

              <button disabled={creating} className="w-full bg-white text-black font-black py-3 rounded-2xl hover:scale-[1.01] active:scale-[0.99] transition-all disabled:opacity-50">
                {creating ? 'Creating…' : 'Create Organization'}
              </button>
            </form>
          </div>

          <div className="glass-dark rounded-[32px] border border-white/5 overflow-hidden">
            <div className="px-6 py-4 border-b border-white/5 flex items-center justify-between">
              <h3 className="font-display text-xl text-white">Organizations</h3>
              <button onClick={load} className="text-xs font-black uppercase tracking-widest text-slate-400 hover:text-white">Refresh</button>
            </div>

            <div className="divide-y divide-white/5">
              {loading ? (
                <div className="p-6 text-slate-500">Loading…</div>
              ) : orgs.length === 0 ? (
                <div className="p-6 text-slate-500">No organizations yet.</div>
              ) : (
                orgs.map((o) => (
                  <button
                    key={o.id}
                    onClick={() => setSelectedOrgId(o.id)}
                    className={`w-full text-left p-6 hover:bg-white/5 transition-colors ${selectedOrgId === o.id ? 'bg-white/5' : ''}`}
                  >
                    <div className="flex items-start justify-between gap-4">
                      <div>
                        <div className="text-white font-semibold">{o.name}</div>
                        <div className="text-[11px] text-slate-500 font-bold">{o.slug}</div>
                      </div>
                      <div className={`text-[10px] font-black uppercase tracking-widest px-2 py-1 rounded-full border ${o.is_active ? 'bg-green-500/10 text-green-400 border-green-500/20' : 'bg-slate-500/10 text-slate-400 border-white/10'}`}>
                        {o.is_active ? 'Active' : 'Inactive'}
                      </div>
                    </div>
                    <div className="mt-3 text-xs text-slate-500">
                      Domains: {o.domains?.length ?? 0}
                    </div>
                  </button>
                ))
              )}
            </div>
          </div>
        </div>

        <div className="col-span-12 lg:col-span-7 space-y-6">
          <div className="glass-dark rounded-[32px] border border-white/5 p-8">
            <h3 className="font-display text-xl text-white mb-2">Selected Organization</h3>
            {!selectedOrg ? (
              <p className="text-slate-500 text-sm">Select an organization to provision an owner account.</p>
            ) : (
              <div className="space-y-6">
                <div className="grid grid-cols-2 gap-4">
                  <div className="p-4 rounded-2xl bg-white/[0.03] border border-white/5">
                    <div className="text-[10px] uppercase tracking-[0.25em] text-slate-500 font-black">Name</div>
                    <div className="text-white font-semibold mt-1">{selectedOrg.name}</div>
                  </div>
                  <div className="p-4 rounded-2xl bg-white/[0.03] border border-white/5">
                    <div className="text-[10px] uppercase tracking-[0.25em] text-slate-500 font-black">Slug</div>
                    <div className="text-white font-semibold mt-1">{selectedOrg.slug}</div>
                  </div>
                </div>

                <div className="p-4 rounded-2xl bg-white/[0.03] border border-white/5">
                  <div className="text-[10px] uppercase tracking-[0.25em] text-slate-500 font-black mb-2">Domains</div>
                  <div className="space-y-2">
                    {(selectedOrg.domains || []).length === 0 ? (
                      <div className="text-slate-500 text-sm">No domains yet.</div>
                    ) : (
                      (selectedOrg.domains || []).map((d) => (
                        <div key={d.id} className="flex items-center justify-between bg-black/20 border border-white/5 rounded-xl px-4 py-2">
                          <div className="text-slate-200 text-sm font-semibold">{d.domain}</div>
                          <div className={`text-[10px] font-black uppercase tracking-widest px-2 py-1 rounded-full border ${d.is_active ? 'bg-green-500/10 text-green-400 border-green-500/20' : 'bg-slate-500/10 text-slate-400 border-white/10'}`}>
                            {d.is_active ? 'Active' : 'Inactive'}
                          </div>
                        </div>
                      ))
                    )}
                  </div>
                </div>

                <div className="border-t border-white/5 pt-6">
                  <h4 className="font-display text-lg text-white mb-4">Create Org Owner (org_super_admin)</h4>
                  <form onSubmit={createOwner} className="space-y-4">
                    <div className="grid grid-cols-2 gap-4">
                      <div className="space-y-2">
                        <label className="text-[10px] uppercase tracking-[0.25em] text-slate-500 font-black">Owner Name</label>
                        <input className="w-full bg-white/[0.03] border border-white/10 rounded-2xl px-4 py-3 text-white focus:outline-none focus:border-primary/50" value={ownerName} onChange={(e) => setOwnerName(e.target.value)} required />
                      </div>
                      <div className="space-y-2">
                        <label className="text-[10px] uppercase tracking-[0.25em] text-slate-500 font-black">Owner Email</label>
                        <input type="email" className="w-full bg-white/[0.03] border border-white/10 rounded-2xl px-4 py-3 text-white focus:outline-none focus:border-primary/50" value={ownerEmail} onChange={(e) => setOwnerEmail(e.target.value)} required />
                      </div>
                    </div>

                    <div className="space-y-2">
                      <label className="text-[10px] uppercase tracking-[0.25em] text-slate-500 font-black">Password (optional)</label>
                      <input type="text" className="w-full bg-white/[0.03] border border-white/10 rounded-2xl px-4 py-3 text-white focus:outline-none focus:border-primary/50" value={ownerPassword} onChange={(e) => setOwnerPassword(e.target.value)} placeholder="Leave blank to auto-generate" />
                    </div>

                    <button disabled={creatingOwner} className="w-full bg-white text-black font-black py-3 rounded-2xl hover:scale-[1.01] active:scale-[0.99] transition-all disabled:opacity-50">
                      {creatingOwner ? 'Provisioning…' : 'Provision Owner'}
                    </button>
                  </form>

                  {ownerResult && (
                    <div className="mt-6 p-4 rounded-2xl border border-green-500/20 bg-green-500/10">
                      <div className="text-green-300 text-sm font-bold">Owner provisioned.</div>
                      {ownerResult?.tenant_url && (
                        <div className="mt-2 text-xs text-green-200 break-all">
                          Tenant URL: <span className="font-semibold">{ownerResult.tenant_url}</span>
                        </div>
                      )}
                      {ownerResult?.temp_password && (
                        <div className="mt-2 text-xs text-green-200 break-all">
                          Temporary Password: <span className="font-semibold">{ownerResult.temp_password}</span>
                        </div>
                      )}
                    </div>
                  )}
                </div>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

export default PlatformOrganizations;
