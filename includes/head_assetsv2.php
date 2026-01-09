<link rel="apple-touch-icon" sizes="180x180" href="<?php echo BASE_URL; ?>assets/images/favicon/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="<?php echo BASE_URL; ?>assets/images/favicon/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="<?php echo BASE_URL; ?>assets/images/favicon/favicon-16x16.png">
<link rel="manifest" href="<?php echo BASE_URL; ?>assets/images/favicon/site.webmanifest">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Montserrat:ital,wght@0,100..900;1,100..900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">

<script src="https://unpkg.com/lucide@latest"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

<style type="text/css">
    @theme {
        /* Custom Fonts */
        --font-sans: "Inter", "Montserrat", "Poppins", ui-sans-serif, system-ui;

        /* Custom Colors */
        --color-primary: #e9922c;
        --color-primary-hover: #d17f1f;
        --color-primary-light: #fff7ed;
        
        --color-navy-dark: #1e293b;
        --color-navy-light: #334155;

        /* Professional Animations */
        --animate-slide-in: slideIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        --animate-slide-out: slideOut 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        --animate-fade-in: fadeIn 0.2s ease-out forwards;
    }

    @keyframes slideIn {
        0% { transform: translateX(100%); opacity: 0; }
        100% { transform: translateX(0); opacity: 1; }
    }

    @keyframes slideOut {
        0% { transform: translateX(0); opacity: 1; }
        100% { transform: translateX(120%); opacity: 0; }
    }

    @keyframes fadeIn {
        0% { opacity: 0; transform: translateY(-10px); }
        100% { opacity: 1; transform: translateY(0); }
    }

    /* Global Scrollbar Utilities */
    .custom-scrollbar::-webkit-scrollbar { width: 5px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f3f4f6; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 5px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #9ca3af; }

    /* Fix for v4 Gradient Syntax fallback if needed */
    .bg-navy-dark { background-color: var(--color-navy-dark); }
    .bg-navy-light { background-color: var(--color-navy-light); }
    * {
        font-family: 'Inter', sans-serif;
    }
</style>