
function loading_login() {
    $('.btn-login').prop("disabled", true);
    // add spinner to button
    $('.btn-login').html(
        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...'
    );
    window.setTimeout(function () {
        $('.btn-login').prop("disabled", false);
        $('.btn-login').html('<ion-icon name="log-in-outline"></ion-icon> Masuk'
        );
    }, 2000);
}


$('.password').keypress(function (e) {
    if (e.which === 32)
        return false;
});


const showPasswordCheckbox = document.getElementById('showPassword');
const passwordField = document.getElementById('password');
showPasswordCheckbox.addEventListener('change', function () {
    if (this.checked) {
        passwordField.type = 'text'; // Menampilkan password
    } else {
        passwordField.type = 'password'; // Menyembunyikan password
    }
});

$(".form-login").validate({
    rules: {
        field: {
            required: true
        },

        password: {
            required: true,
            minlength: 3,
            maxlength: 15
        },
    },

    messages: {
        field: {
            required: "Silahkan masukkan data sesuai inputan",
        },
        password: {
            required: "Please provide a password",
            minlength: "Password anda paling sedikit berisi 6 karakter"
        },
    },
    submitHandler: submitForm_Login
});


function submitForm_Login() {
    var data = $(".form-login").serialize();
    $.ajax({
        type: 'POST',
        url: './web/home/sw-proses.php?action=login',
        data: data,
        cache: false,
        async: false,
        beforeSend: function () {
            loading_login();
        },
        success: function (data) {
            if (data == 'siswa') {
                swal({ title: 'Berhasil!', text: 'Login berhasil, selamat datang kembali!', icon: 'success', timer: 3000, });
                setTimeout(function () { window.location.href = "./siswa/" }, 3000);
            } else if (data == 'pegawai') {
                swal({ title: 'Berhasil!', text: 'Login berhasil, selamat datang kembali!', icon: 'success', timer: 3000, });
                setTimeout(function () { window.location.href = "./pegawai/" }, 3000);
            } else if (data == 'wali-murid') {
                swal({ title: 'Berhasil!', text: 'Login berhasil, selamat datang kembali!', icon: 'success', timer: 3000, });
                setTimeout(function () { window.location.href = "./wali-murid/" }, 3000);
            } else {
                swal({ title: 'Oops!', text: data, icon: 'error', timer: 3500, });
            }
            $('.password').val('');
        }
    });
    return false;
}


$(document).on('click', '.btn-login-google', function () {
    const hakAkses = $('.tipe').val();
    if (hakAkses) {
        window.location.href = `./oauth/google?hak-akses=${encodeURIComponent(hakAkses)}`;
    } else {
        swal({ title: 'Oops!', text: 'Silahkan pilih hak akses terlebih dahulu.', icon: 'error', timer: 2500 });
    }
});