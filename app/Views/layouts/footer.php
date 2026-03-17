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
<?php if (str_starts_with(current_path(), '/citas')): ?>
<script src="<?= e(asset('js/calendar.js')) ?>"></script>
<?php endif; ?>
</body>
</html>
