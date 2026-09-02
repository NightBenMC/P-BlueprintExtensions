import React, { useState, useEffect } from 'react';
import tw from 'twin.macro';
import http from '@/api/http';

interface ExpiryData {
    [serverId: string]: {
        expires_at: string;
        suspended: boolean;
        billing_cycle: string;
        cost: number;
        package_name?: string;
    };
}

let cachedExpirations: ExpiryData | null = null;
let fetchPromise: Promise<ExpiryData> | null = null;

const fetchExpirations = (): Promise<ExpiryData> => {
    if (cachedExpirations) return Promise.resolve(cachedExpirations);
    if (fetchPromise) return fetchPromise;
    fetchPromise = http.get('/api/client/extensions/pterostore/store/expirations')
        .then((res) => {
            cachedExpirations = res.data;
            return res.data;
        })
        .catch(() => ({}));
    return fetchPromise;
};

const ServerExpiry: React.FC<{ server?: any }> = ({ server }) => {
    const [expiry, setExpiry] = useState<{ expires_at: string; suspended: boolean; package_name?: string } | null>(null);

    useEffect(() => {
        if (!server?.id) return;
        fetchExpirations().then((data) => {
            const match = data[server.id] || data[String(server.id)];
            if (match) setExpiry(match);
        });
    }, [server?.id]);

    if (!expiry) return null;

    const expiresAt = new Date(expiry.expires_at);
    const now = new Date();
    const diffMs = expiresAt.getTime() - now.getTime();
    const diffDays = Math.ceil(diffMs / (1000 * 60 * 60 * 24));

    let text: string;
    let colorClass: string;

    if (expiry.suspended) {
        text = 'Expired - Suspended';
        colorClass = 'text-red-400';
    } else if (diffDays < 0) {
        text = 'Expired';
        colorClass = 'text-red-400';
    } else if (diffDays === 0) {
        const diffHours = Math.ceil(diffMs / (1000 * 60 * 60));
        text = diffHours <= 0 ? 'Expires soon' : `Expires in ${diffHours}h`;
        colorClass = 'text-yellow-400';
    } else if (diffDays <= 3) {
        text = `Expires in ${diffDays} day${diffDays === 1 ? '' : 's'}`;
        colorClass = 'text-yellow-400';
    } else {
        text = `Expires in ${diffDays} days`;
        colorClass = 'text-green-400';
    }

    const dateStr = expiresAt.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
    const pkgPrefix = expiry.package_name ? `${expiry.package_name} - ` : 'Store server - ';

    return (
        <span css={tw`block mt-1 text-xs font-medium truncate`} className={colorClass} title={`${pkgPrefix}${text} · ${dateStr}`}>
            {pkgPrefix}{text} &middot; {dateStr}
        </span>
    );
};

export default ServerExpiry;
