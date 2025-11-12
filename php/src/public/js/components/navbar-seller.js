document.addEventListener('DOMContentLoaded', function() {
    
    // Dropdown user navbar
    const userWrapper = document.querySelector('.navbar-user-wrapper');
    const userDropdownToggle = document.getElementById('userDropdownToggle');
    const userDropdownMenu = document.getElementById('userDropdownMenu');

    if (userDropdownToggle && userDropdownMenu) {
        userDropdownToggle.addEventListener('click', function(event) {
            event.stopPropagation();
            userDropdownMenu.classList.toggle('show');
        });
    }
    
    // close dropdown
    document.addEventListener('click', function(event) {
        if (userWrapper && !userWrapper.contains(event.target) && userDropdownMenu) {
            userDropdownMenu.classList.remove('show');
        }
    });

    // Mobile Menu Toggle
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const navbarMenu = document.getElementById('navbarMenu');

    if (mobileMenuToggle && navbarMenu) {
        mobileMenuToggle.addEventListener('click', function() {
            navbarMenu.classList.toggle('active');
        });
    }
});