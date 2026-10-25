import { useLanguage } from '@admin/context/LanguageContext';
import { useClickOutside } from '@admin/hooks/useClickOutside';
import { getLanguage, languages, type Locale } from '@admin/i18n/languages';
import { cn } from '@admin/utils';
import { useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { router, usePage } from '@inertiajs/react';
import { logout } from '@/routes/admin';
import { Dropdown } from '../ui/dropdown/Dropdown';

type AuthUser = { name: string; username: string } | null;

export default function UserDropdown() {
    const { auth } = usePage<{ auth: { user: AuthUser } }>().props;
    const user = auth.user;

    const [isOpen, setIsOpen] = useState(false);
    const [isSubDropdownOpen, setIsSubDropdownOpen] = useState(false);
    const dropdownRef = useRef<HTMLDivElement>(null);
    const subDropdownRef = useRef<HTMLLIElement>(null);
    const { t } = useTranslation();
    const { language: locale, setLanguage } = useLanguage();
    const currentLang = getLanguage(locale as Locale);
    const CurrentFlagIcon = currentLang.FlagIcon;

    useClickOutside(dropdownRef, () => {
        setIsOpen(false);
        setIsSubDropdownOpen(false);
    });

    useClickOutside(subDropdownRef, () => {
        setIsSubDropdownOpen(false);
    });

    const closeDropdown = () => {
        setIsOpen(false);
        setIsSubDropdownOpen(false);
    };

    return (
        <div className="relative" ref={dropdownRef}>
            <button
                onClick={() => setIsOpen((prev) => !prev)}
                className="dropdown-toggle flex items-center text-gray-700 dark:text-gray-400"
            >
                <span className="me-3 h-11 w-11 overflow-hidden rounded-full">
                    <img src="/images/user/owner.jpg" alt="User" />
                </span>
                <span className="text-theme-sm me-1 block font-medium">
                    {user?.name}
                </span>
                <svg
                    className={`stroke-gray-500 transition-transform duration-200 dark:stroke-gray-400 ${
                        isOpen ? 'rotate-180' : ''
                    }`}
                    width="18"
                    height="20"
                    viewBox="0 0 18 20"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        d="M4.3125 8.65625L9 13.3437L13.6875 8.65625"
                        stroke="currentColor"
                        strokeWidth="1.5"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                    />
                </svg>
            </button>

            <Dropdown
                isOpen={isOpen}
                onClose={closeDropdown}
                className="shadow-theme-lg dark:bg-gray-dark absolute inset-e-0 mt-4.25 flex w-65 flex-col rounded-2xl border border-gray-200 bg-white p-3 dark:border-gray-800"
            >
                <div>
                    <span className="text-theme-sm block font-medium text-gray-700 no-underline dark:text-gray-400">
                        {user?.name}
                    </span>
                    <span className="text-theme-xs mt-0.5 block text-gray-500 no-underline dark:text-gray-400">
                        {user?.username}
                    </span>
                </div>

                <ul className="flex flex-col gap-1 border-b border-gray-200 pt-4 pb-3 dark:border-gray-800">
                    <li className="relative" ref={subDropdownRef}>
                        <button
                            type="button"
                            onClick={() => setIsSubDropdownOpen((prev) => !prev)}
                            className={cn(
                                'group text-theme-sm flex max-h-10 w-full items-center justify-between gap-2 rounded-lg px-3 py-2 font-medium transition-colors',
                                isSubDropdownOpen
                                    ? 'bg-gray-100 text-gray-900 dark:bg-white/5 dark:text-white'
                                    : 'text-gray-700 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300',
                            )}
                        >
                            <span className="text-theme-sm flex items-center gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <path d="M12.001 2.75C17.1091 2.75 21.2501 6.89178 21.2501 11.9999C21.2501 17.108 17.1091 21.2498 12.001 21.2498M12.001 2.75C6.89289 2.75 2.75195 6.89178 2.75195 11.9999C2.75195 17.108 6.8929 21.2498 12.001 21.2498M12.001 2.75C14.2097 2.75 16.0005 6.8914 16.0005 11.9993C16.0005 17.1073 14.2098 21.2498 12.001 21.2498M12.001 2.75C9.79226 2.75 8.00195 6.89141 8.00195 11.9994C8.00195 17.1073 9.79226 21.2498 12.001 21.2498M3.24561 8.99976H20.7544M3.24561 14.9998H20.7544" stroke="#667085" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                                </svg>
                                <span>{t('userDropdown.language')}</span>
                            </span>
                            <span className="text-theme-xs flex items-center gap-1.5 rounded-lg border border-gray-200 bg-gray-50 px-2 py-1 font-medium text-gray-700 dark:border-gray-800 dark:bg-white/3 dark:text-gray-300">
                                <span>{currentLang.shortName}</span>
                                <CurrentFlagIcon className="size-3.5 shrink-0 overflow-hidden rounded-full" />
                            </span>
                        </button>

                        {isSubDropdownOpen && (
                            <div className="shadow-theme-lg dark:bg-gray-dark absolute -inset-s-2 top-11 w-62.5 rounded-2xl border border-gray-200 bg-white p-2 md:inset-s-auto md:inset-e-[calc(100%+14px)] md:top-0 dark:border-gray-800">
                                <ul className="flex flex-col gap-1">
                                    {languages.map((language) => {
                                        const isSelected = locale === language.id;
                                        const FlagIcon = language.FlagIcon;
                                        return (
                                            <li key={language.id}>
                                                <button
                                                    type="button"
                                                    onClick={(e) => {
                                                        e.stopPropagation();
                                                        setLanguage(language.id);
                                                        closeDropdown();
                                                    }}
                                                    className={cn(
                                                        'text-theme-sm flex w-full items-center justify-between gap-2 rounded-lg px-2.5 py-2 text-start font-medium transition-colors hover:text-gray-900 dark:text-gray-300 dark:hover:text-white',
                                                        isSelected ? 'bg-brand-50 dark:bg-brand-500/15' : 'hover:bg-gray-100 dark:hover:bg-white/5',
                                                    )}
                                                >
                                                    <span className="flex items-center gap-2">
                                                        <span className={cn('size-1.5 shrink-0 rounded-full transition-opacity', isSelected ? 'bg-brand-500 opacity-100' : 'opacity-0')} />
                                                        <FlagIcon className="size-5 shrink-0 overflow-hidden rounded-full" />
                                                        <span className="truncate">{language.name}</span>
                                                    </span>
                                                </button>
                                            </li>
                                        );
                                    })}
                                </ul>
                            </div>
                        )}
                    </li>
                </ul>

                <button
                    type="button"
                    onClick={() => router.post(logout().url)}
                    className="group text-theme-sm mt-3 flex w-full items-center gap-3 rounded-lg px-2 py-2 font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5"
                >
                    <svg className="fill-gray-500 group-hover:fill-gray-700 dark:group-hover:fill-gray-300" width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path fillRule="evenodd" clipRule="evenodd" d="M15.1007 19.247C14.6865 19.247 14.3507 18.9112 14.3507 18.497V5.49609C14.3507 4.25345 19.7433 3.24609 18.5007 3.24609H15.1007ZM3.25073 11.9984C3.25073 12.2144 3.34204 12.4091 3.48817 12.546L8.09483 17.1556C8.38763 17.4485 8.86251 17.4487 9.15549 17.1559L3.52309 11.4202Z" fill="" />
                    </svg>
                    {t('userDropdown.signOut')}
                </button>
            </Dropdown>
        </div>
    );
}