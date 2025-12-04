<?php
// Member Dashboard – dynamic version

require 'config.php';

// 
// if (!is_logged_in()) {
//     redirect('index.php');
// }


$memberId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

if ($memberId <= 0) {
    $res = mysqli_query($conn, "SELECT member_id FROM Member ORDER BY member_id LIMIT 1");
    if ($row = mysqli_fetch_assoc($res)) {
        $memberId = intval($row['member_id']);
    }
}

$member = null;
if ($memberId > 0) {
    $stmt = mysqli_prepare($conn, "
        SELECT member_id, fname, lname, dob, address, phone, email
        FROM Member
        WHERE member_id = ?
    ");
    mysqli_stmt_bind_param($stmt, "i", $memberId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $member = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

$history = [];
if ($memberId > 0) {
    $stmt = mysqli_prepare($conn, "
        SELECT
            b.title AS book_title,
            a.a_name AS author,
            l.date_out,
            l.due_date,
            l.return_date
        FROM Loan l
        JOIN LoanItem li ON l.loan_id = li.loan_id
        JOIN Book b ON li.book_id = b.book_id
        LEFT JOIN Author a ON a.book_id = b.book_id
        WHERE l.member_id = ?
        ORDER BY l.date_out DESC
        LIMIT 10
    ");
    mysqli_stmt_bind_param($stmt, "i", $memberId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $status = "On Loan";
        $today = new DateTime();
        $due = !empty($row['due_date']) ? new DateTime($row['due_date']) : null;

        if (!empty($row['return_date'])) {
            $status = "Returned";
        } elseif ($due && $today > $due) {
            $status = "Overdue";
        }

        $row['status'] = $status;
        $history[] = $row;
    }
    mysqli_stmt_close($stmt);
}

$initials = "MB";
if ($member) {
    $first = strtoupper(substr($member['fname'], 0, 1));
    $last  = strtoupper(substr($member['lname'], 0, 1));
    $initials = $first . $last;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card {
            border-radius: 10px;
            transition: transform 0.2s;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: white;
            font-weight: bold;
            margin: 0 auto;
        }
        .stat-card {
            border-left: 4px solid #667eea;
        }
        .book-item {
            border-left: 3px solid #28a745;
            transition: all 0.3s;
        }
        .book-item:hover {
            background-color: #f8f9fa;
            border-left-width: 5px;
        }
        .badge-custom {
            padding: 0.5em 1em;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="#">📚 Library System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="book_search.php">Search Books</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="member_dashboard.php">My Account</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <h2 class="mb-4">My Account</h2>

        <div class="row">
            <!-- Left Column - Profile Info -->
            <div class="col-md-4 mb-4">
                <!-- Profile Card -->
                <div class="card shadow-sm">
                    <div class="card-body text-center py-4">
                        <div class="profile-avatar mb-3">
                            <?php echo htmlspecialchars($initials); ?>
                        </div>

                        <?php if ($member): ?>
                            <h5 class="mb-1">
                                <?php echo htmlspecialchars($member['fname'] . ' ' . $member['lname']); ?>
                            </h5>
                            <p class="text-muted mb-2">
                                Member ID: #<?php echo htmlspecialchars($member['member_id']); ?>
                            </p>
                            <p class="mb-1">
                                <small>📧 <?php echo htmlspecialchars($member['email']); ?></small>
                            </p>
                            <p class="mb-1">
                                <small>📞 <?php echo htmlspecialchars($member['phone']); ?></small>
                            </p>
                            <?php if (!empty($member['address'])): ?>
                                <p class="mb-1">
                                    <small>🏠 <?php echo htmlspecialchars($member['address']); ?></small>
                                </p>
                            <?php endif; ?>
                            <?php if (!empty($member['dob'])): ?>
                                <p class="mb-3">
                                    <small>🎂 <?php echo htmlspecialchars($member['dob']); ?></small>
                                </p>
                            <?php else: ?>
                                <p class="mb-3"></p>
                            <?php endif; ?>
                        <?php else: ?>
                            <h5 class="mb-1">Unknown Member</h5>
                            <p class="text-muted mb-3">No member information found.</p>
                        <?php endif; ?>

                        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                            Edit Profile
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right Column - Borrowing History -->
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Recent Borrowing History</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Book Title</th>
                                        <th>Author</th>
                                        <th>Borrowed</th>
                                        <th>Returned</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($history) === 0): ?>
                                        <tr>
                                            <td colspan="5" class="text-center">
                                                No borrowing history found.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($history as $h): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($h['book_title']); ?></td>
                                                <td><?php echo htmlspecialchars($h['author'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($h['date_out']); ?></td>
                                                <td><?php echo htmlspecialchars($h['return_date'] ?? '-'); ?></td>
                                                <td>
                                                    <?php
                                                    $badgeClass = 'bg-primary';
                                                    if ($h['status'] === 'Returned') $badgeClass = 'bg-success';
                                                    if ($h['status'] === 'Overdue')  $badgeClass = 'bg-danger';
                                                    ?>
                                                    <span class="badge <?php echo $badgeClass; ?>">
                                                        <?php echo htmlspecialchars($h['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <button class="btn btn-outline-secondary" onclick="alert('Full history feature coming soon!')">
                                View Full History
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editProfileForm">
                        <div class="mb-3">
                            <label for="editName" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="editName"
                                   value="<?php echo $member ? htmlspecialchars($member['fname'] . ' ' . $member['lname']) : ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="editEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="editEmail"
                                   value="<?php echo $member ? htmlspecialchars($member['email']) : ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="editPhone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="editPhone"
                                   value="<?php echo $member ? htmlspecialchars($member['phone']) : ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="editAddress" class="form-label">Address</label>
                            <textarea class="form-control" id="editAddress" rows="2"><?php
                                echo $member ? htmlspecialchars($member['address']) : '';
                            ?></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveProfile()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function saveProfile() {
            alert('Profile updated successfully! (demo only, not saving to DB yet)');
            var modal = bootstrap.Modal.getInstance(document.getElementById('editProfileModal'));
            modal.hide();
        }
    </script>
</body>
</html>
