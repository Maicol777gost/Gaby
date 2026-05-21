/* ============================================================
   SISTEMA DE CARRITO DE COMPRAS
   Se guarda en sessionStorage (se borra al cerrar la pestaña)
   ============================================================ */

let cart = [];

// Mostrar el carrito
function updateCartDisplay() {
  const cartContainer = document.getElementById("cart-items");
  const totalElement = document.getElementById("cart-total");

  if (!cartContainer || !totalElement) return;

  cartContainer.innerHTML = "";

  if (cart.length === 0) {
    cartContainer.innerHTML = "<p style='color:#888;'>Tu carrito está vacío 🛒</p>";
    totalElement.textContent = "Total: RD$0";
    return;
  }

  let total = 0;

  cart.forEach((item, index) => {
    const itemDiv = document.createElement("div");
    itemDiv.className = "cart-item";
    itemDiv.innerHTML = `
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
        <img src="${item.image}" alt="${item.name}" 
          style="width:80px;height:80px;object-fit:cover;border-radius:10px;">
        <div style="flex:1">
          <strong>${item.name}</strong><br>
          <span style="color:#777">RD$${item.price}</span>
        </div>
        <button class="button btn-outline" onclick="removeFromCart(${index})">Eliminar</button>
      </div>
    `;
    cartContainer.appendChild(itemDiv);
    total += parseFloat(item.price);
  });

  totalElement.textContent = "Total: RD$" + total.toLocaleString("es-DO");
}

// Eliminar un producto
function removeFromCart(index) {
  cart.splice(index, 1);
  sessionStorage.setItem("cart", JSON.stringify(cart));
  updateCartDisplay();
}

/* ============================================================
   AGREGAR AL CARRITO
   ============================================================ */

document.addEventListener("DOMContentLoaded", () => {
  const addButtons = document.querySelectorAll(".add-to-cart");

  addButtons.forEach(btn => {
    btn.addEventListener("click", () => {
      const name = btn.dataset.name;
      const price = btn.dataset.price;
      const card = btn.closest(".card");
      const img = card ? card.querySelector("img").src : "imagenes/imagen de logo.png";

      cart.push({ name, price, image: img });
      sessionStorage.setItem("cart", JSON.stringify(cart));
      alert(`${name} agregado al carrito 🛍️`);
    });
  });

  // Recuperar carrito guardado
  const savedCart = sessionStorage.getItem("cart");
  if (savedCart) {
    cart = JSON.parse(savedCart);
    updateCartDisplay();
  }

  // Botón vaciar carrito
  const clearBtn = document.getElementById("clear-cart");
  if (clearBtn) {
    clearBtn.addEventListener("click", () => {
      cart = [];
      sessionStorage.removeItem("cart");
      updateCartDisplay();
    });
  }
});

// Comprar
const buyBtn = document.getElementById("buy-cart");
if (buyBtn) {
  buyBtn.addEventListener("click", () => {
    if (cart.length === 0) {
      alert("Tu carrito está vacío 🛒. Agrega productos antes de comprar.");
      return;
    }

    const total = cart.reduce((sum, item) => sum + parseFloat(item.price), 0);

    alert(`¡Gracias por tu compra! 🛍️
Total: RD$${total.toLocaleString("es-DO")}
Tu pedido ha sido enviado correctamente.`);

    cart = [];
    sessionStorage.removeItem("cart");
    updateCartDisplay();
  });
}

const form = document.getElementById("form-carrito");

if(form){
    form.addEventListener("submit", function(e){
        e.preventDefault();

        let formData = new FormData(this);

        fetch("carrito.php", {
            method: "POST",
            body: formData
        })
        .then(res => res.text())
        .then(res => {

            if(res.trim() === "ok"){

                // 🔢 contador
                let count = document.getElementById("cart-count");
                if(count){
                    count.textContent = parseInt(count.textContent) + 1;
                }

                // 🔥 cambiar botón
                form.innerHTML = `
                    <button type="button" class="button btn-outline" disabled>
                        ✔ Ya agregado
                    </button>
                `;

            }
        });
    });
}
