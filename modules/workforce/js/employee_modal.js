/**
 * Employee Modal Logic
 * Handles form interactions, validations, computations, and AJAX submission.
 */

document.addEventListener("DOMContentLoaded", function () {
  // Ensure icons render
  if (typeof lucide !== "undefined") {
    lucide.createIcons();
  }

  // --- 1. EMAIL AUTO-GENERATION ---
  const first = document.getElementById("first_name");
  const last = document.getElementById("last_name");
  const email = document.getElementById("email");

  function sanitizePart(s) {
    return s
      .trim()
      .toLowerCase()
      .replace(/\s+/g, ".")
      .replace(/[^a-z0-9.]/g, "")
      .replace(/\.+/g, ".")
      .replace(/^\.+|\.+$/g, "");
  }

  function buildEmail() {
    const f = first ? sanitizePart(first.value) : "";
    const l = last ? sanitizePart(last.value) : "";
    let local = "";
    if (f && l) local = f + "." + l;
    else local = f || l;

    if (local && email) email.value = local + "@icmis.com";
    else if (email) email.value = "";
  }

  if (first) first.addEventListener("input", buildEmail);
  if (last) last.addEventListener("input", buildEmail);
  // Run once on load
  buildEmail();

  // --- 2. AGE CALCULATION ---
  const birthday = document.getElementById("birthday");
  const ageInput = document.getElementById("age");

  function computeAge() {
    if (!birthday || !ageInput) return;
    if (!birthday.value) {
      ageInput.value = "";
      return;
    }
    const dob = new Date(birthday.value);
    if (isNaN(dob)) {
      ageInput.value = "";
      return;
    }
    const today = new Date();
    let age = today.getFullYear() - dob.getFullYear();
    const m = today.getMonth() - dob.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
    ageInput.value = age;
  }

  if (birthday) {
    birthday.addEventListener("change", computeAge);
    computeAge();
  }

  // --- 3. STRICT PHONE INPUT ---
  const prefix = "+63 ";

  function setupStrictPhoneInput(elementId) {
    const phoneInput = document.getElementById(elementId);
    if (!phoneInput) return;

    function ensurePhonePrefix() {
      if (!phoneInput.value.startsWith(prefix)) {
        let v = phoneInput.value
          .replace(/^\s*/, "")
          .replace(/^(\+?63\s*|63\s*|0+)/, "")
          .replace(/[^0-9 ]/g, "");
        phoneInput.value = prefix + v;
      }
    }

    const maxDigits = parseInt(phoneInput.dataset.maxDigits || "10", 10) || 10;

    ensurePhonePrefix();

    phoneInput.addEventListener("input", function () {
      const selStart = phoneInput.selectionStart || 0;
      let currentVal = phoneInput.value;

      if (!currentVal.startsWith(prefix)) {
        ensurePhonePrefix();
        currentVal = phoneInput.value;
      }

      const body = currentVal.substring(prefix.length);
      let cleanBody = body.replace(/[^0-9]/g, "");
      if (cleanBody.length > maxDigits)
        cleanBody = cleanBody.substring(0, maxDigits);

      if (body !== cleanBody) {
        phoneInput.value = prefix + cleanBody;
      } else {
        ensurePhonePrefix();
      }

      if (selStart <= prefix.length)
        phoneInput.setSelectionRange(prefix.length, prefix.length);
    });

    phoneInput.addEventListener("keydown", function (e) {
      const start = phoneInput.selectionStart || 0;
      if (
        (e.key === "Backspace" || e.key === "Delete") &&
        start <= prefix.length
      ) {
        e.preventDefault();
      }
    });
  }

  setupStrictPhoneInput("phone");
  setupStrictPhoneInput("emergency_contact_phone");

  // --- 4. DEFAULT HIRE DATE ---
  const hire = document.getElementById("hire_date");
  if (hire && !hire.value) {
    const d = new Date();
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, "0");
    const dd = String(d.getDate()).padStart(2, "0");
    hire.value = `${yyyy}-${mm}-${dd}`;
  }

  // --- 5. JOB TITLES & DEPARTMENTS ---
  let _jobTitles = [];

  function normalizeString(s) {
    return (s || "")
      .toString()
      .trim()
      .toLowerCase()
      .replace(/\s+/g, " ")
      .replace(/[^a-z0-9 ]/g, "");
  }

  async function loadJobTitlesAndDepartments() {
    try {
      // Updated to use API Gateway
      const res = await fetch(
        `${window.GATEWAY_URL}workforce/employees/options`,
        { headers: { 'Authorization': `Bearer ${window.AUTH_TOKEN}` } }
      );
      const j = await res.json();

      const list = document.getElementById("jobTitlesList");

      if (j && j.success) {
        const data =
          j && typeof j === "object"
            ? j.data && typeof j.data === "object"
              ? j.data
              : j
            : {};
        _jobTitles = Array.isArray(data.job_titles) ? data.job_titles : [];
        if (list) {
          list.innerHTML = "";
          _jobTitles.forEach(function (row) {
            const opt = document.createElement("option");
            opt.value = row.title_name;
            if (row.department) opt.dataset.department = row.department;
            if (row.default_daily_rate)
              opt.setAttribute(
                "data-default-daily-rate",
                row.default_daily_rate,
              );
            if (row.default_monthly_salary)
              opt.setAttribute(
                "data-default-monthly-salary",
                row.default_monthly_salary,
              );
            list.appendChild(opt);
          });
        }
      } else {
        // Fallback: Parse existing datalist options
        if (list) {
          _jobTitles = [];
          Array.from(list.options).forEach(function (opt) {
            _jobTitles.push({
              title_name: opt.value || "",
              department: opt.dataset.department || "",
              default_daily_rate:
                opt.dataset.defaultDailyRate ||
                opt.getAttribute("data-default-daily-rate") ||
                "",
              default_monthly_salary:
                opt.dataset.defaultMonthlySalary ||
                opt.getAttribute("data-default-monthly-salary") ||
                "",
            });
          });
        }
      }
      syncDepartmentFromPosition();
    } catch (err) {
      console.error("Failed to load job titles", err);
    }
  }

  loadJobTitlesAndDepartments();

  // Sync Department logic
  const positionInput = document.getElementById("position");
  const departmentSelect = document.getElementById("department");

  if (departmentSelect) departmentSelect.readOnly = true;

  function syncDepartmentFromPosition() {
    if (!positionInput || !departmentSelect) return;
    const val = (positionInput.value || "").trim();

    departmentSelect.readOnly = true;
    if (!val) {
      departmentSelect.value = "";
      setCurrencyInputs("", "");
      return;
    }

    const normVal = normalizeString(val);

    // Find best match
    let match = _jobTitles.find(
      (jt) => jt.title_name && normalizeString(jt.title_name) === normVal,
    );
    if (!match)
      match = _jobTitles.find(
        (jt) =>
          jt.title_name &&
          normVal.indexOf(normalizeString(jt.title_name)) !== -1,
      );
    if (!match)
      match = _jobTitles.find(
        (jt) =>
          jt.title_name &&
          normalizeString(jt.title_name).indexOf(normVal) !== -1,
      );

    if (match) {
      departmentSelect.value = match.department || "";
      const dRate = match.default_daily_rate ?? "";
      const mSalary = match.default_monthly_salary ?? "";
      setCurrencyInputs(dRate, mSalary);
    } else {
      departmentSelect.value = "";
      setCurrencyInputs("", "");
    }
  }

  if (positionInput) {
    positionInput.addEventListener("input", syncDepartmentFromPosition);
    positionInput.addEventListener("change", syncDepartmentFromPosition);
  }

  // --- 6. CURRENCY INPUTS ---
  const dailyRateHidden = document.getElementById("daily_rate");
  const monthlySalaryHidden = document.getElementById("monthly_salary");
  const dailyRateDisplay = document.getElementById("daily_rate_display");
  const monthlySalaryDisplay = document.getElementById(
    "monthly_salary_display",
  );

  function numberToFormatted(n) {
    if (n === null || n === undefined || n === "") return "";
    const num = Number(String(n).replace(/,/g, ""));
    if (isNaN(num)) return "";
    const parts = (Math.round((num + Number.EPSILON) * 100) / 100)
      .toFixed(2)
      .split(".");
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    return parts[1] === "00" ? parts[0] : parts.join(".");
  }

  // Expose this globally so openEditModal can use it
  window.numberToFormatted = numberToFormatted;

  function setCurrencyInputs(dailyRaw, monthlyRaw) {
    if (dailyRateHidden) dailyRateHidden.value = dailyRaw || "";
    if (monthlySalaryHidden) monthlySalaryHidden.value = monthlyRaw || "";
    if (dailyRateDisplay)
      dailyRateDisplay.value = dailyRaw ? numberToFormatted(dailyRaw) : "";
    if (monthlySalaryDisplay)
      monthlySalaryDisplay.value = monthlyRaw
        ? numberToFormatted(monthlyRaw)
        : "";
  }

  function setupCurrencyInput(displayInput, hiddenInput) {
    if (!displayInput || !hiddenInput) return;

    function sanitize(val) {
      if (!val) return "";
      let s = String(val).replace(/[^0-9.]/g, "");
      const parts = s.split(".");
      if (parts.length <= 1) return parts[0];
      return parts[0] + "." + parts.slice(1).join("");
    }

    displayInput.addEventListener("input", function () {
      const rawSanitized = sanitize(displayInput.value);
      if (displayInput.value !== rawSanitized)
        displayInput.value = rawSanitized;
      hiddenInput.value = rawSanitized;
    });

    displayInput.addEventListener("focus", function () {
      displayInput.value = hiddenInput.value || "";
    });

    displayInput.addEventListener("blur", function () {
      const raw = sanitize(displayInput.value);
      hiddenInput.value = raw;
      displayInput.value = raw ? numberToFormatted(raw) : "";
    });

    displayInput.addEventListener("keydown", function (e) {
      const allowed = [
        "Backspace",
        "Tab",
        "ArrowLeft",
        "ArrowRight",
        "Delete",
        "Home",
        "End",
      ];
      if (
        allowed.indexOf(e.key) !== -1 ||
        e.key === "." ||
        /^[0-9]$/.test(e.key)
      )
        return;
      e.preventDefault();
    });
  }

  setupCurrencyInput(dailyRateDisplay, dailyRateHidden);
  setupCurrencyInput(monthlySalaryDisplay, monthlySalaryHidden);

  // --- 7. FORM SUBMISSION HANDLER ---
  const form = document.getElementById("employeeForm");
  if (form) {
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      saveEmployee();
    });
  }
});

// --- GLOBAL FUNCTIONS (Called by HTML onclicks) ---

window.saveEmployee = async function () {
  const form = document.getElementById("employeeForm");
  if (!form) return;
  const submitBtn = form.querySelector('button[type="submit"]');
  const originalBtnText = submitBtn ? submitBtn.innerHTML : "Save Employee";

  // 1. Determine action and ID
  const idInput = document.getElementById("employee_id");
  const employeeId = idInput ? idInput.value.trim() : "";
  const isUpdate = employeeId !== "";

  // 2. Prepare Data (Convert FormData to JSON for microservice)
  const formData = new FormData(form);
  const object = {};
  formData.forEach((value, key) => object[key] = value);

  // 3. UI Loading State
  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.innerHTML =
      '<div class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-2"></div>Saving...';
  }

  try {
    const url = isUpdate 
      ? `${window.GATEWAY_URL}workforce/employees/${employeeId}` 
      : `${window.GATEWAY_URL}workforce/employees`;
    const method = isUpdate ? "PUT" : "POST";

    const response = await fetch(url, {
      method: method,
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${window.AUTH_TOKEN}`
      },
      body: JSON.stringify(object),
    });

    const result = await response.json();

    if (result.success) {
      if (typeof showToast === "function")
        showToast(result.message || "Saved successfully!", "success");

      // Close the modal
      if (typeof closeModal === "function") {
        closeModal();
      } else {
        const m = document.getElementById("employeeModal");
        if (m) m.classList.add("hidden");
      }

      // REFRESH DATA AJAX (Calls function in parent employees.php)
      if (typeof refreshData === "function") {
        refreshData();
      } else {
        // Fallback if refreshData not found
        setTimeout(() => location.reload(), 1000);
      }
    } else {
      if (typeof showToast === "function")
        showToast(result.message || "Error saving employee.", "error");
    }
  } catch (error) {
    console.error("Network Error:", error);
    if (typeof showToast === "function")
      showToast("Network error occurred.", "error");
  } finally {
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalBtnText;
    }
  }
};

window.closeModal = function () {
  const modal = document.getElementById("employeeModal");
  if (!modal) return;
  const content = modal.querySelector(".modal-content");
  if (content) {
    content.classList.remove("modal-open");
    content.classList.add("modal-close");
  }
  setTimeout(() => {
    modal.classList.add("hidden");
    if (content) content.classList.remove("modal-close");
  }, 240);
};

window.openCreateModal = function () {
  const form = document.getElementById("employeeForm");
  if (form) form.reset();

  const idField = document.getElementById("employee_id");
  const actionField = document.getElementById("form_action");
  if (idField) idField.value = "";
  if (actionField) actionField.value = "create";

  const titleEl = document.getElementById("modalTitle");
  if (titleEl) titleEl.textContent = "Add New Employee";
  
  const ageEl = document.getElementById("age");
  if (ageEl) ageEl.value = "";
  
  const deptEl = document.getElementById("department");
  if (deptEl) deptEl.value = "";

  const today = new Date().toISOString().split("T")[0];
  const hire = document.getElementById("hire_date");
  if (hire) hire.value = today;

  const modal = document.getElementById("employeeModal");
  if (modal) {
    modal.classList.remove("hidden");
    const content = modal.querySelector(".modal-content");
    if (content) setTimeout(() => content.classList.add("modal-open"), 10);
  }
};

window.openEditModal = async function (id) {
  if (!id) return;
  try {
    // Updated to use API Gateway
    const response = await fetch(`${window.GATEWAY_URL}workforce/employees/${id}`, {
        headers: { 'Authorization': `Bearer ${window.AUTH_TOKEN}` }
    });
    const result = await response.json();

    if (!result.success) {
      if (typeof showToast === "function")
        showToast(result.message || "Error fetching data", "error");
      else alert(result.message);
      return;
    }

    const data = result.data;

    // Populate fields
    const fields = [
      "employee_id",
      "first_name",
      "last_name",
      "suffix",
      "gender",
      "birthday",
      "email",
      "phone",
      "address",
      "employment_type",
      "payment_type",
      "status",
      "bank_name",
      "bank_account",
      "emergency_contact_name",
      "emergency_contact_phone",
      "notes",
      "hire_date",
    ];
    fields.forEach((f) => {
      const el = document.getElementById(f);
      if (el && data[f] !== undefined) el.value = data[f];
    });

    if (data.position)
      document.getElementById("position").value = data.position;
    if (data.department)
      document.getElementById("department").value = data.department;

    // Populate currency fields
    const dr = document.getElementById("daily_rate");
    const ms = document.getElementById("monthly_salary");
    if (dr) dr.value = data.daily_rate || 0;
    if (ms) ms.value = data.monthly_salary || 0;

    if (typeof window.numberToFormatted === "function") {
      const drD = document.getElementById("daily_rate_display");
      const msD = document.getElementById("monthly_salary_display");
      if (drD) drD.value = window.numberToFormatted(data.daily_rate);
      if (msD) msD.value = window.numberToFormatted(data.monthly_salary);
    }

    // Trigger age calculation
    if (document.getElementById("birthday"))
      document.getElementById("birthday").dispatchEvent(new Event("change"));

    const actionField = document.getElementById("form_action");
    if (actionField) actionField.value = "update";

    const titleEl = document.getElementById("modalTitle");
    if (titleEl) titleEl.textContent = "Edit Employee";

    const modal = document.getElementById("employeeModal");
    if (modal) {
        modal.classList.remove("hidden");
        const content = modal.querySelector(".modal-content");
        if (content) setTimeout(() => content.classList.add("modal-open"), 10);
    }
  } catch (e) {
    console.error("Fetch error:", e);
    if (typeof showToast === "function")
      showToast("Failed to load employee details.", "error");
  }
};
