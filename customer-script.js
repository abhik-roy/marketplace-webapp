document.addEventListener("DOMContentLoaded", () => {
  loadSearchAndItemsView();
  loadItems();

  document.getElementById("cartBtn").addEventListener("click", loadCartView);
  document
    .getElementById("ordersBtn")
    .addEventListener("click", loadOrdersView);
  document.getElementById("logoutBtn").addEventListener("click", logout);
});

function loadSearchAndItemsView() {
  console.log("this is correctly called");
  const contentDiv = document.getElementById("contentDiv");
  contentDiv.innerHTML = `
      <div id="searchBarContainer">
      <input type="text" id="searchBar" placeholder="Search items...">
      </div>
      <div id="itemsView">
        <div id="item-container">
        </div>
      </div>
    `;
  loadItems();

  const searchBar = document.getElementById("searchBar");
  searchBar.addEventListener("keyup", filterItems);
}

function addToCart(item_id) {
  console.log(`Trying to access input with ID: quantity-${item_id}`);
  console.log(document.getElementById("quantity-10"));
  const quantity = document.getElementById(`quantity-${item_id}`).value;

  //const quantity = document.getElementById(`quantity-${item_id}`).value;
  fetch("cart_operations.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ item_id, quantity }),
  })
    .then((response) => response.json())
    .then((data) => {
      console.log(data);
    })
    .catch((error) => console.error("Error:", error));
}

function loadCartView() {
  const contentDiv = document.getElementById("contentDiv");
  contentDiv.innerHTML = `
      <div id="cartContainer">
      
      <button id="backToHomeBtn">Back to Home</button>
      <h2>Your Cart</h2>
      <div id="cartBox">
      <div id="cartDetails">
      </div>
      </div>
      <button id="checkoutBtn">Checkout</button>
      </div>
    `;

  fetchCartItems();
  document
    .getElementById("backToHomeBtn")
    .addEventListener("click", loadSearchAndItemsView);

  document
    .getElementById("checkoutBtn")
    .addEventListener("click", showCheckoutPrompt);
}

function fetchCartItems() {
  fetch(`cart_operations.php`)
    .then((response) => response.json())
    .then((data) => {
      if (data.length === 0) {
        document.getElementById("cartDetails").innerHTML =
          "Your cart is empty.";
      } else {
        populateCart(data);
      }
    })
    .catch((error) => console.error("Error fetching cart:", error));
}

function populateCart(data) {
  let cartHtml = "";
  data.forEach((item) => {
    cartHtml += `
          <div class="cart-item">
            <img src="${item.image}" alt="${item.name}" width="100"/>
            <h3>${item.name}</h3>
            <p>Price: $${item.price}</p>
            <p>Quantity: ${item.quantity}</p>
            <button class="remove-button" onclick="removeFromCart(${item.item_id})">Remove</button>
          </div>
        `;
  });

  document.getElementById("cartDetails").innerHTML = cartHtml;
}

function loadOrdersView() {
  const contentDiv = document.getElementById("contentDiv");
  contentDiv.innerHTML = `
  <div id="ordersHeader">
      <h2>Your Orders</h2>
      <button id="backToHomeBtnOrders">Back to Home</button>
      
      </div>
      <div id="ordersContainer"></div>
      
    `;
  fetchPreviousOrders();

  document
    .getElementById("backToHomeBtnOrders")
    .addEventListener("click", loadSearchAndItemsView);
}

function logout() {
  fetch("logout.php")
    .then((response) => response.json())
    .then((data) => {
      if (data.status === "success") {
        window.location.href = "index.html";
      }
    });
}

function loadItems(query = "") {
  fetch(`crud.php?action=getAllItems&query=${query}`)
    .then((response) => response.json())
    .then((data) => populateItems(data))
    .catch((error) => console.error("Error fetching items:", error));
}

function filterItems() {
  const query = document.getElementById("searchBar").value;
  loadItems(query);
}

function populateItems(data) {
  let itemsHtml = "";
  data.forEach((item) => {
    itemsHtml += `
          <div class="item-box"> 
            <img src="${item.image}" alt="${item.name}" />
            <h3>${item.name}</h3>
            <div>
            <p>Quantity </p>
            <input type="number" value="1" min="1" class="item-quantity" id="quantity-${item.item_id}">
            </div>
            <button onclick="addToCart(${item.item_id}); ">Add to Cart</button>
            <button onclick="showItemDetails(${item.item_id}); ">View Details</button>
          </div>
        `;
  });

  document.getElementById("item-container").innerHTML = itemsHtml;
}

function showItemDetails(item_id) {
  fetch(`crud.php?action=getItem&item_id=${item_id}`)
    .then((response) => response.json())
    .then((data) => {
      const detailsBox = document.createElement("div");
      detailsBox.id = "detailsBox";
      detailsBox.innerHTML = `
            <h3>${data.name}</h3>
            <img src="${data.image}" alt="${data.name}" />
            <p>${data.description}</p>
            <p>Price: ${data.price}$</p>
            <button id="closeBtn">X</button>
          `;

      document.body.appendChild(detailsBox);

      document.getElementById("closeBtn").addEventListener("click", () => {
        document.body.removeChild(detailsBox);
      });
    })
    .catch((error) => console.error("Error fetching item details:", error));
}

function showCheckoutPrompt() {
  const checkoutBox = document.createElement("div");
  checkoutBox.id = "checkoutBox";
  checkoutBox.innerHTML = `
      <h3>Are you sure you want to place this order?</h3>
      <button id="yesBtn">Yes</button>
      <button id="noBtn">No</button>
    `;

  document.body.appendChild(checkoutBox);

  document.getElementById("yesBtn").addEventListener("click", performCheckout);
  document.getElementById("noBtn").addEventListener("click", () => {
    document.body.removeChild(checkoutBox);
  });
}

function performCheckout() {
  fetch("cart_operations.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ action: "checkout" }),
  })
    .then((response) => response.text())
    .then((text) => {
      console.log("Raw Response:", text); // log the raw response
      return JSON.parse(text);
    })
    .then((data) => {
      if (data.status === "Checkout successful") {
        // remove the popup box on checkout
        const checkoutBox = document.getElementById("checkoutBox");
        if (checkoutBox) {
          document.body.removeChild(checkoutBox);
        }
      } else {
        alert("Checkout failed.");
      }
      loadCartView();
    })
    .catch((error) => console.error("Error:", error));
}

function fetchPreviousOrders() {
  fetch("cart_operations.php?action=previousOrders", {
    method: "GET",
  })
    .then((response) => response.json())
    .then((orders) => {
      console.log(orders);
      const ordersContainer = document.getElementById("ordersContainer");
      let currentOrderNumber = null;
      let orderDiv = null;
      let total = 0;

      orders.forEach((order) => {
        if (currentOrderNumber !== order.order_number) {
          // close the previous order div
          if (orderDiv) {
            orderDiv.innerHTML += `<p>Total: $${total.toFixed(2)}</p>`;
            ordersContainer.appendChild(orderDiv);
          }

          orderDiv = document.createElement("div");
          orderDiv.classList.add("order");
          orderDiv.innerHTML = `<h2># Order Number: ${order.order_number}</h2>`;
          total = 0; // reset total price
          currentOrderNumber = order.order_number;
        }

        // additems to the current order
        const itemTotal = order.price * order.quantity;
        total += itemTotal;
        orderDiv.innerHTML += `
            <div class="item">
              <img src="${order.image}" alt="${
          order.name
        }" width="50" height="50">
              <p>${order.name}</p>
              <p>Quantity: ${order.quantity}</p>
              <p>Price: $${itemTotal.toFixed(2)}</p>
            </div>
          `;
      });

      if (orderDiv) {
        orderDiv.innerHTML += `<p>Total: $${total.toFixed(2)}</p>`;
        ordersContainer.appendChild(orderDiv);
      }
    });
}

function removeFromCart(item_id) {
  fetch("cart_operations.php", {
    method: "DELETE",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ item_id }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status === "Item removed from cart") {
        // refresh display
        fetchCartItems();
      } else {
        alert("Failed to remove item from cart.");
      }
    })
    .catch((error) => console.error("Error:", error));
}
