import React, { useState, useEffect, useCallback } from 'react';
import tw from 'twin.macro';
import http from '@/api/http';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import GreyRowBox from '@/components/elements/GreyRowBox';
import Spinner from '@/components/elements/Spinner';

const API = '/api/client/extensions/pterostore';

interface AvailablePackage {
    id: number;
    name: string;
    price_monthly: number;
    price_weekly: number;
    price_hourly: number;
    cpu: number;
    ram: number;
    disk: number;
}

interface PackageData {
    has_package: boolean;
    server_id: number;
    package_id: number;
    package_name: string;
    billing_cycle: string;
    cost: number;
    currency: string;
    expires_at: string;
    suspended: boolean;
    auto_renew: boolean;
    balance: number;
    available_packages: AvailablePackage[];
    billing_change_enabled: boolean;
}

const PackageTab: React.FC = () => {
    const [data, setData] = useState<PackageData | null>(null);
    const [loading, setLoading] = useState(true);
    const [renewing, setRenewing] = useState(false);
    const [toggling, setToggling] = useState(false);
    const [changingPkg, setChangingPkg] = useState(false);
    const [changingBilling, setChangingBilling] = useState(false);
    const [extendingHours, setExtendingHours] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [success, setSuccess] = useState<string | null>(null);
    const [selectedPkg, setSelectedPkg] = useState<number | ''>('');
    const [selectedBilling, setSelectedBilling] = useState('');
    const [hoursInput, setHoursInput] = useState('1');

    const serverId = window.location.pathname.match(/\/server\/([a-f0-9\-]+)/)?.[1] || '';

    const fetchData = useCallback(() => {
        if (!serverId) return;
        setLoading(true);
        http.get(`${API}/store/server-package/${serverId}`)
            .then((r) => {
                const d = r.data as PackageData;
                setData(d);
                setSelectedBilling(d.billing_cycle);
            })
            .catch((e) => setError(e?.response?.data?.error || 'Failed to load package data.'))
            .finally(() => setLoading(false));
    }, [serverId]);

    useEffect(() => { fetchData(); }, [fetchData]);

    const handleRenew = () => {
        if (!data) return;
        setRenewing(true);
        setError(null);
        setSuccess(null);
        http.post(`${API}/store/renew`, { server_id: data.server_id })
            .then((r) => {
                setSuccess('Server renewed! New expiry: ' + new Date(r.data.expires_at).toLocaleString());
                fetchData();
            })
            .catch((e) => setError(e?.response?.data?.error || 'Renewal failed.'))
            .finally(() => setRenewing(false));
    };

    const handleToggleAutoRenew = () => {
        if (!data) return;
        setToggling(true);
        setError(null);
        setSuccess(null);
        http.post(`${API}/store/toggle-auto-renew`, { server_id: data.server_id })
            .then((r) => {
                setSuccess(r.data.auto_renew ? 'Auto-renew enabled.' : 'Auto-renew disabled.');
                fetchData();
            })
            .catch((e) => setError(e?.response?.data?.error || 'Failed to toggle auto-renew.'))
            .finally(() => setToggling(false));
    };

    const handleChangePackage = () => {
        if (!data || !selectedPkg) return;
        setChangingPkg(true);
        setError(null);
        setSuccess(null);
        http.post(`${API}/store/change-package`, { server_id: data.server_id, new_package_id: selectedPkg })
            .then((r) => {
                setSuccess(r.data.message || 'Package changed!');
                setSelectedPkg('');
                fetchData();
            })
            .catch((e) => setError(e?.response?.data?.error || 'Failed to change package.'))
            .finally(() => setChangingPkg(false));
    };

    const handleChangeBilling = () => {
        if (!data || selectedBilling === data.billing_cycle) return;
        setChangingBilling(true);
        setError(null);
        setSuccess(null);
        http.post(`${API}/store/change-billing`, { server_id: data.server_id, billing_cycle: selectedBilling })
            .then((r) => {
                setSuccess(r.data.message || 'Billing cycle changed!');
                fetchData();
            })
            .catch((e) => setError(e?.response?.data?.error || 'Failed to change billing.'))
            .finally(() => setChangingBilling(false));
    };

    const handleExtendHours = () => {
        if (!data) return;
        const hours = parseInt(hoursInput, 10);
        if (isNaN(hours) || hours < 1 || hours > 10000) {
            setError('Enter a value between 1 and 10000 hours.');
            return;
        }
        setExtendingHours(true);
        setError(null);
        setSuccess(null);
        http.post(`${API}/store/extend-hours`, { server_id: data.server_id, hours })
            .then((r) => {
                setSuccess(r.data.message || 'Hours extended!');
                fetchData();
            })
            .catch((e) => setError(e?.response?.data?.error || 'Failed to extend hours.'))
            .finally(() => setExtendingHours(false));
    };

    if (loading) {
        return <Spinner size={'large'} centered />;
    }

    if (!data || !data.has_package) {
        return <p css={tw`text-center text-sm text-neutral-300`}>This server has no active package/billing.</p>;
    }

    const expiresAt = new Date(data.expires_at);
    const now = new Date();
    const diffMs = expiresAt.getTime() - now.getTime();
    const totalMinutes = Math.floor(diffMs / (1000 * 60));
    const totalHours = Math.floor(diffMs / (1000 * 60 * 60));
    const totalDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
    const remainingHours = totalHours - (totalDays * 24);

    let timeText: string;
    let timeColor: string;
    if (data.suspended) {
        timeText = 'Expired \u2014 Server Suspended';
        timeColor = 'text-red-400';
    } else if (diffMs < 0) {
        timeText = 'Expired';
        timeColor = 'text-red-400';
    } else if (totalMinutes < 60) {
        timeText = totalMinutes <= 0 ? 'Expires very soon' : `Expires in ${totalMinutes} minute${totalMinutes === 1 ? '' : 's'}`;
        timeColor = 'text-yellow-400';
    } else if (totalDays === 0) {
        timeText = `Expires in ${totalHours} hour${totalHours === 1 ? '' : 's'}`;
        timeColor = 'text-yellow-400';
    } else if (totalDays <= 3) {
        timeText = `Expires in ${totalDays} day${totalDays === 1 ? '' : 's'}${remainingHours > 0 ? `, ${remainingHours}h` : ''}`;
        timeColor = 'text-yellow-400';
    } else {
        timeText = `Expires in ${totalDays} day${totalDays === 1 ? '' : 's'}${remainingHours > 0 ? `, ${remainingHours}h` : ''}`;
        timeColor = 'text-green-400';
    }

    const canAfford = data.balance >= data.cost;

    const getPackagePrice = (pkg: AvailablePackage): number => {
        switch (data.billing_cycle) {
            case 'hourly': return pkg.price_hourly;
            case 'weekly': return pkg.price_weekly;
            default: return pkg.price_monthly;
        }
    };

    const selectedPkgData = data.available_packages?.find((p) => p.id === selectedPkg);
    const selectedPkgPrice = selectedPkgData ? getPackagePrice(selectedPkgData) : 0;
    const priceDiff = selectedPkgData ? selectedPkgPrice - data.cost : 0;

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

            {/* Info Row */}
            <div css={tw`grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4`}>
                <TitledGreyBox title={'Package'}>
                    <p css={tw`text-lg font-semibold text-neutral-100`}>{data.package_name}</p>
                </TitledGreyBox>
                <TitledGreyBox title={'Billing Cycle'}>
                    <p css={tw`text-lg font-semibold text-neutral-100 capitalize`}>{data.billing_cycle}</p>
                </TitledGreyBox>
                <TitledGreyBox title={'Cost'}>
                    <p css={tw`text-lg font-semibold text-neutral-100`}>{data.cost} {data.currency}</p>
                </TitledGreyBox>
            </div>

            {/* Expiration & Balance */}
            <GreyRowBox css={tw`mb-4`}>
                <div css={tw`flex justify-between items-center w-full flex-wrap gap-3`}>
                    <div>
                        <p css={tw`text-xs uppercase text-neutral-400 tracking-wide font-semibold mb-1`}>Expiration</p>
                        <p className={`text-lg font-bold ${timeColor}`}>{timeText}</p>
                        <p css={tw`text-xs text-neutral-400 mt-1`}>{expiresAt.toLocaleString()}</p>
                    </div>
                    <div css={tw`text-right`}>
                        <p css={tw`text-xs text-neutral-400 mb-1`}>Your Balance</p>
                        <p className={`text-base font-semibold ${canAfford ? 'text-green-400' : 'text-red-400'}`}>
                            {data.balance} {data.currency}
                        </p>
                    </div>
                </div>
            </GreyRowBox>

            {/* Auto-Renew & Renew */}
            <GreyRowBox css={tw`mb-4`}>
                <div css={tw`flex justify-between items-start w-full flex-wrap gap-4`}>
                    <div css={tw`flex-1`}>
                        <label css={tw`flex items-center gap-3 cursor-pointer`} onClick={handleToggleAutoRenew}>
                            <div
                                css={tw`relative rounded-full transition-colors duration-200`}
                                style={{
                                    width: 42, height: 24,
                                    background: data.auto_renew ? '#3182ce' : 'rgba(255,255,255,0.08)',
                                    cursor: toggling ? 'wait' : 'pointer',
                                }}
                            >
                                <div
                                    css={tw`absolute rounded-full bg-white shadow`}
                                    style={{
                                        width: 20, height: 20, top: 2, left: 2,
                                        transition: 'transform 0.2s',
                                        transform: data.auto_renew ? 'translateX(18px)' : 'translateX(0)',
                                    }}
                                />
                            </div>
                            <span css={tw`text-sm font-medium text-neutral-200`}>
                                Auto-Renew {data.auto_renew ? 'On' : 'Off'}
                            </span>
                        </label>
                        <p css={tw`text-xs text-neutral-400 mt-1`}>
                            {data.auto_renew
                                ? `Will automatically renew 30 minutes before expiration (${data.cost} ${data.currency}).`
                                : 'Server will be suspended when it expires unless manually renewed.'}
                        </p>
                    </div>

                    {data.billing_cycle !== 'hourly' && (
                        <button
                            onClick={handleRenew}
                            disabled={renewing || !canAfford}
                            css={tw`px-6 py-2 rounded text-sm font-semibold text-white transition-colors duration-150`}
                            style={{
                                background: '#3182ce',
                                opacity: (renewing || !canAfford) ? 0.5 : 1,
                                cursor: (renewing || !canAfford) ? 'not-allowed' : 'pointer',
                            }}
                        >
                            {renewing ? 'Renewing...' : `Renew (${data.cost} ${data.currency})`}
                        </button>
                    )}
                </div>
            </GreyRowBox>

            {data.billing_cycle !== 'hourly' && !canAfford && (
                <p css={tw`text-red-400 text-xs mt-1 mb-4`}>
                    Insufficient balance to renew. You need {(data.cost - data.balance).toFixed(2)} more {data.currency}.
                </p>
            )}

            {/* Hourly Extension */}
            {data.billing_cycle === 'hourly' && (
                <TitledGreyBox title={'Extend Hours'} css={tw`mb-4`}>
                    <p css={tw`text-xs text-neutral-400 mb-3`}>
                        Extend this server by up to 10,000 hours at {data.cost} {data.currency}/hour.
                    </p>
                    <div css={tw`flex items-center gap-2 flex-wrap`}>
                        <input
                            type={'number'}
                            min={1}
                            max={10000}
                            value={hoursInput}
                            onChange={(e) => setHoursInput(e.target.value)}
                            placeholder={'Hours (1-10000)'}
                            css={tw`bg-neutral-600 border border-neutral-500 rounded px-3 py-2 text-sm text-neutral-200 w-32 focus:border-primary-400 focus:outline-none`}
                        />
                        <span css={tw`text-xs text-neutral-400`}>
                            = {(parseFloat(hoursInput) * data.cost || 0).toFixed(2)} {data.currency}
                        </span>
                        <button
                            onClick={handleExtendHours}
                            disabled={extendingHours}
                            css={tw`px-4 py-2 rounded text-sm font-semibold text-white transition-colors duration-150`}
                            style={{ background: '#3182ce', opacity: extendingHours ? 0.5 : 1 }}
                        >
                            {extendingHours ? 'Extending...' : 'Extend'}
                        </button>
                    </div>
                </TitledGreyBox>
            )}

            {/* Change Package */}
            {data.available_packages && (
                <TitledGreyBox title={'Change Package'} css={tw`mb-4`}>
                    <p css={tw`text-xs text-neutral-400 mb-3`}>
                        Switch to a different package. Lower cost extends expiry, higher cost shortens it.
                    </p>
                    {data.available_packages.length > 0 ? (
                        <div css={tw`flex flex-col gap-2`}>
                            <select
                                value={selectedPkg}
                                onChange={(e) => setSelectedPkg(e.target.value ? parseInt(e.target.value, 10) : '')}
                                css={tw`bg-neutral-600 border border-neutral-500 rounded px-3 py-2 text-sm text-neutral-200 focus:border-primary-400 focus:outline-none w-full`}
                            >
                                <option value={''}>Select a package...</option>
                                {data.available_packages.map((pkg) => (
                                    <option key={pkg.id} value={pkg.id}>
                                        {pkg.name} — {getPackagePrice(pkg)} {data.currency}/{data.billing_cycle}
                                    </option>
                                ))}
                            </select>
                            {selectedPkg && (
                                <span className={`text-xs font-semibold ${priceDiff > 0 ? 'text-red-400' : priceDiff < 0 ? 'text-green-400' : 'text-neutral-400'}`}>
                                    {priceDiff > 0 ? `+${priceDiff.toFixed(2)} ${data.currency} (shortens expiry)` :
                                     priceDiff < 0 ? `${priceDiff.toFixed(2)} ${data.currency} (extends expiry)` :
                                     'Same price'}
                                </span>
                            )}
                            <button
                                onClick={handleChangePackage}
                                disabled={changingPkg || !selectedPkg}
                                css={tw`px-4 py-2 rounded text-sm font-semibold text-white transition-colors duration-150 self-start`}
                                style={{ background: '#3182ce', opacity: (changingPkg || !selectedPkg) ? 0.5 : 1 }}
                            >
                                {changingPkg ? 'Changing...' : 'Change Package'}
                            </button>
                        </div>
                    ) : (
                        <p css={tw`text-sm text-neutral-400 italic`}>No other packages available. Create more packages in admin settings.</p>
                    )}
                </TitledGreyBox>
            )}

            {/* Change Billing Cycle */}
            {data.billing_change_enabled !== false && (
                <TitledGreyBox title={'Change Billing Cycle'} css={tw`mb-4`}>
                    <p css={tw`text-xs text-neutral-400 mb-3`}>
                        Switch between monthly, weekly, or hourly billing. Charges one period of the new cycle.
                    </p>
                    <div css={tw`flex items-center gap-2 flex-wrap`} style={{ maxWidth: '100%' }}>
                        <select
                            value={selectedBilling}
                            onChange={(e) => setSelectedBilling(e.target.value)}
                            css={tw`bg-neutral-600 border border-neutral-500 rounded px-3 py-2 text-sm text-neutral-200 focus:border-primary-400 focus:outline-none`}
                        >
                            <option value={'monthly'}>Monthly</option>
                            <option value={'weekly'}>Weekly</option>
                            <option value={'hourly'}>Hourly</option>
                        </select>
                        <button
                            onClick={handleChangeBilling}
                            disabled={changingBilling || selectedBilling === data.billing_cycle}
                            css={tw`px-4 py-2 rounded text-sm font-semibold text-white transition-colors duration-150`}
                            style={{ background: '#3182ce', opacity: (changingBilling || selectedBilling === data.billing_cycle) ? 0.5 : 1 }}
                        >
                            {changingBilling ? 'Changing...' : 'Change Billing'}
                        </button>
                    </div>
                </TitledGreyBox>
            )}
        </div>
    );
};

export default PackageTab;
