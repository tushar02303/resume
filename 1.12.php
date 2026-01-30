<?php
session_start();

// Simple authentication
if (!isset($_SESSION['admin_logged_in'])) {
    if ($_POST['password'] === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
    } else {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $error = "Invalid password";
        }
    }
}

// Database connection
$host = 'localhost';
$dbname = 'demo_widget';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['admin_logged_in'])) {
    if (isset($_POST['delete'])) {
        $stmt = $pdo->prepare("DELETE FROM demos WHERE id = ?");
        $stmt->execute([$_POST['delete']]);
    } elseif (isset($_POST['add_demo'])) {
        $stmt = $pdo->prepare("INSERT INTO demos (title, description, url, thumbnail_url, category, tags, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['title'],
            $_POST['description'],
            $_POST['url'],
            $_POST['thumbnail_url'],
            $_POST['category'],
            $_POST['tags'],
            isset($_POST['is_active']) ? 1 : 0
        ]);
    }
}

// Get all demos
$stmt = $pdo->query("SELECT * FROM demos ORDER BY created_at DESC");
$demos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo Widget Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f5f7fa; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: #2575fc; color: white; padding: 30px; border-radius: 10px; margin-bottom: 30px; }
        .header h1 { margin-bottom: 10px; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .stat-card h3 { color: #2575fc; margin-bottom: 10px; }
        .demo-list { background: white; border-radius: 8px; padding: 20px; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: 600; color: #333; }
        .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-weight: 500; }
        .btn-delete { background: #dc3545; color: white; }
        .btn-edit { background: #ffc107; color: #333; }
        .btn-add { background: #28a745; color: white; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-group input, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .login-form { max-width: 400px; margin: 100px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .login-form h2 { margin-bottom: 20px; color: #2575fc; }
    </style>
</head>
<body>
    <?php if (!isset($_SESSION['admin_logged_in'])): ?>
        <div class="login-form">
            <h2>Admin Login</h2>
            <?php if (isset($error)): ?>
                <p style="color: #dc3545; margin-bottom: 20px;"><?php echo $error; ?></p>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-add">Login</button>
            </form>
            <p style="margin-top: 20px; color: #666; font-size: 0.9em;">
                Default password: admin123
            </p>
        </div>
    <?php else: ?>
        <div class="container">
            <div class="header">
                <h1>Demo Widget Admin Panel</h1>
                <p>Manage your website demos</p>
                <form method="POST" style="margin-top: 20px;">
                    <button type="submit" name="logout" class="btn btn-delete">Logout</button>
                </form>
            </div>
            
            <div class="stats">
                <?php
                $totalDemos = count($demos);
                $activeDemos = array_filter($demos, fn($demo) => $demo['is_active']);
                $totalViews = array_sum(array_column($demos, 'views_count'));
                ?>
                <div class="stat-card">
                    <h3>Total Demos</h3>
                    <p style="font-size: 2rem;"><?php echo $totalDemos; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Active Demos</h3>
                    <p style="font-size: 2rem;"><?php echo count($activeDemos); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Views</h3>
                    <p style="font-size: 2rem;"><?php echo $totalViews; ?></p>
                </div>
            </div>
            
            <div class="demo-list">
                <h2 style="margin-bottom: 20px;">Add New Demo</h2>
                <form method="POST" style="margin-bottom: 30px;">
                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="title" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>URL</label>
                        <input type="url" name="url" required>
                    </div>
                    <div class="form-group">
                        <label>Thumbnail URL</label>
                        <input type="url" name="thumbnail_url">
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <input type="text" name="category">
                    </div>
                    <div class="form-group">
                        <label>Tags (comma separated)</label>
                        <input type="text" name="tags">
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_active" checked> Active
                        </label>
                    </div>
                    <button type="submit" name="add_demo" class="btn btn-add">Add Demo</button>
                </form>
                
                <h2 style="margin-bottom: 20px;">All Demos</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>URL</th>
                            <th>Views</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($demos as $demo): ?>
                        <tr>
                            <td><?php echo $demo['id']; ?></td>
                            <td><?php echo htmlspecialchars($demo['title']); ?></td>
                            <td><a href="<?php echo $demo['url']; ?>" target="_blank">View</a></td>
                            <td><?php echo $demo['views_count']; ?></td>
                            <td><?php echo $demo['is_active'] ? 'Active' : 'Inactive'; ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <button type="submit" name="delete" value="<?php echo $demo['id']; ?>" class="btn btn-delete">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>