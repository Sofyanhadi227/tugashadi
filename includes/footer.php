</main>
    </div>
</div>

<nav class="mobile-nav">
    <a class="<?= active_nav('index.php') ?>" href="index.php"><span>⌂</span><small>Home</small></a>
    <a class="<?= active_nav('populasi.php') ?>" href="populasi.php"><span>🐣</span><small>Populasi</small></a>
    <a class="<?= active_nav('pencatatan.php') ?>" href="pencatatan.php"><span>✎</span><small>Catatan</small></a>
    <a class="<?= active_nav('laporan.php') ?>" href="laporan.php"><span>▤</span><small>Laporan</small></a>
    <a class="<?= active_nav('pengaturan.php') ?>" href="pengaturan.php"><span>⚙</span><small>Menu</small></a>
</nav>

<script src="assets/js/app.js"></script>
<?= $pageScript ?? '' ?>
</body>
</html>
