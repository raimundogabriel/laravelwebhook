import './bootstrap';

function maskCPF(cpf) {
    var value = cpf.value.replace(/\D/g, '');
    if (value.length <= 11) {
        cpf.value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{1})/, '$1.$2.$3-$4');
    }
}

// Máscara para telefone
function maskPhone(phone) {
    var value = phone.value.replace(/\D/g, '');
    if (value.length <= 11) {
        phone.value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
    }
}
