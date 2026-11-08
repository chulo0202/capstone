<x-guest-layout>
    <div class="admin-register-wrapper">

        <!-- Header -->
        <div class="admin-register-header">
            <div class="admin-register-icon">
                <svg xmlns="http://www.w3.org/2000/svg"
                     viewBox="0 0 24 24"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="1.8"
                     width="28"
                     height="28">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a8.25 8.25 0 0 1 15 0" />
                </svg>
            </div>

            <div>
                <h1>Admin Registration</h1>
                <p>Create an administrator account for FAMS</p>
            </div>
        </div>

        <!-- Information Notice -->
        <div class="admin-register-notice">
            <div class="notice-icon">
                <svg xmlns="http://www.w3.org/2000/svg"
                     viewBox="0 0 24 24"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="2"
                     width="19"
                     height="19">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          d="M12 9v3.75m0 3h.007v.008H12v-.008ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>

            <div>
                <strong>Administrator approval required</strong>
                <p>
                    Your registration will be reviewed by an existing FAMS administrator
                    before you can access the admin system.
                </p>
            </div>
        </div>

        <!-- Registration Form -->
        <form method="POST"
              action="{{ route('admin.register.store') }}"
              class="admin-register-form">

            @csrf

            <!-- Name -->
            <div class="form-group">
                <x-input-label
                    for="name"
                    :value="__('Full Name')"
                    class="admin-form-label"
                />

                <div class="input-wrapper">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         viewBox="0 0 24 24"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="1.8"
                         width="19"
                         height="19">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a8.25 8.25 0 0 1 15 0" />
                    </svg>

                    <x-text-input
                        id="name"
                        class="admin-form-input"
                        type="text"
                        name="name"
                        :value="old('name')"
                        required
                        autofocus
                        autocomplete="name"
                        placeholder="Enter your full name"
                    />
                </div>

                <x-input-error
                    :messages="$errors->get('name')"
                    class="admin-form-error"
                />
            </div>

            <!-- Email -->
            <div class="form-group">
                <x-input-label
                    for="email"
                    :value="__('Gmail Address')"
                    class="admin-form-label"
                />

                <div class="input-wrapper">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         viewBox="0 0 24 24"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="1.8"
                         width="19"
                         height="19">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25H4.5a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5H4.5a2.25 2.25 0 0 0-2.25 2.25m19.5 0-8.69 5.79a1.5 1.5 0 0 1-1.62 0L2.25 6.75" />
                    </svg>

                    <x-text-input
                        id="email"
                        class="admin-form-input"
                        type="email"
                        name="email"
                        :value="old('email')"
                        required
                        autocomplete="username"
                        placeholder="example@gmail.com"
                    />
                </div>

                <p class="form-hint">
                    Use a valid Gmail address for your administrator account.
                </p>

                <x-input-error
                    :messages="$errors->get('email')"
                    class="admin-form-error"
                />
            </div>

            <!-- Password -->
            <div class="form-group">
                <x-input-label
                    for="password"
                    :value="__('Password')"
                    class="admin-form-label"
                />

                <div class="input-wrapper">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         viewBox="0 0 24 24"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="1.8"
                         width="19"
                         height="19">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M16.5 10.5V7.875a4.5 4.5 0 0 0-9 0V10.5m-.75 0h10.5A2.25 2.25 0 0 1 19.5 12.75v6A2.25 2.25 0 0 1 17.25 21H6.75A2.25 2.25 0 0 1 4.5 18.75v-6A2.25 2.25 0 0 1 6.75 10.5Z" />
                    </svg>

                    <x-text-input
                        id="password"
                        class="admin-form-input"
                        type="password"
                        name="password"
                        required
                        autocomplete="new-password"
                        placeholder="Create a strong password"
                    />
                </div>

                <p class="form-hint">
                    Use at least 8 characters and choose a strong password.
                </p>

                <x-input-error
                    :messages="$errors->get('password')"
                    class="admin-form-error"
                />
            </div>

            <!-- Confirm Password -->
            <div class="form-group">
                <x-input-label
                    for="password_confirmation"
                    :value="__('Confirm Password')"
                    class="admin-form-label"
                />

                <div class="input-wrapper">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         viewBox="0 0 24 24"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="1.8"
                         width="19"
                         height="19">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M16.5 10.5V7.875a4.5 4.5 0 0 0-9 0V10.5m-.75 0h10.5A2.25 2.25 0 0 1 19.5 12.75v6A2.25 2.25 0 0 1 17.25 21H6.75A2.25 2.25 0 0 1 4.5 18.75v-6A2.25 2.25 0 0 1 6.75 10.5Z" />
                    </svg>

                    <x-text-input
                        id="password_confirmation"
                        class="admin-form-input"
                        type="password"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                        placeholder="Re-enter your password"
                    />
                </div>

                <x-input-error
                    :messages="$errors->get('password_confirmation')"
                    class="admin-form-error"
                />
            </div>

            <!-- Actions -->
            <div class="admin-register-actions">

                <a
                    href="{{ route('login') }}"
                    class="admin-back-link"
                >
                    <svg xmlns="http://www.w3.org/2000/svg"
                         viewBox="0 0 24 24"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="2"
                         width="17"
                         height="17">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>

                    Back to Login
                </a>

                <button type="submit" class="admin-submit-button">
                    Submit Registration

                    <svg xmlns="http://www.w3.org/2000/svg"
                         viewBox="0 0 24 24"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="2"
                         width="17"
                         height="17">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                </button>

            </div>

        </form>

        <!-- Footer -->
        <div class="admin-register-footer">
            <svg xmlns="http://www.w3.org/2000/svg"
                 viewBox="0 0 24 24"
                 fill="none"
                 stroke="currentColor"
                 stroke-width="1.7"
                 width="16"
                 height="16">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0-13.5v4.5m0 3h.007v.008H12V15Z" />
            </svg>

            <span>
                Admin accounts are subject to approval by an existing administrator.
            </span>
        </div>

    </div>

    <style>
        .admin-register-wrapper {
            width: 100%;
        }

        .admin-register-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 24px;
        }

        .admin-register-icon {
            width: 52px;
            height: 52px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            background: #eaf5ef;
            color: #176b48;
        }

        .admin-register-header h1 {
            margin: 0;
            color: #173b2d;
            font-size: 1.35rem;
            font-weight: 750;
            line-height: 1.25;
        }

        .admin-register-header p {
            margin: 4px 0 0;
            color: #718096;
            font-size: 0.82rem;
        }

        .admin-register-notice {
            display: flex;
            gap: 11px;
            align-items: flex-start;
            padding: 13px 14px;
            margin-bottom: 23px;
            border: 1px solid #d7ebdf;
            border-radius: 11px;
            background: #f4faf6;
            color: #24523d;
        }

        .notice-icon {
            flex-shrink: 0;
            color: #176b48;
            margin-top: 1px;
        }

        .admin-register-notice strong {
            display: block;
            margin-bottom: 2px;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .admin-register-notice p {
            margin: 0;
            color: #61766b;
            font-size: 0.76rem;
            line-height: 1.45;
        }

        .admin-register-form {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .form-group {
            width: 100%;
        }

        .admin-form-label {
            display: block;
            margin-bottom: 7px;
            color: #344054 !important;
            font-size: 0.78rem !important;
            font-weight: 650 !important;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper > svg {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: #98a2b3;
            pointer-events: none;
            z-index: 2;
        }

        .admin-form-input {
            width: 100%;
            min-height: 44px;
            padding: 10px 13px 10px 42px !important;
            border: 1px solid #d0d5dd !important;
            border-radius: 9px !important;
            background: #fff !important;
            color: #344054 !important;
            font-size: 0.84rem !important;
            box-shadow: none !important;
            transition:
                border-color 0.18s ease,
                box-shadow 0.18s ease;
        }

        .admin-form-input::placeholder {
            color: #a5adb8;
        }

        .admin-form-input:focus {
            border-color: #176b48 !important;
            box-shadow: 0 0 0 3px rgba(23, 107, 72, 0.10) !important;
            outline: none !important;
        }

        .form-hint {
            margin: 5px 0 0;
            color: #8a94a3;
            font-size: 0.7rem;
            line-height: 1.4;
        }

        .admin-form-error {
            margin-top: 5px;
            font-size: 0.72rem;
        }

        .admin-register-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 3px;
        }

        .admin-back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #667085;
            font-size: 0.78rem;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.18s ease;
        }

        .admin-back-link:hover {
            color: #176b48;
        }

        .admin-submit-button {
            min-height: 43px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 17px;
            border: 0;
            border-radius: 9px;
            background: #176b48;
            color: #fff;
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.01em;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(23, 107, 72, 0.16);
            transition:
                background 0.18s ease,
                transform 0.18s ease,
                box-shadow 0.18s ease;
        }

        .admin-submit-button:hover {
            background: #12583b;
            transform: translateY(-1px);
            box-shadow: 0 6px 14px rgba(23, 107, 72, 0.20);
        }

        .admin-submit-button:active {
            transform: translateY(0);
        }

        .admin-register-footer {
            display: flex;
            align-items: flex-start;
            justify-content: center;
            gap: 7px;
            margin-top: 21px;
            padding-top: 16px;
            border-top: 1px solid #eef0f2;
            color: #98a2b3;
            font-size: 0.68rem;
            line-height: 1.45;
            text-align: center;
        }

        .admin-register-footer svg {
            flex-shrink: 0;
            margin-top: 1px;
            color: #98a2b3;
        }

        @media (max-width: 640px) {
            .admin-register-header {
                margin-bottom: 20px;
            }

            .admin-register-icon {
                width: 46px;
                height: 46px;
            }

            .admin-register-header h1 {
                font-size: 1.15rem;
            }

            .admin-register-actions {
                align-items: stretch;
                flex-direction: column-reverse;
            }

            .admin-back-link {
                justify-content: center;
                padding: 8px;
            }

            .admin-submit-button {
                width: 100%;
            }
        }
    </style>
</x-guest-layout>