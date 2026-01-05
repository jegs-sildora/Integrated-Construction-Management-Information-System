class TableManager {
    constructor({
        tableSelector,
        searchSelector = null,
        rowsPerPage = 10,
        recordCountSelector = null,
        pageNumbersSelector = null,
        prevBtnSelector = "#prevPage",
        nextBtnSelector = "#nextPage"
    }) {
        this.table = document.querySelector(tableSelector);
        this.tbody = this.table.querySelector("tbody");
        this.searchInput = searchSelector ? document.querySelector(searchSelector) : null;
        this.rowsPerPage = rowsPerPage;
        this.recordCountEl = recordCountSelector ? document.querySelector(recordCountSelector) : null;
        this.pageNumbersEl = pageNumbersSelector ? document.querySelector(pageNumbersSelector) : null;
        this.prevBtn = document.querySelector(prevBtnSelector);
        this.nextBtn = document.querySelector(nextBtnSelector);

        this.currentPage = 1;
        this.rows = Array.from(this.tbody.children);
        this.filteredRows = [...this.rows];
        this.filterCallback = null;

        this.init();
    }

    init() {
        if (this.searchInput) {
            this.searchInput.addEventListener("input", () => this.applyFilters());
        }
        this.render();
    }

    addRow(row) {
        this.tbody.appendChild(row);
        this.rows.push(row);
        this.filteredRows.push(row);
        this.render();
    }

    clear() {
        this.rows = [];
        this.filteredRows = [];
        this.currentPage = 1;
        this.tbody.innerHTML = "";
        this.render();
    }

    setFilterCallback(callback) {
        this.filterCallback = callback;
        this.applyFilters();
    }

    applyFilters() {
        this.filteredRows = this.rows.filter(row => {
            let passesFilter = this.filterCallback ? this.filterCallback(row) : true;

            let passesSearch = true;
            if (this.searchInput && this.searchInput.value.trim()) {
                const search = this.searchInput.value.toLowerCase();
                passesSearch = row.textContent.toLowerCase().includes(search);
            }

            return passesFilter && passesSearch;
        });

        this.currentPage = 1;
        this.render();
    }

    render() {
        const start = (this.currentPage - 1) * this.rowsPerPage;
        const end = start + this.rowsPerPage;

        this.rows.forEach(row => (row.style.display = "none"));
        this.filteredRows.slice(start, end).forEach(row => (row.style.display = ""));

        this.updateRecordCount();
        this.renderPageNumbers();
        this.updateNavButtons();
    }

    updateRecordCount() {
        if (!this.recordCountEl) return;

        const total = this.filteredRows.length;
        const start = total === 0 ? 0 : (this.currentPage - 1) * this.rowsPerPage + 1;
        const end = Math.min(this.currentPage * this.rowsPerPage, total);

        this.recordCountEl.textContent = `Showing ${start}-${end} of ${total}`;
    }

    renderPageNumbers() {
        if (!this.pageNumbersEl) return;

        const totalPages = Math.ceil(this.filteredRows.length / this.rowsPerPage);
        this.pageNumbersEl.innerHTML = "";

        for (let i = 1; i <= totalPages; i++) {
            const btn = document.createElement("span");
            btn.textContent = i;
            btn.className = "page-number" + (i === this.currentPage ? " active" : "");

            btn.addEventListener("click", () => {
                if (this.currentPage !== i) {
                    this.currentPage = i;
                    this.render();
                }
            });

            this.pageNumbersEl.appendChild(btn);
        }
    }

    updateNavButtons() {
        const totalPages = Math.ceil(this.filteredRows.length / this.rowsPerPage);

        if (this.prevBtn) {
            this.prevBtn.disabled = this.currentPage === 1;
            this.prevBtn.classList.toggle("disabled", this.currentPage === 1);
        }

        if (this.nextBtn) {
            this.nextBtn.disabled = this.currentPage === totalPages || totalPages === 0;
            this.nextBtn.classList.toggle(
                "disabled",
                this.currentPage === totalPages || totalPages === 0
            );
        }
    }

    nextPage() {
        const totalPages = Math.ceil(this.filteredRows.length / this.rowsPerPage);
        if (this.currentPage < totalPages) {
            this.currentPage++;
            this.render();
        }
    }

    prevPage() {
        if (this.currentPage > 1) {
            this.currentPage--;
            this.render();
        }
    }
}
