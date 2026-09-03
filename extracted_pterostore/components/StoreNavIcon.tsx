import React, { useState, useEffect } from 'react';
import tw from 'twin.macro';
import http from '@/api/http';

const StoreNavIcon: React.FC = () => {
    const [balance, setBalance] = useState<number | null>(null);
    const [visible, setVisible] = useState(true);

    useEffect(() => {
        http.get('/api/client/extensions/pterostore/store/settings')
            .then((r) => {
                if (r.data && r.data.store_enabled === false) setVisible(false);
            })
            .catch(() => {});
        http.get('/api/client/extensions/pterostore/store/balance')
            .then((r) => setBalance(r.data?.balance ?? null))
            .catch(() => {});
    }, []);

    if (!visible) return null;

    return (
        <a
            href={'/account/store'}
            title={'Store'}
            css={tw`inline-flex flex-col items-center justify-center text-neutral-400 cursor-pointer no-underline transition-colors duration-150 hover:text-white`}
            style={{ padding: '14px 12px', gap: 2, lineHeight: 1 }}
        >
            <svg width={'24'} height={'24'} viewBox={'0 0 24 24'} fill={'none'} stroke={'currentColor'} strokeWidth={'2'} strokeLinecap={'round'} strokeLinejoin={'round'}>
                <path d={'M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z'} />
                <line x1={'3'} y1={'6'} x2={'21'} y2={'6'} />
                <path d={'M16 10a4 4 0 01-8 0'} />
            </svg>
            {balance !== null && (
                <span css={tw`text-primary-300 font-bold whitespace-nowrap`} style={{ fontSize: 9, lineHeight: 1 }}>
                    {balance.toFixed(0)}
                </span>
            )}
        </a>
    );
};

export default StoreNavIcon;
