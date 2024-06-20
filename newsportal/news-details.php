<?php 
session_start();
include('includes/config.php');

// Generate CSRF Token if not already set
if (empty($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}

// Handle form submission
if(isset($_POST['submit'])) {
    // Verify CSRF Token
    if (!empty($_POST['csrftoken']) && hash_equals($_SESSION['token'], $_POST['csrftoken'])) {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $comment = $_POST['comment'];
        $postid = intval($_GET['nid']);
        $status = '0';

        // Using prepared statement to avoid SQL injection
        $stmt = $con->prepare("INSERT INTO tblcomments (postId, name, email, comment, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isssi", $postid, $name, $email, $comment, $status);
        
        if ($stmt->execute()) {
            echo "<script>alert('Comment successfully submitted. Comment will be displayed after admin review.');</script>";
            unset($_SESSION['token']);
        } else {
            echo "<script>alert('Something went wrong. Please try again.');</script>";
        }
    }
}

// Update view counter for the post
$postid = intval($_GET['nid']);
$sql = "SELECT viewCounter FROM tblposts WHERE id = '$postid'";
$result = $con->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $visits = $row["viewCounter"];
        $sql = "UPDATE tblposts SET viewCounter = $visits + 1 WHERE id ='$postid'";
        $con->query($sql);
    }
} else {
    echo "No results";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>News Portal | Home Page</title>
    <!-- Bootstrap core CSS -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom styles for this template -->
    <link href="css/modern-business.css" rel="stylesheet">
    <!-- Custom CSS for adjustments -->
    <style>
        /* Custom CSS untuk mengatur margin dan padding */
        .post-title {
            margin-top: 10px;
            margin-bottom: 10px;
        }
        .badge-category {
            margin-right: 5px;
        }
        .badge-subcategory {
            margin-right: 5px;
        }
        .share-links {
            margin-top: 10px;
        }
        .comment-form {
            margin-top: 20px;
        }
        .comment {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include('includes/header.php'); ?>
    <!-- Page Content -->
    <div class="container">
        <div class="row" style="margin-top: 4%">
            <!-- Blog Entries Column -->
            <div class="col-md-8">
                <!-- Blog Post -->
                <?php
                $pid = intval($_GET['nid']);
                $currenturl = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                $query = mysqli_query($con, "SELECT tblposts.PostTitle as posttitle, tblposts.PostImage, tblcategory.CategoryName as category, tblcategory.id as cid, tblsubcategory.Subcategory as subcategory, tblposts.PostDetails as postdetails, tblposts.PostingDate as postingdate, tblposts.PostUrl as url, tblposts.postedBy, tblposts.lastUpdatedBy, tblposts.UpdationDate, tblposts.viewCounter FROM tblposts LEFT JOIN tblcategory ON tblcategory.id = tblposts.CategoryId LEFT JOIN tblsubcategory ON tblsubcategory.SubCategoryId = tblposts.SubCategoryId WHERE tblposts.id='$pid'");
                while ($row = mysqli_fetch_array($query)) {
                ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <h2 class="card-title post-title"><?php echo htmlentities($row['posttitle']); ?></h2>
                        <!-- category -->
                        <a class="badge bg-secondary text-decoration-none link-light badge-category" href="category.php?catid=<?php echo htmlentities($row['cid']); ?>" style="color: #fff"><?php echo htmlentities($row['category']); ?></a>
                        <!-- Subcategory -->
                        <a class="badge bg-secondary text-decoration-none link-light badge-subcategory" style="color: #fff"><?php echo htmlentities($row['subcategory']); ?></a>
                        <p>
                            <b>Posted by </b> <?php echo htmlentities($row['postedBy']); ?> on <?php echo htmlentities($row['postingdate']); ?> |
                            <?php if ($row['lastUpdatedBy'] != ''): ?>
                                <b>Last Updated by </b> <?php echo htmlentities($row['lastUpdatedBy']); ?> on <?php echo htmlentities($row['UpdationDate']); ?>
                            <?php endif; ?>
                        </p>
                        <p class="share-links">
                            <strong>Share:</strong>
                            <a href="http://www.facebook.com/share.php?u=<?php echo $currenturl; ?>" target="_blank">Facebook</a> |
                            <a href="https://twitter.com/share?url=<?php echo $currenturl; ?>" target="_blank">Twitter</a> |
                            <a href="https://web.whatsapp.com/send?text=<?php echo $currenturl; ?>" target="_blank">Whatsapp</a> |
                            <a href="http://www.linkedin.com/shareArticle?mini=true&amp;url=<?php echo $currenturl; ?>" target="_blank">Linkedin</a>
                            <b>Visits:</b> <?php echo $row['viewCounter']; ?>
                        </p>
                        <hr />
                        <img class="img-fluid rounded" src="admin/postimages/<?php echo htmlentities($row['PostImage']); ?>" alt="<?php echo htmlentities($row['posttitle']); ?>">
                        <p class="card-text">
                            <?php
                            $pt = $row['postdetails'];
                            echo (substr($pt, 0));
                            ?>
                        </p>
                    </div>
                    <div class="card-footer text-muted">
                    </div>
                </div>
                <?php } ?>
            </div>
            <!-- Sidebar Widgets Column -->
            <?php include('includes/sidebar.php'); ?>
        </div>
        <!-- /.row -->
        <!-- Comment Section -->
        <div class="row">
            <div class="col-md-8">
                <div class="card my-4 comment-form">
                    <h5 class="card-header">Leave a Comment:</h5>
                    <div class="card-body">
                        <form name="Comment" method="post">
                            <input type="hidden" name="csrftoken" value="<?php echo htmlentities($_SESSION['token']); ?>" />
                            <div class="form-group">
                                <input type="text" name="name" class="form-control" placeholder="Enter your fullname" required>
                            </div>
                            <div class="form-group">
                                <input type="email" name="email" class="form-control" placeholder="Enter your Valid email" required>
                            </div>
                            <div class="form-group">
                                <textarea class="form-control" name="comment" rows="3" placeholder="Comment" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary" name="submit">Submit</button>
                        </form>
                    </div>
                </div>
                <!-- Comment Display Section -->
                <?php
                $sts = 1;
                $query = mysqli_query($con, "SELECT name, comment, postingDate FROM tblcomments WHERE postId='$pid' AND status='$sts'");
                while ($row = mysqli_fetch_array($query)) {
                ?>
                <div class="card mb-4 comment">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlentities($row['name']); ?> <span style="font-size: 11px;">at <?php echo htmlentities($row['postingDate']); ?></span></h5>
                        <p class="card-text"><?php echo htmlentities($row['comment']); ?></p>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>
    <!-- /.container -->
    <?php include('includes/footer.php'); ?>
    <!-- Bootstrap core JavaScript -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
