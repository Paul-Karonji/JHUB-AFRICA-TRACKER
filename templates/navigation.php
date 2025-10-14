<!-- PUBLIC PAGES NAVBAR WITH LOGO -->
<!-- Replace your existing navbar in: index.php, public/projects.php, public/about.php, public/contact.php -->

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container">
        <!-- LOGO - UPDATED -->
        <a class="navbar-brand" href="index.php">
            <img src="assets/images/logo/JHUB Africa Logo.png" 
                 alt="JHUB AFRICA - Innovations for Transformation" 
                 class="logo-img"
                 onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%2250%22%3E%3Ctext x=%2210%22 y=%2235%22 font-family=%22Arial%22 font-size=%2224%22 fill=%22%232c409a%22%3EJHUB AFRICA%3C/text%3E%3C/svg%3E';">
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="public/projects.php">Projects</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="public/about.php">About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="public/contact.php">Contact</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link btn btn-primary text-white ms-2 px-3" href="applications/submit.php">
                        Apply Now
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="auth/login.php">Login</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- ADD THIS CSS TO YOUR PAGE OR TO assets/css/public.css -->
<style>
/* Navbar Logo Styling */
.navbar-brand {
    padding: 0.5rem 0;
    display: flex;
    align-items: center;
}

.navbar-brand .logo-img {
    max-height: 50px;
    height: auto;
    width: auto;
    max-width: 200px;
    object-fit: contain;
    transition: all 0.3s ease;
}

.navbar-brand:hover .logo-img {
    opacity: 0.9;
    transform: scale(1.02);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .navbar-brand .logo-img {
        max-height: 40px;
        max-width: 150px;
    }
}

@media (max-width: 576px) {
    .navbar-brand .logo-img {
        max-height: 35px;
        max-width: 130px;
    }
}

/* Navbar styling */
.navbar {
    padding: 0.8rem 0;
}

.navbar-light .navbar-nav .nav-link {
    color: #333;
    font-weight: 500;
    padding: 0.5rem 1rem;
    transition: color 0.3s ease;
}

.navbar-light .navbar-nav .nav-link:hover {
    color: #2c409a;
}

.navbar-light .navbar-nav .nav-link.btn {
    border-radius: 25px;
}
</style>

<!-- NOTE: For pages inside the 'public/' folder, adjust the logo path to: -->
<!-- <img src="../assets/images/logo/JHUB Africa Logo.png" alt="JHUB AFRICA"> -->