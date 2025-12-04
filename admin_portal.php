<?php
require_once 'config.php';

// Ensure only admin can access
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// Get counts for dashboard
$countBooks = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM Book"))['total'];
$countMembers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM Member"))['total'];
$countActiveLoans = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM Loan WHERE return_date IS NULL"))['total'];
$countOverdue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM Loan WHERE return_date IS NULL AND due_date < CURDATE()"))['total'];

// Get all publishers for dropdown
$publishers = mysqli_query($conn, "SELECT p_name FROM Publisher ORDER BY p_name");

// Get all books
$booksSql = "
    SELECT 
        b.book_id, b.title, b.isbn, b.genre, b.publication_date, b.p_name AS publisher,
        GROUP_CONCAT(a.a_name SEPARATOR ', ') AS authors
    FROM Book b
    LEFT JOIN Author a ON a.book_id = b.book_id
    GROUP BY b.book_id
    ORDER BY b.title
";
$booksResult = mysqli_query($conn, $booksSql);

// Get all loans
$loansSql = "
    SELECT l.loan_id, l.date_out, l.due_date, l.return_date,
        CONCAT(m.fname, ' ', m.lname) AS member_name,
        GROUP_CONCAT(DISTINCT b.title ORDER BY b.title SEPARATOR ', ') AS books
    FROM Loan l
    JOIN Member m ON l.member_id = m.member_id
    LEFT JOIN LoanItem li ON l.loan_id = li.loan_id
    LEFT JOIN Book b ON li.book_id = b.book_id
    GROUP BY l.loan_id
    ORDER BY l.date_out DESC
";
$loansResult = mysqli_query($conn, $loansSql);

// Get all members
$membersSql = "SELECT member_id, fname, lname, email, phone, role FROM Member ORDER BY member_id";
$membersResult = mysqli_query($conn, $membersSql);
?>
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Portal - Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .navbar {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }
        .nav-tabs .nav-link {
            color: #495057;
            font-weight: 500;
        }
        .nav-tabs .nav-link.active {
            color: #28a745;
            font-weight: bold;
        }
        .stat-card {
            border-left: 4px solid;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .table-hover tbody tr:hover {
            background-color: rgba(40, 167, 69, 0.1);
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="#">📚 Library Staff Portal</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="book_search.html">Search Books</a>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link">👤 Staff: John Librarian</span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid my-4">
        <h2 class="mb-4">Staff Portal</h2>

        <!-- Tab Navigation -->
        <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="dashboard-tab" data-bs-toggle="tab" data-bs-target="#dashboard" type="button" role="tab" aria-controls="dashboard" aria-selected="true">
                    📊 Dashboard
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="books-tab" data-bs-toggle="tab" data-bs-target="#books" type="button">
                    📚 Book Management
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="loans-tab" data-bs-toggle="tab" data-bs-target="#loans" type="button">
                    🔄 Loan Management
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button">
                    👥 Member Management
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="myTabContent">
            
            
    <!-- DASHBOARD TAB -->
        <div class="tab-pane fade show active" id="dashboard">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card" style="border-left-color: #667eea;">
                            <div class="card-body">
                                <h6 class="text-muted">Total Books</h6>
                                <h3><?= $countBooks ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card" style="border-left-color: #20c997;">
                            <div class="card-body">
                                <h6 class="text-muted">Total Members</h6>
                                <h3><?= $countMembers ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card" style="border-left-color: #ffc107;">
                            <div class="card-body">
                                <h6 class="text-muted">Active Loans</h6>
                                <h3><?= $countActiveLoans ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card" style="border-left-color: #dc3545;">
                            <div class="card-body">
                                <h6 class="text-muted">Overdue Loans</h6>
                                <h3><?= $countOverdue ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- TAB 2: BOOK MANAGEMENT -->
            <div class="tab-pane fade" id="books">
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Add / Edit Book</h5>
                    </div>
                    <div class="card-body">
                        <form id="bookForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Title *</label>
                                    <input type="text" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Author *</label>
                                    <input type="text" class="form-control" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">ISBN *</label>
                                    <input type="text" class="form-control" placeholder="978-0-123456-78-9" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Genre *</label>
                                    <select class="form-select" required>
                                        <option value="">Select Genre</option>
                                        <option>Fiction</option>
                                        <option>Non-Fiction</option>
                                        <option>Mystery</option>
                                        <option>Sci-Fi</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Year *</label>
                                    <input type="number" class="form-control" min="1900" max="2025" required>
                                </div>

                            </div>
                            <button type="submit" class="btn btn-success">Add Book</button>
                            <button type="reset" class="btn btn-secondary">Clear</button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Book List</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    
                                    <th>ISBN</th>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>Genre</th>
                                    <th>Publisher</th>
                                    <th>Publication Date</th>
                                    <th>Actions</th>

                                </tr>
                            </thead>
                            <tbody>
                            <?php if (!$booksResult || mysqli_num_rows($booksResult) === 0): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No books found.</td>
                                </tr>
                            <?php else: ?>
                                <?php while ($b = mysqli_fetch_assoc($booksResult)): ?>
                                    <tr>
                                        
                                        <td><?= htmlspecialchars($b['isbn']) ?></td>
                                        <td><?= htmlspecialchars($b['title']) ?></td>
                                        <td><?= htmlspecialchars($b['authors'] ?: 'N/A') ?></td>
                                        <td><?= htmlspecialchars($b['genre'] ?: 'N/A') ?></td>
                                        <td><?= htmlspecialchars($b['publisher']) ?></td>
                                        <td><?= htmlspecialchars($b['publication_date']) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning">Edit</button>
                                            <button class="btn btn-sm btn-danger">Delete</button>
                                        </td>
                                        
                                    </tr>
                                        
                                <?php endwhile; ?>
                            <?php endif; ?>    
                                        
                            
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 3: LOAN MANAGEMENT -->
            <div class="tab-pane fade" id="loans">
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Process Loan / Return</h5>
                    </div>
                    <div class="card-body">
                        <form id="loanForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Member Email *</label>
                                    <input type="text" class="form-control" placeholder="Enter member identifier" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Book ISBN / Title *</label>
                                    <input type="text" class="form-control" placeholder="Enter book identifier" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Loan Date *</label>
                                    <input type="date" class="form-control" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Due Date *</label>
                                    <input type="date" class="form-control" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Return Date</label>
                                    <input type="date" class="form-control">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success">Create Loan</button>
                            <button type="button" class="btn btn-primary">Mark Returned</button>
                            <button type="button" class="btn btn-info">Renew</button>
                        </form>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">Active Loans</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            
                                            <th>Member</th>
                                            <th>Book</th>
                                            <th>Loan Date</th>
                                            <th>Due Date</th>
                                            <th>Return Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($loansResult && mysqli_num_rows($loansResult) > 0): ?>
                                            <?php while ($loan = mysqli_fetch_assoc($loansResult)): ?>
                                                <?php
                                                    $isReturned  = !is_null($loan['return_date']);
                                                    $statusLabel = $isReturned ? 'Returned' : 'On Loan';
                                                    $statusClass = $isReturned ? 'success'  : 'warning';
                                                ?>
                                                <tr>
                                                
                                                <td><?= htmlspecialchars($loan['member_name']) ?></td>
                                                <td><?= htmlspecialchars($loan['books'] ?: 'N/A') ?></td>
                                                <td><?= htmlspecialchars($loan['date_out']) ?></td>
                                                <td><?= htmlspecialchars($loan['due_date']) ?></td>
                                                <td><?= htmlspecialchars($loan['return_date'] ?? '-') ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $statusClass; ?>">
                                                            <?php echo $statusLabel; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-success">Mark Returned</button>
                                                        <button class="btn btn-sm btn-outline-danger">Mark Lost</button>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center text-muted">
                                                    No loans found.
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>


                </div>
            </div>

            <!-- TAB 4: MEMBER MANAGEMENT -->
            <div class="tab-pane fade" id="users">
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Add / Edit Member</h5>
                    </div>
                    <div class="card-body">
                        <form id="userForm">
                            <div class="row">
                                <div class="col-md-4 mb-4">
                                    <label class="form-label">First Name *</label>
                                    <input type="text" class="form-control" required>
                                </div>
                                <div class="col-md-4 mb-4">
                                    <label class="form-label">Last Name *</label>
                                    <input type="text" class="form-control" required>
                                </div>
                                <div class="col-md-4 mb-4">
                                    <label class="form-label">Email *</label>
                                    <input type="email" class="form-control" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Role *</label>
                                    <select class="form-select" required>
                                        <option value="">Select Role</option>
                                        <option>Member</option>
                                        <option>Admin</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Status *</label>
                                    <select class="form-select" required>
                                        <option>Active</option>
                                        <option>Disabled</option>
                                    </select>
                                </div>
                              
                            </div>
                            <button type="submit" class="btn btn-success">Save User</button>
                            <button type="reset" class="btn btn-secondary">Clear</button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Member List</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($membersResult && mysqli_num_rows($membersResult) > 0): ?>
                                    <?php while ($member = mysqli_fetch_assoc($membersResult)): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($member['member_id']); ?></td>
                                            <td><?php echo htmlspecialchars($member['fname'] . ' ' . $member['lname']); ?></td>
                                            <td><?php echo htmlspecialchars($member['email']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php
                                                    echo $member['role'] === 'admin'
                                                        ? 'danger'
                                                        : ($member['role'] === 'staff' ? 'primary' : 'secondary');
                                                ?>">
                                                    <?php echo ucfirst(htmlspecialchars($member['role'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success">Active</span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-warning">Edit</button>
                                                <button class="btn btn-sm btn-danger">Delete</button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            No members found.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>

                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form submission handlers
        document.getElementById('bookForm').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Book saved successfully!');
        });

        document.getElementById('loanForm').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Loan created successfully!');
        });

        document.getElementById('userForm').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('User saved successfully!');
        });
    </script>
</body>
</html>