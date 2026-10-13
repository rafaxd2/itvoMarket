const toastProducto = document.querySelector('.product-published-toast');

if (toastProducto) {
    window.setTimeout(() => {
        toastProducto.classList.add('is-leaving');
        toastProducto.addEventListener('animationend', () => toastProducto.remove(), { once: true });
    }, 2500);
}
