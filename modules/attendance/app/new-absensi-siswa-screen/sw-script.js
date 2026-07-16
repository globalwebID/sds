/* =========================================================
   sw-script.js FINAL (ANTI-MACET + 5 ABSEN TERAKHIR + SSE DEBOUNCE)
   - Jam realtime
   - Geolocation (lebih cepat: cache + non high accuracy)
   - Webcam selfie + submit RFID/QR (AJAX)
   - Swal auto-close cepat
   - Realtime via SSE (realtime-absensi.php) + DEBOUNCE (anti spam refresh)
   - Offline queue IndexedDB + autosync
   - Tampilan Absensi terbaru: LIST 5 terakhir (tanpa marquee)
     -> setiap update: data terbaru berada di atas (naik)
   ========================================================= */

(function () {
  "use strict";

  // -----------------------------
  // Helper: fokus QR input
  // -----------------------------
  function focusQrInput(clearValue = false) {
    const $qr = $(".qrcode");
    if ($qr.length) {
      if (clearValue) $qr.val("");
      $qr.focus();
    }
  }

  // -----------------------------
  // Helper: swal auto close (dipersingkat)
  // -----------------------------
  function swalAuto(opts) {
    const base = {
      timer: 1100,
      buttons: false,
      closeOnClickOutside: true,
    };
    return swal(Object.assign(base, opts || {}));
  }

  // -----------------------------
  // Helper: validasi latitude
  // -----------------------------
  function getLatitudeValue() {
    return ($(".latitude").html() || "").trim();
  }

  function ensureLatitudeOrWarn() {
    const lat = getLatitudeValue();
    if (!lat) {
      swalAuto({
        title: "Oops!",
        text: "Latitude tidak boleh kosong",
        icon: "error",
        timer: 1500,
      });
      return false;
    }
    return true;
  }

  // =========================================================
  // UI: LIST 5 ABSEN TERAKHIR (tanpa marquee)
  // Wajib ada elemen: <div id="absen5List"></div>
  // =========================================================
  const Absen5UI = (() => {
    const MAX_ITEMS = 8;
    const getEl = () => document.getElementById("absen5List");

    function extractCandidates(html) {
      const tmp = document.createElement("div");
      tmp.innerHTML = html || "";

      // cari item yang paling masuk akal
      const selectors = [
        ".absen5-item",
        ".card", // output Anda berupa card
        ".listview .item",
        ".list-group .list-group-item",
        "table tbody tr",
      ];

      for (const sel of selectors) {
        const nodes = tmp.querySelectorAll(sel);
        if (nodes && nodes.length) return Array.from(nodes);
      }
      return Array.from(tmp.children || []);
    }

    function normalizeToItemHtml(node) {
      if (!node) return "";
      // Bungkus agar aman + gampang diberi animasi
      if (node.classList && node.classList.contains("absen5-item")) return node.outerHTML;
      return `<div class="absen5-item">${node.outerHTML}</div>`;
    }

    function renderFromHtml(html) {
      const el = getEl();
      if (!el) return;

      const nodes = extractCandidates(html).slice(0, MAX_ITEMS);

      el.innerHTML = "";
      nodes.forEach((n) => {
        const holder = document.createElement("div");
        holder.innerHTML = normalizeToItemHtml(n);
        const item = holder.firstElementChild;
        if (item) el.appendChild(item);
      });
    }

    return { renderFromHtml };
  })();

  // =========================================================
  // OFFLINE QUEUE (IndexedDB) + AUTO SYNC (retry + backoff)
  // =========================================================
  const OfflineQueue = (() => {
    const DB_NAME = "absensi_offline_db";
    const DB_VER = 1;
    const STORE = "queue";

    function openDB() {
      return new Promise((resolve, reject) => {
        const req = indexedDB.open(DB_NAME, DB_VER);
        req.onupgradeneeded = () => {
          const db = req.result;
          if (!db.objectStoreNames.contains(STORE)) {
            const st = db.createObjectStore(STORE, { keyPath: "id", autoIncrement: true });
            st.createIndex("createdAt", "createdAt");
          }
        };
        req.onsuccess = () => resolve(req.result);
        req.onerror = () => reject(req.error);
      });
    }

    async function add(item) {
      const db = await openDB();
      return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE, "readwrite");
        tx.objectStore(STORE).add(item);
        tx.oncomplete = () => resolve(true);
        tx.onerror = () => reject(tx.error);
      });
    }

    async function listAll() {
      const db = await openDB();
      return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE, "readonly");
        const req = tx.objectStore(STORE).getAll();
        req.onsuccess = () => resolve(req.result || []);
        req.onerror = () => reject(req.error);
      });
    }

    async function remove(id) {
      const db = await openDB();
      return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE, "readwrite");
        tx.objectStore(STORE).delete(id);
        tx.oncomplete = () => resolve(true);
        tx.onerror = () => reject(tx.error);
      });
    }

    async function update(id, patch) {
      const db = await openDB();
      return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE, "readwrite");
        const st = tx.objectStore(STORE);
        const getReq = st.get(id);
        getReq.onsuccess = () => {
          const cur = getReq.result;
          if (!cur) return resolve(false);
          st.put(Object.assign({}, cur, patch));
        };
        tx.oncomplete = () => resolve(true);
        tx.onerror = () => reject(tx.error);
      });
    }

    return { add, listAll, remove, update };
  })();

  const SyncEngine = (() => {
    let syncing = false;

    function sleep(ms) {
      return new Promise((r) => setTimeout(r, ms));
    }

    // exponential backoff: 2s, 4s, 8s, 16s, 32s, 60s max
    function backoffMs(tries) {
      const base = 2000 * Math.pow(2, Math.min(tries, 5));
      return Math.min(base, 60000);
    }

    async function sendOne(item) {
      if (item.type !== "absen") return { ok: true, text: "skip" };

      const fd = new FormData();
      fd.append("img", item.imgBlob, "selfie.jpg");
      fd.append("qrcode", item.qrcode);
      fd.append("latitude", item.latitude);

      const res = await fetch("./sw-proses.php?action=absen", {
        method: "POST",
        body: fd,
      });

      const text = await res.text();
      const [status] = String(text || "").split("/");
      const ok = (status || "").trim() === "success";
      return { ok, text };
    }

    async function syncNow({ showToast = false } = {}) {
      if (syncing) return;
      if (!navigator.onLine) return;

      syncing = true;
      try {
        const items = await OfflineQueue.listAll();
        if (!items.length) return;

        items.sort((a, b) => (a.createdAt || 0) - (b.createdAt || 0));

        if (showToast) {
          swalAuto({
            title: "Sinkronisasi...",
            text: `Mengirim ${items.length} antrean offline`,
            icon: "info",
            timer: 900,
          });
        }

        for (const item of items) {
          if (!navigator.onLine) break;

          try {
            const result = await sendOne(item);

            if (result.ok) {
              await OfflineQueue.remove(item.id);
              if (typeof loaddata === "function") loaddata();
              if (typeof loaddatacouter === "function") loaddatacouter();
            } else {
              const tries = (item.tries || 0) + 1;
              await OfflineQueue.update(item.id, { tries, lastError: result.text || "failed" });
              await sleep(backoffMs(tries));
            }
          } catch (e) {
            const tries = (item.tries || 0) + 1;
            await OfflineQueue.update(item.id, { tries, lastError: String(e) });
            await sleep(backoffMs(tries));
          }
        }
      } catch (e) {
        console.error("Sync error:", e);
      } finally {
        syncing = false;
      }
    }

    window.addEventListener("online", () => syncNow({ showToast: true }));
    return { syncNow };
  })();

  // =========================================================
  // 1) CLOCK
  // =========================================================
  jQuery(function ($) {
    setInterval(function () {
      const date = new Date();
      $(".clock").html(date.toLocaleTimeString());
    }, 1000);
  });

  // =========================================================
  // 2) GEOLOCATION (lebih cepat)
  // =========================================================
  $(document).ready(function getLocation() {
    if (!navigator.geolocation) {
      swalAuto({
        title: "Oops!",
        text: "Browser tidak mendukung geolokasi.",
        icon: "error",
        timer: 1500,
      });
      return;
    }

    navigator.geolocation.getCurrentPosition(
      function successCallback(position) {
        const latitude = `${position.coords.latitude},${position.coords.longitude}`;
        $(".latitude").html(latitude);
      },
      function errorCallback() {
        swalAuto({
          title: "Oops!",
          text: "Gagal mendapatkan lokasi. Pastikan izin lokasi aktif.",
          icon: "error",
          timer: 1600,
        });
      },
      {
        enableHighAccuracy: false,
        timeout: 5000,
        maximumAge: 30000,
      }
    );
  });

  // =========================================================
  // 3) WEBCAM SELFIE + SUBMIT RFID/QR
  // =========================================================
  window.webcame_selfie = function webcame_selfie() {
    const webcamElement = document.getElementById("webcam");
    const canvasElement = document.getElementById("canvas");
    const webcam = new Webcam(webcamElement, "user", canvasElement);

    function displayError(err) {
      console.error("Webcam error:", err);
      swalAuto({
        title: "Oops!",
        text: "Gagal mengakses kamera. Aktifkan izin kamera.",
        icon: "error",
        timer: 1700,
      });
    }

    function cameraStarted() {
      if (webcam.webcamList && webcam.webcamList.length > 1) {
        $(".btn-cameraFlip").removeClass("d-none");
      }
      window.scrollTo(0, 0);
      focusQrInput(true);
    }

    function startFrontCamera() {
      navigator.mediaDevices
        .enumerateDevices()
        .then((devices) => {
          const frontCamera = devices.find(
            (d) => d.kind === "videoinput" && (d.label || "").toLowerCase().includes("front")
          );

          if (frontCamera) {
            webcam.start(frontCamera.deviceId).then(cameraStarted).catch(displayError);
          } else {
            webcam.start().then(cameraStarted).catch(displayError);
          }
        })
        .catch(() => webcam.start().then(cameraStarted).catch(displayError));
    }

    startFrontCamera();

    $(".btn-cameraFlip").off("click").on("click", function () {
      if (webcam.webcamList && webcam.webcamList.length > 1) {
        webcam.flip();
        webcam.start();
      }
    });

    $("body").off("click._focusqr").on("click._focusqr", function () {
      focusQrInput(false);
    });

    setTimeout(() => focusQrInput(true), 120);

    let isProcessing = false;

    $(".qrcode")
      .off("keydown._submitqr")
      .on("keydown._submitqr", function (e) {
        if (e.which !== 13) return;
        if (isProcessing) return;

        const qrcode = ($(this).val() || "").trim();
        if (!qrcode) return;

        if (!ensureLatitudeOrWarn()) {
          focusQrInput(true);
          return;
        }

        const latitude = getLatitudeValue();
        isProcessing = true;

        // ANTI-MACET: kosongkan & fokus segera
        focusQrInput(true);

        // ambil frame
        let picture;
        try {
          picture = webcam.snap();
        } catch (err) {
          console.error("Snap error:", err);
          swalAuto({ title: "Oops!", text: "Gagal mengambil foto kamera.", icon: "error", timer: 1700 });
          isProcessing = false;
          focusQrInput(true);
          return;
        }

        const img = new Image();
        img.src = picture;

        const canvas = document.getElementById("canvas");
        const ctx = canvas.getContext("2d");

        img.onload = function () {
          const SIZE = 240; // lebih ringan
          canvas.width = SIZE;
          canvas.height = SIZE;

          const imgWidth = img.width;
          const imgHeight = img.height;

          const scale = Math.min(SIZE / imgWidth, SIZE / imgHeight);
          const newWidth = imgWidth * scale;
          const newHeight = imgHeight * scale;

          const x = (SIZE - newWidth) / 2;
          const y = (SIZE - newHeight) / 2;

          ctx.clearRect(0, 0, SIZE, SIZE);
          ctx.drawImage(img, x, y, newWidth, newHeight);

          canvas.toBlob(
            async function (blob) {
              const formData = new FormData();
              formData.append("img", blob, "selfie.jpg");
              formData.append("qrcode", qrcode);
              formData.append("latitude", latitude);

              $.ajax({
                type: "POST",
                url: "./sw-proses.php?action=absen",
                data: formData,
                processData: false,
                contentType: false,

                success: function (data) {
                  const raw = String(data || "").trim();
                  const idx = raw.indexOf("/");
                  const status = (idx >= 0 ? raw.slice(0, idx) : raw).trim();
                  const message = (idx >= 0 ? raw.slice(idx + 1) : "").trim();

                  if (status === "success") {
                    swalAuto({
                      title: "Berhasil!",
                      text: message || "Absensi berhasil.",
                      icon: "success",
                      timer: 900,
                    });

                    // Refresh list 5 + counter (cepat)
                    loaddata();
                    loaddatacouter();
                  } else {
                    swalAuto({
                      title: "Oops!",
                      text: message || raw || "Terjadi kesalahan.",
                      icon: "error",
                      timer: 1400,
                    });
                  }
                },

                error: async function () {
                  // Offline handler
                  try {
                    await OfflineQueue.add({
                      type: "absen",
                      qrcode: qrcode,
                      latitude: latitude,
                      imgBlob: blob,
                      createdAt: Date.now(),
                      tries: 0,
                    });

                    swalAuto({
                      title: "Offline",
                      text: "Internet putus. Data disimpan & akan sync saat online.",
                      icon: "warning",
                      timer: 1200,
                    });
                  } catch (e) {
                    console.error("Gagal simpan antrean offline:", e);
                    swalAuto({
                      title: "Oops!",
                      text: "Gagal menyimpan antrean offline.",
                      icon: "error",
                      timer: 1500,
                    });
                  }
                },

                complete: function () {
                  isProcessing = false;
                  focusQrInput(true);
                  SyncEngine.syncNow();
                },
              });
            },
            "image/jpeg",
            0.7
          );
        };

        img.onerror = function () {
          swalAuto({ title: "Oops!", text: "Gagal memproses gambar kamera.", icon: "error", timer: 1500 });
          isProcessing = false;
          focusQrInput(true);
        };
      });
  };

  // =========================================================
  // 5) LOAD DATA + COUNTER
  // =========================================================
  window.loaddata = function loaddata() {
    // Kalau server sudah refactor: default limit=5 (cepat)
    $.ajax({
      url: "./sw-proses.php?action=data-absensi&limit=8", // bisa ditambah &limit=5 kalau mau eksplisit
      method: "GET",
      cache: false,
      success: function (html) {
        Absen5UI.renderFromHtml(html);

        // kompatibilitas (jika masih ada container lama)
        const $old = $(".data-absensi");
        if ($old.length) $old.html(html);
      },
    });
  };

  window.loaddatacouter = function loaddatacouter() {
    $.ajax({
      type: "POST",
      url: "./sw-proses.php?action=data-counter",
      dataType: "json",
      success: function (response) {
        if (!response) return;
        $(".total-siswa").html(response.total_siswa);
        $(".belum-absen").html(response.belum_absen);
        $(".ontime").html(response.on_time);
        $(".terlambat").html(response.terlambat);
        $(".izin").html(response.izin);
        $(".total-absen").html(response.total_absen);
        $(".persentase").html(response.persentase);
      },
    });
  };

  // Load awal
  loaddata();
  loaddatacouter();
  SyncEngine.syncNow();

  // =========================================================
  // 6) REALTIME SSE + DEBOUNCE (ANTI SPAM REFRESH)
  // =========================================================
  (function initRealtimeSSE() {
    if (!window.EventSource) return;

    let t = null;
    const DEBOUNCE_MS = 400;

    try {
      const source = new EventSource("./realtime-absensi.php");

      source.addEventListener("update", function () {
        clearTimeout(t);
        t = setTimeout(function () {
          loaddata();
          loaddatacouter();
        }, DEBOUNCE_MS);
      });

      source.addEventListener("ping", function () {});

      source.onerror = function () {
        // browser auto reconnect
      };
    } catch (e) {
      console.error("SSE init failed:", e);
    }
  })();

  // =========================================================
  // 7) AUTO RESET SAAT GANTI HARI
  // =========================================================
  let _lastDayKey = new Date().toDateString();
  setInterval(() => {
    const nowKey = new Date().toDateString();
    if (nowKey !== _lastDayKey) {
      _lastDayKey = nowKey;
      location.reload();
    }
  }, 60000);

  // =========================================================
  // 8) ANTI-FREEZE: saat tab kembali aktif
  // =========================================================
  document.addEventListener("visibilitychange", function () {
    if (!document.hidden) {
      loaddata();
      loaddatacouter();
      setTimeout(() => focusQrInput(false), 150);
      SyncEngine.syncNow();
    }
  });
})();
