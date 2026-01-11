// Init Icons
lucide.createIcons();

// Modal Logic
function viewPayload(btn) {
const raw = btn.getAttribute('data-payload');
const container = document.getElementById('jsonContainer');

try {
    // Attempt to pretty print JSON
    const obj = JSON.parse(raw);
    container.innerHTML = syntaxHighlight(JSON.stringify(obj, null, 2));
} catch (e) {
    // Fallback for plain text strings
    container.textContent = raw;
}

const modal = document.getElementById('payloadModal');
modal.classList.remove('hidden');
setTimeout(() => {
    modal.querySelector('.modal-content').classList.add('modal-open');
}, 10);
}

function closeModal() {
const modal = document.getElementById('payloadModal');
const content = modal.querySelector('.modal-content');
content.classList.remove('modal-open');
setTimeout(() => {
    modal.classList.add('hidden');
}, 200);
}

// Export Function
function exportLogs() {
const currentUrl = new URL(window.location.href);
currentUrl.searchParams.set('export', 'csv');
window.open(currentUrl.toString(), '_blank');
}

// JSON Syntax Highlighter Helper
function syntaxHighlight(json) {
json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
    var cls = 'json-number';
    if (/^"/.test(match)) {
        if (/:$/.test(match)) {
            cls = 'json-key';
        } else {
            cls = 'json-string';
        }
    } else if (/true|false/.test(match)) {
        cls = 'json-boolean text-blue-400';
    } else if (/null/.test(match)) {
        cls = 'json-null text-red-400';
    }
    return '<span class="' + cls + '">' + match + '</span>';
});
}

    // Auto-apply filters: submit when any filter changes (debounced)
(function(){
    const form = document.getElementById('filterForm');
    if (!form) return;

    function debounce(fn, wait){
        let t;
        return function(...args){
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, args), wait);
        };
    }

    function buildUrlFromForm(frm){
        const url = new URL(window.location.href);
        const params = new URLSearchParams(new FormData(frm));
        // reset to page 1 when filters change
        params.delete('page');
        url.search = params.toString();
        return url.toString();
    }

    async function refreshAuditView(urlStr){
        const loader = document.getElementById('auditLoading');
        if (loader) loader.classList.remove('hidden');
        try{
            const res = await fetch(urlStr, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const text = await res.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(text, 'text/html');

            // Update table
            const newTable = doc.getElementById('auditTableContainer');
            const curTable = document.getElementById('auditTableContainer');
            if (newTable && curTable) curTable.innerHTML = newTable.innerHTML;

            // Update total records badge
            const newTotal = doc.getElementById('auditTotalRecords');
            const curTotal = document.getElementById('auditTotalRecords');
            if (newTotal && curTotal) curTotal.innerHTML = newTotal.innerHTML;

            // Update pagination
            const newPag = doc.getElementById('auditPagination');
            const curPag = document.getElementById('auditPagination');
            if (curPag) {
                if (newPag) curPag.innerHTML = newPag.innerHTML;
                else curPag.innerHTML = '';
            }

            // Update history
            window.history.pushState({}, '', urlStr);

            // Re-run icons (in case empty-state icon changed)
            if (typeof lucide !== 'undefined') lucide.createIcons();
        } catch (err){
            console.error('Failed to refresh audit view:', err);
        } finally {
            if (loader) loader.classList.add('hidden');
        }
    }

    const dateEl = form.querySelector('input[name="date"]');
    const moduleEl = form.querySelector('select[name="module"]');
    const actionEl = form.querySelector('select[name="action"]');
    const userEl = form.querySelector('input[name="user"]');

    const applyChange = debounce(() => {
        const url = buildUrlFromForm(form);
        refreshAuditView(url);
    }, 420);

    if (dateEl) dateEl.addEventListener('change', applyChange);
    if (moduleEl) moduleEl.addEventListener('change', applyChange);
    if (actionEl) actionEl.addEventListener('change', applyChange);
    if (userEl) {
        userEl.addEventListener('input', debounce(() => { const url = buildUrlFromForm(form); refreshAuditView(url); }, 600));
        userEl.addEventListener('keydown', function(e){ if (e.key === 'Enter'){ e.preventDefault(); const url = buildUrlFromForm(form); refreshAuditView(url); } });
    }

    // Intercept manual form submit (Apply Filters button)
    form.addEventListener('submit', function(e){
        e.preventDefault();
        const url = buildUrlFromForm(form);
        refreshAuditView(url);
    });

    // Intercept pagination clicks to use AJAX
    document.addEventListener('click', function(e){
        const a = e.target.closest('#auditPagination a');
        if (a && a.href) {
            e.preventDefault();
            refreshAuditView(a.href);
        }
    });
})();