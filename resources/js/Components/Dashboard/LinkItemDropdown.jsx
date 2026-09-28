import React, { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { IconChevronDown, IconChevronUp, IconCornerDownRight } from '@tabler/icons-react';

export default function LinkItemDropdown({ icon, title, data, access, sidebarOpen, ...props }) {
    const { url, props: pageProps } = usePage();
    const auth = pageProps.auth || {};
    const [isOpen, setIsOpen] = useState(false);

    if (auth.super !== true && access !== true) return null;

    const visibleItems = data.filter((item) => auth.super === true || item.permissions === true);
    if (!visibleItems.length) return null;

    if (!sidebarOpen) {
        return <div className="relative min-w-full">
            <button type="button" className="flex min-w-full justify-center py-3 text-gray-500 hover:bg-gray-50 hover:text-gray-900" onClick={() => setIsOpen(!isOpen)}>{isOpen ? <IconChevronDown size={20} strokeWidth={1.5} /> : icon}</button>
            {isOpen && <div className="absolute left-full top-0 z-30 min-w-52 rounded-lg border border-slate-200 bg-white py-2 shadow-lg">{visibleItems.map((item) => <Link key={item.title} href={item.href} className="flex items-center gap-2 px-4 py-2 text-sm text-gray-600 hover:bg-slate-50 hover:text-gray-900" {...props}>{item.icon}{item.title}</Link>)}</div>}
        </div>;
    }

    return <div>
        <button type="button" className="flex min-w-full items-center justify-between gap-x-3.5 px-4 py-3 text-sm font-medium capitalize text-gray-500 hover:border-r-2 hover:border-r-gray-700 hover:text-gray-900" onClick={() => setIsOpen(!isOpen)}>
            <span className="flex items-center gap-x-3.5">{icon}{title}</span>{isOpen ? <IconChevronUp size={18} strokeWidth={1.5} /> : <IconChevronDown size={18} strokeWidth={1.5} />}
        </button>
        {isOpen && visibleItems.map((item) => <Link key={item.title} href={item.href} className={`${url.startsWith(item.href) ? 'border-r-2 border-r-gray-400 bg-gray-100 text-gray-700' : ''} flex items-center gap-2 px-5 py-3 text-sm text-gray-500 hover:border-r-2 hover:border-r-gray-700 hover:text-gray-900`} {...props}><IconCornerDownRight size={18} strokeWidth={1.5} />{item.title}</Link>)}
    </div>;
}
