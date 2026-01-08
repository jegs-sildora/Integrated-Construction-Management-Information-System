<style>
/* ================= Profile Header ================= */
.profile-header {
  display: flex;
  align-items: flex-start;
  gap: 15px; /* reduced gap to bring avatar closer */
  padding: 20px 25px;
  background-color: #ffffff;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  position: relative;
  margin-bottom: 30px;
}

.profile-avatar {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background-color: #fbbf24; /* yellow/gold */
  color: #fff;
  font-size: 32px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  text-transform: uppercase;
  flex-shrink: 0;
  border: 2px solid #fff;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

.profile-info {
  flex: 1;
  position: relative;
}

.profile-edit-btn {
  position: absolute;
  top: 0;
  right: 0;
}


.profile-info h2 {
  margin: 0;
  font-size: 24px;
  font-weight: 600;
  color: #111827;
}

.profile-info p {
  margin: 5px 0 10px 0;
  color: #6b7280;
  font-size: 14px;
}

.profile-details {
  display: flex;
  gap: 25px; /* slightly larger gap for readability */
  flex-wrap: wrap;
  font-size: 14px;
  color: #374151;
  margin-top: 5px;
}

.profile-details div {
  display: flex;
  align-items: center;
  gap: 5px;
}

.profile-details span {
  font-weight: 600;
}

.status-active {
  color: #059669; /* green for active */
}
.status-inactive {
  color: #ef4444; /* red for inactive */
}


  /* ================= Back Button ================= */

.back-btn-container {
  margin-bottom: 20px;
}




/* Responsive adjustments */
@media (max-width: 768px) {
  .profile-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .profile-avatar {
    width: 60px;
    height: 60px;
    font-size: 24px;
  }

  .profile-info h2 {
    font-size: 20px;
  }

  .profile-info p {
    font-size: 13px;
  }

  .profile-details {
    gap: 15px;
  }

  .profile-edit-btn {
    position: static;
    margin-bottom: 8px;
  }

  .profile-edit-btn button {
    font-size: 12px;
    padding: 4px 10px;
  }




}
</style>



 <!-- Back Button -->
<div class="back-btn-container" style="margin-bottom: 15px;">
  <button id="backBtn" class="btn-secondary">
    <i class="fa-solid fa-arrow-left"></i> Back
  </button>
</div>

<!-- Control Bar: Profile Header -->
<section class="control-card profile-header">
  <div class="profile-avatar" id="profileInitials">N/A</div>
  <div class="profile-info">
    <div class="profile-edit-btn">
      <button id="editProfileBtn" class="btn-primary editbtn">
        <i class="fa-solid fa-pen"></i> Edit
      </button>
    </div>
    <h2 id="profileName">N/A</h2>
    <p id="profilePosition">N/A | N/A | N/A</p>
    <div class="profile-details" id="profileStats">
      <div>Status: <span id="profileStatus" class="status-active">N/A</span></div>
      <div>Skill Type: <span id="profileSkill">N/A</span></div>
      <div>Start Date: <span id="profileStartDate">N/A</span></div>
      <div>Rate: <span id="profileRate">N/A</span></div>
    </div>
  </div>
</section>
