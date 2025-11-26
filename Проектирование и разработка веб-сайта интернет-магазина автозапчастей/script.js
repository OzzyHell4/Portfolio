// --- Корзина: добавление товара ---
function getCart() {
  return JSON.parse(localStorage.getItem('cart') || '[]');
}
function setCart(cart) {
  localStorage.setItem('cart', JSON.stringify(cart));
}
function addToCart(title, price) {
  let cart = getCart();
  const idx = cart.findIndex(item => item.title === title);
  if (idx !== -1) {
    cart[idx].qty += 1;
  } else {
    cart.push({ title, price: Number(price), qty: 1 });
  }
  setCart(cart);
}

// Для product.html: обработка кнопок 'Добавить в корзину'
document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    const title = this.getAttribute('data-title');
    const price = this.getAttribute('data-price');
    addToCart(title, price);
    // Модальное окно
    const modal = document.getElementById('cart-modal');
    if (modal) {
      modal.style.display = 'flex';
      document.getElementById('cart-modal-text').textContent = `Товар \"${title}\" добавлен в корзину!`;
      document.getElementById('cart-modal-close').onclick = function() {
        modal.style.display = 'none';
      };
      // Закрытие по клику вне окна
      modal.onclick = function(e) {
        if (e.target === modal) modal.style.display = 'none';
      };
    }
  });
});

// Для product.html: обработка кнопок 'Добавить в избранное'
document.querySelectorAll('.add-to-fav-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    const title = this.getAttribute('data-title');
    let favs = JSON.parse(localStorage.getItem('favorites') || '[]');
    const products = JSON.parse(localStorage.getItem('products') || '[]');
    const prod = products.find(p => p.title === title);
    if (!prod) return;
    if (favs.some(fav => fav.title === title)) {
      favs = favs.filter(fav => fav.title !== title);
    } else {
      favs.push(prod);
    }
    localStorage.setItem('favorites', JSON.stringify(favs));
    // Обновить иконку (если есть функция renderProducts)
    if (typeof renderProducts === 'function') renderProducts();
  });
});

// --- Корзина: динамическое отображение на странице корзины ---
if (document.querySelector('.cart-items-list')) {
  const cart = getCart();
  const list = document.querySelector('.cart-items-list');
  const totalElem = document.querySelector('.cart-summary-total');
  function recalcTotal() {
    let total = 0;
    const items = list.querySelectorAll('.cart-item');
    items.forEach((item, idx) => {
      const qty = parseInt(item.querySelector('.cart-item-qty-value').textContent, 10);
      total += cart[idx].price * qty;
    });
    totalElem.textContent = total + 'руб';
  }
  list.innerHTML = '';
  // Получаем все товары для поиска фото
  const products = JSON.parse(localStorage.getItem('products') || '[]');
  cart.forEach((item, idx) => {
    const el = document.createElement('div');
    el.className = 'cart-item';
    // Найти фото по title
    const prod = products.find(p => p.title === item.title);
    let photoHtml = 'Фото товара';
    if (prod && prod.img) {
      photoHtml = `<img src='${prod.img}' alt='Фото' style='max-width:80px;max-height:60px;border-radius:8px;'>`;
    }
    el.innerHTML = `
      <div class=\"cart-item-photo\">${photoHtml}</div>
      <div class=\"cart-item-desc\">${item.title}</div>
      <div class=\"cart-item-qty\">
        <button class=\"cart-item-qty-btn\">-</button>
        <span class=\"cart-item-qty-value\">${item.qty}</span>
        <button class=\"cart-item-qty-btn\">+</button>
      </div>
      <button class=\"cart-item-remove\" title=\"Удалить\" style=\"margin-left:18px;background:none;border:none;color:#e53935;font-size:1.5em;cursor:pointer;\">&times;</button>
    `;
    list.appendChild(el);
    const minusBtn = el.querySelectorAll('.cart-item-qty-btn')[0];
    const plusBtn = el.querySelectorAll('.cart-item-qty-btn')[1];
    const qtyElem = el.querySelector('.cart-item-qty-value');
    const removeBtn = el.querySelector('.cart-item-remove');
    minusBtn.addEventListener('click', function() {
      let qty = parseInt(qtyElem.textContent, 10);
      if (qty > 1) {
        qty--;
        qtyElem.textContent = qty;
        cart[idx].qty = qty;
        setCart(cart);
        recalcTotal();
      }
    });
    plusBtn.addEventListener('click', function() {
      let qty = parseInt(qtyElem.textContent, 10);
      qty++;
      qtyElem.textContent = qty;
      cart[idx].qty = qty;
      setCart(cart);
      recalcTotal();
    });
    removeBtn.addEventListener('click', function() {
      cart.splice(idx, 1);
      setCart(cart);
      el.remove();
      recalcTotal();
    });
  });
  recalcTotal();
}

// --- Сессия пользователя ---
function getCurrentUser() {
  return JSON.parse(localStorage.getItem('currentUser') || 'null');
}
function setCurrentUser(user) {
  localStorage.setItem('currentUser', JSON.stringify(user));
}
function logoutUser() {
  localStorage.removeItem('currentUser');
}
// Проверка сессии на защищённых страницах (пример)
if (document.querySelector('.profile-sidebar')) {
  const user = getCurrentUser();
  if (!user) {
    window.location.href = 'login.html';
  }
  // Кнопка выхода
  const logoutBtn = document.querySelector('.profile-menu.logout');
  if (logoutBtn) {
    logoutBtn.onclick = function() {
      logoutUser();
      window.location.href = 'login.html';
    };
  }
}
// --- Динамическая ссылка профиля в header ---
document.addEventListener('DOMContentLoaded', function() {
  const user = getCurrentUser();
  document.querySelectorAll('.header__icons a').forEach(a => {
    if (a.querySelector('.fa-user')) {
      // Всегда ведёт на профиль если залогинен, иначе на login.html
      a.setAttribute('href', user ? 'profile.html' : 'login.html');
    }
  });
});
