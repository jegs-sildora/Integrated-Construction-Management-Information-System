$(document).ready(function () {
  // Open Filter Menu when button is clicked
  $("#FilterBtn").click(function (e) {
    const $filterMenu = $("#FilterMenu");

    // Get the position of the filter button
    const buttonOffset = $(this).offset();
    const buttonHeight = $(this).outerHeight();

    // Calculate the position: place the menu directly below the button
    const topPosition = buttonOffset.top + buttonHeight + 10; // 10px margin below the button
    const leftPosition = buttonOffset.left;

    // Set the position dynamically
    $filterMenu.css({
      top: topPosition + 'px',    // Position the menu below the button
      left: leftPosition + 'px'   // Align the menu with the left edge of the button
    });

    // Toggle visibility of the filter menu
    $filterMenu.toggle();
  });

  // Close the filter menu when clicking outside
  $(document).click(function (e) {
    if (!$(e.target).closest("#FilterMenu, #FilterBtn").length) {
      $("#FilterMenu").hide();
    }
  });

  // Hide the filter menu when scrolling
  $(window).scroll(function () {
    if ($("#FilterMenu").is(":visible")) {
      $("#FilterMenu").hide(); // Hide the filter menu when scrolling
    }
  });
});
