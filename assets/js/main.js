document.addEventListener('DOMContentLoaded', () => {
    
    

    //  Mobile Menu Toggle
    if(document.querySelector('.sidebar')) {
        const menuBtn = document.createElement('div');
        menuBtn.className = 'menu-toggle';
        menuBtn.innerHTML = '☰ Menu';
        document.body.appendChild(menuBtn);

        menuBtn.addEventListener('click', () => {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    }

    //  Register Form Validation
    const regForm = document.querySelector('form[action*="register"]');
    if (regForm) {
        regForm.addEventListener('submit', (e) => {
            const pass = regForm.querySelector('input[name="password"]').value;
            const confirm = regForm.querySelector('input[name="confirm_password"]').value;
            const errorDiv = document.getElementById('pass-error');
            
            if (pass !== confirm) {
                e.preventDefault();
                if(errorDiv) {
                    errorDiv.style.display = 'block';
                    errorDiv.innerText = "Passwords do not match!";
                } else {
                    alert("Passwords do not match!");
                }
            }
        });
    }
});