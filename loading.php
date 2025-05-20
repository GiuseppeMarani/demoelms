<!-- Loading Screen -->
<div class="loading-screen" id="loadingScreen">
    <img src="img/logo.png" alt="Barbiere" class="loading-logo">
    <div class="loading-scissors">
        <i class="fas fa-cut"></i>
    </div>
</div>

<script>
// Loading screen handler with 3 second delay
window.addEventListener('load', function() {
    const loadingScreen = document.getElementById('loadingScreen');
    setTimeout(() => {
        loadingScreen.classList.add('fade-out');
        setTimeout(() => {
            loadingScreen.style.display = 'none';
        }, 300);
    }, 3000); // 3 second delay
});
</script>
