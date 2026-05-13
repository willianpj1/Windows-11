import Requests from '../components/requests.js';
import Validate from '../components/validate.js';

// ─── Máscaras ─────────────────────────────────────────────────────────────────
Inputmask({ mask: '999.999.999-99' }).mask('#cpf');
Inputmask({ mask: '9999999' }).mask('#rg');
Inputmask({ mask: '(99) 9 9999-9999' }).mask('#telefone');

// ─── Toggle visibilidade de senha ─────────────────────────────────────────────
function togglePassword(buttonId, inputId) {
    document.getElementById(buttonId).addEventListener('click', function () {
        const input = document.getElementById(inputId);
        const icon = this.querySelector('i');
        const hidden = input.type === 'password';

        input.type = hidden ? 'text' : 'password';
        icon.classList.toggle('fa-eye', !hidden);
        icon.classList.toggle('fa-eye-slash', hidden);
    });
}

togglePassword('toggleSenha', 'senha');
togglePassword('toggleConfirmarSenha', 'confirmarSenha');

// ─── Validação customizada — senhas coincidem ──────────────────────────────────
$.validator.addMethod('equalToField', function (value, element, param) {
    return value === $(param).val();
}, 'As senhas não coincidem.');

$.validator.addMethod('senhaForte', function (value) {
    return value.length >= 8;
}, 'A senha deve ter pelo menos 8 caracteres.');



// ─── Inicializa validação do formulário ───────────────────────────────────────
Validate.SetForm('form');

$('#form').validate({
    rules: {
        nome: { required: true, minlength: 2 },
        sobrenome: { required: true, minlength: 2 },
        email: { required: true, email: true },
        telefone: { required: true, telefone: true },
        cpf: { required: true, cpfValido: false },
        rg: { required: false, minlength: 5 },
        senha: { required: true, senhaForte: true },
        confirmarSenha: { required: true, equalToField: '#senha' },
    },
    messages: {
        nome: { required: 'Informe seu nome.', minlength: 'Mínimo 2 caracteres.' },
        sobrenome: { required: 'Informe seu sobrenome.', minlength: 'Mínimo 2 caracteres.' },
        email: { required: 'Informe seu e-mail.', email: 'E-mail inválido.' },
        cpf: { required: 'Informe seu CPF.' },
        rg: { required: 'Informe seu RG.', minlength: 'RG inválido.' },
        senha: { required: 'Informe uma senha.' },
        confirmarSenha: { required: 'Confirme sua senha.' },
    },
    // Posiciona o erro abaixo do input-group, não dentro dele
    errorPlacement: function (error, element) {
        error.addClass('invalid-feedback d-block');
        element.closest('.input-group').after(error);
    },
    highlight: (el) => $(el).addClass('is-invalid'),
    unhighlight: (el) => $(el).removeClass('is-invalid'),
});

// ─── Submit ───────────────────────────────────────────────────────────────────
document.getElementById('form').addEventListener('submit', async (e) => {
    e.preventDefault();
    await cadastrar();
});

async function cadastrar() {
    if (!$('#form').valid()) {
        Swal.fire({
            icon: 'error',
            title: 'Atenção',
            text: 'Corrija os erros antes de continuar.',
            timer: 3000,
            timerProgressBar: true,
        });
        return;
    }

    const btn = document.getElementById('btnCadastrar');
    btn.disabled = true;

    try {
        const response = await new Requests().setForm('form').post('/cadastro');

        if (!response?.status) {
            Swal.fire({
                icon: 'error',
                title: 'Erro no cadastro',
                text: response?.message ?? 'Não foi possível criar a conta.',
                timer: 4000,
                timerProgressBar: true,
            });
            return;
        }

        Swal.fire({
            icon: 'success',
            title: 'Cadastro realizado!',
            text: 'Sua conta foi criada com sucesso.',
            timer: 2500,
            timerProgressBar: true,
        }).then(() => {
            window.location.href = '/login';
        });

    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: error.message ?? 'Não foi possível conectar ao servidor.',
            timer: 3000,
            timerProgressBar: true,
        });
    } finally {
        btn.disabled = false;
    }
}