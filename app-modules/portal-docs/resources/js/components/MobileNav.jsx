import { router } from '@inertiajs/react';
import { Menu, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { createPortal } from 'react-dom';
import Sidebar from './Sidebar';

/**
 * Below lg the sidebar is hidden, so without this the portal has no navigation at all
 * on a phone. Same Sidebar component, shown as an overlay.
 */
export default function MobileNav({ groups, anchors, currentSlug }) {
    const [open, setOpen] = useState(false);

    // A client-side visit keeps the layout mounted, so the panel has to close itself.
    useEffect(() => router.on('navigate', () => setOpen(false)), []);

    useEffect(() => {
        if (!open) return undefined;

        const onKeyDown = (event) => event.key === 'Escape' && setOpen(false);
        document.addEventListener('keydown', onKeyDown);
        document.body.style.overflow = 'hidden';

        return () => {
            document.removeEventListener('keydown', onKeyDown);
            document.body.style.overflow = '';
        };
    }, [open]);

    return (
        <>
            <button
                type="button"
                onClick={() => setOpen(true)}
                aria-label="Abrir navegação"
                aria-expanded={open}
                className="rounded-lg p-2 text-stone-500 hover:bg-stone-100 hover:text-stone-900 lg:hidden dark:text-stone-400 dark:hover:bg-stone-800 dark:hover:text-stone-100"
            >
                <Menu size={20} />
            </button>

            {/* Rendered into the body: the sticky header uses backdrop-blur, which makes it
                a containing block and would trap this fixed panel inside its 4rem height. */}
            {open &&
                createPortal(
                    <div className="fixed inset-0 z-50 lg:hidden">
                        <button
                            type="button"
                            aria-label="Fechar navegação"
                            onClick={() => setOpen(false)}
                            className="absolute inset-0 bg-stone-900/40"
                        />

                        <div className="dark:bg-background-dark relative flex h-full w-80 max-w-[85vw] flex-col overflow-y-auto bg-white p-4 shadow-xl">
                            <button
                                type="button"
                                onClick={() => setOpen(false)}
                                aria-label="Fechar navegação"
                                className="mb-4 self-end rounded-lg p-2 text-stone-500 hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-800"
                            >
                                <X size={20} />
                            </button>

                            <Sidebar groups={groups} anchors={anchors} currentSlug={currentSlug} />
                        </div>
                    </div>,
                    document.body,
                )}
        </>
    );
}
