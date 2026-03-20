// Javascripty
// Character counter for the compose textarea on the feed page
const textarea = document.getElementById('postContent');
const counter  = document.getElementById('charCounter');

if (textarea && counter) {
    const MAX = 500;

    function updateCounter() {
        const remaining = MAX - textarea.value.length;
        counter.textContent = remaining + ' characters left';

        // Turn red when close to the limit
        counter.style.color = remaining <= 20 ? '#f87171' : '';
    }

    textarea.addEventListener('input', updateCounter);

    // Run once on load in case the field is pre-filled (e.g. after a failed submit)
    updateCounter();
}