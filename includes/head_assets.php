<link rel="apple-touch-icon" sizes="180x180" href="<?php echo BASE_URL; ?>assets/images/favicon/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="<?php echo BASE_URL; ?>assets/images/favicon/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="<?php echo BASE_URL; ?>assets/images/favicon/favicon-16x16.png">
<link rel="manifest" href="<?php echo BASE_URL; ?>assets/images/favicon/site.webmanifest" crossorigin="use-credentials">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Montserrat:ital,wght@0,100..900;1,100..900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">

<script src="https://unpkg.com/lucide@latest"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                fontFamily: {
                    sans: ['Inter', 'Montserrat', 'sans-serif'],
                },
                colors: {
                    primary: {
                        DEFAULT: '#e9922c', 
                        hover: '#d17f1f',
                        light: '#fff7ed',   
                    },
                    navy: {
                        dark: '#1e293b',
                        light: '#334155'
                    }
                },
                keyframes: {
                    slideIn: {
                        '0%': { transform: 'translateX(100%)', opacity: '0' },
                        '100%': { transform: 'translateX(0)', opacity: '1' },
                    },
                    slideOut: {
                        '0%': { transform: 'translateX(0)', opacity: '1' },
                        '100%': { transform: 'translateX(120%)', opacity: '0' },
                    },
                    fadeIn: {
                        '0%': { opacity: '0', transform: 'translateY(-10px)' },
                        '100%': { opacity: '1', transform: 'translateY(0)' },
                    }
                },
                animation: {
                    'slide-in': 'slideIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards',
                    'slide-out': 'slideOut 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards',
                    'fade-in': 'fadeIn 0.2s ease-out forwards',
                }
            }
        }
    }
</script>

<style type="text/tailwindcss">
    @layer utilities {
        .custom-scrollbar::-webkit-scrollbar { width: 5px; }
        .custom-scrollbar::-webkit-scrollbar-track { @apply bg-gray-100; }
        .custom-scrollbar::-webkit-scrollbar-thumb { @apply bg-gray-300 rounded; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { @apply bg-gray-400; }
    }
</style>