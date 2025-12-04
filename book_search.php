<?php
require_once 'config.php';

// Get all books with authors and publisher
$booksSql = "
    SELECT 
        b.book_id,
        b.title,
        b.isbn,
        b.genre,
        b.publication_date,
        p.p_name AS publisher,
        GROUP_CONCAT(a.a_name SEPARATOR ', ') AS authors
    FROM Book b
    JOIN Publisher p ON b.p_name = p.p_name
    LEFT JOIN Author a ON a.book_id = b.book_id
    GROUP BY b.book_id
    ORDER BY b.title
";
$booksResult = mysqli_query($conn, $booksSql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Search - Library Management System</title>
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .table-hover tbody tr:hover {
            background-color: rgba(102, 126, 234, 0.1);
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
                        <a class="nav-link active" href="book_search.php">Search Books</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="member_dashboard.php">My Account</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <h2 class="mb-4">Available Books</h2>
        
        <!-- Search Bar -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-10">
                        <input type="text" class="form-control" id="searchInput" placeholder="Search by title, author, ISBN, or genre..." onkeyup="searchTable()">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100" onclick="searchTable()">Search</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Books Table -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Book Catalog</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="booksTable">
                        <thead>
                            <tr>
                                <th>ISBN</th>
                                <th>Title</th>
                                <th>Author(s)</th>
                                <th>Genre</th>
                                <th>Publisher</th>
                                <th>Publication Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$booksResult || mysqli_num_rows($booksResult) === 0): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No books found.</td>
                                </tr>
                            <?php else: ?>
                                <?php while ($book = mysqli_fetch_assoc($booksResult)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($book['isbn']) ?></td>
                                        <td><strong><?= htmlspecialchars($book['title']) ?></strong></td>
                                        <td><?= htmlspecialchars($book['authors'] ?: 'N/A') ?></td>
                                        <td><?= htmlspecialchars($book['genre'] ?: 'N/A') ?></td>
                                        <td><?= htmlspecialchars($book['publisher']) ?></td>
                                        <td><?= htmlspecialchars($book['publication_date']) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function searchTable() {
            var input = document.getElementById('searchInput');
            var filter = input.value.toLowerCase();
            var table = document.getElementById('booksTable');
            var tr = table.getElementsByTagName('tr');

            for (var i = 1; i < tr.length; i++) {
                var td = tr[i].getElementsByTagName('td');
                var found = false;
                
                for (var j = 0; j < td.length; j++) {
                    if (td[j]) {
                        var txtValue = td[j].textContent || td[j].innerText;
                        if (txtValue.toLowerCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                }
                
                tr[i].style.display = found ? '' : 'none';
            }
        }
    </script>
</body>
</html>