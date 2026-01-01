<?php
// 1. Load Configuration FIRST
require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ICMIS | Login & Sign Up</title>
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo BASE_URL; ?>assets/images/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo BASE_URL; ?>assets/images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo BASE_URL; ?>assets/images/favicon/favicon-16x16.png">
    <link rel="manifest" href="<?php echo BASE_URL; ?>assets/images/favicon/site.webmanifest">
    
    <style>
        /* Page-Specific Background Pattern */
        .login-bg {
            background-image: linear-gradient(135deg, #f1f5f9 50%, transparent 50%), 
                              url('https://images.unsplash.com/photo-1504307651254-35680f356dfd?q=80&w=2070&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            filter: grayscale(100%) opacity(0.6);
        }
        /* Decorative Dots */
        .dot-pattern {
            background-image: radial-gradient(#e9922c 1px, transparent 1px);
            background-size: 20px 20px;
            opacity: 0.1;
        }

        body {
          font-family: 'Montserrat', sans-serif;
        }
    </style>
</head>
<body class="bg-slate-100 h-screen flex items-center justify-center overflow-hidden text-slate-800 relative">

    <?php include __DIR__ . '/includes/toast.php'; ?>

    <div class="login-bg fixed inset-0 -z-10 w-full h-full"></div>

    <?php include __DIR__ . '/includes/head_assets.php'; ?>

    <div class="w-[900px] max-w-[95%] h-[600px] md:h-[550px] bg-white flex rounded-xl shadow-2xl overflow-hidden relative border border-slate-200 animate-fade-in">
        
        <div class="hidden md:flex flex-1 flex-col justify-center p-12 bg-[#1e293b] text-white relative">
            <div class="dot-pattern absolute bottom-0 left-0 w-32 h-32"></div>
            
            <h1 class="text-5xl font-black uppercase leading-tight mb-5 drop-shadow-lg">
                Navigating<br><span class="text-[#e9922c]">The Future</span>
            </h1>
            <p class="text-slate-300 text-sm leading-relaxed mb-8 font-medium">
                Unveiling the ins and outs of modern construction. Built for strength, designed for excellence.
            </p>
            
            <div class="text-[10px] text-[#e9922c] border-t border-slate-600 pt-4 w-fit tracking-[2px] font-bold">
                INTEGRATED CONSTRUCTION MANAGEMENT SYSTEM
            </div>
        </div>

        <div class="flex-1 flex flex-col justify-center p-8 md:p-12 bg-white relative">
            
            <div class="flex gap-6 mb-8 border-b border-slate-100 pb-1">
                <button onclick="toggleForm('login')" id="tab-login" class="pb-2 text-lg font-bold text-[#1e293b] border-b-4 border-[#e9922c] transition-all duration-300">
                    LOGIN
                </button>
                <button onclick="toggleForm('signup')" id="tab-signup" class="pb-2 text-lg font-bold text-slate-400 hover:text-[#1e293b] transition-all duration-300 border-b-4 border-transparent hover:border-slate-200">
                    SIGN UP
                </button>
            </div>

            <form id="login-form" action="modules/auth/api/login_process.php" method="POST" class="space-y-5">
                <div>
                    <label class="block text-xs font-bold text-[#1e293b] uppercase tracking-wide mb-2">Work Email</label>
                    <div class="relative group">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 group-focus-within:text-[#e9922c] transition-colors">
                            <i class="fa-solid fa-envelope"></i>
                        </span>
                        <input type="email" name="email" 
                               value="<?php if(isset($_GET['email'])) echo htmlspecialchars($_GET['email']); ?>" 
                               class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-lg text-[#1e293b] text-sm focus:outline-none focus:border-[#e9922c] focus:ring-1 focus:ring-[#e9922c] transition-all placeholder-slate-400"
                               placeholder="email@icmis.com" required>
                    </div>
                </div>
                
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <label class="block text-xs font-bold text-[#1e293b] uppercase tracking-wide">Password</label>
                        <a href="#" class="text-xs font-semibold text-slate-400 hover:text-[#e9922c] transition-colors">Forgot Password?</a>
                    </div>
                    <div class="relative group">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 group-focus-within:text-[#e9922c] transition-colors">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <input type="password" name="password" id="login-pass" 
                               class="w-full pl-10 pr-10 py-3 bg-slate-50 border border-slate-200 rounded-lg text-[#1e293b] text-sm focus:outline-none focus:border-[#e9922c] focus:ring-1 focus:ring-[#e9922c] transition-all placeholder-slate-400"
                               placeholder="••••••••" required>
                        <i class="fa-solid fa-eye absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 cursor-pointer hover:text-[#1e293b] transition-colors" 
                           onclick="togglePassword('login-pass', this)"></i>
                    </div>
                </div>

                <button type="submit" name="login" class="w-full py-3.5 bg-[#e9922c] hover:bg-[#d97706] text-white font-black uppercase tracking-widest rounded-lg shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all duration-300 text-sm mt-4">
                    Enter Dashboard
                </button>
            </form>

            <form id="signup-form" class="hidden space-y-5 animate-fade-in" action="modules/auth/api/signup_process.php" method="POST">
                <div>
                    <label class="block text-xs font-bold text-[#1e293b] uppercase tracking-wide mb-2">Full Name</label>
                    <div class="relative group">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 group-focus-within:text-[#e9922c] transition-colors">
                            <i class="fa-solid fa-user"></i>
                        </span>
                        <input type="text" name="full_name" 
                               value="<?php if(isset($_GET['signup_name'])) echo htmlspecialchars($_GET['signup_name']); ?>"
                               class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-lg text-[#1e293b] text-sm focus:outline-none focus:border-[#e9922c] focus:ring-1 focus:ring-[#e9922c] transition-all placeholder-slate-400"
                               placeholder="John Doe" required>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-[#1e293b] uppercase tracking-wide mb-2">Work Email</label>
                    <div class="relative group">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 group-focus-within:text-[#e9922c] transition-colors">
                            <i class="fa-solid fa-envelope"></i>
                        </span>
                        <input type="email" name="email" 
                               value="<?php if(isset($_GET['signup_email'])) echo htmlspecialchars($_GET['signup_email']); ?>"
                               class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-lg text-[#1e293b] text-sm focus:outline-none focus:border-[#e9922c] focus:ring-1 focus:ring-[#e9922c] transition-all placeholder-slate-400"
                               placeholder="email@icmis.com" required>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-[#1e293b] uppercase tracking-wide mb-2">Create Password</label>
                    <div class="relative group">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 group-focus-within:text-[#e9922c] transition-colors">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <input type="password" name="password" id="signup-pass" 
                               class="w-full pl-10 pr-10 py-3 bg-slate-50 border border-slate-200 rounded-lg text-[#1e293b] text-sm focus:outline-none focus:border-[#e9922c] focus:ring-1 focus:ring-[#e9922c] transition-all placeholder-slate-400"
                               placeholder="••••••••" required minlength="6">
                        <i class="fa-solid fa-eye absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 cursor-pointer hover:text-[#1e293b] transition-colors" 
                           onclick="togglePassword('signup-pass', this)"></i>
                    </div>
                </div>

                <button type="submit" name="signup" class="w-full py-3.5 bg-[#e9922c] hover:bg-[#d97706] text-white font-black uppercase tracking-widest rounded-lg shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all duration-300 text-sm mt-4">
                    Register Account
                </button>
            </form>

        </div>
    </div>

    <script>
        // 1. Tab Switching Logic
        function toggleForm(type) {
            const loginForm = document.getElementById('login-form');
            const signupForm = document.getElementById('signup-form');
            const tabLogin = document.getElementById('tab-login');
            const tabSignup = document.getElementById('tab-signup');

            const activeClasses = ['text-[#1e293b]', 'border-[#e9922c]'];
            const inactiveClasses = ['text-slate-400', 'border-transparent', 'hover:text-[#1e293b]', 'hover:border-slate-200'];

            if (type === 'login') {
                loginForm.classList.remove('hidden');
                signupForm.classList.add('hidden');
                tabLogin.classList.add(...activeClasses);
                tabLogin.classList.remove(...inactiveClasses);
                tabSignup.classList.remove(...activeClasses);
                tabSignup.classList.add(...inactiveClasses);
            } else {
                loginForm.classList.add('hidden');
                signupForm.classList.remove('hidden');
                tabLogin.classList.remove(...activeClasses);
                tabLogin.classList.add(...inactiveClasses);
                tabSignup.classList.add(...activeClasses);
                tabSignup.classList.remove(...inactiveClasses);
            }
        }

        // 2. Show/Hide Password Logic
        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                input.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }

        // 3. Auto-switch to Signup if needed
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('signup_name')) {
            toggleForm('signup');
        }
    </script>
</body>
</html>