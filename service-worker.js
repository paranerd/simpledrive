// Cache for app shell
var cacheName = 'simpledrive-1';

// Cache for dynamic data
var dataCacheName = 'simpleDriveData-v1';

// List of files to cache
var filesToCache = [
  '/simpledrive/',
  /*'public/js/util/jquery-1.11.3.min.js',
  'public/js/util/list.js',
  'public/js/util/util.js',
  'public/js/util/simplescroll.js',
  'public/js/core/files.js',
  'public/css/colors.css',
  'public/css/fileviews.css',
  'public/css/fileviews.css',
  'public/css/icons.css',
  'public/css/layout.css',
  'app/views/files.php',
  'app/views/login.php',*/
];

self.addEventListener('install', function(e) {
  self.skipWaiting();
  console.log('[ServiceWorker] Install');
  e.waitUntil(
    caches.open(cacheName).then(function(cache) {
      console.log('[ServiceWorker] Caching app shell');
      return cache.addAll(filesToCache);
    })
  );
});

self.addEventListener('activate', function(e) {
  console.log('[ServiceWorker] Activate');
  e.waitUntil(
    caches.keys().then(function(keyList) {
      return Promise.all(keyList.map(function(key) {
        if (key !== cacheName && key !== dataCacheName) {
          console.log('[ServiceWorker] Removing old cache', key);
          return caches.delete(key);
        }
      }));
    })
  );

  return self.clients.claim();
});

self.addEventListener('fetch', function(e) {
	console.log('[Service Worker] Fetch', e.request.url);
	var dataUrl = 'api/';
	if (e.request.url.indexOf(dataUrl) > -1) {
		/*
		* When the request URL contains dataUrl, the app is asking for fresh
		* weather data. In this case, the service worker always goes to the
		* network and then caches the response. This is called the "Cache then
		* network" strategy:
		* https://jakearchibald.com/2014/offline-cookbook/#cache-then-network
		*/
		e.respondWith(
			caches.open(dataCacheName).then(function(cache) {
				return fetch(e.request).then(function(response) {
					console.log("fetch ok");
					console.log(response);
					cache.put(e.request.url, response.clone());
					return response;
				}).catch(function(err) {
					console.log("fetch catch");
					cache.match(e.request).then(function(response) {
						console.log("cache match");
						return response;
					})
				});
			})
		);
	}
	else {
		/*
		* The app is asking for app shell files. In this scenario the app uses the
		* "Cache, falling back to the network" offline strategy:
		* https://jakearchibald.com/2014/offline-cookbook/#cache-falling-back-to-network
		*/
		e.respondWith(
			caches.match(e.request).then(function(response) {
				return response || fetch(e.request);
			})
		);
	}
});
