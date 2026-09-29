{{-- UTR / USTA rating toggle. Elements marked .rating-utr / .rating-usta are shown based on the saved choice. --}}
@once
    <style>
        html[data-rating="utr"] .rating-usta,
        html[data-rating="usta"] .rating-utr { display: none !important; }
    </style>
    <script>
        (function () {
            var mode = 'utr';
            try { mode = localStorage.getItem('ratingMode') === 'usta' ? 'usta' : 'utr'; } catch (e) {}
            document.documentElement.dataset.rating = mode;

            window.setRatingMode = function (next) {
                document.documentElement.dataset.rating = next;
                try { localStorage.setItem('ratingMode', next); } catch (e) {}
                document.querySelectorAll('[data-rating-option]').forEach(function (btn) {
                    var active = btn.dataset.ratingOption === next;
                    btn.classList.toggle('bg-blue-600', active);
                    btn.classList.toggle('text-white', active);
                    btn.classList.toggle('bg-white', !active);
                    btn.classList.toggle('text-gray-700', !active);
                    btn.setAttribute('aria-pressed', active ? 'true' : 'false');
                });
            };

            document.addEventListener('DOMContentLoaded', function () {
                window.setRatingMode(document.documentElement.dataset.rating);
            });
        })();
    </script>
@endonce

<div class="inline-flex rounded-md shadow-sm border border-gray-300 overflow-hidden text-xs font-semibold" role="group" aria-label="Rating type">
    <button type="button" data-rating-option="utr" onclick="setRatingMode('utr')" class="px-3 py-1.5 bg-blue-600 text-white">UTR</button>
    <button type="button" data-rating-option="usta" onclick="setRatingMode('usta')" class="px-3 py-1.5 bg-white text-gray-700 border-l border-gray-300">USTA Dynamic</button>
</div>
