<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Asisten Virtual dengan Video</title>
  <style>
    body {
      font-family: sans-serif;
      background: #eef2f3;
      text-align: center;
      padding-top: 40px;
    }

    #bot-video {
      width: 50%;
      height: auto;
      border-radius: 10px;
      background: #000;
    }

    #output {
      margin-top: 20px;
      font-size: 18px;
    }

    button {
      margin-top: 20px;
      padding: 10px 20px;
      font-size: 16px;
      cursor: pointer;
    }
  </style>
</head>

<body>
  <h2>🎤 Asisten Video SMKN 1 Probolinggo</h2>

  <!-- Video Bot -->
  <video id="bot-video" src="bot_video.mp4" muted preload="auto" poster="bot_idle.png"></video>

  <!-- Output dan Tombol -->
  <div id="output">Klik tombol lalu bicara...</div>
  <button id="speak-btn">🎙️ Bicara</button>

  <script>
    const video = document.getElementById('bot-video');
    const output = document.getElementById('output');
    const speakBtn = document.getElementById('speak-btn');
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

    if (!SpeechRecognition) {
      alert("Browser Anda tidak mendukung Speech Recognition. Silakan gunakan Google Chrome.");
      speakBtn.disabled = true;
    }

    speakBtn.addEventListener('click', () => {
      const recognition = new SpeechRecognition();
      recognition.lang = 'id-ID';
      recognition.start();

      recognition.onresult = (event) => {
        const userText = event.results[0][0].transcript;
        output.innerHTML = `🗣️ Anda: ${userText}`;
        fetchBotResponse(userText);
      };

      recognition.onerror = (event) => {
        output.innerHTML = `❌ Terjadi kesalahan: ${event.error}`;
      };
    });

    function fetchBotResponse(text) {
      fetch(`logic.php?pesan=${encodeURIComponent(text)}`)
        .then(res => res.text())
        .then(response => {
          output.innerHTML += `<br>🤖 Bot: ${response}`;
          speakWithVideo(response);
        })
        .catch(err => {
          output.innerHTML += `<br>⚠️ Gagal mengambil respons dari server.`;
          console.error(err);
        });
    }

    function speakWithVideo(text) {
      const utter = new SpeechSynthesisUtterance(text);
      utter.lang = 'id-ID';

      // Reset video ke awal, aktifkan loop
      video.currentTime = 0;
      video.loop = true;
      video.play();

      // Jalankan suara
      speechSynthesis.cancel(); // pastikan kosong dulu
      speechSynthesis.speak(utter);

      // Debug log (opsional)
      utter.onstart = () => {
        console.log("🔊 Bot mulai bicara");
      };

      utter.onend = () => {
        console.log("✅ Bot selesai bicara");
        // NONAKTIFKAN loop terlebih dahulu baru pause
        video.loop = false;
        video.pause();
        video.currentTime = 0;
      };
    }
  </script>
</body>

</html>