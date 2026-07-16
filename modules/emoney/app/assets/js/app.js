fetch('../api/saldo.php', { credentials:'include' })
.then(r=>r.json())
.then(d=>{
if(!d.success) return;
document.getElementById('saldo').innerText = 'Rp ' + d.data.saldo.toLocaleString('id-ID');
document.getElementById('nama').innerText = d.data.nama;
});