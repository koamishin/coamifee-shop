<script>
    // Initialize cart state
    let cartItems = [];

    // Define cart functions globally FIRST
    window.addToCart = function(productId, product_name, price, variant_id = null, variant_name = null) {
        console.log("Adding to cart:", { productId, product_name, price, variant_id, variant_name });

        const existingItemIndex = cartItems.findIndex(item =>
            parseInt(item.product_id) === parseInt(productId) && parseInt(item.variant_id) === parseInt(variant_id)
        );

        if (existingItemIndex !== -1) {
            cartItems[existingItemIndex].quantity += 1;
        } else {
            cartItems.push({
                product_id: productId,
                product_name: product_name,
                variant_id: variant_id,
                variant_name: variant_name,
                price: price,
                quantity: 1
            });
        }

        updateCartHiddenField();
    };

    window.removeFromCart = function(productId, variant_id = null) {
        cartItems = cartItems.filter(item =>
            !(parseInt(item.product_id) === parseInt(productId) && parseInt(item.variant_id) === parseInt(variant_id))
        );
        updateCartHiddenField();
    };

    window.updateQuantity = function(productId, quantity, variant_id = null) {
        const item = cartItems.find(item =>
            parseInt(item.product_id) === parseInt(productId) && parseInt(item.variant_id) === parseInt(variant_id)
        );

        if (item) {
            item.quantity = Math.max(1, parseInt(quantity) || 1);
            updateCartHiddenField();
        }
    };

    function updateCartHiddenField() {
        const hiddenField = document.querySelector("input[name='items']");
        if (hiddenField) {
            hiddenField.value = JSON.stringify(cartItems);
            hiddenField.dispatchEvent(new Event("change", { bubbles: true }));
            hiddenField.dispatchEvent(new Event("input", { bubbles: true }));

            // Trigger form update in Filament
            setTimeout(() => {
                const event = new CustomEvent("form-updated", { detail: { name: "items" } });
                document.dispatchEvent(event);
            }, 100);
        }
    }

    console.log("Cart functions registered globally");
</script>
