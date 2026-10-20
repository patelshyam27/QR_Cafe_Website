// ============================================================================
// QR CAFE — CLIENT JAVASCRIPT SYSTEM
// Cart management, live search, category filtering & interactive UI
// ============================================================================

const CafeApp = {
  cart: [],

  init() {
    this.loadCart();
    this.updateCartBadge();
    this.bindEvents();
  },

  loadCart() {
    try {
      const saved = localStorage.getItem('qr_cafe_cart');
      this.cart = saved ? JSON.parse(saved) : [];
    } catch (e) {
      this.cart = [];
    }
  },

  saveCart() {
    localStorage.setItem('qr_cafe_cart', JSON.stringify(this.cart));
    this.updateCartBadge();
  },

  addToCart(id, name, price) {
    const existing = this.cart.find(item => item.id === id);
    if (existing) {
      existing.qty += 1;
    } else {
      this.cart.push({ id, name, price: parseFloat(price), qty: 1 });
    }
    this.saveCart();
    this.showToast(`Added "${name}" to your order! ☕`);
  },

  updateQty(id, delta) {
    const item = this.cart.find(i => i.id === id);
    if (!item) return;
    item.qty += delta;
    if (item.qty <= 0) {
      this.cart = this.cart.filter(i => i.id !== id);
    }
    this.saveCart();
    this.renderCartTable();
  },

  clearCart() {
    this.cart = [];
    this.saveCart();
    this.renderCartTable();
  },

  updateCartBadge() {
    const countEl = document.getElementById('cartItemCount');
    if (countEl) {
      const totalQty = this.cart.reduce((sum, item) => sum + item.qty, 0);
      countEl.innerText = totalQty;
      const floatingBtn = document.getElementById('floatingCartBtn');
      if (floatingBtn) {
        floatingBtn.style.display = totalQty > 0 ? 'flex' : 'none';
      }
    }
  },

  renderCartTable() {
    const container = document.getElementById('cartItemsList');
    const totalEl = document.getElementById('cartTotalAmount');
    const inputEl = document.getElementById('cartDataInput');
    if (!container) return;

    if (this.cart.length === 0) {
      container.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:2rem;color:#786f66;">Your cart is currently empty. Scan a table QR code or choose items from the menu!</td></tr>`;
      if (totalEl) totalEl.innerText = '₹0.00';
      if (inputEl) inputEl.value = '[]';
      return;
    }

    let subtotal = 0;
    let html = '';
    this.cart.forEach(item => {
      const itemTotal = item.price * item.qty;
      subtotal += itemTotal;
      html += `
        <tr>
          <td style="font-weight:700;">${item.name}</td>
          <td>₹${item.price.toFixed(2)}</td>
          <td>
            <div style="display:inline-flex;align-items:center;gap:8px;">
              <button type="button" class="btn btn-sm btn-dark" onclick="CafeApp.updateQty(${item.id}, -1)">-</button>
              <span style="font-weight:700;width:24px;text-align:center;">${item.qty}</span>
              <button type="button" class="btn btn-sm btn-dark" onclick="CafeApp.updateQty(${item.id}, 1)">+</button>
            </div>
          </td>
          <td style="font-weight:700;">₹${itemTotal.toFixed(2)}</td>
          <td><button type="button" style="background:none;border:none;cursor:pointer;color:#ef4444;font-size:1.1rem;" onclick="CafeApp.updateQty(${item.id}, -${item.qty})">✕</button></td>
        </tr>
      `;
    });

    container.innerHTML = html;
    if (totalEl) totalEl.innerText = `₹${subtotal.toFixed(2)}`;
    if (inputEl) inputEl.value = JSON.stringify(this.cart);
  },

  showToast(msg) {
    let toast = document.getElementById('cafeToast');
    if (!toast) {
      toast = document.createElement('div');
      toast.id = 'cafeToast';
      toast.style.cssText = `
        position: fixed;
        bottom: 2rem;
        left: 50%;
        transform: translateX(-50%);
        background: #432818;
        color: #fff;
        padding: 0.85rem 1.6rem;
        border-radius: 9999px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        z-index: 1000;
        font-weight: 700;
        font-size: 0.95rem;
        transition: opacity 0.3s ease;
      `;
      document.body.appendChild(toast);
    }
    toast.innerText = msg;
    toast.style.opacity = '1';
    clearTimeout(this._toastTimeout);
    this._toastTimeout = setTimeout(() => {
      toast.style.opacity = '0';
    }, 2500);
  },

  bindEvents() {
    // Category tabs filter
    const tabs = document.querySelectorAll('.tab-btn');
    tabs.forEach(tab => {
      tab.addEventListener('click', (e) => {
        tabs.forEach(t => t.classList.remove('active'));
        e.currentTarget.classList.add('active');
        const cat = e.currentTarget.dataset.category;
        const cards = document.querySelectorAll('.menu-card');
        cards.forEach(card => {
          if (cat === 'all' || card.dataset.category === cat) {
            card.style.display = 'flex';
          } else {
            card.style.display = 'none';
          }
        });
      });
    });

    // Live search input
    const searchInput = document.getElementById('menuSearch');
    if (searchInput) {
      searchInput.addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase().trim();
        const cards = document.querySelectorAll('.menu-card');
        cards.forEach(card => {
          const name = card.querySelector('h3').innerText.toLowerCase();
          const desc = card.querySelector('p').innerText.toLowerCase();
          if (name.includes(query) || desc.includes(query)) {
            card.style.display = 'flex';
          } else {
            card.style.display = 'none';
          }
        });
      });
    }

    // Load cart items if on checkout page
    if (document.getElementById('cartItemsList')) {
      this.renderCartTable();
    }
  }
};

document.addEventListener('DOMContentLoaded', () => CafeApp.init());
