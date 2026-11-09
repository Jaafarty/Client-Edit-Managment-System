<?php

$frontend = '../frontend/resources/js';

return [
    'login' => "$frontend/pages/auth/login.tsx",
    'register' => "$frontend/pages/auth/register.tsx",
    'welcome' => "$frontend/pages/welcome.tsx",
    'profile' => "$frontend/pages/settings/profile.tsx",
    'security' => "$frontend/pages/settings/security.tsx",
    'verify_email' => "$frontend/pages/auth/verify-email.tsx",
    'two_factor_challenge' => "$frontend/pages/auth/two-factor-challenge.tsx",
    'confirm_password' => "$frontend/pages/auth/confirm-password.tsx",
    'auth_types' => "$frontend/types/auth.ts",

    'two_factor_files' => [
        "$frontend/components/manage-two-factor.tsx",
        "$frontend/components/two-factor-setup-modal.tsx",
        "$frontend/components/two-factor-recovery-codes.tsx",
        "$frontend/components/ui/input-otp.tsx",
        "$frontend/hooks/use-two-factor-auth.ts",
    ],

    'two_factor_otp_package' => 'input-otp',

    'passkey_files' => [
        "$frontend/components/passkey-item.tsx",
        "$frontend/components/passkey-register.tsx",
        "$frontend/components/passkey-verify.tsx",
        "$frontend/components/manage-passkeys.tsx",
    ],
];
