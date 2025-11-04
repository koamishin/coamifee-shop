class PosStandalone {
  constructor() {
    this.cart = {};
    this.categories = [];
    this.products = [];
    this.currentCategory = 0;
    this.searchTerm = "";
    this.addOns = [];
    this.totals = { subtotal: 0, tax: 0, total: 0 };
    this.isProcessing = false;

    this.init();
  }

  async init() {
    await this.loadCategories();
    await this.loadProducts();
    await this.loadStats();
    this.setupEventListeners();
    this.render();
  }

  async loadCategories() {
    try {
      const response = await fetch("/pos/api/categories");
      const data = await response.json();
      this.categories = [
        { id: 0, name: "All", icon: "grid" },
        ...data.categories,
      ];
    } catch (error) {
      console.error("Failed to load categories:", error);
      this.categories = [{ id: 0, name: "All", icon: "grid" }];
    }
  }

  async loadProducts() {
    const params = new URLSearchParams();
    if (this.currentCategory > 0)
      params.append("category_id", this.currentCategory);
    if (this.searchTerm) params.append("search", this.searchTerm);

    try {
      const response = await fetch(`/pos/api/products?${params}`);
      const data = await response.json();
      this.products = data.products;
      this.renderProducts();
    } catch (error) {
      console.error("Failed to load products:", error);
      this.products = [];
      this.renderProducts();
    }
  }

  async loadStats() {
    try {
      const [statsResponse, ordersResponse] = await Promise.all([
        fetch("/pos/api/stats"),
        fetch("/pos/api/recent-orders"),
      ]);

      const statsData = await statsResponse.json();
      const ordersData = await ordersResponse.json();

      this.updateStats(statsData.stats);
      this.renderRecentOrders(ordersData.recent_orders);
    } catch (error) {
      console.error("Failed to load stats:", error);
    }
  }

  setupEventListeners() {
    // Category filtering
    document.addEventListener("click", (e) => {
      if (e.target.matches("[data-category]")) {
        this.currentCategory = parseInt(e.target.dataset.category);
        this.loadProducts();
        this.updateActiveCategory();
      }
    });

    // Search
    const searchInput = document.getElementById("pos-search");
    if (searchInput) {
      searchInput.addEventListener("input", (e) => {
        this.searchTerm = e.target.value;
        this.loadProducts();
      });
    }

    // Add to cart
    document.addEventListener("click", (e) => {
      if (
        e.target.matches("[data-add-to-cart]") ||
        e.target.closest("[data-add-to-cart]")
      ) {
        const element = e.target.matches("[data-add-to-cart]")
          ? e.target
          : e.target.closest("[data-add-to-cart]");
        const productId = parseInt(element.dataset.productId);
        this.addToCart(productId);
      }
    });

    // Cart management
    document.addEventListener("click", (e) => {
      if (e.target.matches("[data-remove-from-cart]")) {
        const productId = parseInt(e.target.dataset.productId);
        this.removeFromCart(productId);
      }

      if (e.target.matches("[data-increment-cart]")) {
        const productId = parseInt(e.target.dataset.productId);
        this.incrementCartItem(productId);
      }

      if (e.target.matches("[data-decrement-cart]")) {
        const productId = parseInt(e.target.dataset.productId);
        this.decrementCartItem(productId);
      }
    });

    // Checkout
    document.addEventListener("click", (e) => {
      if (e.target.matches("[data-checkout]")) {
        this.openCheckout();
      }
    });

    // Add-ons
    document.addEventListener("click", (e) => {
      if (e.target.matches("[data-add-addon]")) {
        this.addAddOn();
      }

      if (e.target.matches("[data-remove-addon]")) {
        const index = parseInt(e.target.dataset.index);
        this.removeAddOn(index);
      }
    });

    // Quick items
    document.addEventListener("click", (e) => {
      if (
        e.target.matches("[data-quick-add]") ||
        e.target.closest("[data-quick-add]")
      ) {
        const element = e.target.matches("[data-quick-add]")
          ? e.target
          : e.target.closest("[data-quick-add]");
        const productId = parseInt(element.dataset.productId);
        this.quickAdd(productId);
      }
    });
  }

  async addToCart(productId) {
    if (this.isProcessing) return;

    const product = this.products.find((p) => p.id === productId);
    if (!product) return;

    if (!product.availability.can_produce) {
      this.showNotification(
        `Cannot add ${product.name}: Insufficient ingredients`,
        "error",
      );
      return;
    }

    if (this.cart[productId]) {
      if (!product.availability.can_increment) {
        this.showNotification(
          `Cannot add more ${product.name}: Insufficient ingredients`,
          "error",
        );
        return;
      }
      this.cart[productId].quantity += 1;
    } else {
      this.cart[productId] = {
        id: product.id,
        name: product.name,
        price: product.price,
        quantity: 1,
        image: product.image,
      };
    }

    await this.updateCartDisplay();
    this.showNotification(`${product.name} added to cart`, "success");
  }

  removeFromCart(productId) {
    delete this.cart[productId];
    this.updateCartDisplay();
  }

  incrementCartItem(productId) {
    if (this.cart[productId]) {
      this.cart[productId].quantity += 1;
      this.updateCartDisplay();
    }
  }

  decrementCartItem(productId) {
    if (this.cart[productId]) {
      if (this.cart[productId].quantity > 1) {
        this.cart[productId].quantity -= 1;
      } else {
        delete this.cart[productId];
      }
      this.updateCartDisplay();
    }
  }

  addAddOn() {
    this.addOns.push({ label: "", amount: 0 });
    this.updateCartDisplay();
  }

  removeAddOn(index) {
    this.addOns.splice(index, 1);
    this.updateCartDisplay();
  }

  async quickAdd(productId) {
    await this.addToCart(productId);
  }

  async updateCartDisplay() {
    const cartArray = Object.values(this.cart);

    try {
      const response = await fetch("/pos/api/calculate-totals", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute("content"),
        },
        body: JSON.stringify({
          cart: cartArray,
          add_ons: this.addOns,
        }),
      });

      const data = await response.json();
      this.totals = data.totals;

      this.renderCart();
      this.updateCartCount(data.cart_count);
    } catch (error) {
      console.error("Failed to calculate totals:", error);
    }
  }

  async openCheckout() {
    if (Object.keys(this.cart).length === 0) {
      this.showNotification("Cart is empty", "error");
      return;
    }

    const modal = document.getElementById("checkout-modal");
    if (modal) {
      modal.classList.remove("hidden");
      modal.classList.add("flex");
    }
  }

  closeCheckoutModal() {
    const modal = document.getElementById("checkout-modal");
    if (modal) {
      modal.classList.add("hidden");
      modal.classList.remove("flex");
    }
  }

  closeSuccessModal() {
    const modal = document.getElementById("success-modal");
    if (modal) {
      modal.classList.add("hidden");
      modal.classList.remove("flex");
    }
  }

  async processCheckout() {
    if (this.isProcessing) return;

    this.isProcessing = true;

    const form = document.getElementById("checkout-form");
    const formData = new FormData(form);

    const orderData = {
      cart: Object.values(this.cart),
      customer_name: formData.get("customer_name"),
      order_type: formData.get("order_type"),
      table_number: formData.get("table_number"),
      payment_method: formData.get("payment_method"),
      add_ons: this.addOns,
      notes: formData.get("notes"),
    };

    try {
      const response = await fetch("/pos/api/checkout", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute("content"),
        },
        body: JSON.stringify(orderData),
      });

      const data = await response.json();

      if (data.success) {
        this.showNotification(data.message, "success");
        this.cart = {};
        this.addOns = [];
        await this.updateCartDisplay();
        await this.loadStats();

        this.closeCheckoutModal();
        this.showSuccessModal();

        this.showSuccessAnimation(data.order);
      } else {
        this.showNotification(data.error, "error");
      }
    } catch (error) {
      console.error("Checkout failed:", error);
      this.showNotification("Checkout failed", "error");
    } finally {
      this.isProcessing = false;
    }
  }

  showSuccessModal() {
    const modal = document.getElementById("success-modal");
    if (modal) {
      modal.classList.remove("hidden");
      modal.classList.add("flex");
    }
  }

  updateActiveCategory() {
    document.querySelectorAll("[data-category]").forEach((el) => {
      el.classList.remove("bg-blue-500", "text-white");
      if (parseInt(el.dataset.category) === this.currentCategory) {
        el.classList.add("bg-blue-500", "text-white");
      }
    });
  }

  updateStats(stats) {
    const elements = {
      "today-orders": stats.today_orders,
      "today-sales": `$${stats.today_sales.toFixed(2)}`,
      "low-stock-count": stats.low_stock_count,
    };

    Object.entries(elements).forEach(([id, value]) => {
      const element = document.getElementById(id);
      if (element) element.textContent = value;
    });
  }

  render() {
    this.renderCategories();
    this.renderProducts();
    this.renderCart();
    this.renderQuickItems();
  }

  renderCategories() {
    const container = document.getElementById("category-sidebar");
    if (!container) return;

    container.innerHTML = this.categories
      .map(
        (category) => `
            <button
                data-category="${category.id}"
                class="w-full text-left px-4 py-2 rounded-lg hover:bg-gray-100 transition-colors ${category.id === this.currentCategory ? "bg-blue-500 text-white" : "text-gray-700"}"
            >
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-gray-200 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                    </div>
                    <span class="font-medium">${category.name}</span>
                </div>
            </button>
        `,
      )
      .join("");
  }

  renderProducts() {
    const container = document.getElementById("products-grid");
    if (!container) return;

    if (this.products.length === 0) {
      container.innerHTML = `
                <div class="col-span-full text-center py-12">
                    <div class="text-gray-400 mb-4">
                        <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2M4 13h2m13-8l-4 4m0 0l-4-4m4 4V3"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No products found</h3>
                    <p class="text-gray-500">Try adjusting your search or category filter.</p>
                </div>
            `;
      return;
    }

    container.innerHTML = this.products
      .map(
        (product) => `
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow ${!product.availability.can_produce ? "opacity-60" : ""}">
                <div class="aspect-square bg-gray-100 relative">
                    ${
                      product.image
                        ? `
                        <img src="${product.image}" alt="${product.name}" class="w-full h-full object-cover">
                    `
                        : `
                        <div class="w-full h-full flex items-center justify-center text-gray-400">
                            <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2M4 13h2m13-8l-4 4m0 0l-4-4m4 4V3"/>
                            </svg>
                        </div>
                    `
                    }

                    ${
                      !product.availability.can_produce
                        ? `
                        <div class="absolute inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center">
                            <span class="bg-red-500 text-white px-3 py-1 rounded-full text-sm font-medium">
                                Out of Stock
                            </span>
                        </div>
                    `
                        : ""
                    }

                    ${
                      product.availability.max_quantity <= 5 &&
                      product.availability.max_quantity > 0
                        ? `
                        <div class="absolute top-2 right-2 bg-orange-500 text-white px-2 py-1 rounded-full text-xs font-medium">
                            Low Stock
                        </div>
                    `
                        : ""
                    }
                </div>

                <div class="p-4">
                    <h3 class="font-semibold text-gray-900 mb-1">${product.name}</h3>
                    <p class="text-sm text-gray-600 mb-2 line-clamp-2">${product.description || "No description"}</p>
                    <div class="flex items-center justify-between">
                        <span class="text-lg font-bold text-green-600">$${product.price.toFixed(2)}</span>
                        <button
                            data-add-to-cart
                            data-product-id="${product.id}"
                            class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            ${!product.availability.can_produce ? "disabled" : ""}
                        >
                            Add to Cart
                        </button>
                    </div>
                </div>
            </div>
        `,
      )
      .join("");
  }

  renderCart() {
    const container = document.getElementById("cart-items");
    if (!container) return;

    const cartItems = Object.values(this.cart);

    if (cartItems.length === 0) {
      container.innerHTML = `
                <div class="text-center py-12">
                    <div class="text-gray-400 mb-4">
                        <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-1.5 6H19M7 13v6a2 2 0 002 2h8a2 2 0 002-2v-6"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Cart is empty</h3>
                    <p class="text-gray-500">Add some products to get started.</p>
                </div>
            `;
    } else {
      container.innerHTML = cartItems
        .map(
          (item) => `
                <div class="flex items-center gap-4 p-4 border-b border-gray-200 last:border-b-0">
                    <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        ${
                          item.image
                            ? `
                            <img src="${item.image}" alt="${item.name}" class="w-full h-full object-cover rounded-lg">
                        `
                            : `
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2M4 13h2m13-8l-4 4m0 0l-4-4m4 4V3"/>
                            </svg>
                        `
                        }
                    </div>

                    <div class="flex-1 min-w-0">
                        <h4 class="font-medium text-gray-900 truncate">${item.name}</h4>
                        <p class="text-sm text-gray-600">$${item.price.toFixed(2)} each</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            data-decrement-cart
                            data-product-id="${item.id}"
                            class="w-8 h-8 bg-gray-200 hover:bg-gray-300 rounded-full flex items-center justify-center transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                            </svg>
                        </button>

                        <span class="w-8 text-center font-medium">${item.quantity}</span>

                        <button
                            data-increment-cart
                            data-product-id="${item.id}"
                            class="w-8 h-8 bg-blue-500 hover:bg-blue-600 text-white rounded-full flex items-center justify-center transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                        </button>

                        <button
                            data-remove-from-cart
                            data-product-id="${item.id}"
                            class="w-8 h-8 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center transition-colors ml-2"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="text-right">
                        <p class="font-semibold text-gray-900">$${(item.price * item.quantity).toFixed(2)}</p>
                    </div>
                </div>
            `,
        )
        .join("");
    }

    // Render totals
    const totalsContainer = document.getElementById("cart-totals");
    if (totalsContainer) {
      totalsContainer.innerHTML = `
                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span>Subtotal</span>
                        <span>$${this.totals.subtotal.toFixed(2)}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span>Tax</span>
                        <span>$${this.totals.tax.toFixed(2)}</span>
                    </div>
                    <div class="border-t pt-2 flex justify-between font-semibold text-lg">
                        <span>Total</span>
                        <span>$${this.totals.total.toFixed(2)}</span>
                    </div>
                </div>
            `;
    }

    // Render add-ons
    const addOnsContainer = document.getElementById("cart-addons");
    if (addOnsContainer) {
      addOnsContainer.innerHTML = `
                <div class="space-y-2">
                    <button
                        data-add-addon
                        class="w-full px-4 py-2 border-2 border-dashed border-gray-300 text-gray-600 rounded-lg hover:border-gray-400 transition-colors"
                    >
                        + Add On
                    </button>
                    ${this.addOns
                      .map(
                        (addon, index) => `
                        <div class="flex gap-2">
                            <input
                                type="text"
                                placeholder="Add-on name"
                                value="${addon.label}"
                                class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm"
                                data-addon-label="${index}"
                            >
                            <input
                                type="number"
                                step="0.01"
                                placeholder="0.00"
                                value="${addon.amount}"
                                class="w-24 px-3 py-2 border border-gray-300 rounded-lg text-sm"
                                data-addon-amount="${index}"
                            >
                            <button
                                data-remove-addon
                                data-index="${index}"
                                class="px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    `,
                      )
                      .join("")}
                </div>
            `;
    }
  }

  renderQuickItems() {
    // This will be populated by additional API call if needed
    const container = document.getElementById("quick-items");
    if (container && container.children.length === 0) {
      container.innerHTML = `
                <div class="text-center py-8">
                    <p class="text-gray-500">Quick items will appear here</p>
                </div>
            `;
    }
  }

  renderRecentOrders(orders) {
    const container = document.getElementById("recent-orders");
    if (!container) return;

    if (orders.length === 0) {
      container.innerHTML = `
                <div class="text-center py-8">
                    <p class="text-gray-500">No recent orders</p>
                </div>
            `;
      return;
    }

    container.innerHTML = orders
      .map(
        (order) => `
            <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                <div>
                    <p class="font-medium">#${order.order_number}</p>
                    <p class="text-sm text-gray-600">${order.customer_name}</p>
                </div>
                <div class="text-right">
                    <p class="font-semibold">$${order.total.toFixed(2)}</p>
                    <p class="text-sm text-gray-600">${order.items_count} items</p>
                </div>
            </div>
        `,
      )
      .join("");
  }

  updateCartCount(count) {
    const badge = document.getElementById("cart-count");
    if (badge) {
      badge.textContent = count;
      badge.classList.toggle("hidden", count === 0);
    }
  }

  showNotification(message, type = "info") {
    // Simple notification - you might want to use a more sophisticated notification system
    const notification = document.createElement("div");
    notification.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg text-white transition-all duration-300 ${
      type === "success"
        ? "bg-green-500"
        : type === "error"
          ? "bg-red-500"
          : "bg-blue-500"
    }`;
    notification.textContent = message;

    document.body.appendChild(notification);

    setTimeout(() => {
      notification.remove();
    }, 3000);
  }

  showSuccessAnimation(orderData) {
    // Show success animation or receipt modal
    const modal = document.getElementById("success-modal");
    if (modal) {
      modal.classList.remove("hidden");
      // Populate success modal with order data
    }
  }
}

// Initialize POS when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
  new PosStandalone();
});
