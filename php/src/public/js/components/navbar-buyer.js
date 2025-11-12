document.addEventListener('DOMContentLoaded', function() {
    
    // dropdown user navbar
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

    // Auto refresh balance saat page focus kembali
    window.addEventListener('focus', function() {
        refreshBalance();
    });

    // Initial balance refresh setelah 1 detik (untuk memastikan DOM ready)
    setTimeout(refreshBalance, 1000);

});

// Global function untuk update balance di navbar
function updateNavbarBalance(newBalance) {
    const balanceElement = document.getElementById('navbar-balance');
    if (balanceElement) {
        const formattedBalance = 'Rp ' + new Intl.NumberFormat('id-ID').format(newBalance);
        balanceElement.textContent = formattedBalance;
        
        balanceElement.classList.add('balance-updated');
        setTimeout(() => {
            balanceElement.classList.remove('balance-updated');
        }, 1000);
    }
}

// Function untuk fetch current balance dari server
function refreshBalance() {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', '/api/user/balance', true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.setRequestHeader('Accept', 'application/json');
    
    xhr.onload = function() {
        if (xhr.status >= 200 && xhr.status < 300) {
            try {
                const data = JSON.parse(xhr.responseText);
                if (data.success && data.data && data.data.balance !== undefined) {
                    updateNavbarBalance(data.data.balance);
                }
            } catch (e) {
                console.log('Failed to parse balance response:', e);
            }
        } else {
            console.log('Failed to refresh balance:', xhr.status);
        }
    };
    
    xhr.onerror = function() {
        console.log('Failed to refresh balance: Network error');
    };
    
    xhr.send();
}