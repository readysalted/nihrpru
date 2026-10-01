<?php

/**
 * Use the public site as the destination for the login-page logo.
 */
function pru_login_header_url(): string
{
    return home_url('/');
}
add_filter('login_headerurl', 'pru_login_header_url');

/**
 * Keep the logo's accessible text aligned with the current site identity.
 */
function pru_login_header_text(): string
{
    return 'NIHR Policy Research Unit in Behavioural and Social Sciences';
}
add_filter('login_headertext', 'pru_login_header_text');

/**
 * Apply the current PRU branding to the WordPress login screen.
 */
function pru_login_styles(): void
{
    $assetUrl = get_stylesheet_directory_uri() . '/assets/images/pru/';
    ?>
    <style>
        body.login {
            align-items: center;
            background-color: #1c285e;
            background-image:
                linear-gradient(rgba(28, 40, 94, 0.68), rgba(28, 40, 94, 0.82)),
                url('<?php echo esc_url($assetUrl . 'hero.jpg'); ?>');
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
            display: flex;
            flex-direction: column;
            font-family: "Inclusive Sans", Arial, sans-serif;
            justify-content: center;
            min-height: 100vh;
        }

        body.login #login {
            box-sizing: border-box;
            margin: 0 auto;
            padding: 32px 0;
            position: relative;
            width: min(400px, calc(100% - 32px));
            z-index: 1;
        }

        body.login #login h1 {
            margin: 0 0 24px;
        }

        body.login #login h1 a {
            background-color: #fff;
            background-image:
                url('<?php echo esc_url($assetUrl . 'nihr-logo.svg'); ?>'),
                url('<?php echo esc_url($assetUrl . 'nihr-service-wordmark.svg'); ?>');
            background-position: left 22px center, right 22px center;
            background-repeat: no-repeat;
            background-size: 43% auto, 41% auto;
            border-radius: 12px;
            box-shadow: 0 16px 40px rgba(10, 19, 54, 0.28);
            box-sizing: border-box;
            height: 82px;
            margin: 0;
            padding: 16px;
            width: 100%;
        }

        body.login form {
            border: 0;
            border-radius: 12px;
            box-shadow: 0 16px 40px rgba(10, 19, 54, 0.28);
            margin-top: 0;
            padding: 32px;
        }

        body.login form .input,
        body.login input[type="text"] {
            border-color: #9ca3af;
            border-radius: 6px;
        }

        body.login form .input:focus,
        body.login input[type="text"]:focus {
            border-color: #0051c2;
            box-shadow: 0 0 0 1px #0051c2;
        }

        body.login .button-primary {
            background: #1c285e;
            border-color: #1c285e;
            border-radius: 999px;
            font-weight: 700;
            min-height: 40px;
            padding: 0 24px;
        }

        body.login .button-primary:focus,
        body.login .button-primary:hover {
            background: #0051c2;
            border-color: #0051c2;
        }

        body.login #backtoblog,
        body.login #nav,
        body.login .privacy-policy-page-link {
            color: #fff;
            text-align: center;
        }

        body.login #backtoblog a,
        body.login #nav a,
        body.login .privacy-policy-page-link a {
            color: #fff;
            font-weight: 700;
            text-shadow: 0 1px 3px rgba(10, 19, 54, 0.65);
        }

        body.login #backtoblog a:focus,
        body.login #backtoblog a:hover,
        body.login #nav a:focus,
        body.login #nav a:hover,
        body.login .privacy-policy-page-link a:focus,
        body.login .privacy-policy-page-link a:hover {
            color: #ffe626;
        }

        body.login .language-switcher {
            margin: 24px auto 0;
            padding: 0;
        }

        body.login .language-switcher label,
        body.login .language-switcher label .dashicons {
            color: #fff;
        }

        body.login .language-switcher .button {
            border-color: #fff;
            color: #fff;
        }

        body.login .language-switcher .button:focus,
        body.login .language-switcher .button:hover {
            background: #ffe626;
            border-color: #ffe626;
            color: #1c285e;
        }

        @media (max-width: 480px) {
            body.login {
                background-position: 44% center;
            }

            body.login #login {
                padding-block: 20px;
            }

            body.login #login h1 a {
                background-position: left 16px center, right 16px center;
                background-size: 43% auto, 41% auto;
                height: 72px;
            }

            body.login form {
                padding: 24px;
            }
        }
    </style>
    <?php
}
add_action('login_enqueue_scripts', 'pru_login_styles');
