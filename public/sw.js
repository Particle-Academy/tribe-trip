"use strict";

/**
 * TribeTrip Service Worker
 *
 * Handles offline caching and provides fallback offline page
 * for the TribeTrip PWA.
 */

const CACHE_NAME = "tribetrip-cache-v1";
const OFFLINE_URL = '/offline.html';

// Files to cache for offline support
const filesToCache = [
    OFFLINE_URL,
    '/images/logo1-icon-100.png',
];

// Install event - cache offline assets
self.addEventListener("install", (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(filesToCache))
    );
});

// Fetch event - serve cached content when offline
self.addEventListener("fetch", (event) => {
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request)
                .catch(() => {
                    return caches.match(OFFLINE_URL);
                })
        );
    } else {
        event.respondWith(
            caches.match(event.request)
                .then((response) => {
                    return response || fetch(event.request);
                })
        );
    }
});

// Activate event - cleanup old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});
