<!-- Kirim Saldo Card -->
<div class="card shadow-lg border-0 rounded-4 p-4 text-center">
    <h4 class="fw-bold mb-3">🔁 Kirim Saldo ke Teman</h4>

    <!-- Area Tap Kartu -->
    <div id="tap-area" style="cursor: pointer;">
        <img src="images/tap.png" alt="Tempelkan Kartu" class="mx-auto d-block" style="max-width: 200px;">
        <!--<input type="text" id="rfid_teman" class="form-control form-control-lg text-center" placeholder="👆 Tempelkan kartu..." autocomplete="off">-->
        <input type="text" id="rfid_teman" class="form-control form-control-lg text-center"  placeholder="👆 Tempelkan kartu..." inputmode="none" readonly onfocus="this.removeAttribute('readonly');" autofocus required>
        <p class="mt-3 text-muted">Tempelkan kartu teman ke reader RFID</p>
    </div>

    <div id="feedback_rfid" class="text-success fw-bold mt-3"></div>
</div>


