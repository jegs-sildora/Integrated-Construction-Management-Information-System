function renderPageNumbers(totalPages, currentPage) {
  const container = $("#pageNumbers");
  container.empty();

  for (let i = 1; i <= totalPages; i++) {
    const pageBtn = $(`<span class="page-number">${i}</span>`);

    if (i === currentPage) {
      pageBtn.addClass("active");
    }

    pageBtn.on("click", () => tableManager.goToPage(i));

    container.append(pageBtn);
  }
}
