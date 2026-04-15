<?php
/**
 * Astra Child Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Astra Child
 * @since 1.0.0
 */

/**
 * Define Constants
 */
define('CHILD_THEME_ASTRA_CHILD_VERSION', '1.0.0');

/**
 * Enqueue styles
 */
function child_enqueue_styles()
{

    wp_enqueue_style('astra-child-theme-css', get_stylesheet_directory_uri() . '/style.css', array('astra-theme-css'), CHILD_THEME_ASTRA_CHILD_VERSION, 'all');

}

add_action('wp_enqueue_scripts', 'child_enqueue_styles', 15);

// Code pour bouton WhatsApp
add_action('wp_footer', 'afficher_bouton_tel');
function afficher_bouton_tel()
{
    ?>
    <style>
        .bouton-tel-fixe {
            position: fixed !important;
            bottom: 24px !important;
            right: 24px !important;
            background: linear-gradient(135deg, #25d366, #1da851) !important;
            color: #ffffff !important;
            padding: 13px 20px !important;
            border-radius: 50px !important;
            text-decoration: none !important;
            font-weight: 700 !important;
            font-size: 15px !important;
            letter-spacing: 0.5px !important;
            box-shadow: 0 4px 15px rgba(37, 211, 102, 0.45) !important;
            z-index: 9999 !important;
            display: flex !important;
            align-items: center !important;
            gap: 9px !important;
            transition: transform 0.2s ease, box-shadow 0.2s ease !important;
            animation: pulse-urgence 2s infinite !important;
        }

        .bouton-tel-fixe:hover {
            transform: scale(1.06) !important;
            box-shadow: 0 6px 20px rgba(37, 211, 102, 0.75) !important;
            color: #ffffff !important;
            animation: none !important;
        }

        .bouton-tel-fixe svg {
            width: 20px !important;
            height: 20px !important;
            flex-shrink: 0 !important;
            animation: sonnerie 2s infinite !important;
        }

        @keyframes pulse-urgence {

            0%,
            100% {
                box-shadow: 0 4px 15px rgba(37, 211, 102, 0.45);
            }

            50% {
                box-shadow: 0 4px 25px rgba(37, 211, 102, 0.85);
            }
        }

        @keyframes sonnerie {

            0%,
            100% {
                transform: rotate(0deg);
            }

            10% {
                transform: rotate(-15deg);
            }

            20% {
                transform: rotate(15deg);
            }

            30% {
                transform: rotate(-10deg);
            }

            40% {
                transform: rotate(10deg);
            }

            50% {
                transform: rotate(0deg);
            }
        }

        @media (max-width: 768px) {
            .bouton-tel-fixe {
                padding: 11px 16px !important;
                font-size: 14px !important;
                bottom: 16px !important;
                right: 16px !important;
            }
        }
    </style>
    <a href="https://wa.me/33760457070" target="_blank" rel="noopener noreferrer" class="bouton-tel-fixe">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path
                d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
        </svg>
        07 60 45 70 70
    </a>
    <?php
}





// Modifier le breakpoint tablette Astra à 1060px
add_filter('astra_tablet_breakpoint', function () {
    return 1060;
});