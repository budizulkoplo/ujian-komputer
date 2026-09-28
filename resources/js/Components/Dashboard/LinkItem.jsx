import React from 'react';
import { Link, usePage } from '@inertiajs/react';

export default function LinkItem({ href, icon, access, title, sidebarOpen, ...props }) {
    const { url, props: pageProps } = usePage();
    const auth = pageProps.auth || {};

    if (auth.super !== true && access !== true) return null;

    if (!sidebarOpen) {
        return <Link href={href} className={`${url.startsWith(href) ? 'border-r-2 border-r-gray-400 bg-gray-100 text-gray-700' : ''} flex min-w-full justify-center py-3 text-gray-500 hover:bg-gray-50 hover:text-gray-900`} {...props}>{icon}</Link>;
    }

    return <Link href={href} className={`${url.startsWith(href) ? 'border-r-2 border-r-gray-400 bg-gray-100 text-gray-700' : ''} flex items-center gap-x-3.5 px-4 py-3 text-sm font-medium capitalize text-gray-500 hover:border-r-2 hover:border-r-gray-700 hover:text-gray-900`} {...props}>{icon}{title}</Link>;
}
