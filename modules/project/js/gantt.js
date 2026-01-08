// modules/project/js/gantt.js

let gantt = null;
let ganttData = [];
let currentViewMode = 'Month';

// ------------------ Data Parsing ------------------
function parseGanttData() {
    const el = document.getElementById('gantt-data');
    if (!el) return [];
    try {
        return JSON.parse(el.textContent || el.innerText || '[]');
    } catch (e) {
        console.error('Failed to parse Gantt data JSON', e);
        return [];
    }
}

document.addEventListener('DOMContentLoaded', function() {
    ganttData = parseGanttData();
    if (ganttData.length > 0) {
        initGantt(currentViewMode);
    }
});

// ------------------ Initialization ------------------
function initGantt(viewMode) {
    if (ganttData.length === 0) return;

    // Transform data for Frappe Gantt
    const tasks = ganttData.map(item => ({
        id: item.id,
        name: item.name,
        start: item.start,
        end: item.end,
        progress: item.progress,
        custom_class: item.custom_class
    }));

    // Clear existing SVG if any
    const svgElement = document.getElementById('gantt');
    if (svgElement) svgElement.innerHTML = '';

    gantt = new Gantt("#gantt", tasks, {
        view_mode: viewMode,
        date_format: 'YYYY-MM-DD',
        language: 'en',
        custom_popup_html: function(task) {
            const item = ganttData.find(d => d.id === task.id);
            if (!item) return '';
            
            const typeLabel = item.type === 'phase' ? 'Phase' : 'Task';
            const typeColor = item.type === 'phase' ? '#e9922c' : '#3b82f6';

            let html = `
                <div class="p-3 min-w-[200px] bg-white">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="px-2 py-0.5 text-xs font-bold text-white rounded" style="background-color: ${typeColor}">
                            ${typeLabel}
                        </span>
                    </div>
                    <h4 class="font-semibold text-gray-900 mb-1">${task.name}</h4>
                    <p class="text-sm text-gray-500 mb-2">${item.project}</p>
                    <div class="flex items-center gap-4 text-xs text-gray-500">
                        <span><strong>Start:</strong> ${formatDate(task.start)}</span>
                        <span><strong>End:</strong> ${formatDate(task.end)}</span>
                    </div>
                    <div class="mt-2 pt-2 border-t border-gray-100">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-500">Progress</span>
                            <span class="font-semibold text-gray-700">${task.progress}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-1.5 mt-1">
                            <div class="h-1.5 rounded-full" style="width: ${task.progress}%; background-color: ${typeColor}"></div>
                        </div>
                    </div>
            `;

            if (item.type === 'task' && item.status) {
                const statusColors = {
                    'Not Started': 'bg-slate-100 text-slate-700',
                    'In Progress': 'bg-blue-100 text-blue-700',
                    'On Hold': 'bg-amber-100 text-amber-700',
                    'Completed': 'bg-green-100 text-green-700'
                };
                const statusClass = statusColors[item.status] || 'bg-gray-100 text-gray-700';
                html += `
                    <div class="mt-2 pt-2 border-t border-gray-100">
                        <span class="px-2 py-0.5 text-xs font-medium rounded ${statusClass}">${item.status}</span>
                    </div>
                `;
            }

            html += '</div>';
            return html;
        },
        on_click: function(task) {
            const item = ganttData.find(d => d.id === task.id);
            if (item) {
                // Briefly highlight the clicked item using the system accent color
                const typeColor = item.type === 'phase' ? '#e9922c' : '#3b82f6';
                setHighlightForItem(item.id, typeColor);
                setTimeout(() => {
                    if (item.type === 'phase') {
                        window.location.href = 'phases.php';
                    } else {
                        window.location.href = 'tasks.php';
                    }
                }, 150);
            }
        }
    });

    // Apply dynamic text colors once DOM is ready
    setTimeout(applyDynamicTextColors, 60);
}

// ------------------ AJAX Filter ------------------
function filterByProject(projectId) {
    const url = new URL(window.location.href);
    if (projectId) {
        url.searchParams.set('project_id', projectId);
    } else {
        url.searchParams.delete('project_id');
    }
    
    // Update browser URL without reloading
    window.history.pushState({}, '', url);
    
    // Perform AJAX refresh
    refreshGanttView(url);
}

async function refreshGanttView(url) {
    try {
        const response = await fetch(url);
        const text = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(text, 'text/html');

        // 1. Update Gantt Data Script
        const newDataScript = doc.getElementById('gantt-data');
        const currentDataScript = document.getElementById('gantt-data');
        if (newDataScript && currentDataScript) {
            currentDataScript.textContent = newDataScript.textContent;
            ganttData = parseGanttData(); // Re-parse data
        }

        // 2. Update Stats
        const stats = ['statTotalPhases', 'statTotalTasks', 'statTotalProjects', 'statTimelineItems'];
        stats.forEach(id => {
            const newEl = doc.getElementById(id);
            const currentEl = document.getElementById(id);
            if (newEl && currentEl) {
                currentEl.textContent = newEl.textContent;
            }
        });

        // 3. Update Chart Container (handles Empty State vs Chart toggle)
        const newWrapper = doc.getElementById('ganttChartWrapper');
        const currentWrapper = document.getElementById('ganttChartWrapper');
        if (newWrapper && currentWrapper) {
            currentWrapper.innerHTML = newWrapper.innerHTML;
        }

        // 4. Re-initialize Gantt
        if (ganttData.length > 0) {
            initGantt(currentViewMode);
        }

    } catch (err) {
        console.error("Failed to refresh gantt view:", err);
        if (typeof showToast === 'function') {
            showToast("Failed to update filter. Please reload.", "error");
        }
    }
}

// ------------------ Visual Helpers ------------------

// Utility: compute contrast (black or white) for a given hex color
function getContrastingTextColor(hex) {
    if (!hex) return '#111';
    hex = (hex || '').replace('#', '');
    if (hex.length === 3) {
        hex = hex.split('').map(h => h + h).join('');
    }
    const r = parseInt(hex.substr(0,2) || '0',16);
    const g = parseInt(hex.substr(2,2) || '0',16);
    const b = parseInt(hex.substr(4,2) || '0',16);
    const lum = (0.2126*r + 0.7152*g + 0.0722*b)/255;
    return lum > 0.55 ? '#111827' : '#ffffff';
}

function applyDynamicTextColors() {
    document.querySelectorAll('.gantt .bar-wrapper').forEach(wrapper => {
        const label = wrapper.querySelector('.bar-label');
        const bar = wrapper.querySelector('.bar');
        if (!label || !bar) return;

        let fill = bar.getAttribute('fill') || window.getComputedStyle(bar).fill || '';
        if (fill && fill.indexOf('rgb') === 0) {
            const nums = fill.match(/\d+/g).map(Number);
            fill = '#' + nums.map(n => n.toString(16).padStart(2,'0')).join('');
        }

        if (!fill || fill === 'none') {
            if (wrapper.classList.contains('bar-phase')) fill = '#e9922c';
            else if (wrapper.classList.contains('bar-task')) fill = '#3b82f6';
        }

        const contrast = getContrastingTextColor(fill);
        label.style.fill = contrast;
    });
}

function setHighlightForItem(itemId, color) {
    let wrapper = document.querySelector(`.gantt .bar-wrapper[data-id="${itemId}"]`);
    if (!wrapper) {
        const item = ganttData.find(d => d.id === itemId);
        if (item) {
            wrapper = Array.from(document.querySelectorAll('.gantt .bar-wrapper')).find(w => {
                const l = w.querySelector('.bar-label');
                return l && l.textContent.trim() === item.name;
            });
        }
    }
    if (!wrapper) return;

    const label = wrapper.querySelector('.bar-label');
    const bar = wrapper.querySelector('.bar');
    if (label) label.style.fill = color;
    if (bar) {
        bar.style.stroke = color;
        bar.style.strokeWidth = '1.5';
    }
    setTimeout(() => {
        if (label) label.style.fill = getContrastingTextColor(bar ? (bar.getAttribute('fill')||'') : '');
        if (bar) { bar.style.stroke = ''; bar.style.strokeWidth = ''; }
    }, 800);
}

function changeViewMode(mode) {
    currentViewMode = mode; // Update global state
    if (gantt) {
        gantt.change_view_mode(mode);

        document.querySelectorAll('.view-mode-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.textContent.trim() === mode) {
                btn.classList.add('active');
            }
        });
    }
}

function formatDate(date) {
    const d = new Date(date);
    const options = { month: 'short', day: 'numeric', year: 'numeric' };
    return d.toLocaleDateString('en-US', options);
}