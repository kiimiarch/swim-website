function validateRegister() {

    const fullname   = document.getElementById('fullname');
    const phoneInput = document.getElementById('phone');
    const password  = document.getElementById('password');
    const repassword= document.getElementById('repassword');
    const terms     = document.getElementById('terms');
    const error     = document.getElementById('error');

    const name  = fullname.value.trim();
    const phone = phoneInput.value.trim();
    const pass  = password.value;
    const repass= repassword.value;

    error.innerHTML = "";

    if (name.length < 3) {
        error.innerHTML = "❌ نام و نام خانوادگی معتبر نیست";
        return false;
    }

    if (!/^09\d{9}$/.test(phone)) {
        error.innerHTML = "❌ شماره موبایل نامعتبر است";
        return false;
    }

    if (pass.length < 4) {
        error.innerHTML = "❌ رمز عبور حداقل ۴ کاراکتر باشد";
        return false;
    }

    if (pass !== repass) {
        error.innerHTML = "❌ رمزهای عبور یکسان نیستند";
        return false;
    }

    if (!terms.checked) {
        error.innerHTML = "❌ پذیرش قوانین الزامی است";
        return false;
    }

    return true;
}
