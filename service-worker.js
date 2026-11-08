   const CACHE = "dueitnow-v1";
   const FILES = [
     "/due_it_now/assets/style.css",
     "/due_it_now/assets/dashboard.js",
     "/due_it_now/assets/script.js",
     "/due_it_now/offline.html",
   ];

   self.addEventListener("install", (e) => {
     e.waitUntil(caches.open(CACHE).then((c) => c.addAll(FILES)));
     self.skipWaiting();
   });

   self.addEventListener("activate", (e) => {
     e.waitUntil(
       caches.keys().then((keys) =>
         Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))
       )
     );
   });

   self.addEventListener("fetch", (e) => {
     if (e.request.method !== "GET") return;
     e.respondWith(
       fetch(e.request).catch(() =>
         caches.match(e.request).then((r) => r || caches.match("/due_it_now/offline.html"))
       )
     );
   });