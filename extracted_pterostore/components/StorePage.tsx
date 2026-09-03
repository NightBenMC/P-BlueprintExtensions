import React, { useState, useEffect, useCallback } from 'react';
import tw from 'twin.macro';
import http from '@/api/http';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import GreyRowBox from '@/components/elements/GreyRowBox';
import Spinner from '@/components/elements/Spinner';
import PageContentBlock from '@/components/elements/PageContentBlock';

const API = '/api/client/extensions/pterostore';

interface Package {
    id: number;
    name: string;
    description: string;
    image: string;
    cpu: number;
    ram: number;
    disk: number;
    ports: number;
    databases: number;
    custom_specs: Record<string, string>;
    price_monthly: number;
    price_weekly: number;
    price_hourly: number;
    stock_limit: number;
    stock_used: number;
    stock_remaining: number | null;
}

interface Category {
    id: number;
    name: string;
    description: string;
    packages: Package[];
}

interface Transaction {
    id: number;
    type: string;
    amount: number;
    description: string;
    created_at: string;
}

const formatSpec = (key: string, value: number): string => {
    if (key === 'cpu') return `${value}%`;
    if (key === 'ram' || key === 'disk') return value >= 1024 ? `${(value / 1024).toFixed(1)} GB` : `${value} MB`;
    return String(value);
};

const specLabels: Record<string, string> = { cpu: 'CPU', ram: 'RAM', disk: 'Disk', ports: 'Ports', databases: 'DBs' };

type Tab = 'shop' | 'transactions';

const StorePage: React.FC = () => {
    const [balance, setBalance] = useState<number>(0);
    const [tab, setTab] = useState<Tab>('shop');
    const [categories, setCategories] = useState<Category[]>([]);
    const [transactions, setTransactions] = useState<Transaction[]>([]);
    const [billingCycle, setBillingCycle] = useState<'monthly' | 'weekly' | 'hourly'>('monthly');
    const [purchasing, setPurchasing] = useState<number | null>(null);
    const [toast, setToast] = useState<{ msg: string; ok: boolean } | null>(null);
    const [couponCode, setCouponCode] = useState<string>('');
    const [couponResult, setCouponResult] = useState<{ valid: boolean; type: string; value: number; code: string } | null>(null);
    const [couponError, setCouponError] = useState<string>('');
    const [applyingCoupon, setApplyingCoupon] = useState(false);

    useEffect(() => {
        http.get(`${API}/store/balance`).then((r) => setBalance(r.data.balance ?? 0)).catch(() => {});
        http.get(`${API}/store/categories`).then((r) => setCategories(r.data || [])).catch(() => {});
        http.get(`${API}/store/transactions`).then((r) => setTransactions(r.data || [])).catch(() => {});
    }, []);

    const showToast = useCallback((msg: string, ok: boolean) => {
        setToast({ msg, ok });
        setTimeout(() => setToast(null), 4000);
    }, []);

    const handleApplyCoupon = async (pkgId: number) => {
        if (!couponCode.trim()) return;
        setApplyingCoupon(true);
        setCouponError('');
        setCouponResult(null);
        try {
            const res = await http.post(`${API}/store/apply-coupon`, { code: couponCode, package_id: pkgId });
            setCouponResult(res.data);
        } catch (err: any) {
            setCouponError(err?.response?.data?.error || 'Invalid coupon.');
        } finally {
            setApplyingCoupon(false);
        }
    };

    const getDiscountedPrice = (price: number) => {
        if (!couponResult || !couponResult.valid) return price;
        if (couponResult.type === 'percent') return Math.max(0, price - price * (couponResult.value / 100));
        return Math.max(0, price - couponResult.value);
    };

    const handlePurchase = async (pkgId: number) => {
        setPurchasing(pkgId);
        try {
            const res = await http.post(`${API}/store/purchase`, {
                package_id: pkgId,
                billing_cycle: billingCycle,
                coupon_code: couponResult?.valid ? couponResult.code : undefined,
            });
            showToast(res.data.message || 'Server created!', true);
            setCouponCode('');
            setCouponResult(null);
            http.get(`${API}/store/balance`).then((r) => setBalance(r.data.balance ?? 0)).catch(() => {});
            http.get(`${API}/store/transactions`).then((r) => setTransactions(r.data || [])).catch(() => {});
        } catch (err: any) {
            showToast(err?.response?.data?.error || 'Purchase failed.', false);
        } finally {
            setPurchasing(null);
        }
    };

    const getPrice = (pkg: Package) => {
        if (billingCycle === 'hourly') return pkg.price_hourly;
        if (billingCycle === 'weekly') return pkg.price_weekly;
        return pkg.price_monthly;
    };

    const cycleLabel = billingCycle === 'hourly' ? 'hr' : billingCycle === 'weekly' ? 'wk' : 'mo';

    return (
        <PageContentBlock title={'Store'}>
            {/* Toast */}
            {toast && (
                <div
                    css={tw`fixed top-4 right-4 z-50 px-5 py-3 rounded shadow-lg text-sm text-white`}
                    className={toast.ok ? 'bg-green-700' : 'bg-red-700'}
                >
                    {toast.msg}
                </div>
            )}

            {/* Balance & Tabs */}
            <div css={tw`flex items-center justify-between mb-6 flex-wrap gap-3`}>
                <GreyRowBox css={tw`px-4 py-2 inline-flex items-center gap-2`}>
                    <svg width={'20'} height={'20'} viewBox={'0 0 24 24'} fill={'none'} stroke={'#63b3ed'} strokeWidth={'2'} strokeLinecap={'round'} strokeLinejoin={'round'}>
                        <circle cx={'12'} cy={'12'} r={'10'} /><path d={'M16 8h-6a2 2 0 100 4h4a2 2 0 110 4H8'} /><path d={'M12 18V6'} />
                    </svg>
                    <span css={tw`text-lg font-bold text-neutral-100`}>{balance.toFixed(2)}</span>
                    <span css={tw`text-xs text-neutral-400`}>balance</span>
                </GreyRowBox>

                <div css={tw`flex gap-1`}>
                    {(['shop', 'transactions'] as Tab[]).map((t) => (
                        <button
                            key={t}
                            onClick={() => setTab(t)}
                            css={tw`px-4 py-2 rounded text-sm capitalize transition-colors duration-150`}
                            className={tab === t ? 'bg-primary-500 bg-opacity-20 text-primary-300 font-semibold' : 'text-neutral-400 hover:text-neutral-200'}
                        >
                            {t}
                        </button>
                    ))}
                </div>
            </div>

            {/* Shop Tab */}
            {tab === 'shop' && (
                <div>
                    {/* Coupon */}
                    <div css={tw`mb-4 flex items-center gap-2 flex-wrap`}>
                        <input
                            type={'text'}
                            value={couponCode}
                            onChange={(e) => { setCouponCode(e.target.value); setCouponResult(null); setCouponError(''); }}
                            placeholder={'Coupon code'}
                            css={tw`bg-neutral-600 border border-neutral-500 rounded px-3 py-2 text-sm text-neutral-200 focus:border-primary-400 focus:outline-none w-48`}
                        />
                        {couponResult && couponResult.valid && (
                            <span css={tw`text-green-400 text-sm font-semibold`}>
                                {couponResult.type === 'percent' ? `${couponResult.value}% off` : `${couponResult.value} off`}
                            </span>
                        )}
                        {couponError && <span css={tw`text-red-400 text-sm`}>{couponError}</span>}
                    </div>

                    {/* Billing Cycle Selector */}
                    <div css={tw`mb-5 flex items-center gap-3`}>
                        <span css={tw`text-sm text-neutral-400`}>Billing:</span>
                        <div css={tw`flex rounded overflow-hidden border border-neutral-600`}>
                            {(['monthly', 'weekly', 'hourly'] as const).map((c) => (
                                <button
                                    key={c}
                                    onClick={() => setBillingCycle(c)}
                                    css={tw`px-4 py-2 text-sm capitalize border-0 cursor-pointer transition-colors duration-150`}
                                    className={billingCycle === c ? 'bg-primary-600 text-white font-semibold' : 'bg-neutral-700 text-neutral-400 hover:text-neutral-200'}
                                >
                                    {c}
                                </button>
                            ))}
                        </div>
                    </div>

                    {categories.length === 0 ? (
                        <p css={tw`text-center text-sm text-neutral-400 py-10`}>No packages available yet.</p>
                    ) : (
                        categories.map((cat) => (
                            <div key={cat.id} css={tw`mb-8`}>
                                <h2 css={tw`text-lg font-semibold text-neutral-100 mb-1`}>{cat.name || 'Category'}</h2>
                                {cat.description && <p css={tw`text-sm text-neutral-400 mb-4`}>{cat.description}</p>}
                                <div css={tw`grid gap-4`} style={{ gridTemplateColumns: 'repeat(auto-fill, minmax(280px, 1fr))' }}>
                                    {(cat.packages || []).map((pkg) => (
                                        <div key={pkg.id} css={tw`rounded shadow-md bg-neutral-700 p-5 flex flex-col gap-3 border border-transparent hover:border-neutral-500 transition-colors duration-150`}>
                                            {pkg.image && (
                                                <img src={pkg.image} alt={pkg.name} css={tw`w-full rounded`} style={{ height: 120, objectFit: 'cover' }} />
                                            )}
                                            <div css={tw`flex items-center justify-between`}>
                                                <h3 css={tw`text-base font-semibold text-neutral-100`}>{pkg.name}</h3>
                                                {pkg.stock_remaining !== null && (
                                                    <span
                                                        css={tw`text-xs font-semibold px-2 py-1 rounded`}
                                                        className={pkg.stock_remaining > 0 ? 'bg-green-500 bg-opacity-15 text-green-400' : 'bg-red-500 bg-opacity-15 text-red-400'}
                                                    >
                                                        {pkg.stock_remaining > 0 ? `${pkg.stock_remaining} left` : 'Out of stock'}
                                                    </span>
                                                )}
                                            </div>
                                            {pkg.description && <p css={tw`text-sm text-neutral-400 leading-snug`}>{pkg.description}</p>}
                                            <div css={tw`flex flex-wrap gap-2`}>
                                                {(['cpu', 'ram', 'disk', 'ports', 'databases'] as const).map((k) => {
                                                    const v = pkg[k];
                                                    if (!v && k !== 'cpu') return null;
                                                    return (
                                                        <span key={k} css={tw`inline-flex items-center gap-1 px-2 py-1 rounded bg-neutral-600 text-xs text-neutral-300`}>
                                                            {specLabels[k]}: {formatSpec(k, v)}
                                                        </span>
                                                    );
                                                })}
                                                {Object.entries(pkg.custom_specs || {}).map(([k, v]) => (
                                                    <span key={k} css={tw`inline-flex items-center gap-1 px-2 py-1 rounded bg-neutral-600 text-xs text-neutral-300`}>
                                                        {k}: {v}
                                                    </span>
                                                ))}
                                            </div>
                                            <div css={tw`flex justify-between items-center mt-auto`}>
                                                <div>
                                                    {couponResult?.valid && getDiscountedPrice(getPrice(pkg)) < getPrice(pkg) ? (
                                                        <>
                                                            <span css={tw`text-neutral-500 text-sm line-through mr-1`}>{getPrice(pkg)}</span>
                                                            <span css={tw`text-green-400 text-xl font-bold`}>{getDiscountedPrice(getPrice(pkg)).toFixed(2)}</span>
                                                        </>
                                                    ) : (
                                                        <span css={tw`text-primary-300 text-xl font-bold`}>{getPrice(pkg)}</span>
                                                    )}
                                                    <span css={tw`text-xs text-neutral-500 ml-1`}>/{cycleLabel}</span>
                                                </div>
                                                <div css={tw`flex gap-2`}>
                                                    {couponCode.trim() && !couponResult?.valid && (
                                                        <button
                                                            onClick={() => handleApplyCoupon(pkg.id)}
                                                            disabled={applyingCoupon}
                                                            css={tw`px-3 py-2 rounded text-xs font-semibold border transition-colors duration-150`}
                                                            className={'bg-green-500 bg-opacity-15 text-green-400 border-green-500 border-opacity-30'}
                                                            style={{ opacity: applyingCoupon ? 0.6 : 1, cursor: applyingCoupon ? 'not-allowed' : 'pointer' }}
                                                        >
                                                            {applyingCoupon ? '...' : 'Apply'}
                                                        </button>
                                                    )}
                                                    <button
                                                        onClick={() => handlePurchase(pkg.id)}
                                                        disabled={purchasing === pkg.id || (pkg.stock_remaining !== null && pkg.stock_remaining <= 0)}
                                                        css={tw`px-5 py-2 rounded text-sm font-semibold text-white transition-colors duration-150`}
                                                        style={{
                                                            background: (pkg.stock_remaining !== null && pkg.stock_remaining <= 0) ? '#4a5568' : '#2b6cb0',
                                                            opacity: (purchasing === pkg.id || (pkg.stock_remaining !== null && pkg.stock_remaining <= 0)) ? 0.6 : 1,
                                                            cursor: (purchasing === pkg.id || (pkg.stock_remaining !== null && pkg.stock_remaining <= 0)) ? 'not-allowed' : 'pointer',
                                                        }}
                                                    >
                                                        {purchasing === pkg.id ? 'Creating...' : (pkg.stock_remaining !== null && pkg.stock_remaining <= 0) ? 'Out of Stock' : 'Purchase'}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        ))
                    )}
                </div>
            )}

            {/* Transactions Tab */}
            {tab === 'transactions' && (
                <div>
                    {transactions.length === 0 ? (
                        <p css={tw`text-center text-sm text-neutral-400 py-10`}>No transactions yet.</p>
                    ) : (
                        <div css={tw`rounded shadow-md bg-neutral-700 overflow-hidden`}>
                            <table css={tw`w-full`} style={{ borderCollapse: 'collapse' }}>
                                <thead>
                                    <tr css={tw`border-b border-neutral-600`}>
                                        <th css={tw`px-4 py-3 text-left text-xs uppercase text-neutral-400 font-semibold`}>Type</th>
                                        <th css={tw`px-4 py-3 text-left text-xs uppercase text-neutral-400 font-semibold`}>Description</th>
                                        <th css={tw`px-4 py-3 text-right text-xs uppercase text-neutral-400 font-semibold`}>Amount</th>
                                        <th css={tw`px-4 py-3 text-right text-xs uppercase text-neutral-400 font-semibold`}>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {transactions.map((tx) => (
                                        <tr key={tx.id} css={tw`border-b border-neutral-600 border-opacity-50`}>
                                            <td css={tw`px-4 py-3 text-sm text-neutral-200 capitalize`}>{tx.type?.replace(/_/g, ' ')}</td>
                                            <td css={tw`px-4 py-3 text-sm text-neutral-400`}>{tx.description}</td>
                                            <td css={tw`px-4 py-3 text-right text-sm font-semibold`} className={tx.amount >= 0 ? 'text-green-400' : 'text-red-400'}>
                                                {tx.amount >= 0 ? '+' : ''}{tx.amount}
                                            </td>
                                            <td css={tw`px-4 py-3 text-right text-xs text-neutral-500`}>
                                                {new Date(tx.created_at).toLocaleDateString()}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            )}
        </PageContentBlock>
    );
};

export default StorePage;
