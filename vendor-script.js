function loadSection(section) {
  const content = document.getElementById("content");

  switch (section) {
    case "addItem":
      content.innerHTML = `
      
            <form id="add-item-form" action="crud.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create">
                <label for="name">Item Name</label>
                <input type="text" id="name" name="name">
                
                <label for="description">Description</label>
                <input type="text" id="description" name="description">
                
                <label for="price">Price</label>
                <input type="number" id="price" name="price">
                
                <label for="stock">Stock</label>
                <input type="number" id="stock" name="stock">

                <label for="image">Image</label>
                <input type="file" name="image">

                <input type="submit" value="Add Item">
            </form>
            
            `;
      break;
    case "viewAndUpdateItems":
      //get the data from the backend
      fetch("crud.php?action=read")
        .then((response) => response.json())
        .then((data) => {
          let itemHTML = "<h2>Your Items</h2>";
          //individually populate each item
          data.forEach((item) => {
            itemHTML += `
                    <div>
                        <h3>${item.name}</h3>
                        <p>${item.description}</p>
                        <p>Price: ${item.price}</p>
                        <p>Stock: ${item.stock}</p>
                        <img src="${item.image}">
                        <button onclick="console.log('Button clicked');loadUpdateForm(${item.item_id})" class="update-button">Update</button>
                        <button onclick="console.log('Delete button clicked');deleteItem(${item.item_id})" class="delete-button">Delete</button>
                    </div>
                    `;
          });

          content.innerHTML = itemHTML;
        });
      break;
  }
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

function loadUpdateForm(itemID) {
  fetch(`crud.php?action=getItem&item_id=${itemID}`)
    .then((response) => response.json())
    .then((data) => {
      //debugging line
      //console.log(data);
      //console.log("Type of data:", typeof data);
      const item = data;
      const content = document.getElementById("content");
      content.innerHTML = `
          <form id="update-item-form" action="crud.php" method="post" enctype="multipart/form-data">
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="item_id" value="${item.item_id}">
              <label for="name">Item Name</label>
              <input type="text" id="name" name="name" value="${item.name}">
              <label for="description">Description</label>
              <input type="text" id="description" name="description" value="${item.description}">
              <label for="price">Price</label>
              <input type="number" id="price" name="price" value="${item.price}">
              <label for="stock">Stock</label>
              <input type="number" id="stock" name="stock" value="${item.stock}">
              <label for="image">Image</label>
              <input type="file" name="image">
              <input type="submit" value="Update Item">
          </form>
        `;
    })
    .catch((error) => console.error("Fetch error:", error));
}

function deleteItem(itemID) {
  console.log("Trying to delete item with ID:", itemID);
  let formData = new FormData();
  formData.append("action", "delete");
  formData.append("item_id", itemID);
  fetch(`crud.php?action=delete`, {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      //debugging statement
      //console.log(data);
      if (data.status === "success") {
        loadSection("viewAndUpdateItems");
      }
    })
    .catch((error) => console.error("Fetch error:", error));
}
