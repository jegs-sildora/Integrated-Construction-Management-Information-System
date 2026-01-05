function showToast(message, type = 'success') {
    const toast = $("<div>").addClass(`toast-notification ${type}`);
    
    // Choose icon based on type
    let iconClass = 'fa-circle-check';
    if(type === 'error') iconClass = 'fa-circle-xmark';
    if(type === 'warning') iconClass = 'fa-circle-exclamation';

    toast.html(`<i class="fa-solid ${iconClass}"></i><span class="toast-message">${message}</span>`);
    
    $("body").append(toast);
    
    // Animation
    setTimeout(() => { toast.addClass("show"); }, 10);
    setTimeout(() => { 
        toast.removeClass("show"); 
        setTimeout(() => { toast.remove(); }, 400); 
    }, 3000);
}
