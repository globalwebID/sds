jQuery(function ($) {
  setInterval(function () {
    const date = new Date();
    $(".clock").html(date.toLocaleTimeString());
  }, 1000);
});

$(document).ready(function getLocation() {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(successCallback, errorCallback);
  } else {
    swal({ title: 'Oops!', text: 'Maaf, browser Anda tidak mendukung geolokasi HTML5.', icon: 'error', timer: 3000 });
  }
});

function successCallback(position) {
  const latitude = position.coords.latitude + "," + position.coords.longitude;
  $('.latitude').html(latitude);
}

function errorCallback(error) {
  if (error.code == 1) {
    swal({ title: 'Oops!', text: 'Anda menolak lokasi. Tidak apa-apa.', icon: 'error', timer: 3000 });
  } else if (error.code == 2) {
    swal({ title: 'Oops!', text: 'Jaringan tidak aktif / layanan posisi tidak dapat dijangkau.', icon: 'error', timer: 3000 });
  } else {
    swal({ title: 'Oops!', text: 'Gagal mendapatkan lokasi (timeout).', icon: 'error', timer: 3000 });
  }
}

/* ======== LIST ABSEN (MAX 5, NO MARQUEE) ======== */
loaddata(true);
loaddatacouter();

function limit5Dom() {
  const $wrap = $(".data-absensi");
  const $cards = $wrap.children(".card");
  if ($cards.length > 5) $cards.slice(5).remove();
}

function loaddata(isFirst = false) {
  const $wrap = $(".data-absensi");
  if (!$wrap.length) return;

  if (isFirst) {
    $wrap.html(
      '<div class="text-center text-white">' +
      '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>' +
      ' <p>Loading data...</p></div>'
    );
  }

  $wrap.load("./sw-proses.php?action=data-absensi", function () {
    limit5Dom();
  });
}

// Polling ringan
setInterval(function () {
  loaddata(false);
  loaddatacouter();
}, 5000);

function loaddatacouter() {
  $.ajax({
    type: 'POST',
    url: './sw-proses.php?action=data-counter',
    dataType: 'json',
    success: function (response) {
      $('.total-pegawai').html(response.total_pegawai);
      $('.belum-absen').html(response.belum_absen);
      $('.ontime').html(response.on_time);
      $('.terlambat').html(response.terlambat);
      $('.izin').html(response.izin);
      $('.total-absen').html(response.total_absen);
      $('.persentase').html(response.persentase);
    },
    error: function (xhr, status, error) {
      console.error("AJAX Error: " + status + ": " + error);
    }
  });
}

/* ======== MODE: WEBCAM SELFIE (QR/RFID via scanner input) ======== */
function webcame_selfie() {
  const webcamElement = document.getElementById("webcam");
  const canvasElement = document.getElementById("canvas");
  const webcam = new Webcam(webcamElement, "user", canvasElement);

  function startFrontCamera() {
    navigator.mediaDevices.enumerateDevices()
      .then((devices) => {
        const frontCamera = devices.find(
          (d) => d.kind === "videoinput" && (d.label || "").toLowerCase().includes("front")
        );
        if (frontCamera) {
          webcam.start(frontCamera.deviceId).then(cameraStarted).catch(() => displayError());
        } else {
          webcam.start().then(cameraStarted).catch(() => displayError());
        }
      })
      .catch(() => {
        webcam.start().then(cameraStarted).catch(() => displayError());
      });
  }

  startFrontCamera();

  function cameraStarted() {
    if (webcam.webcamList && webcam.webcamList.length > 1) {
      $(".btn-cameraFlip").removeClass("d-none");
    }
    window.scrollTo(0, 0);
  }

  $(".btn-cameraFlip").click(function () {
    if (webcam.webcamList && webcam.webcamList.length > 1) {
      webcam.flip();
      webcam.start();
    }
  });

  // Fokus input scanner
  $('body').on('click', function () { $('.qrcode').focus(); });
  setTimeout(function () { $('.qrcode').focus().val(''); }, 200);

  let isProcessing = false;

  // Scanner biasanya mengirim ENTER -> gunakan keydown (lebih akurat dari input)
  $(document).on('keydown', '.qrcode', function (e) {
    if (isProcessing) return;

    if (e.key === "Enter") {
      e.preventDefault();
      const qrcode = ($(".qrcode").val() || "").trim();
      const latitude = $(".latitude").html() || "";
      if (!qrcode) return;

      isProcessing = true;

      setTimeout(function () {
        // snap
        let picture = webcam.snap();
        let img = new Image();
        img.src = picture;

        const canvas = document.getElementById("canvas");
        const ctx = canvas.getContext("2d");

        img.onload = function () {
          canvas.width = 300;
          canvas.height = 300;

          let imgWidth = img.width;
          let imgHeight = img.height;
          let scale = Math.min(canvas.width / imgWidth, canvas.height / imgHeight);
          let newWidth = imgWidth * scale;
          let newHeight = imgHeight * scale;

          let x = (canvas.width - newWidth) / 2;
          let y = (canvas.height - newHeight) / 2;

          ctx.clearRect(0, 0, canvas.width, canvas.height);
          ctx.drawImage(img, x, y, newWidth, newHeight);

          canvas.toBlob(function (blob) {
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
                const parts = String(data).split("/");
                const status = parts[0] || "";
                const message = parts.slice(1).join("/") || data;

                if (status === "success") {
                  swal({ title: "Berhasil!", text: message, icon: "success", timer: 2500 });
                  loaddata(false);
                  loaddatacouter();
                } else {
                  swal({ title: "Oops!", text: data, icon: "error", timer: 3000 });
                }
              },
              error: function (err) {
                console.error("Error sending data: ", err);
                swal({ title: "Oops!", text: "Gagal mengirim data.", icon: "error", timer: 3000 });
              },
              complete: function () {
                setTimeout(function () {
                  $('.qrcode').val('').focus();
                  isProcessing = false;
                }, 400);
              }
            });
          }, "image/jpeg", 0.8);
        };
      }, 250);
    }
  });
}

/* ======== MODE: QR CAMERA (qrcode-webcame) ======== */
function qrcode_webcame() {
  let isScanned = false;

  function onScanSuccess(decodedText) {
    if (isScanned) return;
    isScanned = true;

    const latitude = $(".latitude").html() || "";
    new Audio('../template/vendor/html5-qrcode/audio/beep.mp3').play().catch(console.error);

    $.ajax({
      type: "POST",
      url: "./sw-proses.php?action=absen-webcame",
      data: { qrcode: decodedText, latitude: latitude },
      success: function (data) {
        const parts = String(data).split("/");
        const status = parts[0] || "";
        const message = parts.slice(1).join("/") || data;

        if (status === "success") {
          swal({ title: "Berhasil!", text: message, icon: "success", timer: 2500 });
          loaddata(false);
          loaddatacouter();
        } else {
          swal({ title: "Oops!", text: data, icon: "error", timer: 3000 });
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX Error: " + status + ": " + error);
        swal({ title: "Oops!", text: "Terjadi kesalahan saat mengirim data.", icon: "error", timer: 3000 });
      },
      complete: function () {
        setTimeout(() => { isScanned = false; }, 2000);
      }
    });
  }

  const config = {
    fps: 24,
    qrbox: (vw, vh) => {
      const size = Math.floor(Math.min(vw, vh) * 0.7);
      return { width: size, height: size };
    },
    rememberLastUsedCamera: true,
    supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA]
  };

  const html5QrcodeScanner = new Html5QrcodeScanner("reader", config, false);
  html5QrcodeScanner.render(onScanSuccess);
}