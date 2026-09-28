/*
 * Calculator behaviour.
 *
 * The page works without this file: the form posts and the server renders the
 * result. With it, the result updates as the inputs change.
 *
 * The rules are NOT duplicated here. Every calculation goes to the one PHP
 * engine over a small JSON request, because two copies of the Faraid rules
 * would mean two places for a rule to be wrong, and the round trip costs far
 * less than that risk.
 */
(function () {
    'use strict';

    var form = document.getElementById('calc-form');
    var target = document.getElementById('result');
    if (!form || !target) { return; }

    var errorBox = document.getElementById('calc-error');
    var endpoint = form.getAttribute('data-endpoint') || '/api/calculate';
    var timer = null;
    var inFlight = null;

    function showError(message) {
        if (!errorBox) { return; }
        if (!message) { errorBox.hidden = true; errorBox.textContent = ''; return; }
        errorBox.textContent = message;
        errorBox.hidden = false;
    }

    function syncSpouseFields() {
        var checked = form.querySelector('input[name="deceased_gender"]:checked');
        var gender = checked ? checked.value : 'male';
        Array.prototype.forEach.call(form.querySelectorAll('[data-spouse]'), function (block) {
            var show = block.getAttribute('data-spouse') === gender;
            block.hidden = !show;
            if (!show) {
                Array.prototype.forEach.call(block.querySelectorAll('input'), function (input) {
                    if (input.type === 'checkbox') { input.checked = false; } else { input.value = '0'; }
                });
            }
        });
    }

    function calculate() {
        var data = new FormData(form);
        if (!data.get('madhhab')) { return; }

        if (inFlight) { inFlight.abort(); }
        var controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
        inFlight = controller;

        fetch(endpoint, {
            method: 'POST',
            body: data,
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'fetch' },
            signal: controller ? controller.signal : undefined
        }).then(function (response) {
            return response.json();
        }).then(function (payload) {
            inFlight = null;
            if (payload.ok) {
                showError('');
                target.innerHTML = payload.html;
                bindResultButtons();
            } else if (payload.empty) {
                showError('');
            } else {
                showError(payload.error || '');
            }
        }).catch(function (error) {
            if (error && error.name === 'AbortError') { return; }
            inFlight = null;
        });
    }

    function schedule() {
        window.clearTimeout(timer);
        timer = window.setTimeout(calculate, 250);
    }

    // Whether a result actually gets passed on to the family is the most
    // useful thing this page can tell us, and it needs no identifiers to
    // answer — just a count.
    function report(name) {
        var school = form.querySelector('[name="madhhab"]');
        var body = new FormData();
        body.append('event', name);
        body.append('madhhab', school ? school.value : '');

        if (navigator.sendBeacon) {
            navigator.sendBeacon(form.getAttribute('data-event-endpoint') || '/api/event', body);
            return;
        }
        fetch(form.getAttribute('data-event-endpoint') || '/api/event', {
            method: 'POST', body: body, keepalive: true
        }).catch(function () { /* never let counting break the page */ });
    }

    function bindResultButtons() {
        var printButton = target.querySelector('[data-print]');
        if (printButton) {
            printButton.addEventListener('click', function () {
                report('printed_result');
                window.print();
            });
        }

        var shareLink = target.querySelector('[data-whatsapp]');
        if (shareLink) {
            shareLink.addEventListener('click', function () { report('whatsapp_share'); });
        }

        var copyButton = target.querySelector('[data-copy]');
        var source = target.querySelector('#share-text');
        if (copyButton && source) {
            copyButton.addEventListener('click', function () {
                var text = source.value;
                var done = function () {
                    var original = copyButton.textContent;
                    copyButton.textContent = copyButton.getAttribute('data-copied') || 'Copied';
                    window.setTimeout(function () { copyButton.textContent = original; }, 1800);
                };
                report('copied_result');
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(done, function () { fallbackCopy(source, done); });
                } else {
                    fallbackCopy(source, done);
                }
            });
        }
    }

    function fallbackCopy(source, done) {
        source.hidden = false;
        source.select();
        try { document.execCommand('copy'); done(); } catch (e) { /* nothing more to try */ }
        source.hidden = true;
    }

    form.addEventListener('input', schedule);
    form.addEventListener('change', function (event) {
        if (event.target && event.target.name === 'deceased_gender') { syncSpouseFields(); }
        schedule();
    });
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        window.clearTimeout(timer);
        calculate();
    });

    // Predeceased children rows, used when Pakistan law is selected.
    var addButton = document.getElementById('add-predeceased');
    var rows = document.getElementById('predeceased-rows');
    var template = document.getElementById('predeceased-template');
    if (addButton && rows && template) {
        var nextRow = 0;
        addButton.addEventListener('click', function () {
            var fragment = template.content.cloneNode(true);
            // Give the row its own index. An unchecked checkbox sends nothing,
            // so without this a later row's answers would land on an earlier
            // child.
            var index = nextRow++;
            Array.prototype.forEach.call(fragment.querySelectorAll('[name]'), function (field) {
                field.name = field.name.replace('__row__', index);
            });
            rows.appendChild(fragment);
            schedule();
        });
        rows.addEventListener('click', function (event) {
            if (event.target && event.target.hasAttribute('data-remove')) {
                var row = event.target.closest('[data-row]');
                if (row) { row.parentNode.removeChild(row); schedule(); }
            }
        });
    }

    syncSpouseFields();
    bindResultButtons();
    if (form.querySelector('[name="madhhab"]').value) { calculate(); }
})();
