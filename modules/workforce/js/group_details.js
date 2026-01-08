        (function initGroupDetails(){
            function init() {
                lucide.createIcons();

                // Cached DOM refs (may be inside modal)
                const modal = document.getElementById('groupModal');
                const modalContent = modal ? modal.querySelector('.modal-content') : null;
                const form = document.getElementById('groupForm');
                const selectedCountLabel = document.getElementById('selectedCount');
                const memberSearch = document.getElementById('memberSearch');
                const memberItems = document.querySelectorAll('#memberList label');

                // Safe updateCount
                function updateCount() {
                    const count = document.querySelectorAll('.member-checkbox:checked').length;
                    if (selectedCountLabel) selectedCountLabel.textContent = `${count} Selected`;
                }

                // Init member checkbox listeners
                document.querySelectorAll('.member-checkbox').forEach(cb => cb.addEventListener('change', updateCount));

                // Init search
                if (memberSearch) {
                    memberSearch.addEventListener('input', (e) => {
                        const term = e.target.value.toLowerCase();
                        memberItems.forEach(item => {
                            const text = item.innerText.toLowerCase();
                            item.style.display = text.includes(term) ? 'flex' : 'none';
                        });
                    });
                }
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }
        })();