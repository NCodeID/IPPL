<template>
  <aside class="warehouse-sidebar">
    <div class="warehouse-brand">
      <div class="warehouse-brand-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24">
          <path d="M6 3v6M3.5 3v4.5a2.5 2.5 0 0 0 5 0V3M6 10v11M16 3c-2 2-3 5-3 8h3v10M16 3v8" />
        </svg>
      </div>
      <div>
        <h2>Resto POS</h2>
        <span>&amp; OPS SYSTEM</span>
      </div>
    </div>

    <section class="warehouse-connection" aria-label="Koneksi sistem">
      <div class="warehouse-connection-heading">
        <strong>KONEKSI SISTEM</strong>
        <span class="warehouse-connection-dot" aria-label="Online"></span>
      </div>
      <div class="warehouse-connection-row">
        <span>KDS Sync</span>
        <span class="warehouse-connection-value">Online (14ms)</span>
      </div>
      <div class="warehouse-connection-row">
        <span>POS Terminal #1</span>
        <span class="warehouse-connection-value">Terhubung</span>
      </div>
    </section>
  </aside>
</template>
