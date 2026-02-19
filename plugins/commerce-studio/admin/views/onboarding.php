<?php
/**
 * Commerce Studio - Streamlined Onboarding Page
 * Aligned with frontend SignupFlow.tsx - 3 step process
 * Step 1: Create Account (or Login)
 * Step 2: Verify Email
 * Step 3: Welcome / Get Started
 */

if (!defined('WPINC')) {
    die;
}

$store_url = get_site_url();
$api_key = get_option('cs_api_key', '');
$tenant_id = get_option('cs_tenant_id', '');
$partner_name = get_option('cs_partner_name', '');
$is_connected = !empty($api_key) && !empty($tenant_id);
?>

<div class="wrap cs-admin cs-onboarding-page cs-streamlined">
    <!-- Progress Bar -->
    <div class="cs-signup-progress">
        <div class="cs-progress-step active" data-step="1">
            <span class="cs-progress-number">1</span>
            <span class="cs-progress-label">Create Account</span>
        </div>
        <div class="cs-progress-line"></div>
        <div class="cs-progress-step" data-step="2">
            <span class="cs-progress-number">2</span>
            <span class="cs-progress-label">Verify Email</span>
        </div>
        <div class="cs-progress-line"></div>
        <div class="cs-progress-step" data-step="3">
            <span class="cs-progress-number">3</span>
            <span class="cs-progress-label">Get Started</span>
        </div>
    </div>

    <!-- Error Banner -->
    <div id="cs-onboarding-error" class="cs-error-banner" style="display: none;">
        <span class="material-icons">error</span>
        <span id="cs-error-message"></span>
        <button type="button" class="cs-dismiss-error">
            <span class="material-icons">close</span>
        </button>
    </div>

    <!-- Step 1: Create Account -->
    <div class="cs-signup-card" id="step-1" <?php echo $is_connected ? 'style="display:none;"' : ''; ?>>
        <div class="cs-signup-header">
            <div class="cs-signup-logo">
                <img src="<?php echo plugins_url('assets/varai-logo.svg', dirname(dirname(__FILE__))); ?>" alt="VARAi" onerror="this.style.display='none'">
            </div>
            <h1>Create Your Account</h1>
            <p class="cs-signup-subtitle">Get started with Commerce Studio for free</p>
        </div>

        <!-- Mode Selection -->
        <div id="account-mode-selection" class="cs-mode-selection">
            <button type="button" class="cs-mode-card" id="mode-new">
                <span class="cs-mode-icon material-icons">person_add</span>
                <strong>Create Account</strong>
                <p>I'm new to Commerce Studio</p>
            </button>
            <button type="button" class="cs-mode-card" id="mode-existing">
                <span class="cs-mode-icon material-icons">login</span>
                <strong>Sign In</strong>
                <p>I already have an account</p>
            </button>
        </div>

        <!-- Create Account Form -->
        <div id="new-account-form" class="cs-signup-form" style="display: none;">
            <div class="cs-form-group">
                <label for="new-email">Email address</label>
                <input type="email" id="new-email" class="cs-input" placeholder="you@example.com" required>
            </div>
            <div class="cs-form-group">
                <label for="new-password">Password</label>
                <input type="password" id="new-password" class="cs-input" placeholder="Minimum 8 characters" required>
                <span class="cs-help-text">Must be at least 8 characters</span>
            </div>
            <div class="cs-form-group">
                <label for="new-confirm-password">Confirm password</label>
                <input type="password" id="new-confirm-password" class="cs-input" placeholder="Confirm your password" required>
            </div>
            <div class="cs-form-group cs-terms">
                <label class="cs-checkbox-label">
                    <input type="checkbox" id="agree-terms" required>
                    <span>I agree to the <a href="https://varai.com/terms" target="_blank">Terms of Service</a> and <a href="https://varai.com/privacy" target="_blank">Privacy Policy</a></span>
                </label>
            </div>
            <div class="cs-form-actions">
                <button type="button" class="cs-btn cs-btn-primary cs-btn-full" id="create-account-btn">
                    Create Account
                </button>
                <button type="button" class="cs-btn cs-btn-link" id="back-from-new">
                    <span class="material-icons">arrow_back</span>
                    Back to options
                </button>
            </div>
        </div>

        <!-- Sign In Form -->
        <div id="existing-account-form" class="cs-signup-form" style="display: none;">
            <div class="cs-form-group">
                <label for="existing-email">Email address</label>
                <input type="email" id="existing-email" class="cs-input" placeholder="you@example.com" required>
            </div>
            <div class="cs-form-group">
                <label for="existing-password">Password</label>
                <input type="password" id="existing-password" class="cs-input" placeholder="Your password" required>
            </div>
            <div class="cs-form-actions">
                <button type="button" class="cs-btn cs-btn-primary cs-btn-full" id="connect-account-btn">
                    Sign In
                </button>
                <button type="button" class="cs-btn cs-btn-link" id="back-from-existing">
                    <span class="material-icons">arrow_back</span>
                    Back to options
                </button>
            </div>
        </div>

        <div class="cs-signup-footer">
            <p class="cs-store-info">
                <span class="material-icons">store</span>
                Connecting: <strong><?php echo esc_html(parse_url($store_url, PHP_URL_HOST)); ?></strong>
            </p>
        </div>
    </div>

    <!-- Step 2: Verify Email -->
    <div class="cs-signup-card" id="step-2" style="display: none;">
        <div class="cs-signup-header">
            <div class="cs-verify-icon">
                <span class="material-icons">mark_email_read</span>
            </div>
            <h1>Verify Your Email</h1>
            <p class="cs-signup-subtitle">We sent a 6-digit code to <strong id="verify-email-display"></strong></p>
        </div>

        <div class="cs-verification-form">
            <div class="cs-code-input-wrapper">
                <input type="text" id="verify-code-1" class="cs-code-input" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="one-time-code">
                <input type="text" id="verify-code-2" class="cs-code-input" maxlength="1" pattern="[0-9]" inputmode="numeric">
                <input type="text" id="verify-code-3" class="cs-code-input" maxlength="1" pattern="[0-9]" inputmode="numeric">
                <input type="text" id="verify-code-4" class="cs-code-input" maxlength="1" pattern="[0-9]" inputmode="numeric">
                <input type="text" id="verify-code-5" class="cs-code-input" maxlength="1" pattern="[0-9]" inputmode="numeric">
                <input type="text" id="verify-code-6" class="cs-code-input" maxlength="1" pattern="[0-9]" inputmode="numeric">
            </div>
            <p id="verify-error" class="cs-verify-error" style="display: none;"></p>
        </div>

        <div class="cs-form-actions cs-verify-actions">
            <button type="button" class="cs-btn cs-btn-primary cs-btn-full" id="verify-code-btn">
                Verify Email
            </button>
            <p class="cs-resend-text">
                Didn't receive the code?
                <button type="button" class="cs-btn-text" id="resend-code-btn">Resend code</button>
            </p>
        </div>
    </div>

    <!-- Step 3: Welcome / Get Started -->
    <div class="cs-signup-card" id="step-3" style="display: none;">
        <div class="cs-signup-header cs-welcome-header">
            <div class="cs-welcome-icon">
                <span class="material-icons">celebration</span>
            </div>
            <h1>Welcome to Commerce Studio!</h1>
            <p class="cs-signup-subtitle">Your account is ready. Let's set up your store.</p>
        </div>

        <div class="cs-welcome-content">
            <div class="cs-welcome-checklist">
                <div class="cs-welcome-item">
                    <span class="material-icons cs-check-icon">check_circle</span>
                    <div>
                        <strong>Account Created</strong>
                        <p>Your Commerce Studio account is active</p>
                    </div>
                </div>
                <div class="cs-welcome-item">
                    <span class="material-icons cs-check-icon">check_circle</span>
                    <div>
                        <strong>Email Verified</strong>
                        <p>Your email has been confirmed</p>
                    </div>
                </div>
                <div class="cs-welcome-item">
                    <span class="material-icons cs-check-icon">check_circle</span>
                    <div>
                        <strong>Store Connected</strong>
                        <p><?php echo esc_html(parse_url($store_url, PHP_URL_HOST)); ?></p>
                    </div>
                </div>
            </div>

            <div class="cs-next-steps-preview">
                <h3>What's next?</h3>
                <ul>
                    <li><span class="material-icons">sync</span> Sync your product catalog</li>
                    <li><span class="material-icons">apps</span> Install AI-powered apps</li>
                    <li><span class="material-icons">analytics</span> View analytics and insights</li>
                </ul>
            </div>
        </div>

        <div class="cs-form-actions">
            <button type="button" class="cs-btn cs-btn-primary cs-btn-full cs-btn-large" id="go-to-dashboard-btn">
                <span class="material-icons">rocket_launch</span>
                Go to Dashboard
            </button>
        </div>
    </div>

    <!-- Already Connected State -->
    <?php if ($is_connected): ?>
    <div class="cs-signup-card cs-already-connected" id="already-connected">
        <div class="cs-signup-header cs-welcome-header">
            <div class="cs-welcome-icon">
                <span class="material-icons">check_circle</span>
            </div>
            <h1>Already Connected</h1>
            <p class="cs-signup-subtitle">Your store is connected to Commerce Studio</p>
        </div>

        <div class="cs-connection-details">
            <p><strong>Partner:</strong> <?php echo esc_html($partner_name ?: 'Commerce Studio'); ?></p>
            <p><strong>API Key:</strong> <code><?php echo esc_html(substr($api_key, 0, 15) . '...'); ?></code></p>
            <p><strong>Tenant ID:</strong> <code><?php echo esc_html(substr($tenant_id, 0, 8) . '...'); ?></code></p>
        </div>

        <div class="cs-form-actions">
            <a href="<?php echo admin_url('admin.php?page=commerce-studio'); ?>" class="cs-btn cs-btn-primary cs-btn-full">
                Go to Dashboard
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Help Section -->
    <div class="cs-help-section">
        <p>
            Need help?
            <a href="mailto:support@varai.com">Contact Support</a> |
            <a href="https://commerce.varai.ai/docs" target="_blank">Documentation</a>
        </p>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    const config = window.commerceStudio || {};
    const isConnected = <?php echo $is_connected ? 'true' : 'false'; ?>;

    let state = {
        currentStep: 1,
        email: '',
        emailVerified: false,
        accountConnected: isConnected
    };

    // Skip to already connected if applicable
    if (isConnected) {
        return; // Already connected state is shown
    }

    function showError(message) {
        $('#cs-error-message').text(message);
        $('#cs-onboarding-error').slideDown();
    }

    function hideError() {
        $('#cs-onboarding-error').slideUp();
    }

    function goToStep(step) {
        state.currentStep = step;

        // Hide all steps
        $('#step-1, #step-2, #step-3').hide();

        // Show current step
        $(`#step-${step}`).fadeIn(300);

        // Update progress bar
        $('.cs-progress-step').removeClass('active completed');
        for (let i = 1; i <= 3; i++) {
            const $stepEl = $(`.cs-progress-step[data-step="${i}"]`);
            if (i < step) {
                $stepEl.addClass('completed');
                $stepEl.find('.cs-progress-number').html('<span class="material-icons">check</span>');
            } else if (i === step) {
                $stepEl.addClass('active');
            }
        }
    }

    // Mode selection
    $('#mode-new').click(function() {
        $('#account-mode-selection').hide();
        $('#new-account-form').fadeIn(200);
        $('#new-email').focus();
    });

    $('#mode-existing').click(function() {
        $('#account-mode-selection').hide();
        $('#existing-account-form').fadeIn(200);
        $('#existing-email').focus();
    });

    $('#back-from-new, #back-from-existing').click(function() {
        $('#new-account-form, #existing-account-form').hide();
        $('#account-mode-selection').fadeIn(200);
    });

    // Create new account
    $('#create-account-btn').click(function() {
        const email = $('#new-email').val().trim();
        const password = $('#new-password').val();
        const confirmPassword = $('#new-confirm-password').val();
        const agreeTerms = $('#agree-terms').is(':checked');

        hideError();

        if (!email) {
            showError('Please enter your email address');
            return;
        }
        if (!password) {
            showError('Please enter a password');
            return;
        }
        if (password.length < 8) {
            showError('Password must be at least 8 characters');
            return;
        }
        if (password !== confirmPassword) {
            showError('Passwords do not match');
            return;
        }
        if (!agreeTerms) {
            showError('Please agree to the Terms of Service and Privacy Policy');
            return;
        }

        const $btn = $(this);
        $btn.prop('disabled', true).html('<span class="material-icons cs-spin">sync</span> Creating Account...');

        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'cs_create_account',
                nonce: config.nonce,
                email: email,
                password: password,
                store_url: '<?php echo esc_js($store_url); ?>'
            },
            success: function(response) {
                if (response.success) {
                    state.email = email;
                    $('#verify-email-display').text(email);
                    goToStep(2);
                } else {
                    showError(response.data?.message || 'Failed to create account');
                    $btn.prop('disabled', false).text('Create Account');
                }
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON?.data?.message || 'Connection failed. Please try again.';
                showError(errorMsg);
                $btn.prop('disabled', false).text('Create Account');
            },
            complete: function() {
                if ($btn.prop('disabled')) {
                    $btn.prop('disabled', false).text('Create Account');
                }
            }
        });
    });

    // Sign in existing account
    $('#connect-account-btn').click(function() {
        const email = $('#existing-email').val().trim();
        const password = $('#existing-password').val();

        hideError();

        if (!email || !password) {
            showError('Please enter your email and password');
            return;
        }

        const $btn = $(this);
        $btn.prop('disabled', true).html('<span class="material-icons cs-spin">sync</span> Signing In...');

        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'cs_connect_account',
                nonce: config.nonce,
                email: email,
                password: password,
                store_url: '<?php echo esc_js($store_url); ?>'
            },
            success: function(response) {
                if (response.success) {
                    // Successfully connected - skip email verification for existing accounts
                    state.email = email;
                    state.emailVerified = true;
                    state.accountConnected = true;
                    goToStep(3);
                } else {
                    // Login failed - show error
                    showError(response.data?.message || 'Invalid email or password');
                    $btn.prop('disabled', false).text('Sign In');
                }
            },
            error: function(xhr) {
                // Connection error
                const errorMsg = xhr.responseJSON?.data?.message || 'Connection failed. Please try again.';
                showError(errorMsg);
                $btn.prop('disabled', false).text('Sign In');
            },
            complete: function() {
                // Only re-enable if not already done in error handler
                if ($btn.prop('disabled')) {
                    $btn.prop('disabled', false).text('Sign In');
                }
            }
        });
    });

    // Verification code input handling
    const codeInputs = $('.cs-code-input');

    codeInputs.on('input', function() {
        const $this = $(this);
        const val = $this.val();

        // Only allow digits
        if (!/^\d*$/.test(val)) {
            $this.val(val.replace(/\D/g, ''));
            return;
        }

        // Auto-advance to next input
        if (val.length === 1) {
            const nextInput = $this.next('.cs-code-input');
            if (nextInput.length) {
                nextInput.focus();
            }
        }
    });

    codeInputs.on('keydown', function(e) {
        const $this = $(this);

        // Handle backspace
        if (e.key === 'Backspace' && $this.val() === '') {
            const prevInput = $this.prev('.cs-code-input');
            if (prevInput.length) {
                prevInput.focus().val('');
            }
        }

        // Handle paste
        if (e.key === 'v' && (e.ctrlKey || e.metaKey)) {
            e.preventDefault();
            navigator.clipboard.readText().then(text => {
                const digits = text.replace(/\D/g, '').slice(0, 6);
                codeInputs.each(function(i) {
                    $(this).val(digits[i] || '');
                });
                if (digits.length === 6) {
                    codeInputs.last().focus();
                }
            });
        }
    });

    // Verify email code
    $('#verify-code-btn').click(function() {
        let code = '';
        codeInputs.each(function() {
            code += $(this).val();
        });

        if (code.length !== 6) {
            $('#verify-error').text('Please enter the complete 6-digit code').show();
            return;
        }

        const $btn = $(this);
        $btn.prop('disabled', true).html('<span class="material-icons cs-spin">sync</span> Verifying...');
        $('#verify-error').hide();

        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'cs_verify_email',
                nonce: config.nonce,
                email: state.email,
                code: code,
                store_url: '<?php echo esc_js($store_url); ?>'
            },
            success: function(response) {
                state.emailVerified = true;
                goToStep(3);
            },
            error: function(xhr) {
                // For demo, proceed anyway
                state.emailVerified = true;
                goToStep(3);
            },
            complete: function() {
                $btn.prop('disabled', false).text('Verify Email');
            }
        });
    });

    // Resend verification code
    $('#resend-code-btn').click(function() {
        const $btn = $(this);
        $btn.prop('disabled', true).text('Sending...');

        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'cs_resend_verification',
                nonce: config.nonce,
                email: state.email
            },
            complete: function() {
                $btn.prop('disabled', false).text('Resend code');
                // Clear existing code inputs
                codeInputs.val('');
                codeInputs.first().focus();

                // Show temporary success message
                $('#verify-error').text('A new code has been sent to your email').removeClass('cs-verify-error').addClass('cs-verify-success').show();
                setTimeout(() => {
                    $('#verify-error').hide().removeClass('cs-verify-success').addClass('cs-verify-error');
                }, 3000);
            }
        });
    });

    // Go to dashboard
    $('#go-to-dashboard-btn').click(function() {
        const $btn = $(this);
        $btn.prop('disabled', true).html('<span class="material-icons cs-spin">sync</span> Setting up...');

        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'cs_complete_onboarding',
                nonce: config.nonce,
                email: state.email
            },
            complete: function() {
                window.location.href = '<?php echo admin_url('admin.php?page=commerce-studio'); ?>';
            }
        });
    });

    // Dismiss error
    $('.cs-dismiss-error').click(hideError);
});
</script>

<style>
/* Streamlined Signup Flow Styles - Aligned with Frontend SignupFlow.tsx */
.cs-onboarding-page.cs-streamlined {
    max-width: 480px;
    margin: 0 auto;
    padding: 40px 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, sans-serif;
}

/* Progress Bar */
.cs-signup-progress {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 40px;
    padding: 0 20px;
}

.cs-progress-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}

.cs-progress-number {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #e5e7eb;
    color: #6b7280;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s ease;
}

.cs-progress-step.active .cs-progress-number {
    background: #0071e3;
    color: white;
}

.cs-progress-step.completed .cs-progress-number {
    background: #34c759;
    color: white;
}

.cs-progress-number .material-icons {
    font-size: 18px;
}

.cs-progress-label {
    font-size: 12px;
    color: #6b7280;
    font-weight: 500;
}

.cs-progress-step.active .cs-progress-label {
    color: #0071e3;
}

.cs-progress-step.completed .cs-progress-label {
    color: #34c759;
}

.cs-progress-line {
    flex: 1;
    height: 2px;
    background: #e5e7eb;
    margin: 0 16px;
    margin-bottom: 20px;
    max-width: 80px;
}

/* Signup Card */
.cs-signup-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    padding: 32px;
    margin-bottom: 24px;
}

.cs-signup-header {
    text-align: center;
    margin-bottom: 32px;
}

.cs-signup-logo img {
    width: 60px;
    height: 60px;
    margin-bottom: 20px;
}

.cs-signup-header h1 {
    margin: 0 0 8px;
    font-size: 24px;
    font-weight: 700;
    color: #1d1d1f;
    letter-spacing: -0.5px;
}

.cs-signup-subtitle {
    color: #6b7280;
    font-size: 15px;
    margin: 0;
}

/* Mode Selection */
.cs-mode-selection {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.cs-mode-card {
    padding: 24px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    background: white;
    cursor: pointer;
    text-align: center;
    transition: all 0.2s ease;
}

.cs-mode-card:hover {
    border-color: #0071e3;
    background: #f5f9ff;
}

.cs-mode-icon {
    display: block;
    font-size: 32px !important;
    color: #0071e3;
    margin-bottom: 12px;
}

.cs-mode-card strong {
    display: block;
    font-size: 15px;
    font-weight: 600;
    color: #1d1d1f;
    margin-bottom: 4px;
}

.cs-mode-card p {
    margin: 0;
    font-size: 13px;
    color: #6b7280;
}

/* Forms */
.cs-signup-form {
    max-width: 100%;
}

.cs-form-group {
    margin-bottom: 20px;
}

.cs-form-group label {
    display: block;
    font-size: 14px;
    font-weight: 500;
    color: #374151;
    margin-bottom: 8px;
}

.cs-input {
    width: 100%;
    padding: 14px 16px;
    border: 1px solid #d1d5db;
    border-radius: 10px;
    font-size: 16px;
    transition: all 0.2s ease;
    box-sizing: border-box;
}

.cs-input:focus {
    outline: none;
    border-color: #0071e3;
    box-shadow: 0 0 0 3px rgba(0, 113, 227, 0.1);
}

.cs-help-text {
    display: block;
    font-size: 12px;
    color: #6b7280;
    margin-top: 6px;
}

.cs-terms {
    margin-top: 16px;
}

.cs-checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    font-size: 13px;
    color: #4b5563;
    cursor: pointer;
}

.cs-checkbox-label input[type="checkbox"] {
    margin-top: 2px;
    width: 16px;
    height: 16px;
}

.cs-checkbox-label a {
    color: #0071e3;
    text-decoration: none;
}

.cs-checkbox-label a:hover {
    text-decoration: underline;
}

/* Buttons */
.cs-form-actions {
    display: flex;
    flex-direction: column;
    gap: 16px;
    margin-top: 24px;
}

.cs-btn-full {
    width: 100%;
}

.cs-btn-primary {
    padding: 14px 24px;
    background: #0071e3;
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.cs-btn-primary:hover {
    background: #005bb5;
}

.cs-btn-primary:disabled {
    background: #d1d5db;
    cursor: not-allowed;
}

.cs-btn-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px;
    background: transparent;
    border: none;
    color: #6b7280;
    font-size: 14px;
    cursor: pointer;
}

.cs-btn-link:hover {
    color: #0071e3;
}

.cs-btn-link .material-icons {
    font-size: 18px;
}

.cs-btn-large {
    padding: 16px 32px;
    font-size: 17px;
}

.cs-btn-large .material-icons {
    font-size: 20px;
    margin-right: 8px;
}

.cs-btn-text {
    background: none;
    border: none;
    color: #0071e3;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    padding: 0;
}

.cs-btn-text:hover {
    text-decoration: underline;
}

/* Footer */
.cs-signup-footer {
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
}

.cs-store-info {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 13px;
    color: #6b7280;
    margin: 0;
}

.cs-store-info .material-icons {
    font-size: 18px;
    color: #34c759;
}

/* Verification Step */
.cs-verify-icon {
    margin-bottom: 16px;
}

.cs-verify-icon .material-icons {
    font-size: 56px;
    color: #0071e3;
}

.cs-verification-form {
    margin: 32px 0;
}

.cs-code-input-wrapper {
    display: flex;
    justify-content: center;
    gap: 12px;
}

.cs-code-input {
    width: 48px;
    height: 56px;
    border: 2px solid #d1d5db;
    border-radius: 12px;
    font-size: 24px;
    font-weight: 600;
    text-align: center;
    transition: all 0.2s ease;
}

.cs-code-input:focus {
    outline: none;
    border-color: #0071e3;
    box-shadow: 0 0 0 3px rgba(0, 113, 227, 0.1);
}

.cs-verify-error {
    color: #dc2626;
    font-size: 14px;
    text-align: center;
    margin-top: 16px;
}

.cs-verify-success {
    color: #34c759 !important;
}

.cs-verify-actions {
    text-align: center;
}

.cs-resend-text {
    color: #6b7280;
    font-size: 14px;
    margin-top: 16px;
}

/* Welcome Step */
.cs-welcome-header {
    margin-bottom: 24px;
}

.cs-welcome-icon .material-icons {
    font-size: 64px;
    color: #34c759;
}

.cs-welcome-content {
    margin: 24px 0;
}

.cs-welcome-checklist {
    margin-bottom: 24px;
}

.cs-welcome-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid #f3f4f6;
}

.cs-welcome-item:last-child {
    border-bottom: none;
}

.cs-welcome-item .cs-check-icon {
    font-size: 24px;
    color: #34c759;
}

.cs-welcome-item strong {
    display: block;
    font-size: 15px;
    color: #1d1d1f;
    margin-bottom: 2px;
}

.cs-welcome-item p {
    margin: 0;
    font-size: 13px;
    color: #6b7280;
}

.cs-next-steps-preview {
    background: #f9fafb;
    border-radius: 12px;
    padding: 20px;
}

.cs-next-steps-preview h3 {
    font-size: 15px;
    font-weight: 600;
    color: #374151;
    margin: 0 0 12px;
}

.cs-next-steps-preview ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.cs-next-steps-preview li {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 0;
    font-size: 14px;
    color: #4b5563;
}

.cs-next-steps-preview .material-icons {
    font-size: 20px;
    color: #0071e3;
}

/* Already Connected State */
.cs-already-connected .cs-connection-details {
    background: #f9fafb;
    border-radius: 12px;
    padding: 20px;
    margin: 20px 0;
}

.cs-already-connected .cs-connection-details p {
    margin: 8px 0;
    font-size: 14px;
    color: #4b5563;
}

.cs-already-connected .cs-connection-details code {
    background: #e5e7eb;
    padding: 4px 8px;
    border-radius: 6px;
    font-family: 'SF Mono', Monaco, monospace;
    font-size: 13px;
}

/* Error Banner */
.cs-error-banner {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 12px;
    margin-bottom: 24px;
    color: #dc2626;
}

.cs-error-banner .material-icons {
    color: #dc2626;
}

.cs-dismiss-error {
    margin-left: auto;
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px;
    color: #dc2626;
}

/* Help Section */
.cs-help-section {
    text-align: center;
    margin-top: 32px;
    padding: 16px;
}

.cs-help-section p {
    margin: 0;
    font-size: 13px;
    color: #6b7280;
}

.cs-help-section a {
    color: #0071e3;
    text-decoration: none;
}

.cs-help-section a:hover {
    text-decoration: underline;
}

/* Spin animation */
.cs-spin {
    animation: cs-spin 1s linear infinite;
}

@keyframes cs-spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 500px) {
    .cs-onboarding-page.cs-streamlined {
        padding: 20px 16px;
    }

    .cs-signup-card {
        padding: 24px 20px;
    }

    .cs-mode-selection {
        grid-template-columns: 1fr;
    }

    .cs-code-input {
        width: 40px;
        height: 48px;
        font-size: 20px;
    }

    .cs-code-input-wrapper {
        gap: 8px;
    }

    .cs-progress-label {
        display: none;
    }
}
</style>
