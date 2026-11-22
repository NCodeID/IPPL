<template>
  <header class="warehouse-topbar">
    <button class="warehouse-notification" type="button" aria-label="Notifikasi">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" />
      </svg>
      <span class="warehouse-notification-dot"></span>
    </button>

    <div class="warehouse-page-badge">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <rect x="4" y="5" width="16" height="15" rx="1" />
        <path d="M8 3v4m8-4v4M4 10h16" />
      </svg>
      Gudang &amp; Stok
      <span class="warehouse-badge-dot"></span>
    </div>

    <div class="warehouse-user">
      <div class="warehouse-user-info">
        <strong>Dimas Arya</strong>
        <span>Supervisor On-Duty</span>
      </div>
      <div class="warehouse-user-avatar" aria-hidden="true">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3" /><path d="M6 19a6 6 0 0 1 12 0" /></svg>
      </div>
    </div>
  </header>
</template>
