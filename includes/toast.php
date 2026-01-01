<div id="toast-container" class="fixed top-5 right-5 space-y-4 z-[9999] pointer-events-none font-sans"></div>

<script>
    function showToast(message, type = 'success', persist = false) {
        if (persist) {
            sessionStorage.setItem('pendingToast', JSON.stringify({ message, type }));
            return;
        }

        const container = document.getElementById('toast-container');
        if (!container) return;

        const toastId = 'toast-' + Date.now();

        // Config for themes
        const config = {
            success: { icon: '<i class="fa-solid fa-check"></i>', color: 'text-green-500', bg: 'bg-green-100', border: 'border-green-200' },
            error:   { icon: '<i class="fa-solid fa-xmark"></i>', color: 'text-red-500', bg: 'bg-red-100', border: 'border-red-200' },
            warning: { icon: '<i class="fa-solid fa-exclamation"></i>', color: 'text-amber-500', bg: 'bg-amber-100', border: 'border-amber-200' },
            info:    { icon: '<i class="fa-solid fa-info"></i>', color: 'text-blue-500', bg: 'bg-blue-100', border: 'border-blue-200' }
        };
        const theme = config[type] || config.success;

        const toast = document.createElement('div');
        toast.id = toastId;
        // Styles applied via JS
        toast.className = `pointer-events-auto flex items-center w-full max-w-xs p-4 bg-white rounded-xl shadow-2xl border ${theme.border} animate-slide-in mb-3`;
        
        toast.innerHTML = `
            <div class="inline-flex items-center justify-center shrink-0 w-8 h-8 ${theme.color} ${theme.bg} rounded-lg">
                ${theme.icon}
            </div>
            <div class="ml-3 text-sm font-bold text-gray-800">${message}</div>
            <button type="button" class="ml-auto -mx-1.5 -my-1.5 bg-white text-gray-400 hover:text-gray-900 rounded-lg focus:ring-2 focus:ring-gray-300 p-1.5 hover:bg-gray-100 inline-flex items-center justify-center h-8 w-8 transition-colors" onclick="dismissToast('${toastId}')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        `;
        
        container.appendChild(toast);
        setTimeout(() => { dismissToast(toastId); }, 4000);
    }
    
    function dismissToast(toastId) {
        const toast = document.getElementById(toastId);
        if (toast) {
            toast.classList.replace('animate-slide-in', 'animate-slide-out');
            setTimeout(() => { toast.remove(); }, 300);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        let hasMessage = false;

        if (urlParams.has('success')) { showToast(urlParams.get('success'), 'success'); hasMessage = true; }
        if (urlParams.has('error')) { showToast(urlParams.get('error'), 'error'); hasMessage = true; }
        if (urlParams.has('warning')) { showToast(urlParams.get('warning'), 'warning'); hasMessage = true; }

        if (hasMessage) {
            const newUrl = window.location.pathname; 
            window.history.replaceState({}, document.title, newUrl);
        }

        const pending = sessionStorage.getItem('pendingToast');
        if (pending) {
            try {
                const { message, type } = JSON.parse(pending);
                sessionStorage.removeItem('pendingToast');
                showToast(message, type);
            } catch (e) {}
        }
    });
</script>

<div class="hidden animate-slide-in animate-slide-out bg-green-100 text-green-500 border-green-200 bg-red-100 text-red-500 border-red-200 bg-amber-100 text-amber-500 border-amber-200 bg-blue-100 text-blue-500 border-blue-200 pointer-events-auto"></div>