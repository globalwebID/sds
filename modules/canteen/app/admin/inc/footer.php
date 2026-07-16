<footer class="dashboard-footer">
  <div class="footer-container">
    <div class="footer-left">
      <h6>M-Kantin • Sistem e-Money Kartu Pelajar</h6>
      <p>Monitoring transaksi • Top-up • Kantin • Siswa</p>
    </div>

    <div class="footer-center">
      <span>© <span id="yearNow"></span> SMKN 1 PROBOLINGGO</span>
    </div>

    <div class="footer-right">
      <span class="badge-footer">Admin Panel</span>
      <span class="badge-footer secondary">Secure</span>
    </div>
  </div>
</footer>

<style>
  .dashboard-footer {
    margin-top: 60px;
    padding: 18px 0;
    background: linear-gradient(135deg, #0f766e, #16a34a);
    color: #fff;
    font-size: 14px;
    bottom: 0;
    position: relative;
    width: 100%;
  }

  .footer-container {
    max-width: 1300px;
    margin: auto;
    padding: 0 20px;

    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    justify-content: space-between;
    align-items: center;
  }

  .footer-left h6 {
    margin: 0;
    font-weight: 600;
    letter-spacing: .4px;
  }

  .footer-left p {
    margin: 0;
    opacity: .9;
    font-size: 13px;
  }

  .footer-center {
    font-weight: 500;
    opacity: .95;
  }

  .footer-right {
    display: flex;
    gap: 8px;
  }

  .badge-footer {
    padding: 5px 12px;
    border-radius: 20px;
    background: rgba(255, 255, 255, .25);
    font-size: 12px;
    backdrop-filter: blur(4px);
  }

  .badge-footer.secondary {
    background: rgba(0, 0, 0, .25);
  }

  @media(max-width:768px) {
    .footer-container {
      text-align: center;
      justify-content: center;
    }

    .footer-left,
    .footer-center,
    .footer-right {
      width: 100%;
    }
  }
</style>

<script>
  document.getElementById("yearNow").textContent = new Date().getFullYear();
</script>

</body>

</html>