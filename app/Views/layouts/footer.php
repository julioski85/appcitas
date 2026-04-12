    </main>
</div>

<script>
window.APP = {
    baseUrl: <?= json_encode(url('')) ?>,
    currentPath: <?= json_encode(current_path()) ?>,
    csrfToken: <?= json_encode(csrf_token()) ?>,
};
</script>
<script src="<?= e(asset('js/app.js')) ?>"></script>
<?php if (current_path() === '/calendario'): ?>
<script src="<?= e(asset('js/calendar.js')) ?>"></script>
<?php endif; ?>
</body>
</html>
