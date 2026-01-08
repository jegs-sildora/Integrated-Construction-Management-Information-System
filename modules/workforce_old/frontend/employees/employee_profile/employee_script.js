// employee_header.js
(function ($, window) {



  function EmployeeHeader(options) {
     const backendUrl = "../../backend/employee/backend_employee.php";

    // You can get employeeID from a data attribute or query string
    // Example: <body data-employee-id="EMP001">
    const employeeID = $('body').data('employee-id') || new URLSearchParams(window.location.search).get('id');

    if (!employeeID) {
      console.error("Employee ID not found!");
      return;
    }

    // -------------------- LOAD HEADER --------------------
    function loadHeader() {
      $.getJSON(backendUrl, { fetch_id: employeeID }, res => {
        if (!res.success) {
          console.error("Error loading employee header:", res.message);
          return;
        }

        const emp = res.employee;

        // Profile Header
        $('#profileInitials').text(
          ((emp.first_name?.[0] || '') + (emp.last_name?.[0] || '')).toUpperCase()
        );
        $('#profileName').text(`${emp.first_name} ${emp.last_name}`);
        $('#profilePosition').text(`${emp.position} | ${emp.employee_id} | ${emp.employment_type}`);
        $('#profileStatus').text(emp.status);
        $('#profileSkill').text(emp.skill_type);
        $('#profileStartDate').text(emp.start_date);
                let rate = '$0';
            if (emp.daily_rate && Number(emp.daily_rate) > 0) {
            rate = `$${emp.daily_rate}`;
            } else if (emp.monthly_salary && Number(emp.monthly_salary) > 0) {
            rate = `$${emp.monthly_salary}`;
            }

            $('#profileRate').text(rate);

      });
    }

    loadHeader();

    // -------------------- EDIT BUTTON --------------------
    $(document).on("click", "#editProfileBtn", function () {
      $.getJSON(backendUrl, { fetch_id: employeeID }, res => {
        if (!res.success) return alert(res.message || "Error fetching employee");

        const emp = res.employee;
        Object.keys(emp).forEach(k => {
          const $field = $("#" + k);
          if ($field.length) $field.val(emp[k]);
        });

        $("#employeeModalTitle").text("Edit Employee");
        $("#employeeModalBtnText").text("Update Employee");
        $("#employeeModal").fadeIn();
      });
    });


        // ------------------ Close Modal ------------------
    function closeModal() {
        $("#employeeForm")[0].reset();
        $("#employeeModal").fadeOut(300, function () {
            $(this).css("display", "none");
        });
    }
    $("#closeEmployeeModal, #cancelEmployeeModal").click(closeModal);

    // ------------------ Submit Add/Edit ------------------
    $("#employeeForm").submit(function (e) {
        e.preventDefault();
        $.ajax({
            url: backendUrl,
            method: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function (res) {
                if (res.success) {
                    showToast(res.message, "success");
                    loadEmployees();
                    closeModal();
                } else {
                    showToast(res.message || "Error saving employee", "error");
                }
            },
            error: function () {
                showToast("AJAX error occurred", "error");
            }
        });
    });



 $('#employment_type').change(function() {
    const type = $(this).val();

    if (type === 'Daily') {
        $('#daily_rate').prop('disabled', false);
        $('#monthly_salary').prop('disabled', true).val('');
        $('#end_date').prop('disabled', true).val('');
        $('#payment_type').val('Daily').prop('disabled', true);
    } 
    else if (type === 'Regular') {
        $('#monthly_salary').prop('disabled', false);
        $('#daily_rate').prop('disabled', true).val('');
        $('#end_date').prop('disabled', true).val('');
        $('#payment_type').val('Monthly').prop('disabled', true);
    } 
    else if (type === 'Contractual') {
        $('#end_date').prop('disabled', false);
        $('#payment_type').prop('disabled', false);

        // Apply payment type logic immediately
        const payment = $('#payment_type').val();
        if (payment === 'Daily') {
            $('#daily_rate').prop('disabled', false);
            $('#monthly_salary').prop('disabled', true).val('');
        } else if (payment === 'Monthly') {
            $('#monthly_salary').prop('disabled', false);
            $('#daily_rate').prop('disabled', true).val('');
        } else {
            $('#daily_rate, #monthly_salary').prop('disabled', false);
        }
    } 
    else {
        $('#daily_rate, #monthly_salary').prop('disabled', false);
        $('#end_date').prop('disabled', true).val('');
        $('#payment_type').prop('disabled', true);
    }
});

    // -------------------- BACK BUTTON --------------------
    $("#backBtn").on("click", () => window.location.href = "../employees/employees.php");
  }

  // Expose globally
  window.EmployeeHeader = EmployeeHeader;

})(jQuery, window);
