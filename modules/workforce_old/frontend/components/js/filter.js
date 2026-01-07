$(document).ready(function () {
  // Open Filter Menu when button is clicked
  $("#FilterBtn").click(function (e) {
    const $filterMenu = $("#FilterMenu");

   
    const buttonOffset = $(this).offset();
    const buttonHeight = $(this).outerHeight();


    const topPosition = buttonOffset.top + buttonHeight + 10; 
    const leftPosition = buttonOffset.left;

   
    $filterMenu.css({
      top: topPosition + 'px',   
      left: leftPosition + 'px'   
    });

   
    $filterMenu.toggle();
  });

  
  $(document).click(function (e) {
    if (!$(e.target).closest("#FilterMenu, #FilterBtn").length) {
      $("#FilterMenu").hide();
    }
  });

  
  $(window).scroll(function () {
    if ($("#FilterMenu").is(":visible")) {
      $("#FilterMenu").hide(); 
    }
  });
});
