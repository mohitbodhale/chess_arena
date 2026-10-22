/**
 * Chess Arena — lobby interactions.
 *
 * The category filters react instantly on the client so the lobby feels live
 * while the server keeps the canonical listing.
 */
(function () {
    'use strict';

    var grid = document.getElementById('contest-grid');
    var chips = document.querySelectorAll('[data-filter-category]');
    var statusChips = document.querySelectorAll('[data-filter-status]');
    var search = document.getElementById('lobby-search');
    var empty = document.getElementById('lobby-empty');

    var activeCategory = grid ? grid.dataset.category || 'All' : 'All';
    var activeStatus = grid ? grid.dataset.status || 'Open' : 'Open';
    var term = '';

    function applyFilters() {
        if (!grid) {
            return;
        }

        var visible = 0;
        grid.querySelectorAll('[data-contest]').forEach(function (card) {
            var matchesCategory = activeCategory === 'All' || card.dataset.category === activeCategory;
            var matchesStatus = activeStatus === 'All'
                || card.dataset.status === activeStatus
                || (activeStatus === 'Open' && card.dataset.status !== 'Completed');
            var matchesTerm = term === '' || (card.dataset.title || '').toLowerCase().indexOf(term) !== -1;

            var show = matchesCategory && matchesStatus && matchesTerm;
            card.style.display = show ? '' : 'none';

            if (show) {
                visible++;
            }
        });

        if (empty) {
            empty.style.display = visible === 0 ? '' : 'none';
        }
    }

    chips.forEach(function (chip) {
        chip.addEventListener('click', function (event) {
            event.preventDefault();
            activeCategory = chip.dataset.filterCategory;
            chips.forEach(function (other) {
                other.classList.toggle('is-active', other === chip);
            });
            applyFilters();
        });
    });

    statusChips.forEach(function (chip) {
        chip.addEventListener('click', function (event) {
            event.preventDefault();
            activeStatus = chip.dataset.filterStatus;
            statusChips.forEach(function (other) {
                other.classList.toggle('is-active', other === chip);
            });
            applyFilters();
        });
    });

    if (search) {
        search.addEventListener('input', function () {
            term = search.value.trim().toLowerCase();
            applyFilters();
        });
    }

    /* Animate the slot bars once they are in the viewport. */
    var bars = document.querySelectorAll('[data-slot-bar]');
    if (bars.length && 'IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) {
                    return;
                }
                var bar = entry.target;
                var value = bar.dataset.slotBar || '0';
                window.requestAnimationFrame(function () {
                    bar.querySelector('span').style.width = value + '%';
                });
                observer.unobserve(bar);
            });
        }, { threshold: 0.2 });

        bars.forEach(function (bar) {
            observer.observe(bar);
        });
    } else {
        bars.forEach(function (bar) {
            bar.querySelector('span').style.width = (bar.dataset.slotBar || '0') + '%';
        });
    }

    /* Countdown to the start of the next contest. */
    var countdowns = document.querySelectorAll('[data-countdown]');
    function tick() {
        countdowns.forEach(function (node) {
            var target = new Date(node.dataset.countdown.replace(/-/g, '/')).getTime();
            if (isNaN(target)) {
                return;
            }

            var diff = Math.max(0, target - Date.now());
            var hours = Math.floor(diff / 3600000);
            var minutes = Math.floor((diff % 3600000) / 60000);
            var seconds = Math.floor((diff % 60000) / 1000);

            node.textContent = (hours > 0 ? hours + 'h ' : '')
                + minutes + 'm '
                + (seconds < 10 ? '0' : '') + seconds + 's';
        });
    }

    if (countdowns.length) {
        tick();
        window.setInterval(tick, 1000);
    }
})();
