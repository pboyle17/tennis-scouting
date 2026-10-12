{{-- Status icon with a message box that opens on hover and stays open on click/tap. Expects $status ('error'|'warning') and $message. --}}
@once
    <script>
        (function () {
            function place(btn, box) {
                var r = btn.getBoundingClientRect();
                box.classList.remove('hidden');
                var left = Math.min(Math.max(8, r.left + r.width / 2 - box.offsetWidth / 2), window.innerWidth - box.offsetWidth - 8);
                var top = r.bottom + 6;
                if (top + box.offsetHeight > window.innerHeight - 8) top = r.top - box.offsetHeight - 6;
                box.style.left = left + 'px';
                box.style.top = top + 'px';
            }
            function hideAll(except) {
                document.querySelectorAll('[data-status-popover]').forEach(function (w) {
                    if (w !== except) { w.dataset.pinned = ''; w.querySelector('[data-status-box]').classList.add('hidden'); }
                });
            }
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-status-popover]').forEach(function (wrap) {
                    var btn = wrap.querySelector('button'), box = wrap.querySelector('[data-status-box]');
                    wrap.addEventListener('mouseenter', function () { place(btn, box); });
                    wrap.addEventListener('mouseleave', function () { if (!wrap.dataset.pinned) box.classList.add('hidden'); });
                    btn.addEventListener('click', function (e) {
                        e.stopPropagation();
                        var pin = !wrap.dataset.pinned;
                        hideAll(wrap);
                        wrap.dataset.pinned = pin ? '1' : '';
                        if (pin) { place(btn, box); } else { box.classList.add('hidden'); }
                    });
                    box.addEventListener('click', function (e) { e.stopPropagation(); });
                });
                document.addEventListener('click', function () { hideAll(null); });
                document.addEventListener('keydown', function (e) { if (e.key === 'Escape') hideAll(null); });
                window.addEventListener('scroll', function () { hideAll(null); }, { passive: true });
            });
        })();
    </script>
@endonce
<span data-status-popover class="relative inline-block">
    <button type="button" class="{{ $status === 'error' ? 'text-red-600' : 'text-amber-500' }} cursor-pointer px-1 rounded hover:bg-gray-100" aria-label="{{ $status === 'error' ? 'Error' : 'Warning' }}: {{ $message }}">
        {{ $status === 'error' ? '⛔' : '⚠️' }}
    </button>
    <span data-status-box role="tooltip" class="hidden fixed z-50 w-80 max-w-[calc(100vw-16px)] text-left whitespace-normal rounded-lg shadow-lg border p-3 text-sm {{ $status === 'error' ? 'bg-red-50 border-red-200 text-red-800' : 'bg-amber-50 border-amber-200 text-amber-900' }}">
        <span class="block font-semibold mb-1">{{ $status === 'error' ? 'Last UTR update failed' : 'Last UTR update had problems' }}</span>
        <span class="block">{{ $message }}</span>
    </span>
</span>
