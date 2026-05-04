// Javascripty
// Character counter
const textarea = document.getElementById('postContent');
const counter  = document.getElementById('charCounter');

if (textarea && counter) {
    const MAX = 500;

    function updateCounter() {
        const remaining = MAX - textarea.value.length;
        counter.textContent = remaining + ' characters left';

        counter.style.color = remaining <= 20 ? '#f87171' : '';
    }

    textarea.addEventListener('input', updateCounter);

    updateCounter();
}