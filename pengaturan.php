<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'profile') {
        $stmt = $pdo->prepare('
            UPDATE profil_peternakan SET nama_peternakan=?, pemilik=?, alamat=?, lama_usaha_tahun=?, frekuensi_panen_tahun=?, jumlah_pekerja=?
            WHERE id=1
        ');
        $stmt->execute([
            trim($_POST['nama_peternakan'] ?? ''),
            trim($_POST['pemilik'] ?? ''),
            trim($_POST['alamat'] ?? ''),
            max(0, (int)($_POST['lama_usaha_tahun'] ?? 0)),
            max(0, (int)($_POST['frekuensi_panen_tahun'] ?? 0)),
            max(0, (int)($_POST['jumlah_pekerja'] ?? 0)),
        ]);
        flash('success', 'Profil peternakan berhasil disimpan.');
        redirect('pengaturan.php');
    }

    if ($action === 'account' && (current_user()['role'] ?? '') === 'admin') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = in_array($_POST['role'] ?? '', ['admin','operator'], true) ? $_POST['role'] : 'operator';

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
            flash('error', 'Nama, email valid, dan password minimal 6 karakter wajib diisi.');
            redirect('pengaturan.php');
        }

        try {
            $stmt = $pdo->prepare('INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)');
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
            flash('success', 'Akun baru berhasil dibuat.');
        } catch (Throwable $e) {
            flash('error', 'Email sudah digunakan.');
        }
        redirect('pengaturan.php');
    }

    if ($action === 'password') {
        $old = $_POST['old_password'] ?? '';
        $new = $_POST['new_password'] ?? '';

        $stmt = $pdo->prepare('SELECT password FROM users WHERE id=?');
        $stmt->execute([(int)current_user()['id']]);
        $hash = (string)$stmt->fetchColumn();

        if (!password_verify($old, $hash)) {
            flash('error', 'Password lama salah.');
        } elseif (strlen($new) < 6) {
            flash('error', 'Password baru minimal 6 karakter.');
        } else {
            $stmt = $pdo->prepare('UPDATE users SET password=? WHERE id=?');
            $stmt->execute([password_hash($new, PASSWORD_DEFAULT), (int)current_user()['id']]);
            flash('success', 'Password berhasil diubah.');
        }
        redirect('pengaturan.php');
    }

    if ($action === 'delete_user' && (current_user()['role'] ?? '') === 'admin') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === (int)current_user()['id']) {
            flash('error', 'Akun yang sedang digunakan tidak dapat dihapus.');
        } else {
            $stmt = $pdo->prepare('DELETE FROM users WHERE id=?');
            $stmt->execute([$id]);
            flash('success', 'Akun pengguna dihapus.');
        }
        redirect('pengaturan.php');
    }
}

$profile = $pdo->query('SELECT * FROM profil_peternakan WHERE id=1')->fetch();
$users = (current_user()['role'] ?? '') === 'admin'
    ? $pdo->query('SELECT id,name,email,role,created_at FROM users ORDER BY id')->fetchAll()
    : [];

$pageTitle = 'Pengaturan';
require __DIR__ . '/includes/header.php';
?>
<div class="grid cols-2">
    <section class="card">
        <div class="card-header"><h3>Profil Peternakan</h3></div>
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="profile">
                <div class="form-group"><label>Nama Peternakan</label><input name="nama_peternakan" value="<?= e($profile['nama_peternakan'] ?? '') ?>" required></div>
                <div class="form-group" style="margin-top:12px"><label>Nama Pemilik</label><input name="pemilik" value="<?= e($profile['pemilik'] ?? '') ?>"></div>
                <div class="form-group" style="margin-top:12px"><label>Alamat</label><textarea name="alamat"><?= e($profile['alamat'] ?? '') ?></textarea></div>
                <div class="form-grid" style="margin-top:12px">
                    <div class="form-group"><label>Lama Usaha (tahun)</label><input type="number" min="0" name="lama_usaha_tahun" value="<?= e((string)($profile['lama_usaha_tahun'] ?? 0)) ?>"></div>
                    <div class="form-group"><label>Panen per Tahun</label><input type="number" min="0" name="frekuensi_panen_tahun" value="<?= e((string)($profile['frekuensi_panen_tahun'] ?? 0)) ?>"></div>
                    <div class="form-group full"><label>Jumlah Pekerja</label><input type="number" min="0" name="jumlah_pekerja" value="<?= e((string)($profile['jumlah_pekerja'] ?? 0)) ?>"></div>
                </div>
                <button class="btn btn-primary" type="submit" style="margin-top:18px">Simpan Profil</button>
            </form>
        </div>
    </section>

    <section class="card">
        <div class="card-header"><h3>Ubah Password</h3></div>
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="password">
                <div class="form-group"><label>Password Lama</label><input type="password" name="old_password" required></div>
                <div class="form-group" style="margin-top:12px"><label>Password Baru</label><input type="password" name="new_password" minlength="6" required></div>
                <button class="btn btn-primary" type="submit" style="margin-top:18px">Ubah Password</button>
            </form>
        </div>
    </section>
</div>

<?php if ((current_user()['role'] ?? '') === 'admin'): ?>
<div class="grid cols-3" style="margin-top:18px">
    <section class="card">
        <div class="card-header"><h3>Tambah Akun</h3></div>
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="account">
                <div class="form-group"><label>Nama</label><input name="name" required></div>
                <div class="form-group" style="margin-top:12px"><label>Email</label><input type="email" name="email" required></div>
                <div class="form-group" style="margin-top:12px"><label>Password</label><input type="password" name="password" minlength="6" required></div>
                <div class="form-group" style="margin-top:12px"><label>Role</label><select name="role"><option value="operator">Operator</option><option value="admin">Admin</option></select></div>
                <button class="btn btn-primary" type="submit" style="margin-top:18px">Buat Akun</button>
            </form>
        </div>
    </section>
    <section class="card" style="grid-column:span 2">
        <div class="card-header"><h3>Manajemen Akun</h3></div>
        <div class="card-body" style="padding:0">
            <div class="table-wrap"><table><thead><tr><th>Nama</th><th>Email</th><th>Role</th><th>Dibuat</th><th>Aksi</th></tr></thead><tbody>
            <?php foreach($users as $u): ?><tr>
                <td><strong><?= e($u['name']) ?></strong></td><td><?= e($u['email']) ?></td><td><span class="badge"><?= e(ucfirst($u['role'])) ?></span></td><td><?= e($u['created_at']) ?></td>
                <td><?php if ((int)$u['id'] !== (int)current_user()['id']): ?><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete_user"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><button class="btn btn-danger btn-sm" data-confirm="Hapus akun ini?" type="submit">Hapus</button></form><?php else: ?><span class="badge gray">Sedang dipakai</span><?php endif; ?></td>
            </tr><?php endforeach; ?>
            </tbody></table></div>
        </div>
    </section>
</div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
