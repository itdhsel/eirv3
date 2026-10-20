<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In | eIRv3</title>
    <!-- Load styles -->
    <link rel="stylesheet" href="{{ asset('css/eirv3.css') }}?v=4">
    <link rel="stylesheet" href="{{ asset('css/eirv3-login.css') }}?v=2">
</head>
<body>
    <div class="login-layout">
        <!-- Angled Background Element -->
        <div class="login-bg-shape"></div>
        
        <div class="login-content">
            
            <!-- Left Side: Form -->
            <div class="login-left">
                <div class="login-card">
                    <div class="login-brand">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px;">
                            <!-- Restored the green/teal theme colors here -->
                            <span style="font-size: 32px; font-weight: 800; color: var(--brand-dark); line-height: 1; letter-spacing: -1px;">eIR<span style="color: var(--brand-primary);">v3</span></span>
                            <div style="height: 30px; width: 1px; background: #cbd5e1;"></div>
                            <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; line-height: 1.2;">
                                Hospital<br>Selayang
                            </div>
                        </div>
                        <h1>Welcome Back</h1>
                        <p>Log in with your credentials to continue.</p>
                    </div>

                    <form method="POST" action="{{ route('login') }}" class="login-form">
                        @csrf
                        <div class="form-group">
                            <label for="nokp">No. Kad Pengenalan (IC)</label>
                            <input type="text" id="nokp" name="nokp" class="login-input" value="{{ old('nokp') }}" placeholder="e.g. 901122034455" required autofocus>
                            @error('nokp')
                                <span class="error-message">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" class="login-input" placeholder="••••••••" required>
                            @error('password')
                                <span class="error-message">{{ $message }}</span>
                            @enderror
                        </div>

                        <button type="submit" class="login-btn">Secure Login</button>
                    </form>
                </div>

                <!-- Footer Text placed at bottom left -->
                <div class="login-footer-absolute">
                    <strong>eIRv3</strong> &middot; Hospital Selayang &middot; <strong>Team IR Wizard</strong><br>
                    <span style="opacity: 0.8;">Lead: Dr. Ariffah Binti Mokhtar (Unit PRSK) &nbsp;|&nbsp; Analyst: Mr. Nur Nu’man Bin Sidik (ITD) &nbsp;|&nbsp; Developer: Mr. Muhammad Haziq Zikri Bin Zainodin (ITD)</span>
                </div>
            </div>

            <!-- Right Side: Brief IR Wording -->
            <div class="login-right">
                <div class="hero-text">
                    <h2>Incident Reporting<br><span>(eIRv3)</span></h2>
                    <p>A centralized platform for Hospital Selayang staff to securely report, track, and manage incidents. Designed to support continuous quality improvement and promote patient safety.</p>
                </div>
            </div>

        </div>
    </div>
</body>
</html>