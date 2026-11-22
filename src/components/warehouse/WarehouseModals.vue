<script setup lang="ts">
import type {
  CompletionRequestForm,
  InventoryItem,
  PurchaseRequestForm,
  WarehouseRequest,
} from '@/types/warehouse'

defineProps<{
  showPurchaseModal: boolean
  showCompletionModal: boolean
  items: InventoryItem[]
  requests: WarehouseRequest[]
  purchaseForm: PurchaseRequestForm
  completionForm: CompletionRequestForm
  purchaseTotal: number
  completionTotal: number
}>()

const emit = defineEmits<{
  'close-purchase': []
  'close-completion': []
  'submit-purchase': []
  'submit-completion': []
}>()

function formatCurrency(value: number) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(value)
}

function updateQuantity(
  form: PurchaseRequestForm | CompletionRequestForm,
  amount: number,
) {
  form.quantity = Math.max(1, form.quantity + amount)
}
</script>

<template>
  <!-- PURCHASE REQUEST MODAL -->
  <div
    v-if="showPurchaseModal"
    class="warehouse-modal-overlay"
    @click.self="emit('close-purchase')"
  >
    <section
      class="warehouse-modal"
      role="dialog"
      aria-modal="true"
      aria-labelledby="purchase-modal-title"
    >
      <div class="warehouse-modal-header">
        <div class="warehouse-modal-title-group">
          <span class="warehouse-modal-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h8.5a2 2 0 0 0 1.9-1.4L21 8H7M10 11h8m-4-4v8m-4 5h.01M18 20h.01" /></svg>
          </span>
          <div>
            <h2 id="purchase-modal-title">Form Request Pembelian Stok</h2>
            <p>Pengajuan restock bahan menipis langsung ke Purchasing / Manager</p>
          </div>
        </div>

        <button
          type="button"
          class="warehouse-modal-close"
          aria-label="Tutup"
          @click="emit('close-purchase')"
        >
          ×
        </button>
      </div>

      <form
        class="warehouse-modal-form"
        @submit.prevent="emit('submit-purchase')"
      >
        <label class="warehouse-form-field">
          <span>Nama Barang / Bahan Baku:</span>
          <select v-model="purchaseForm.itemId" required>
            <option :value="null" disabled>Pilih bahan baku</option>
            <option
              v-for="item in items"
              :key="item.id"
              :value="item.id"
            >
              {{ item.name }}
            </option>
          </select>
        </label>

        <div class="warehouse-modal-quantity-summary">
          <div class="warehouse-form-field">
            <span>Kuantitas Jumlah Pembelian:</span>
            <div class="warehouse-quantity-control">
              <button
                type="button"
                aria-label="Kurangi jumlah"
                @click="updateQuantity(purchaseForm, -1)"
              >
                −
              </button>
              <input
                v-model.number="purchaseForm.quantity"
                type="number"
                min="1"
                required
              />
              <button
                type="button"
                aria-label="Tambah jumlah"
                @click="updateQuantity(purchaseForm, 1)"
              >
                +
              </button>
            </div>
          </div>

          <div class="warehouse-form-summary">
            <label class="warehouse-summary-price">
              <span>Est. Harga Satuan:</span>
              <input
                v-model.number="purchaseForm.unitPrice"
                type="number"
                min="0"
                placeholder="Harga satuan"
                required
              />
            </label>
            <div class="warehouse-form-total">
              <span>Estimasi Total:</span>
              <strong>{{ formatCurrency(purchaseTotal) }}</strong>
            </div>
          </div>
        </div>

        <label class="warehouse-form-field">
          <span>Catatan Tambahan untuk Purchasing / Manager:</span>
          <textarea
            v-model="purchaseForm.notes"
            rows="3"
            placeholder="Tambahkan catatan jika diperlukan..."
          ></textarea>
        </label>

        <div class="warehouse-modal-actions">
          <button
            type="button"
            class="warehouse-modal-cancel"
            @click="emit('close-purchase')"
          >
            Batal / Tutup
          </button>

          <button type="submit" class="warehouse-modal-submit">
            <span aria-hidden="true">➤</span>
            Kirim Pengajuan Request Stok
          </button>
        </div>
      </form>
    </section>
  </div>

  <!-- COMPLETION REQUEST MODAL -->
  <div
    v-if="showCompletionModal"
    class="warehouse-modal-overlay"
    @click.self="emit('close-completion')"
  >
    <section
      class="warehouse-modal"
      role="dialog"
      aria-modal="true"
      aria-labelledby="completion-modal-title"
    >
      <div class="warehouse-modal-header">
        <div class="warehouse-modal-title-group">
          <span class="warehouse-modal-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h8.5a2 2 0 0 0 1.9-1.4L21 8H7M10 11h8m-4-4v8m-4 5h.01M18 20h.01" /></svg>
          </span>
          <div>
            <h2 id="completion-modal-title">Form Request Penyelesaian Stok</h2>
            <p>Pengajuan restock bahan menipis langsung ke Purchasing / Manager</p>
          </div>
        </div>

        <button
          type="button"
          class="warehouse-modal-close"
          aria-label="Tutup"
          @click="emit('close-completion')"
        >
          ×
        </button>
      </div>

      <form
        class="warehouse-modal-form"
        @submit.prevent="emit('submit-completion')"
      >
        <label class="warehouse-form-field">
          <span>Nama Barang / Bahan Baku:</span>
          <div class="warehouse-readonly-item">
            {{ requests.find((request) => request.id === completionForm.requestId)?.itemName ?? 'Pilih request' }}
          </div>
        </label>

        <select
          v-model="completionForm.requestId"
          class="warehouse-visually-hidden"
          aria-hidden="true"
          tabindex="-1"
          required
        >
          <option :value="null" disabled>Pilih request</option>
          <option
            v-for="request in requests.filter((item) => item.status === 'Approved')"
            :key="request.id"
            :value="request.id"
          >
            {{ request.itemName }} — {{ request.quantity }} {{ request.unit }}
          </option>
        </select>

        <div class="warehouse-modal-quantity-summary">
          <div class="warehouse-form-field">
            <span>Kuantitas Jumlah Diterima:</span>
            <div class="warehouse-quantity-control">
              <button
                type="button"
                aria-label="Kurangi jumlah"
                @click="updateQuantity(completionForm, -1)"
              >
                −
              </button>
              <input
                v-model.number="completionForm.quantity"
                type="number"
                min="1"
                required
              />
              <button
                type="button"
                aria-label="Tambah jumlah"
                @click="updateQuantity(completionForm, 1)"
              >
                +
              </button>
            </div>
          </div>

          <div class="warehouse-form-summary">
            <label class="warehouse-summary-price">
              <span>Harga Satuan Aktual:</span>
              <input
                v-model.number="completionForm.unitPrice"
                type="number"
                min="0"
                placeholder="Harga aktual"
                required
              />
            </label>
            <div class="warehouse-form-total">
              <span>Total Penerimaan:</span>
              <strong>{{ formatCurrency(completionTotal) }}</strong>
            </div>
          </div>
        </div>

        <label class="warehouse-form-field">
          <span>Catatan Tambahan untuk Purchasing / Manager:</span>
          <textarea
            v-model="completionForm.notes"
            rows="3"
            placeholder="Tambahkan catatan penerimaan..."
          ></textarea>
        </label>

        <div class="warehouse-modal-actions">
          <button
            type="button"
            class="warehouse-modal-cancel"
            @click="emit('close-completion')"
          >
            Batal / Tutup
          </button>

          <button type="submit" class="warehouse-modal-submit">
            <span aria-hidden="true">➤</span>
            Laporkan Penerimaan Barang
          </button>
        </div>
      </form>
    </section>
  </div>
</template>
