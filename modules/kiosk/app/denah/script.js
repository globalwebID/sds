const tooltip = document.getElementById('tooltip');
document.querySelectorAll('.area').forEach(area => {
    area.addEventListener('mouseover', e => {
        tooltip.innerText = area.dataset.nama;
        tooltip.style.display = 'block';
    });
    area.addEventListener('mousemove', e => {
        tooltip.style.top = (e.pageY + 10) + 'px';
        tooltip.style.left = (e.pageX + 10) + 'px';
    });
    area.addEventListener('mouseout', () => {
        tooltip.style.display = 'none';
    });
    area.addEventListener('click', () => {
        alert('Menuju ke: ' + area.dataset.nama);
        // bisa diarahkan ke halaman lain atau menampilkan arah
    });
});

const arrow = document.getElementById('arrow');

// Titik asal panah (misalnya: tengah halaman atau lokasi user)
const titikAwal = { x: 600, y: 500 }; // Sesuaikan posisi awal panah

document.querySelectorAll('.area').forEach(area => {
    area.addEventListener('mouseover', e => {
        tooltip.innerText = area.dataset.nama;
        tooltip.style.display = 'block';
    });
    area.addEventListener('mousemove', e => {
        tooltip.style.top = (e.pageY + 10) + 'px';
        tooltip.style.left = (e.pageX + 10) + 'px';
    });
    area.addEventListener('mouseout', () => {
        tooltip.style.display = 'none';
    });
    area.addEventListener('click', () => {
        // Ambil posisi target
        const rect = area.getBoundingClientRect();
        const targetX = rect.left + rect.width / 2 + window.scrollX;
        const targetY = rect.top + rect.height / 2 + window.scrollY;

        // Hitung jarak dan sudut
        const dx = targetX - titikAwal.x;
        const dy = targetY - titikAwal.y;
        const panjang = Math.sqrt(dx * dx + dy * dy);
        const sudut = Math.atan2(dy, dx) * 180 / Math.PI;

        // Tampilkan panah
        arrow.style.display = 'block';
        arrow.style.left = titikAwal.x + 'px';
        arrow.style.top = titikAwal.y + 'px';
        arrow.style.width = panjang + 'px';
        arrow.style.transform = `rotate(${sudut}deg)`;
    });
});
