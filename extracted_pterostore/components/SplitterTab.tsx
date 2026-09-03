import React, { useState, useEffect, useCallback } from 'react';
import tw from 'twin.macro';
import http from '@/api/http';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import GreyRowBox from '@/components/elements/GreyRowBox';
import Spinner from '@/components/elements/Spinner';

const API = '/api/client/extensions/pterostore';

interface SplitServerData {
    is_split: boolean;
    id: number;
    server_id: number;
    cpu: number;
    ram: number;
    disk: number;
    ports: number;
    databases: number;
    free_cpu: number;
    free_ram: number;
    free_disk: number;
    free_ports: number;
    free_databases: number;
}

const fmt = (key: string, val: number): string => {
    if (key === 'cpu') return val + ' %';
    if (key === 'ram' || key === 'disk') return val >= 1024 ? (val / 1024).toFixed(0) + ' GiB' : val + ' MiB';
    return String(val);
};

const SplitterTab: React.FC = () => {
    const [data, setData] = useState<SplitServerData | null>(null);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [deleting, setDeleting] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [success, setSuccess] = useState<string | null>(null);
    const [cpu, setCpu] = useState(0);
    const [ram, setRam] = useState(0);
    const [disk, setDisk] = useState(0);
    const [ports, setPorts] = useState(0);
    const [databases, setDatabases] = useState(0);
    const [freeInfo, setFreeInfo] = useState<{ enabled: boolean; claimed: boolean; cpu: number; ram: number; disk: number; ports: number; databases: number } | null>(null);
    const [claiming, setClaiming] = useState(false);

    const serverId = window.location.pathname.match(/\/server\/([a-f0-9\-]+)/)?.[1] || '';

    const fetchData = useCallback(() => {
        if (!serverId) return;
        setLoading(true);
        http.get(`${API}/splitter/server-info/${serverId}`)
            .then((r) => {
                const d = r.data as SplitServerData;
                setData(d);
                setCpu(d.cpu);
                setRam(d.ram);
                setDisk(d.disk);
                setPorts(d.ports);
                setDatabases(d.databases);
            })
            .catch((e) => setError(e?.response?.data?.error || 'Failed to load splitter data.'))
            .finally(() => setLoading(false));
    }, [serverId]);

    useEffect(() => { fetchData(); }, [fetchData]);

    useEffect(() => {
        http.get(`${API}/store/free-resources-info`).then((r) => setFreeInfo(r.data)).catch(() => {});
    }, []);

    const handleClaimFree = () => {
        setClaiming(true);
        setError(null);
        http.post(`${API}/store/claim-free-resources`, {})
            .then((r) => {
                setSuccess(r.data.message || 'Free resources claimed!');
                setFreeInfo((prev) => prev ? { ...prev, claimed: true } : prev);
                fetchData();
            })
            .catch((e) => setError(e?.response?.data?.error || 'Failed to claim.'))
            .finally(() => setClaiming(false));
    };

    const handleSave = () => {
        if (!data) return;
        if (ports < data.ports) { setError('Ports cannot be lower than ' + data.ports); return; }
        if (databases < data.databases) { setError('Databases cannot be lower than ' + data.databases); return; }
        setSaving(true);
        setError(null);
        setSuccess(null);
        http.post(`${API}/splitter/update-server`, {
            server_id: data.server_id,
            cpu, ram, disk, ports, databases,
        }).then(() => {
            setSuccess('Server updated successfully!');
            fetchData();
        }).catch((e) => setError(e?.response?.data?.error || 'Failed to update.'))
        .finally(() => setSaving(false));
    };

    const handleDelete = () => {
        if (!data || !window.confirm('Permanently delete this split server? Resources will be freed.')) return;
        setDeleting(true);
        setError(null);
        http.post(`${API}/splitter/delete-server`, { server_id: data.server_id })
            .then(() => { window.location.href = '/'; })
            .catch((e) => { setError(e?.response?.data?.error || 'Failed to delete.'); setDeleting(false); });
    };

    if (loading) {
        return <Spinner size={'large'} centered />;
    }

    if (!data || !data.is_split) {
        return <p css={tw`text-center text-sm text-neutral-300`}>This server is not a split server.</p>;
    }

    return (
        <div css={tw`mt-4 px-2 sm:px-0`}>
            {error && (
                <div css={tw`bg-red-500 bg-opacity-25 border border-red-400 border-opacity-50 rounded p-3 mb-4 text-red-100 text-sm`}>
                    {error}
                </div>
            )}
            {success && (
                <div css={tw`bg-green-500 bg-opacity-25 border border-green-400 border-opacity-50 rounded p-3 mb-4 text-green-100 text-sm`}>
                    {success}
                </div>
            )}

            {/* Free Resources Available */}
            <GreyRowBox css={tw`mb-4`}>
                <div css={tw`w-full`}>
                    <p css={tw`text-xs font-semibold text-green-400 mb-1`}>Free Resources Available</p>
                    <p css={tw`text-xs text-neutral-400`}>
                        CPU: {data.free_cpu}% &bull; RAM: {fmt('ram', data.free_ram)} &bull; Disk: {fmt('disk', data.free_disk)} &bull; Ports: {data.free_ports} &bull; DBs: {data.free_databases}
                    </p>
                </div>
            </GreyRowBox>

            {/* Claim Free Resources */}
            {freeInfo && freeInfo.enabled && !freeInfo.claimed && (
                <GreyRowBox css={tw`mb-4`}>
                    <div css={tw`flex justify-between items-center w-full flex-wrap gap-2`}>
                        <div>
                            <p css={tw`text-sm font-semibold text-blue-400`}>Claim Free Resources</p>
                            <p css={tw`text-xs text-neutral-400 mt-1`}>
                                CPU: {freeInfo.cpu}% &bull; RAM: {fmt('ram', freeInfo.ram)} &bull; Disk: {fmt('disk', freeInfo.disk)} &bull; Ports: {freeInfo.ports} &bull; DBs: {freeInfo.databases}
                            </p>
                        </div>
                        <button
                            onClick={handleClaimFree}
                            disabled={claiming}
                            css={tw`px-4 py-2 rounded text-sm font-semibold text-white transition-colors duration-150`}
                            style={{ background: '#3182ce', opacity: claiming ? 0.6 : 1, cursor: claiming ? 'not-allowed' : 'pointer' }}
                        >
                            {claiming ? 'Claiming...' : 'Claim Now'}
                        </button>
                    </div>
                </GreyRowBox>
            )}
            {freeInfo && freeInfo.enabled && freeInfo.claimed && (
                <div css={tw`bg-green-500 bg-opacity-10 border border-green-400 border-opacity-25 rounded p-2 mb-4 text-green-400 text-xs font-medium`}>
                    Free resources already claimed.
                </div>
            )}

            {/* Resource Fields */}
            <TitledGreyBox title={'Server Resources'} css={tw`mb-4`}>
                <div css={tw`grid grid-cols-2 sm:grid-cols-3 gap-4`}>
                    <div>
                        <label css={tw`block text-xs uppercase text-neutral-400 tracking-wide font-semibold mb-1`}>CPU (%)</label>
                        <input type={'number'} value={cpu} min={1} onChange={(e) => setCpu(Number(e.target.value))}
                            css={tw`w-full bg-neutral-600 border border-neutral-500 rounded px-3 py-2 text-sm text-neutral-200 focus:border-primary-400 focus:outline-none`} />
                    </div>
                    <div>
                        <label css={tw`block text-xs uppercase text-neutral-400 tracking-wide font-semibold mb-1`}>RAM (MB)</label>
                        <input type={'number'} value={ram} min={64} onChange={(e) => setRam(Number(e.target.value))}
                            css={tw`w-full bg-neutral-600 border border-neutral-500 rounded px-3 py-2 text-sm text-neutral-200 focus:border-primary-400 focus:outline-none`} />
                    </div>
                    <div>
                        <label css={tw`block text-xs uppercase text-neutral-400 tracking-wide font-semibold mb-1`}>Disk (MB)</label>
                        <input type={'number'} value={disk} min={256} onChange={(e) => setDisk(Number(e.target.value))}
                            css={tw`w-full bg-neutral-600 border border-neutral-500 rounded px-3 py-2 text-sm text-neutral-200 focus:border-primary-400 focus:outline-none`} />
                    </div>
                    <div>
                        <label css={tw`block text-xs uppercase text-neutral-400 tracking-wide font-semibold mb-1`}>Ports</label>
                        <input type={'number'} value={ports} min={data.ports} onChange={(e) => setPorts(Number(e.target.value))}
                            css={tw`w-full bg-neutral-600 border border-neutral-500 rounded px-3 py-2 text-sm text-neutral-200 focus:border-primary-400 focus:outline-none`} />
                        <small css={tw`text-yellow-400 text-xs`}>Min: {data.ports}</small>
                    </div>
                    <div>
                        <label css={tw`block text-xs uppercase text-neutral-400 tracking-wide font-semibold mb-1`}>Databases</label>
                        <input type={'number'} value={databases} min={data.databases} onChange={(e) => setDatabases(Number(e.target.value))}
                            css={tw`w-full bg-neutral-600 border border-neutral-500 rounded px-3 py-2 text-sm text-neutral-200 focus:border-primary-400 focus:outline-none`} />
                        <small css={tw`text-yellow-400 text-xs`}>Min: {data.databases}</small>
                    </div>
                </div>
            </TitledGreyBox>

            {/* Actions */}
            <div css={tw`flex gap-3 justify-end`}>
                <button
                    onClick={handleDelete}
                    disabled={deleting}
                    css={tw`px-6 py-2 rounded text-sm font-semibold text-white transition-colors duration-150`}
                    style={{ background: '#e53e3e', cursor: deleting ? 'not-allowed' : 'pointer' }}
                >
                    {deleting ? 'Deleting...' : 'Delete Server'}
                </button>
                <button
                    onClick={handleSave}
                    disabled={saving}
                    css={tw`px-6 py-2 rounded text-sm font-semibold text-white transition-colors duration-150`}
                    style={{ background: '#3182ce', cursor: saving ? 'not-allowed' : 'pointer' }}
                >
                    {saving ? 'Saving...' : 'Save Changes'}
                </button>
            </div>
        </div>
    );
};

export default SplitterTab;
