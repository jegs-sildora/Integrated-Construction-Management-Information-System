<?php
// If requested via AJAX POST, return JSON before emitting any HTML
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    $message = htmlspecialchars($input['message'] ?? '');
    $type = $input['type'] ?? 'success';
    $config = [
        'success' => ['icon' => '<i class="fa-solid fa-check"></i>', 'color' => 'text-green-500', 'bg' => 'bg-green-100', 'border' => 'border-green-200'],
        'error' => ['icon' => '<i class="fa-solid fa-xmark"></i>', 'color' => 'text-red-500', 'bg' => 'bg-red-100', 'border' => 'border-red-200'],
        'warning' => ['icon' => '<i class="fa-solid fa-exclamation"></i>', 'color' => 'text-amber-500', 'bg' => 'bg-amber-100', 'border' => 'border-amber-200'],
        'info' => ['icon' => '<i class="fa-solid fa-info"></i>', 'color' => 'text-blue-500', 'bg' => 'bg-blue-100', 'border' => 'border-blue-200']
    ];
    $theme = $config[$type] ?? $config['success'];
    $toastId = 'toast-' . time() . rand(1000, 9999);
    $toast = "<div id=\"$toastId\" class=\"pointer-events-auto flex items-center w-full max-w-xs p-4 bg-white rounded-xl shadow-xl border {$theme['border']} animate-slide-in mb-3 backdrop-blur-sm bg-opacity-95\">\n        <div class=\"inline-flex items-center justify-center shrink-0 w-8 h-8 {$theme['color']} {$theme['bg']} rounded-lg shadow-sm\">\n            {$theme['icon']}\n        </div>\n        <div class=\"ml-3 text-sm font-bold text-gray-800 leading-snug\">$message</div>\n        <button type=\"button\" class=\"ml-auto -mx-1.5 -my-1.5 bg-transparent text-gray-400 hover:text-gray-900 rounded-lg p-1.5 hover:bg-gray-50 inline-flex items-center justify-center h-8 w-8 transition-colors duration-200\" onclick=\"dismissToast('$toastId')\">\n            <i class=\"fa-solid fa-xmark\"></i>\n        </button>\n    </div>";
    echo json_encode(['html' => $toast, 'id' => $toastId]);
    exit;
}
?>

<style>
    /* Professional Slide-In Animation (Smooth Deceleration) */
    @keyframes slideInRight {
        0% { transform: translateX(120%); opacity: 0; }
        100% { transform: translateX(0); opacity: 1; }
    }

    /* Professional Slide-Out Animation (Smooth Acceleration) */
    @keyframes slideOutRight {
        0% { transform: translateX(0); opacity: 1; }
        100% { transform: translateX(120%); opacity: 0; }
    }

    .animate-slide-in { animation: slideInRight 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
    .animate-slide-out { animation: slideOutRight 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
</style>

<div id="toast-container" class="fixed top-5 right-5 space-y-4 z-[9999] pointer-events-none font-sans"></div>

<script>
    function showToast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        if (!container) return;

        const toastId = 'toast-' + Date.now();

        // Theme Configuration
        const config = {
            success: { icon: '<i class="fa-solid fa-check"></i>', color: 'text-green-500', bg: 'bg-green-100', border: 'border-green-200' },
            error:   { icon: '<i class="fa-solid fa-xmark"></i>', color: 'text-red-500', bg: 'bg-red-100', border: 'border-red-200' },
            warning: { icon: '<i class="fa-solid fa-exclamation"></i>', color: 'text-amber-500', bg: 'bg-amber-100', border: 'border-amber-200' },
            info:    { icon: '<i class="fa-solid fa-info"></i>', color: 'text-blue-500', bg: 'bg-blue-100', border: 'border-blue-200' }
        };
        const theme = config[type] || config.success;

        // Create Toast Element
        const toast = document.createElement('div');
        toast.id = toastId;
        
        // Classes: 
        // - pointer-events-auto: allows clicking the close button
        // - shadow-xl + border: gives it depth and definition
        // - backdrop-blur: subtle modern touch if over complex backgrounds
        toast.className = `pointer-events-auto flex items-center w-full max-w-xs p-4 bg-white rounded-xl shadow-xl border ${theme.border} animate-slide-in mb-3 backdrop-blur-sm bg-opacity-95`;
        
        toast.innerHTML = `
            <div class="inline-flex items-center justify-center shrink-0 w-8 h-8 ${theme.color} ${theme.bg} rounded-lg shadow-sm">
                ${theme.icon}
            </div>
            <div class="ml-3 text-sm font-bold text-gray-800 leading-snug">${message}</div>
            <button type="button" class="ml-auto -mx-1.5 -my-1.5 bg-transparent text-gray-400 hover:text-gray-900 rounded-lg p-1.5 hover:bg-gray-50 inline-flex items-center justify-center h-8 w-8 transition-colors duration-200" onclick="dismissToast('${toastId}')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        `;
        
        container.appendChild(toast);

        // Auto-dismiss after 4 seconds
        setTimeout(() => { dismissToast(toastId); }, 4000);
    }
    
    function dismissToast(toastId) {
        const toast = document.getElementById(toastId);
        if (toast) {
            // Switch animation class
            toast.classList.replace('animate-slide-in', 'animate-slide-out');
            
            // Remove from DOM after animation completes (0.4s = 400ms)
            setTimeout(() => { 
                if (toast && toast.parentNode) {
                    toast.remove(); 
                }
            }, 700); 
        }
    }

    // Check for toasts on page load (URL params or Session Storage)
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        let hasMessage = false;

        if (urlParams.has('success')) { showToast(urlParams.get('success'), 'success'); hasMessage = true; }
        if (urlParams.has('error')) { showToast(urlParams.get('error'), 'error'); hasMessage = true; }
        if (urlParams.has('warning')) { showToast(urlParams.get('warning'), 'warning'); hasMessage = true; }

        // Clean up URL if a toast was shown
        if (hasMessage) {
            const newUrl = window.location.pathname; 
            window.history.replaceState({}, document.title, newUrl);
        }

        // Check pending toasts from redirects
        const pending = sessionStorage.getItem('pendingToast');
        if (pending) {
            try {
                const parsed = JSON.parse(pending);
                const message = parsed.message;
                const type = parsed.type;
                const target = parsed.target || null;
                // If a target is specified, only show when the current pathname matches (endsWith allows filename-only targets)
                const path = window.location.pathname || '';
                if (!target || path.endsWith(target) || path === target) {
                    sessionStorage.removeItem('pendingToast');
                    // Small delay to ensure smooth entrance after layout paints
                    setTimeout(() => showToast(message, type), 100);
                }
                // otherwise leave pendingToast in storage for the target page to consume
            } catch (e) {}
        }
    });
</script>

<div class="hidden bg-green-100 text-green-500 border-green-200 bg-red-100 text-red-500 border-red-200 bg-amber-100 text-amber-500 border-amber-200 bg-blue-100 text-blue-500 border-blue-200"></div>