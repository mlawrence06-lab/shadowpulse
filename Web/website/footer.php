    </main>

    <footer class="site-footer">
        <div class="footer-left">
            <span class="footer-brand">ShadowPulse</span>
            &copy; <span id="year"></span>
        </div>
        <div class="footer-right">
            <span class="footer-pill">Bitcointalk analytics layer</span>
        </div>
    </footer>
</div>

<div class="bottom-separator"></div>
<script>
    (function () {
        var el = document.getElementById('year');
        if (el) {
            el.textContent = new Date().getFullYear();
        }
    }());
</script>
</body>
</html>
