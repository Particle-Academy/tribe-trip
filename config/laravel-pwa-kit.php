<?php

/**
 * PWA Configuration for TribeTrip
 *
 * This configuration transforms the TribeTrip web application into a Progressive Web App,
 * enabling mobile installation, offline support, and native app-like experience.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Enable PWA
    |--------------------------------------------------------------------------
    | Globally enable or disable Progressive Web App functionality.
    */
    'enable_pwa' => true,

    /*
    |--------------------------------------------------------------------------
    | Show Install Toast on First Load
    |--------------------------------------------------------------------------
    |
    | Determines whether the PWA install toast should be displayed when a user
    | first visits the site. Once the toast is shown or dismissed, it will not
    | reappear for that user on the same day, preventing repeated interruptions
    | and improving user experience.
    |
    | Type: `bool`
    | Default: true
    |
    */
    'install-toast-show' => true,


    /*
    |--------------------------------------------------------------------------
    | PWA Manifest Configuration
    |--------------------------------------------------------------------------
    | Defines metadata for your Progressive Web App.
    | This configuration is used to generate the manifest.json file.
    | Reference: https://developer.mozilla.org/en-US/docs/Web/Manifest
    */
    'manifest' => [
        'appName' => env('APP_NAME', 'TribeTrip'),
        'name' => env('APP_NAME', 'TribeTrip'),
        'shortName' => 'TribeTrip',
        'short_name' => 'TribeTrip',
        'startUrl' => '/',
        'start_url' => '/',
        'scope' => '/',
        'author' => 'Dream Together LLC',
        'version' => '1.0',
        'description' => 'Community resource sharing made simple. Reserve vehicles, equipment, and spaces with transparent tracking and fair billing.',
        'orientation' => 'portrait',
        'dir' => 'auto',
        'lang' => 'en',
        'display' => 'standalone',
        // TribeTrip brand colors - olive green theme
        'themeColor' => '#4A5240',
        'theme_color' => '#4A5240',
        'backgroundColor' => '#F2EDE4',
        'background_color' => '#F2EDE4',
        'icons' => [
            [
                'src' => 'images/logo1-icon-100.png',
                'sizes' => '100x100',
                'type' => 'image/png',
                'purpose' => 'any maskable',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    | Enables verbose logging for service worker events and cache information.
    */
    'debug' => env('LARAVEL_PWA_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Toast Content
    |--------------------------------------------------------------------------
    | Title and description text for the install prompt toast.
    */
    'title' => 'Add TribeTrip to Home Screen',
    'description' => 'Install TribeTrip for quick access to reservations and usage logging — works offline too!',

    /*
    |--------------------------------------------------------------------------
    | Mobile View Position
    |--------------------------------------------------------------------------
    | Position of the PWA install toast on small devices.
    | Supported values: "top", "bottom".
    | RTL mode is supported and respects <html dir="rtl">.
    */
    'small_device_position' => 'bottom',

    /*
    |--------------------------------------------------------------------------
    | Install Now Button Text
    |--------------------------------------------------------------------------
    | Defines the text shown on the "Install Now" button inside the PWA
    | installation toast. This can be customized for localization.
    |
    | Example: 'install_now_button_text' => 'অ্যাপ ইন্সটল করুন'
    */
    'install_now_button_text' => 'Install App',

    /*
    |--------------------------------------------------------------------------
    | Livewire Integration
    |--------------------------------------------------------------------------
    | Optimize PWA functionality for applications using Laravel Livewire.
    | Set to true since TribeTrip is a Livewire application.
    */
    'livewire-app' => true,
];
